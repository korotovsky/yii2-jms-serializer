<?php

namespace krtv\yii2\serializer;

use JMS\Serializer\Context;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer as JMSSerializer;
use JMS\Serializer\SerializerInterface;
use Metadata\MetadataFactoryInterface;
use yii\base\Component;

/**
 * Class Serializer
 * @package krtv\yii2\serializer
 */
class Serializer extends Component implements SerializerInterface
{
    /**
     * @var JMSSerializer
     */
    private $serializer;

    /**
     * @param JMSSerializer $serializer
     * @param array $config
     */
    public function __construct(JMSSerializer $serializer, $config = [])
    {
        $this->serializer = $serializer;

        parent::__construct($config);
    }

    /**
     * @return JMSSerializer
     */
    public function getInnerSerializer()
    {
        return $this->serializer;
    }

    /**
     * Serializes the given data to the specified output format.
     *
     * @param object|array|scalar $data
     * @param string $format
     * @param Context $context
     *
     * @return string
     */
    public function serialize($data, $format, SerializationContext $context = null)
    {
        return $this->serializer->serialize($data, $format, $context);
    }

    /**
     * Deserializes the given data to the specified type.
     *
     * @param string $data
     * @param string $type
     * @param string $format
     * @param Context $context
     *
     * @return object|array|scalar
     */
    public function deserialize($data, $type, $format, DeserializationContext $context = null)
    {
        return $this->serializer->deserialize($data, $type, $format, $context);
    }
}
