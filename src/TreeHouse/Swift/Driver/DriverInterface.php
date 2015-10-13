<?php

namespace TreeHouse\Swift\Driver;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TreeHouse\Swift\Container;
use TreeHouse\Swift\Exception\SwiftException;
use TreeHouse\Swift\Object as SwiftObject;

interface DriverInterface
{
    /**
     * Perform HEAD request.
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     *
     * @throws SwiftException
     *
     * @return ResponseInterface
     */
    public function head($path, array $query = null, array $headers = []);

    /**
     * Perform GET request.
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     *
     * @throws SwiftException
     *
     * @return ResponseInterface
     */
    public function get($path, array $query = null, array $headers = []);

    /**
     * Perform PUT request.
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     * @param string $body
     *
     * @throws SwiftException
     *
     * @return ResponseInterface
     */
    public function put($path, array $query = null, array $headers = [], $body = null);

    /**
     * Perform POST request.
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     * @param string $body
     *
     * @throws SwiftException
     *
     * @return ResponseInterface
     */
    public function post($path, array $query = null, array $headers = [], $body = null);

    /**
     * Perform COPY request.
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     *
     * @throws SwiftException
     *
     * @return ResponseInterface
     */
    public function copy($path, array $query = null, array $headers = []);

    /**
     * Perform DELETE request.
     *
     * @param string $path
     * @param array  $query
     * @param array  $headers
     * @param string $body
     *
     * @throws SwiftException
     *
     * @return ResponseInterface
     */
    public function delete($path, array $query = null, array $headers = [], $body = null);

    /**
     * Create a container.
     *
     * @param Container $container
     *
     * @throws SwiftException When the creation failed
     *
     * @return boolean True on success, an exception is thrown otherwise
     */
    public function createContainer(Container $container);

    /**
     * Check if a container exists.
     *
     * @param Container $container
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function containerExists(Container $container);

    /**
     * Get container by name.
     *
     * @param string $name
     *
     * @throws SwiftException
     *
     * @return Container|null
     */
    public function getContainer($name);

    /**
     * @param Container $container
     *
     * @throws SwiftException When container was not found
     *
     * @return boolean True on success, an exception is thrown otherwise
     */
    public function updateContainer(Container $container);

    /**
     * @param Container $container
     *
     * @throws SwiftException
     *
     * @return boolean True on success, an exception is thrown otherwise
     */
    public function deleteContainer(Container $container);

    /**
     * Check if an object exists.
     *
     * @param SwiftObject $object
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function objectExists(SwiftObject $object);

    /**
     * Create an object.
     *
     * @param Container         $container
     * @param string            $name
     * @param ResponseInterface $response
     *
     * @return SwiftObject
     */
    public function createObject(Container $container, $name, ResponseInterface $response = null);

    /**
     * Get an object by name.
     *
     * @param Container $container
     * @param string    $name
     *
     * @throws SwiftException
     *
     * @return SwiftObject|null
     */
    public function getObject(Container $container, $name);

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
     * @throws SwiftException
     *
     * @return SwiftObject[]
     */
    public function getObjects(Container $container, $prefix = null, $delimiter = null, $limit = null, $start = null, $end = null);

    /**
     * Return object url.
     *
     * @param SwiftObject $object
     *
     * @throws SwiftException
     *
     * @return string
     */
    public function getObjectUrl(SwiftObject $object);

    /**
     * @param SwiftObject $object
     * @param bool        $asString
     * @param array       $headers
     *
     * @throws SwiftException When anything other than the expected outcome
     *                        occurred, eg: when the object was not found.
     *
     * @return StreamInterface|mixed
     */
    public function getObjectContent(SwiftObject $object, $asString = true, array $headers = []);

    /**
     * @param SwiftObject $object
     *
     * @throws SwiftException
     *
     * @return boolean True on success, an exception is thrown otherwise
     */
    public function updateObject(SwiftObject $object);

    /**
     * @param SwiftObject $object
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function updateObjectMetadata(SwiftObject $object);

    /**
     * @param SwiftObject $object
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function deleteObject(SwiftObject $object);

    /**
     * @param SwiftObject[] $objects
     *
     * @throws SwiftException
     *
     * @return integer The number of removed objects
     */
    public function deleteObjects(array $objects);

    /**
     * @param SwiftObject $object
     * @param Container   $toContainer
     * @param string      $name
     *
     * @throws SwiftException When the copy operation failed
     *
     * @return SwiftObject The newly created object
     */
    public function copyObject(SwiftObject $object, Container $toContainer, $name);
}
