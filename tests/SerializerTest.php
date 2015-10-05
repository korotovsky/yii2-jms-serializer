<?php

namespace krtv\yii2\serializer\tests;

use krtv\yii2\serializer\Serializer;
use krtv\yii2\serializer\tests\dto\BarBazModel;
use krtv\yii2\serializer\tests\dto\FooBarModel;

/**
 * Class SerializerTest
 * @package krtv\yii2\serializer\tests
 */
class SerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $serializedDto = '{"foo1":12345,"foo2":"12345","foo3":{"baz1":{"foo":"bar"},"baz2":{"bar":"baz"},"baz3":"string"},"collection":[{"baz1":{"foo":"bar"},"baz2":{"bar":"baz"},"baz3":"string"},{"baz1":{"foo":"bar"},"baz2":{"bar":"baz"},"baz3":"string"},{"baz1":{"foo":"bar"},"baz2":{"bar":"baz"},"baz3":"string"}]}';

    /**
     * @var FooBarModel
     */
    private $deserializedDto;

    /**
     * @var \yii\console\Application
     */
    private $application;

    /**
     *
     */
    protected function setUp()
    {
        parent::setUp();

        $barBazModel = new BarBazModel();
        $barBazModel->baz1 = [
            'foo' => 'bar',
        ];
        $barBazModel->baz2 = [
            'bar' => 'baz',
        ];
        $barBazModel->baz3 = 'string';

        $fooBarModel = new FooBarModel();
        $fooBarModel->foo1 = 12345;
        $fooBarModel->foo2 = '12345';
        $fooBarModel->foo3 = $barBazModel;

        $fooBarModel->collection[] = $barBazModel;
        $fooBarModel->collection[] = $barBazModel;
        $fooBarModel->collection[] = $barBazModel;

        $this->deserializedDto = $fooBarModel;

        \Yii::$container = new \yii\di\Container();

        $this->application = new \yii\console\Application([
            'id' => 'testApp',
            'basePath' => __DIR__,
            'components' => [
                'serializer' => [
                    'class' => 'krtv\yii2\serializer\Serializer',
                    'formats' => [
                        'json',
                    ],
                    'handlers' => [
                       'datetime' => [
                           'defaultFormat' => 'c',  // ISO8601
                       ],
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
                            [
                                'namespace' => 'krtv\\yii2\\serializer\\tests\\dto',
                                'alias' => '@tests/config/serializer',
                            ],
                            // ...
                        ]
                    ],
                ],
            ]
        ]);
    }

    /**
     *
     */
    protected function tearDown()
    {
        $this->application = null;

        \Yii::$app = null;
        \Yii::$container = null;

        parent::tearDown();
    }

    /**
     *
     */
    public function testSerialize()
    {
        $serializer = $this->application->serializer; /* @var $serializer Serializer */

        $this->assertEquals($this->serializedDto, $serializer->serialize($this->deserializedDto, 'json'));
    }

    /**
     *
     */
    public function testDeserialize()
    {
        $serializer = $this->application->serializer; /* @var $serializer Serializer */

        $data = $serializer->deserialize($this->serializedDto, 'krtv\yii2\serializer\tests\dto\FooBarModel', 'json');

        $this->assertEquals($this->deserializedDto, $data);
    }

    /**
     *
     */
    public function testGetInnerSerializer()
    {
        $serializer = $this->application->serializer; /* @var $serializer Serializer */

        $this->assertInstanceOf('JMS\Serializer\Serializer', $serializer->getInnerSerializer());
    }
}
