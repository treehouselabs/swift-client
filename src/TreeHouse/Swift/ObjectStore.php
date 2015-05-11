<?php

namespace TreeHouse\Swift;

use GuzzleHttp\Message\ResponseInterface;
use TreeHouse\Swift\Driver\DriverInterface;
use TreeHouse\Swift\Exception\SwiftException;
use TreeHouse\Swift\Object as SwiftObject;

class ObjectStore
{
    /**
     * The driver that communicates with the Swift backend.
     *
     * @var DriverInterface
     */
    protected $driver;

    /**
     * Local cache of fetched containers.
     *
     * @var array
     */
    protected $containers = [];

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Perform HEAD request.
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     *
     * @return ResponseInterface
     *
     * @see DriverInterface::head()
     */
    public function head($path, array $query = null, array $headers = [])
    {
        return $this->driver->head($path, $query, $headers);
    }

    /**
     * Perform GET request.
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     *
     * @return ResponseInterface
     *
     * @see DriverInterface::get()
     */
    public function get($path, array $query = null, array $headers = [])
    {
        return $this->driver->get($path, $query, $headers);
    }

    /**
     * Perform PUT request.
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     * @param string $body
     *
     * @return ResponseInterface
     *
     * @see DriverInterface::put()
     */
    public function put($path, array $query = null, array $headers = [], $body = null)
    {
        return $this->driver->put($path, $query, $headers, $body);
    }

    /**
     * Perform POST request.
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     * @param string $body
     *
     * @return ResponseInterface
     *
     * @see DriverInterface::post()
     */
    public function post($path, array $query = null, array $headers = [], $body = null)
    {
        return $this->driver->post($path, $query, $headers, $body);
    }

    /**
     * Perform COPY request.
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     *
     * @return ResponseInterface
     *
     * @see DriverInterface::copy()
     */
    public function copy($path, array $query = null, array $headers = [])
    {
        return $this->driver->copy($path, $query, $headers);
    }

    /**
     * Perform DELETE request.
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     * @param string $body
     *
     * @return ResponseInterface
     *
     * @see DriverInterface::delete()
     */
    public function delete($path, array $query = null, array $headers = [], $body = null)
    {
        return $this->driver->delete($path, $query, $headers, $body);
    }

    /**
     * Return object url.
     *
     * @param SwiftObject $object
     *
     * @throws Exception\SwiftException
     *
     * @return string
     *
     * @see DriverInterface::getObjectUrl()
     */
    public function getObjectUrl(SwiftObject $object)
    {
        if ($object->getContainer()->isPrivate()) {
            throw new SwiftException('Object container is private');
        }

        return $this->driver->getObjectUrl($object);
    }

    /**
     * Create a container.
     *
     * @param string $name
     * @param bool   $private
     *
     * @throws SwiftException
     *
     * @return Container
     *
     * @see DriverInterface::createContainer()
     */
    public function createContainer($name, $private = true)
    {
        if (false === isset($this->containers[$name])) {
            $container = new Container($name);
            $private ? $container->setPrivate() : $container->setPublic();

            if (!$this->driver->createContainer($container)) {
                throw new SwiftException(sprintf('Could not create container %s', $name));
            }

            $this->containers[$name] = $container;
        }

        return $this->containers[$name];
    }

    /**
     * Check if a container exists.
     *
     * @param Container $container
     *
     * @return bool
     *
     * @see DriverInterface::containerExists()
     */
    public function containerExists(Container $container)
    {
        return (boolean) $this->driver->containerExists($container);
    }

    /**
     * Get container by name.
     *
     * @param string $name
     *
     * @return Container
     *
     * @see DriverInterface::getContainer()
     */
    public function getContainer($name)
    {
        if (false === isset($this->containers[$name])) {
            $this->containers[$name] = $this->driver->getContainer($name);
        }

        return $this->containers[$name];
    }

    /**
     * @param Container $container
     *
     * @return bool
     *
     * @see DriverInterface::updateContainer()
     */
    public function updateContainer(Container $container)
    {
        return $this->driver->updateContainer($container);
    }

    /**
     * @param Container $container
     *
     * @return bool
     *
     * @see DriverInterface::deleteContainer()
     */
    public function deleteContainer(Container $container)
    {
        if ($this->driver->deleteContainer($container)) {
            unset($this->containers[$container->getName()]);

            return true;
        }

        return false;
    }

    /**
     * Check if an object exists.
     *
     * @param SwiftObject $object
     *
     * @return bool
     *
     * @see DriverInterface::objectExists()
     */
    public function objectExists(SwiftObject $object)
    {
        return (boolean) $this->driver->objectExists($object);
    }

    /**
     * Create an object.
     *
     * @param Container $container
     * @param string    $name
     *
     * @return SwiftObject
     *
     * @see DriverInterface::createObject()
     */
    public function createObject(Container $container, $name)
    {
        // make sure container exists
        if (false === $this->containerExists($container)) {
            $this->driver->createContainer($container);
        }

        return $this->driver->createObject($container, $name);
    }

    /**
     * Get an object by name.
     *
     * @param Container $container
     * @param string    $name
     *
     * @return SwiftObject
     *
     * @see DriverInterface::getObject()
     */
    public function getObject(Container $container, $name)
    {
        return $this->driver->getObject($container, $name);
    }

    /**
     * Get objects inside a container, optionally filtered by prefix/delimiter.
     *
     * @param Container $container
     * @param string    $prefix
     * @param string    $delimiter
     * @param int       $limit
     * @param int       $start
     * @param int       $end
     *
     * @return SwiftObject[]
     *
     * @see DriverInterface::getObjects()
     */
    public function getObjects(Container $container, $prefix = null, $delimiter = null, $limit = null, $start = null, $end = null)
    {
        if (null !== $prefix) {
            // if delimiter is specified, make sure prefix ends with it
            if (!is_null($delimiter) && !is_null($prefix)) {
                $prefix = rtrim($prefix, $delimiter) . $delimiter;
            }
        }

        return $this->driver->getObjects($container, $prefix, $delimiter, $limit, $start, $end);
    }

    /**
     * @param SwiftObject $object
     * @param bool        $asString
     * @param array       $headers
     *
     * @return mixed
     *
     * @see DriverInterface::getObjectContent()
     */
    public function getObjectContent(Object $object, $asString = true, array $headers = [])
    {
        return $this->driver->getObjectContent($object, $asString, $headers);
    }

    /**
     * @param SwiftObject $object
     *
     * @throws SwiftException
     *
     * @return bool
     *
     * @see DriverInterface::updateObject()
     */
    public function updateObject(SwiftObject $object)
    {
        if (is_null($object->getLocalFile()) && !$this->objectExists($object)) {
            throw new SwiftException('Cannot update a new object without a body');
        }

        return $this->driver->updateObject($object);
    }

    /**
     * @param SwiftObject $object
     *
     * @return bool
     *
     * @see DriverInterface::updateObjectMetadata()
     */
    public function updateObjectMetadata(SwiftObject $object)
    {
        return $this->driver->updateObjectMetadata($object);
    }

    /**
     * @param SwiftObject $object
     *
     * @return bool
     *
     * @see DriverInterface::deleteObject()
     */
    public function deleteObject(SwiftObject $object)
    {
        return $this->driver->deleteObject($object);
    }

    /**
     * @param array $objects
     *
     * @throws SwiftException
     *
     * @return bool
     *
     * @see DriverInterface::deleteObjects()
     */
    public function deleteObjects(array $objects)
    {
        return $this->driver->deleteObjects($objects);
    }

    /**
     * @param SwiftObject $object
     * @param Container   $destination
     * @param string      $name
     *
     * @throws SwiftException
     *
     * @return SwiftObject
     *
     * @see DriverInterface::copyObject()
     */
    public function copyObject(SwiftObject $object, Container $destination, $name = null)
    {
        if (is_null($name)) {
            $name = $object->getName();
        }

        // detect circular reference
        if (($object->getContainer() === $destination) && ($object->getName() === $name)) {
            throw new SwiftException('Destination is same as source');
        }

        return $this->driver->copyObject($object, $destination, $name);
    }

    /**
     * Clears the internal cache.
     */
    public function clear()
    {
        $this->containers = [];
    }
}
