<?php

namespace krtv\yii2\serializer;

use JMS\Serializer\Construction\UnserializeObjectConstructor;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\ArrayCollectionHandler;
use JMS\Serializer\Handler\DateHandler;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Metadata\Driver\PhpDriver;
use JMS\Serializer\Metadata\Driver\XmlDriver;
use JMS\Serializer\Metadata\Driver\YamlDriver;
use JMS\Serializer\Naming\CacheNamingStrategy;
use JMS\Serializer\Naming\CamelCaseNamingStrategy;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\Serializer as JMSSerializer;
use JMS\Serializer\XmlDeserializationVisitor;
use JMS\Serializer\XmlSerializationVisitor;
use Metadata\Cache\FileCache;
use Metadata\ClassHierarchyMetadata;
use Metadata\Driver\DriverChain;
use Metadata\Driver\FileLocator;
use Metadata\MetadataFactory;
use yii\base\BootstrapInterface;
use yii\base\InvalidConfigException;
use yii\di\Container;
use yii\helpers\FileHelper;
use krtv\yii2\serializer\dispatcher\LazyEventDispatcher;
use krtv\yii2\serializer\handler\LazyHandlerRegistry;
use krtv\yii2\serializer\metadata\driver\LazyLoadingDriver;
use krtv\yii2\serializer\metadata\driver\LocatorDirectoryBag;

/**
 * Class Bootstrap
 * @package krtv\yii2\serializer
 */
class Bootstrap implements BootstrapInterface
{
    const NAMING_STRATEGY_CAMEL_CASE = 'camel_case';
    const NAMING_STRATEGY_IDENTICAL = 'identical';
    const NAMING_STRATEGY_CUSTOM = 'custom';

    /**
     * @var array
     */
    private static $namingStrategies = [
        self::NAMING_STRATEGY_CAMEL_CASE => CamelCaseNamingStrategy::class,
        self::NAMING_STRATEGY_IDENTICAL  => IdenticalPropertyNamingStrategy::class,
    ];

    /**
     * @var array
     */
    private static $defaults = [
        'class' => Serializer::class,
        'formats' => [
            'json',
        ],
        'handlers' => [
            'datetime' => [
                'defaultFormat' => 'c',  // ISO8601
            ]
        ],
        'namingStrategy' => [
            'name' => 'camel_case',
            'options' => [
                'separator' => '_',
                'lowerCase' => true,
            ],
        ],
        'metadata' => [
            'cache' => true,
            'directories' => [

            ],
        ],
    ];

    /**
     * @param \yii\base\Application $app
     * @throws \yii\base\InvalidConfigException
     */
    public function bootstrap($app)
    {
        $components = $app->getComponents();
        if (!isset($components['serializer'])) {
            throw new InvalidConfigException('Component "serializer" not found. Please make sure that you have it in your config.');
        }

        $definition = array_merge(self::$defaults, $components['serializer']);

        $app->set('serializer', $definition);

        $container = \Yii::$container;

        if (!$container->has(self::getMetadataDirectoryBagId())) {
            $container->setSingleton(self::getMetadataDirectoryBagId(), function () {
                return new LocatorDirectoryBag();
            });
        }

        $container->setSingleton(self::getEventDispatcherId(), function (Container $container) {
            return new LazyEventDispatcher($container);
        });

        $container->setSingleton(self::getVisitorId(GraphNavigator::DIRECTION_SERIALIZATION, 'json'), function (Container $container, array $params, array $config) {
            $namingStrategy  = $container->get(self::getNamingStrategyId(), [], $config['namingStrategy']);

            return new JsonSerializationVisitor($namingStrategy);
        });
        $container->setSingleton(self::getVisitorId(GraphNavigator::DIRECTION_DESERIALIZATION, 'json'), function (Container $container, array $params, array $config) {
            $namingStrategy  = $container->get(self::getNamingStrategyId(), [], $config['namingStrategy']);

            return new JsonDeserializationVisitor($namingStrategy);
        });

        $container->setSingleton(self::getVisitorId(GraphNavigator::DIRECTION_SERIALIZATION, 'xml'), function (Container $container, array $params, array $config) {
            $namingStrategy  = $container->get(self::getNamingStrategyId(), [], $config['namingStrategy']);

            return new XmlSerializationVisitor($namingStrategy);
        });
        $container->setSingleton(self::getVisitorId(GraphNavigator::DIRECTION_DESERIALIZATION, 'xml'), function (Container $container, array $params, array $config) {
            $namingStrategy  = $container->get(self::getNamingStrategyId(), [], $config['namingStrategy']);

            return new XmlDeserializationVisitor($namingStrategy);
        });

        $container->setSingleton(self::getMetadataDriverId(), function (Container $container, array $params, array $config) {
            $directoryBag = $container->get(self::getMetadataDirectoryBagId());
            $directoryBag->freeze();

            $dirs = [];
            foreach ($directoryBag->get() as $directory) {
                $dirs[$directory['namespace']] = $directory['path'];
            }
            foreach ($config['directories'] as $directory) {
                $dirs[$directory['namespace']] = \Yii::getAlias($directory['alias']);
            }

            $locator = new FileLocator($dirs);

            return new DriverChain([
                new YamlDriver($locator),
                new XmlDriver($locator),
                new PhpDriver($locator),
            ]);
        });

        $container->setSingleton(self::getMetadataFactoryId(), function (Container $container, array $params, array $config) use ($app) {
            $metadataDriver = new LazyLoadingDriver($container, self::getMetadataDriverId(), $config);

            $metadataFactory = new MetadataFactory($metadataDriver, ClassHierarchyMetadata::class, YII_DEBUG);
            if ($config['cache'] === true) {
                $path = \Yii::getAlias('@runtime/jms_serializer');

                if (!FileHelper::createDirectory($path)) {
                    throw new \RuntimeException('Unable to create cache directory in "@runtime/jms_serializer".');
                }

                $metadataFactory->setCache(new FileCache($path));
            } elseif (is_string($config['cache'])) {
                throw new InvalidConfigException('Not implemented.');
            }

            return $metadataFactory;
        });

        $container->setSingleton(self::getNamingStrategyId(), function (Container $container, array $params, array $config) {
            $type = $config['name'];

            switch ($type) {
                case self::NAMING_STRATEGY_CAMEL_CASE:
                    $class = self::getNamingStrategyClass(self::NAMING_STRATEGY_CAMEL_CASE);

                    $strategy = new $class($config['options']['separator'], $config['options']['lowerCase']);

                    break;
                case self::NAMING_STRATEGY_IDENTICAL:
                    $class = self::getNamingStrategyClass(self::NAMING_STRATEGY_IDENTICAL);

                    $strategy = new $class();

                    break;
                case self::NAMING_STRATEGY_CUSTOM:
                    if (!isset($config['class'])) {
                        throw new \RuntimeException('Option "class" not found under "custom" naming strategy');
                    }

                    $strategy = $container->get($config['class']);

                    break;

                default:
                    throw new InvalidConfigException('Unknown strategy name. Supported strategies are: "camel_case", "identical" and "custom".');
            }

            return new CacheNamingStrategy(
                new SerializedNameAnnotationStrategy($strategy)
            );
        });

        $container->set(self::getHandlerId('datetime'), DateHandler::class);
        $container->setSingleton(DateHandler::class, function (Container $container, array $params, array $config) {
            $defaultFormat = isset ($config['defaultFormat'])
                ? $config['defaultFormat']
                : \DateTime::ISO8601;

            $defaultTimezone = isset ($config['defaultTimezone'])
                ? $config['defaultTimezone']
                : 'UTC';

            return new DateHandler($defaultFormat, $defaultTimezone);
        });

        $container->setSingleton(self::getHandlerRegistryId(), function (Container $container, array $params, array $config) {
            $definitions = $container->getDefinitions();

            $handlers = [];
            foreach ($config as $name => $options) {
                if (isset($definitions[self::getHandlerId($name)])) {
                    $id = self::getHandlerId($name);
                    $class = $definitions[self::getHandlerId($name)]['class'];
                } elseif (isset($options['class'])) {
                    $id = $options['class'];
                    $class = $options['class'];
                } else {
                    throw new InvalidConfigException('Neither default handler nor "class" in option not found.');
                }

                if (in_array(SubscribingHandlerInterface::class, class_implements($class))) {
                    $methods = $class::getSubscribingMethods();

                    foreach ($methods as $properties) {
                        $method = isset($properties['method'])
                            ? $properties['method']
                            : HandlerRegistry::getDefaultMethod($properties['direction'], $properties['type'], $properties['format']);

                        $handlers[$properties['direction']][$properties['type']][$properties['format']] = [$id, $method];
                    }
                } else {
                    throw new InvalidConfigException(sprintf('Class "%s" should implement %s', $class, SubscribingHandlerInterface::class));
                }
            }

            $handlerRegistry = new LazyHandlerRegistry($container, $handlers);
            $handlerRegistry->registerSubscribingHandler(new ArrayCollectionHandler());

            return $handlerRegistry;
        });

        $container->setSingleton(self::getUnserializeObjectConstructorId(), function () {
            return new UnserializeObjectConstructor();
        });

        $container->set(self::getSerializerId(), Serializer::class);
        $container->setSingleton(Serializer::class, function (Container $container, array $params, array $config) {
            $metadataFactory = $container->get(self::getMetadataFactoryId(), [], $config['metadata']);
            $handlerRegistry = $container->get(self::getHandlerRegistryId(), [], $config['handlers']);
            $objConstructor  = $container->get(self::getUnserializeObjectConstructorId());
            $eventDispatcher = $container->get(self::getEventDispatcherId());

            $serializationVisitors = new \PhpCollection\Map();
            foreach ($config['formats'] as $format) {
                $serializationVisitors->set($format, $container->get(self::getVisitorId(GraphNavigator::DIRECTION_SERIALIZATION, $format), [], $config));
            }

            $deserializationVisitors = new \PhpCollection\Map();
            foreach ($config['formats'] as $format) {
                $deserializationVisitors->set($format, $container->get(self::getVisitorId(GraphNavigator::DIRECTION_DESERIALIZATION, $format), [], $config));
            }

            $jmsSerializer = new JMSSerializer(
                $metadataFactory,
                $handlerRegistry,
                $objConstructor,
                $serializationVisitors,
                $deserializationVisitors,
                $eventDispatcher
            );

            return new Serializer($jmsSerializer);
        });
    }

    public static function getSerializerId()
    {
        return sprintf('serializer');
    }

    public static function getMetadataDirectoryBagId()
    {
        return sprintf('serializer.metadata_directory_bag');
    }

    public static function getEventDispatcherId()
    {
        return sprintf('serializer.event_dispatcher');
    }

    public static function getHandlerRegistryId()
    {
        return sprintf('serializer.handler_registry');
    }

    public static function getHandlerId($id)
    {
        return sprintf('serializer.handler.%s', $id);
    }

    private static function getMetadataFactoryId()
    {
        return sprintf('serializer.metadata_factory');
    }

    private static function getMetadataDriverId()
    {
        return sprintf('serializer.metadata_driver');
    }

    private static function getNamingStrategyId()
    {
        return sprintf('serializer.naming_strategy');
    }

    private static function getUnserializeObjectConstructorId()
    {
        return sprintf('serializer.unserialize_object_constructor');
    }

    private static function getVisitorId($direction, $type)
    {
        if ($direction === GraphNavigator::DIRECTION_SERIALIZATION) {
            $direction = 'serialization';
        } elseif ($direction === GraphNavigator::DIRECTION_DESERIALIZATION) {
            $direction = 'deserialization';
        } else {
            throw new InvalidConfigException('Unknown direction type');
        }

        return sprintf('serializer.visitor.%s_%s', $direction, $type);
    }

    private static function getNamingStrategyClass($strategy)
    {
        return self::$namingStrategies[$strategy];
    }
}
