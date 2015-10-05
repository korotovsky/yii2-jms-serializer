Basic usage
===========

You can access to an `\krtv\serializer\Serializer` instance through `\Yii::$app->serializer` or `\Yii::$container->get('serializer')`.

## Data serialization

```php
echo $serializer->serialize(['foo' => 'bar'], 'json'); // {"foo": "bar"}
```

## Data deserialization

```php
$data = $serializer->serialize('{"foo": "bar"}', 'json');
```
