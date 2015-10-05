<?php

namespace krtv\yii2\serializer\metadata\driver;

/**
 * Class LocatorDirectoryBag
 * @package krtv\yii2\serializer\metadata\driver
 */
class LocatorDirectoryBag
{
    /**
     * @var array
     */
    private $directories = [];

    /**
     * @var bool
     */
    private $frozen = false;

    /**
     * @param string $namespace
     * @param string $alias
     */
    public function add($namespace, $alias)
    {
        if ($this->frozen === true) {
            throw new \RuntimeException('LocatorDirectoryBag is frozen.');
        }

        $this->directories[] = [
            'namespace' => $namespace,
            'path' => \Yii::getAlias($alias),
        ];
    }

    /**
     * @return array
     */
    public function get()
    {
        return $this->directories;
    }

    /**
     *
     */
    public function freeze()
    {
        $this->frozen = true;
    }
}
