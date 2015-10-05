<?php

namespace krtv\yii2\serializer\handler;

use JMS\Serializer\Handler\HandlerRegistry;
use yii\di\Container;

/**
 * Class LazyHandlerRegistry
 * @package krtv\yii2\serializer\handler
 */
class LazyHandlerRegistry extends HandlerRegistry
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var array
     */
    private $initializedHandlers = [];

    /**
     * @param Container $container
     * @param array $handlers
     */
    public function __construct(Container $container, array $handlers = [])
    {
        $this->container = $container;

        parent::__construct($handlers);
    }

    /**
     * @param int $direction
     * @param string $typeName
     * @param string $format
     * @param callable $handler
     */
    public function registerHandler($direction, $typeName, $format, $handler)
    {
        parent::registerHandler($direction, $typeName, $format, $handler);

        unset($this->initializedHandlers[$direction][$typeName][$format]);
    }

    /**
     * @param int $direction
     * @param string $typeName
     * @param string $format
     * @return array|null
     */
    public function getHandler($direction, $typeName, $format)
    {
        if (isset($this->initializedHandlers[$direction][$typeName][$format])) {
            return $this->initializedHandlers[$direction][$typeName][$format];
        }

        if (!isset($this->handlers[$direction][$typeName][$format])) {
            return null;
        }

        $handler = $this->handlers[$direction][$typeName][$format];
        if (is_array($handler) && is_string($handler[0]) && $this->container->has($handler[0])) {
            $handler[0] = $this->container->get($handler[0]);
        }

        return $this->initializedHandlers[$direction][$typeName][$format] = $handler;
    }
}
