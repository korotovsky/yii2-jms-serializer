<?php

namespace krtv\yii2\serializer\dispatcher;

use JMS\Serializer\EventDispatcher\EventDispatcher;
use yii\di\Container;

/**
 * Class LazyEventDispatcher
 * @package krtv\yii2\serializer\dispatcher
 */
class LazyEventDispatcher extends EventDispatcher
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeListeners($eventName, $loweredClass, $format)
    {
        $listeners = parent::initializeListeners($eventName, $loweredClass, $format);

        foreach ($listeners as &$listener) {
            if (!is_array($listener) || !is_string($listener[0])) {
                continue;
            }

            if (!$this->container->has($listener[0])) {
                continue;
            }

            $listener[0] = $this->container->get($listener[0]);
        }

        return $listeners;
    }
}
