<?php

return [
    'krtv/yii2-jms-serializer' => [
        'name' => 'krtv/yii2-jms-serializer',
        'version' => '9999999-dev',
        'alias' =>
            [
                '@krtv/yii2/serializer' => __DIR__ . '/../src',
            ],
        'bootstrap' => 'krtv\\yii2\\serializer\\Bootstrap',
    ]
];
