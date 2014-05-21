<?php

namespace TreeHouse\Swift\Driver;

use Guzzle\Http\Message\Response;
use TreeHouse\Swift\Container;
use TreeHouse\Swift\Exception\SwiftException;
use TreeHouse\Swift\Object;

interface DriverInterface
{
    /**
     * Perform HEAD request
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     *
     * @throws SwiftException
     *
     * @return Response
     */
    public function head($path, array $query = null, array $headers = []);

    /**
     * Perform GET request
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     *
     * @throws SwiftException
     *
     * @return Response
     */
    public function get($path, array $query = null, array $headers = []);

    /**
     * Perform PUT request
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     * @param string $body
     *
     * @throws SwiftException
     *
     * @return Response
     */
    public function put($path, array $query = null, array $headers = [], $body = null);

    /**
     * Perform POST request
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     * @param string $body
     *
     * @throws SwiftException
     *
     * @return Response
     */
    public function post($path, array $query = null, array $headers = [], $body = null);

    /**
     * Perform COPY request
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     *
     * @throws SwiftException
     *
     * @return Response
     */
    public function copy($path, array $query = null, array $headers = []);

    /**
     * Perform DELETE request
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     *
     * @throws SwiftException
     *
     * @return Response
     */
    public function delete($path, array $query = null, array $headers = []);

    /**
     * Create a container
     *
     * @param Container $container
     *
     * @throws SwiftException
     *
     * @return Container
     */
    public function createContainer(Container $container);

    /**
     * Check if a container exists
     *
     * @param Container $container
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function containerExists(Container $container);

    /**
     * Get container by name
     *
     * @param string $name
     *
     * @throws SwiftException
     *
     * @return Container
     */
    public function getContainer($name);

    /**
     * @param Container $container
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function updateContainer(Container $container);

    /**
     * @param Container $container
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function deleteContainer(Container $container);

    /**
     * Check if an object exists
     *
     * @param \TreeHouse\Swift\Object $object
     *
     * @throws SwiftException
     *
     * @return bool
     */
    public function objectExists(Object $object);

    /**
     * Create an object
     *
     * @param Container $container
     * @param string    $name
     * @param Response  $response
     *
     * @return \TreeHouse\Swift\Object
     */
    public function createObject(Container $container, $name, Response $response = null);

    /**
     * Get an object by name
     *
     * @param Container $container
     * @param string    $name
     *
     * @throws SwiftException
     *
     * @return \TreeHouse\Swift\Object
     */
    public function getObject(Container $container, $name);

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
     * @throws SwiftException
     *
     * @return array
     */
    public function getObjects(Container $container, $prefix = null, $delimiter = null, $limit = null, $start = null, $end = null);

    /**
     * Return object url
     *
     * @param \TreeHouse\Swift\Object $object
     *
     * @throws SwiftException
     *
     * @return string
     */
    public function getObjectUrl(Object $object);

    /**
     * @param \TreeHouse\Swift\Object $object
     * @param boolean                 $asString
     * @param array                   $headers
     *
     * @throws SwiftException
     *
     * @return mixed
     */
    public function getObjectContent(Object $object, $asString = true, array $headers = []);

    /**
     * @param \TreeHouse\Swift\Object $object
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function updateObject(Object $object);

    /**
     * @param \TreeHouse\Swift\Object $object
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function updateObjectMetadata(Object $object);

    /**
     * @param \TreeHouse\Swift\Object $object
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function deleteObject(Object $object);

    /**
     * @param array $objects
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function deleteObjects(array $objects);

    /**
     * @param \TreeHouse\Swift\Object $object
     * @param Container               $toContainer
     * @param string                  $name
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function copyObject(Object $object, Container $toContainer, $name);
}
