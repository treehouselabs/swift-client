<?php

namespace TreeHouse\Swift;

use Symfony\Component\HttpFoundation\HeaderBag;
use TreeHouse\Swift\Metadata\ContainerMetadata;

class Container implements \Countable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $private;

    /**
     * @var int
     */
    private $objectCount;

    /**
     * @var int
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

    /**
     * @param  string     $name
     * @param array $headers
     *
     * @return static
     */
    public static function create($name, array $headers = [])
    {
        $container = new Container($name);
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

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Makes the container private
     */
    public function setPrivate()
    {
        $this->private = true;
    }

    /**
     * Makes the container public
     */
    public function setPublic()
    {
        $this->private = false;
    }

    /**
     * @return bool
     */
    public function isPrivate()
    {
        return $this->private;
    }

    /**
     * @return bool
     */
    public function isPublic()
    {
        return !$this->private;
    }

    /**
     * @return ContainerMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            if ($this->metadata->isPrefixedKey($name)) {
                $this->metadata->set($name, $value);
            } else {
                // make sure we store an array
                $this->headers->set($name, (array) $value);
            }
        }
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return array_merge($this->headers->all(), $this->metadata->getHeaders());
    }

    /**
     * @param string $name
     *
     * @return array|string
     */
    public function getHeader($name)
    {
        return $this->headers->get($name);
    }

    /**
     * @return HeaderBag
     */
    public function getHeaderBag()
    {
        return $this->headers;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->objectCount;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->objectCount === 0;
    }

    /**
     * @param int $count
     */
    public function setObjectCount($count)
    {
        $this->objectCount = (int) $count;
    }

    /**
     * @return int
     */
    public function getObjectCount()
    {
        return $this->objectCount;
    }

    /**
     * @param int $bytes
     */
    public function setBytesUsed($bytes)
    {
        $this->bytesUsed = (int) $bytes;
    }

    /**
     * @return int
     */
    public function getBytesUsed()
    {
        return $this->bytesUsed;
    }
}
