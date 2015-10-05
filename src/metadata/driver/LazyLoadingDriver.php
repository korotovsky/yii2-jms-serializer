<?php

namespace krtv\yii2\serializer\metadata\driver;

use Metadata\Driver\DriverInterface;
use yii\di\Container;

/**
 * Class LazyLoadingDriver
 * @package krtv\yii2\serializer\metadata\driver
 */
class LazyLoadingDriver implements DriverInterface
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var string
     */
    private $realDriverId;

    /**
     * @var array
     */
    private $config;

    /**
     * @param Container $container
     * @param string $realDriverId
     * @param array $config
     */
    public function __construct(Container $container, $realDriverId, array $config)
    {
        $this->container = $container;
        $this->realDriverId = $realDriverId;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        return $this->container->get($this->realDriverId, [], $this->config)->loadMetadataForClass($class);
    }
}
