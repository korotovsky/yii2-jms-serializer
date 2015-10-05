Installation
============

Installation consists of two parts: getting composer package and configuring an application.

## Installing an extension

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist krtv/yii2-jms-serializer
```

or add

```json
"krtv/yii2-jms-serializer": "~2.0.0"
```

to the require section of your composer.json.

## Configuring application

To use this extension, simply add the following code in your application configuration:

```php
return [
    // ...
    'components' => [
        'serializer' => [
            'class' => 'krtv\serializer\Serializer',
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

Next chapter: [Basic usage](basic-usage.md)
