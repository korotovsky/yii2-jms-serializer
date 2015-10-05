# krtv/yii2-jms-serializer

This extension provides a `Serializer` component, that would allow you to use [JMS Serializer](http://jmsyst.com/libs/serializer) library
with [Yii framework 2.0](http://www.yiiframework.com).

JMSSerializer allows you to (de-)serialize data of any complexity. Currently, this extension supports JSON and XML.

It also provides you with a rich tool-set to adapt the output to your specific needs.

Built-in features include:

 * (De-)serialize data of any complexity; circular references are handled gracefully.
 * Supports many built-in PHP types (such as dates)
 * Supports versioning, e.g. for APIs
 * Configurable via PHP, XML and YAML

For license information check the [LICENSE](LICENSE.md)-file.

Documentation is at [docs/guide/README.md](docs/guide/README.md).

[![Build Status](https://travis-ci.org/krtv/yii2-jms-serializer.svg?branch=2.0.x)](https://travis-ci.org/krtv/yii2-jms-serializer)

## Install

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
php composer.phar require --prefer-dist krtv/yii2-jms-serializer
```

or add

```json
"krtv/yii2-jms-serializer": "~2.0.0"
```

to the require section of your composer.json.

Usage
-----

To use this extension, simply add the following code in your application configuration:

```php
return [
    // ...
    'components' => [
        'serializer' => [
            'class' => 'krtv\yii2\serializer\Serializer',
            'formats' => [
                'json',

                // XML is also available to use.
                // 'xml',
            ],

            // Uncomment if you would like to use handlers: http://jmsyst.com/libs/serializer/master/handlers
            //
            // 'handlers' => [
            //    'datetime' => [
            //        'defaultFormat' => 'c',  // ISO8601
            //    ],
            //    'my_handler' => [
            //        'class' => 'app\\serializer\\handler\\MyHandler',
            //    ],
            //    // ...
            // ],

            // Uncomment if you would like to use different naming strategy for properties.
            // "camel_case" is a default one. Available strategies are: "camel_case", "identical" and "custom".
            //
            // 'namingStrategy' => [
            //     'name' => 'camel_case',
            //     'options' => [
            //         'separator' => '_',
            //         'lowerCase' => true,
            //     ],
            // ],

            // Uncomment if you would like to configure class-metadata or enable cache.
            //
            // 'metadata' => [
            //     'cache' => true,
            //     'directories' => [
            //         [
            //             'namespace' => 'Foo\\Bar',
            //             'alias' => '@app/config/serializer/foo/bar',
            //         ],
            //         // ...
            //     ]
            // ],
        ],

        // ...
    ],

    // ...
];
```

Now you can access to an `krtv\yii2\serializer\Serializer` instance through `\Yii::$app->serializer` or `\Yii::$container->get('serializer')`.

Data serialization:

```php
echo $serializer->serialize(['foo' => 'bar'], 'json'); // {"foo": "bar"}
```
