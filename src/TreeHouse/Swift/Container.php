<?php

namespace TreeHouse\Swift;

use Symfony\Component\HttpFoundation\HeaderBag;
use TreeHouse\Swift\Metadata\ContainerMetadata;

class Container
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var boolean
     */
    private $private;

    /**
     * @var integer
     */
    private $objectCount;

    /**
     * @var integer
     */
    private $bytesUsed;

    /**
     * @var HeaderBag
     */
    private $headers;

    /**
     * @var ContainerMetadata
     */
    private $metadata;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name     = $name;
        $this->private  = true;
        $this->headers  = new HeaderBag();
        $this->metadata = new ContainerMetadata();
    }

    public static function create($name, array $headers = array())
    {
        $container = new static($name);
        $container->setHeaders($headers);

        // set visibility
        if (strstr($container->getMetadata()->get('Read'), '.r:*') !== false) {
            $container->setPublic();
        } else {
            $container->setPrivate();
        }

        // set object count
        if (null !== $count = $container->headers->get('X-Container-Object-Count')) {
            $container->setObjectCount($count);
        }

        // set bytes
        if (null !== $bytes = $container->headers->get('X-Container-Bytes-Used')) {
            $container->setBytesUsed($bytes);
        }

        return $container;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setPrivate()
    {
        $this->private = true;
    }

    public function setPublic()
    {
        $this->private = false;
    }

    public function isPrivate()
    {
        return $this->private;
    }

    public function isPublic()
    {
        return !$this->private;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            if ($this->metadata->isPrefixedKey($name)) {
                $this->metadata->set($name, $value);
            } else {
                // make sure we store an array
                $values = (array) $value;

                $this->headers->set($name, $values);
            }
        }
    }

    public function getHeaders()
    {
        return array_merge($this->headers->all(), $this->metadata->getHeaders());
    }

    public function getHeader($name)
    {
        return $this->headers->get($name);
    }

    public function getHeaderBag()
    {
        return $this->headers;
    }

    public function count()
    {
        return $this->objectCount;
    }

    public function isEmpty()
    {
        return $this->objectCount === 0;
    }

    public function setObjectCount($count)
    {
        $this->objectCount = (int) $count;
    }

    public function getObjectCount()
    {
        return $this->objectCount;
    }

    public function setBytesUsed($bytes)
    {
        $this->bytesUsed = (int) $bytes;
    }

    public function getBytesUsed()
    {
        return $this->bytesUsed;
    }
}
