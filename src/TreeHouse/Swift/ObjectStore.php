<?php

namespace TreeHouse\Swift;

use Guzzle\Http\Message\Response;
use TreeHouse\Swift\Driver\DriverInterface;
use TreeHouse\Swift\Exception\SwiftException;

class ObjectStore
{
    /**
     * The driver that communicates with the Swift backend
     *
     * @var DriverInterface
     */
    protected $driver;

    /**
     * Local cache of fetched containers
     *
     * @var array
     */
    protected $containers = array();

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Perform HEAD request
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     *
     * @return Response
     *
     * @see DriverInterface::head()
     */
    public function head($path, array $query = null, array $headers = array())
    {
        return $this->driver->head($path, $query, $headers);
    }

    /**
     * Perform GET request
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     *
     * @return Response
     *
     * @see DriverInterface::get()
     */
    public function get($path, array $query = null, array $headers = array())
    {
        return $this->driver->get($path, $query, $headers);
    }

    /**
     * Perform PUT request
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     * @param string $body
     *
     * @return Response
     *
     * @see DriverInterface::put()
     */
    public function put($path, array $query = null, array $headers = array(), $body = null)
    {
        return $this->driver->put($path, $query, $headers, $body);
    }

    /**
     * Perform POST request
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     * @param string $body
     *
     * @return Response
     *
     * @see DriverInterface::post()
     */
    public function post($path, array $query = null, array $headers = array(), $body = null)
    {
        return $this->driver->post($path, $query, $headers, $body);
    }

    /**
     * Perform COPY request
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     *
     * @return Response
     *
     * @see DriverInterface::copy()
     */
    public function copy($path, array $query = null, array $headers = array())
    {
        return $this->driver->copy($path, $query, $headers);
    }

    /**
     * Perform DELETE request
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     *
     * @return Response
     *
     * @see DriverInterface::delete()
     */
    public function delete($path, array $query = null, array $headers = array())
    {
        return $this->driver->delete($path, $query, $headers);
    }

    /**
     * Return object url
     *
     * @param \TreeHouse\Swift\Object $object
     *
     * @throws Exception\SwiftException
     *
     * @return string
     *
     * @see DriverInterface::getObjectUrl()
     */
    public function getObjectUrl(Object $object)
    {
        if ($object->getContainer()->isPrivate()) {
            throw new SwiftException('Object container is private');
        }

        return $this->driver->getObjectUrl($object);
    }

    /**
     * Create a container
     *
     * @param string  $name
     * @param boolean $private
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
     * Check if a container exists
     *
     * @param Container $container
     *
     * @return boolean
     *
     * @see DriverInterface::containerExists()
     */
    public function containerExists(Container $container)
    {
        return (boolean) $this->driver->containerExists($container);
    }

    /**
     * Get container by name
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
     * Check if an object exists
     *
     * @param \TreeHouse\Swift\Object $object
     *
     * @return boolean
     *
     * @see DriverInterface::objectExists()
     */
    public function objectExists(Object $object)
    {
        return (boolean) $this->driver->objectExists($object);
    }

    /**
     * Create an object
     *
     * @param Container $container
     * @param string    $name
     *
     * @return \TreeHouse\Swift\Object
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
     * Get an object by name
     *
     * @param Container $container
     * @param string    $name
     *
     * @return \TreeHouse\Swift\Object
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
     * @param integer   $limit
     * @param integer   $start
     * @param integer   $end
     *
     * @return \TreeHouse\Swift\Object[]
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
     * @param \TreeHouse\Swift\Object $object
     * @param boolean                 $asString
     * @param array                   $headers
     *
     * @return mixed
     *
     * @see DriverInterface::getObjectContent()
     */
    public function getObjectContent(Object $object, $asString = true, array $headers = array())
    {
        return $this->driver->getObjectContent($object, $asString, $headers);
    }

    /**
     * @param Container $container
     *
     * @return boolean
     *
     * @see DriverInterface::updateContainer()
     */
    public function updateContainer(Container $container)
    {
        return $this->driver->updateContainer($container);
    }

    /**
     * @param \TreeHouse\Swift\Object $object
     *
     * @throws SwiftException
     *
     * @return boolean
     *
     * @see DriverInterface::updateObject()
     */
    public function updateObject(Object $object)
    {
        if ($object->getContainer() === null) {
            throw new SwiftException('Object doesn\'t have a container');
        }

        if (is_null($object->getLocalFile()) && !$this->objectExists($object)) {
            throw new SwiftException('Cannot update a new object without a body');
        }

        return $this->driver->updateObject($object);
    }

    /**
     * @param \TreeHouse\Swift\Object $object
     *
     * @return boolean
     *
     * @see DriverInterface::updateObjectMetadata()
     */
    public function updateObjectMetadata(Object $object)
    {
        return $this->driver->updateObjectMetadata($object);
    }

    /**
     * @param Container $container
     *
     * @return boolean
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
     * @param \TreeHouse\Swift\Object $object
     *
     * @return boolean
     *
     * @see DriverInterface::deleteObject()
     */
    public function deleteObject(Object $object)
    {
        return $this->driver->deleteObject($object);
    }

    /**
     * @param array $objects
     *
     * @throws SwiftException
     *
     * @return boolean
     *
     * @see DriverInterface::deleteObjects()
     */
    public function deleteObjects(array $objects)
    {
        return $this->driver->deleteObjects($objects);
    }

    /**
     * @param \TreeHouse\Swift\Object $object
     * @param Container               $destination
     * @param string                  $name
     *
     * @throws Exception\SwiftException
     *
     * @return boolean
     *
     * @see DriverInterface::copyObject()
     */
    public function copyObject(Object $object, Container $destination, $name = null)
    {
        if (is_null($name)) {
            $name = $object->getName();
        }

        if ($object->getContainer() === null) {
            throw new SwiftException('Object doesn\'t have a container');
        }

        // detect circular reference
        if (($object->getContainer() === $destination) && ($object->getName() === $name)) {
            throw new SwiftException('Destination is same as source');
        }

        return $this->driver->copyObject($object, $destination, $name);
    }

    /**
     * Clears the internal cache
     */
    public function clear()
    {
        $this->containers = array();
    }
}
