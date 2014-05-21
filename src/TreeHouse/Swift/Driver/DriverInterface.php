<?php

namespace TreeHouse\Swift\Driver;

use Guzzle\Http\Message\Response;
use TreeHouse\Swift\Container;
use TreeHouse\Swift\Exception\SwiftException;

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
    public function head($path, $query, $headers);

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
    public function get($path, $query, $headers);

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
    public function put($path, $query, $headers, $body);

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
    public function post($path, $query, $headers, $body);

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
    public function copy($path, $query, $headers);

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
    public function delete($path, $query, $headers);

    /**
     * Return object url
     *
     * @param \TreeHouse\Swift\Object $object
     *
     * @throws SwiftException
     *
     * @return string
     */
    public function getObjectUrl($object);

    /**
     * Create a container
     *
     * @param string $container
     *
     * @throws SwiftException
     *
     * @return Container
     */
    public function createContainer($container);

    /**
     * Check if a container exists
     *
     * @param Container $container
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function containerExists($container);

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
     * Check if an object exists
     *
     * @param \TreeHouse\Swift\Object $object
     *
     * @throws SwiftException
     *
     * @return bool
     */
    public function objectExists($object);

    /**
     * Create an object
     *
     * @param Container $container
     * @param string    $name
     *
     * @throws SwiftException
     *
     * @return \TreeHouse\Swift\Object
     */
    public function createObject($container, $name);

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
    public function getObject($container, $name);

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
    public function getObjects($container, $prefix, $delimiter, $limit, $start, $end);

    /**
     * @param \TreeHouse\Swift\Object $object
     * @param boolean                 $asString
     * @param array                   $headers
     *
     * @throws SwiftException
     *
     * @return mixed
     */
    public function getObjectContent($object, $asString, $headers);

    /**
     * @param Container $container
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function updateContainer($container);

    /**
     * @param \TreeHouse\Swift\Object $object
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function updateObject($object);

    /**
     * @param \TreeHouse\Swift\Object $object
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function updateObjectMetadata($object);

    /**
     * @param Container $container
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function deleteContainer($container);

    /**
     * @param \TreeHouse\Swift\Object $object
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function deleteObject($object);

    /**
     * @param array $objects
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function deleteObjects($objects);

    /**
     * @param \TreeHouse\Swift\Object $object
     * @param Container               $destination
     * @param string                  $name
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function copyObject($object, $destination, $name);
}
