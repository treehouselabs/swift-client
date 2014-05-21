<?php

namespace TreeHouse\Swift\Driver;

use Guzzle\Http\Exception\MultiTransferException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Psr\Log\LoggerInterface;
use TreeHouse\Keystone\Client\Client;
use TreeHouse\Swift\Container;
use TreeHouse\Swift\Exception\SwiftException;
use TreeHouse\Swift\Object;

class SwiftDriver implements DriverInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var boolean
     */
    protected $debug;

    /**
     * @param Client          $client
     * @param boolean         $debug
     * @param LoggerInterface $logger
     */
    public function __construct(Client $client, $debug, LoggerInterface $logger = null)
    {
        $this->client = $client;
        $this->debug  = $debug;
        $this->logger = $logger;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return string
     */
    public function getPublicUrl()
    {
        return $this->client->getPublicUrl();
    }

    /**
     * @param string $method
     * @param string $path
     * @param array  $query
     * @param array  $headers
     * @param string $body
     *
     * @throws SwiftException
     * @throws \InvalidArgumentException
     *
     * @return RequestInterface
     */
    protected function getRequest($method, $path, array $query = null, array $headers = [], $body = null)
    {
        if (!empty($query)) {
            $path .= '?' . http_build_query($query);
        }

        switch ($method) {
            case 'head':
            case 'get':
                return $request = $this->client->$method($path, $headers);
            case 'delete':
            case 'put':
            case 'patch':
            case 'post':
                return $request = $this->client->$method($path, $headers, $body);
            case 'copy':
                return $this->client->createRequest('COPY', $path, $headers);
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported request method "%s"', $method));
        }
    }

    /**
     * @param string $method
     * @param string $path
     * @param array  $query
     * @param array  $headers
     * @param string $body
     *
     * @throws SwiftException
     *
     * @return Response
     */
    protected function request($method, $path, array $query = null, array $headers = [], $body = null)
    {
        $request = $this->getRequest($method, $path, $query, $headers, $body);

        try {
            return $request->send();
        } catch (\Exception $e) {
            throw new SwiftException($e->getMessage());
        }
    }

    /**
     * @param string $path
     * @param array  $query
     * @param array  $headers
     *
     * @throws SwiftException
     *
     * @return Response
     */
    public function head($path, array $query = null, array $headers = [])
    {
        return $this->request('head', $path, $query, $headers);
    }

    /**
     * @param string $path
     * @param array  $query
     * @param array  $headers
     *
     * @throws SwiftException
     *
     * @return Response
     */
    public function get($path, array $query = null, array $headers = [])
    {
        return $this->request('get', $path, $query, $headers);
    }

    /**
     * @param string $path
     * @param array  $query
     * @param array  $headers
     * @param string $body
     *
     * @throws SwiftException
     *
     * @return Response
     */
    public function put($path, array $query = null, array $headers = [], $body = null)
    {
        return $this->request('put', $path, $query, $headers, $body);
    }

    /**
     * @param string $path
     * @param array  $query
     * @param array  $headers
     * @param string $body
     *
     * @throws SwiftException
     *
     * @return Response
     */
    public function post($path, array $query = null, array $headers = [], $body = null)
    {
        return $this->request('post', $path, $query, $headers, $body);
    }

    /**
     * @param string $path
     * @param array  $query
     * @param array  $headers
     *
     * @throws SwiftException
     *
     * @return Response
     */
    public function copy($path, array $query = null, array $headers = [])
    {
        return $this->request('copy', $path, $query, $headers);
    }

    /**
     * @param string $path
     * @param array  $query
     * @param array  $headers
     *
     * @throws SwiftException
     *
     * @return Response
     */
    public function delete($path, array $query = null, array $headers = [])
    {
        return $this->request('delete', $path, $query, $headers);
    }

    /**
     * @param \TreeHouse\Swift\Object $object
     *
     * @return string
     */
    public function getObjectUrl(Object $object)
    {
        return sprintf('%s/%s', $this->getPublicUrl(), $object->getPath());
    }

    /**
     * @param Container $container
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function containerExists(Container $container)
    {
        return $this->head($container->getName())->isSuccessful();
    }

    /**
     * @param Container $container
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function createContainer(Container $container)
    {
        // make readable for public
        if ($container->isPublic()) {
            $container->getMetadata()->set('Read', '.r:*');
        }

        try {
            $response = $this->put($container->getName(), null, $container->getHeaders());

            return $response->isSuccessful();
        } catch (\Exception $e) {
            throw new SwiftException(
                sprintf('Error putting container "%s": %s', $container->getName(), $e->getMessage())
            );
        }
    }

    /**
     * @param string $name
     *
     * @throws SwiftException
     *
     * @return Container
     */
    public function getContainer($name)
    {
        try {
            $response = $this->head($name);
        } catch (\Exception $e) {
            return null;
        }

        if (!$response->isSuccessful()) {
            return null;
        }

        return Container::create($name, $response->getHeaders()->toArray());
    }

    /**
     * @param Container $container
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function updateContainer(Container $container)
    {
        // make readable for public
        if ($container->isPublic()) {
            $container->getMetadata()->set('Read', '.r:*');
        }

        try {
            $response = $this->post($container->getName(), null, $container->getHeaders());

            return $response->isSuccessful();
        } catch (\Exception $e) {
            throw new SwiftException(
                sprintf('Error updating metadata of container "%s": %s', $container->getName(), $e->getMessage())
            );
        }
    }

    /**
     * @param Container $container
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function deleteContainer(Container $container)
    {
        // empty container first
        foreach ($this->getObjects($container) as $object) {
            $this->deleteObject($object);
        }

        $response = $this->delete($container->getName());

        return $response->isSuccessful() || ($response->getStatusCode() === 404);
    }

    /**
     * @param \TreeHouse\Swift\Object $object
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function objectExists(Object $object)
    {
        return $this->head($object->getPath())->isSuccessful();
    }

    /**
     * @param Container $container
     * @param string    $name
     * @param Response  $response
     *
     * @return \TreeHouse\Swift\Object
     */
    public function createObject(Container $container, $name, Response $response = null)
    {
        $headers = $response ? $response->getHeaders()->toArray() : array();

        return Object::create($container, $name, $headers);
    }

    /**
     * @param Container $container
     * @param string    $name
     *
     * @throws SwiftException
     *
     * @return \TreeHouse\Swift\Object
     */
    public function getObject(Container $container, $name)
    {
        $path = sprintf('%s/%s', $container->getName(), $name);

        try {
            $response = $this->head($path);
        } catch (\Exception $e) {
            return null;
        }

        if (!$response->isSuccessful()) {
            return null;
        }

        $object = $this->createObject($container, $name, $response);

        return $object;
    }

    /**
     * @param \TreeHouse\Swift\Object $object
     * @param boolean                 $asString
     * @param array                   $headers
     *
     * @throws SwiftException
     *
     * @return \Guzzle\Http\EntityBodyInterface|string
     */
    public function getObjectContent(Object $object, $asString = true, array $headers = [])
    {
        $response = $this->get($object->getPath(), null, $headers);

        return $response->getBody($asString);
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
     * @throws SwiftException
     *
     * @return array
     */
    public function getObjects(Container $container, $prefix = null, $delimiter = null, $limit = null, $start = null, $end = null)
    {
        $query = array();

        if (!is_null($prefix)) {
            $query['prefix'] = $prefix;
        }

        if (!is_null($delimiter)) {
            $query['delimiter'] = $delimiter;
        }

        if (!is_null($limit)) {
            $query['limit'] = $limit;
        }

        if (!is_null($start)) {
            $query['marker'] = $start;
        }

        if (!is_null($end)) {
            $query['end_marker'] = $end;
        }

        $result = array();

        $response = $this->get($container->getName(), $query);
        $content = trim($response->getBody(true));

        if ($content !== '') {
            $objects = explode("\n", $content);
            if (!empty($objects)) {
                $requests = array();

                foreach ($objects as $path) {
                    // if path ends with delimiter, it's a pseudo-dir
                    if (!is_null($delimiter) && (substr($path, -1) === $delimiter)) {
                        $object = Object::create($container, $path);
                        $object->setContentType('application/directory');
                        $result[] = $object;
                    } else {
                        $requests[] = $this->getRequest('head', sprintf('%s/%s', $container->getName(), $path));
                    }
                }

                if (!empty($requests)) {
                    try {
                        /** @var Response[] $responses */
                        $responses = $this->client->send($requests);
                        foreach ($responses as $response) {
                            if (!$response->isSuccessful()) {
                                throw new SwiftException(
                                    sprintf(
                                        'Could not get object "%s": %s (%s)',
                                        $response->getEffectiveUrl(),
                                        $response->getStatusCode(),
                                        $response->getReasonPhrase()
                                    )
                                );
                            }

                            $path = parse_url($response->getEffectiveUrl(), PHP_URL_PATH);
                            list(, $name) = explode('/', ltrim($path, '/'), 2);

                            $result[] = $this->createObject($container, $name, $response);
                        }
                    } catch (MultiTransferException $e) {
                        // NOTE: we could be less harsh here and just log the error
                        // the exception contains information about all requests:
                        // - $e->getFailedRequests()
                        // - $e->getSuccessfulRequests()
                        // see http://guzzlephp.org/http-client/client.html#sending-requests-in-parallel
                        throw new SwiftException(
                            sprintf(
                                'Could not get all objects for container "%s" with params %s. Reason: %s',
                                $container->getName(),
                                json_encode($query),
                                $e->getMessage()
                            )
                        );
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param \TreeHouse\Swift\Object $object
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function updateObject(Object $object)
    {
        // persist container
        $this->updateContainer($object->getContainer());

        // see if local file is specified
        if (is_null($object->getLocalFile())) {
            // just update the headers
            return $this->updateObjectMetadata($object);
        }

        try {
            // timestamp the object
            $object->setLastModifiedDate(new \DateTime());
            $response = $this->put($object->getPath(), null, $object->getUpdateHeaders(), $object->getBody());

            return $response->isSuccessful();
        } catch (\Exception $e) {
            throw new SwiftException(sprintf('Error updating object "%s": %s', $object->getPath(), $e->getMessage()));
        }
    }

    /**
     * @param \TreeHouse\Swift\Object $object
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function updateObjectMetadata(Object $object)
    {
        try {
            $response = $this->post($object->getPath(), null, $object->getUpdateHeaders());

            return $response->isSuccessful();
        } catch (\Exception $e) {
            throw new SwiftException(sprintf('Error updating metadata for object "%s": %s', $object->getPath(), $e->getMessage()));
        }
    }

    /**
     * @param \TreeHouse\Swift\Object $object
     *
     * @throws SwiftException
     *
     * @return boolean
     */
    public function deleteObject(Object $object)
    {
        return $this->delete($object->getPath())->isSuccessful();
    }

    /**
     * @param \TreeHouse\Swift\Object[] $objects
     *
     * @throws SwiftException
     *
     * @return integer
     */
    public function deleteObjects(array $objects)
    {
        $requests = array();

        foreach ($objects as $object) {
            if ($object->isPseudoDir()) {
                continue;
            }

            $requests[] = $this->getRequest('delete', $object->getPath());
        }

        $numRemoved = 0;

        if (!empty($requests)) {
            try {
                /** @var Response[] $responses */
                $responses = $this->client->send($requests);
                foreach ($responses as $response) {
                    if ($response->isSuccessful()) {
                        $numRemoved++;
                    }
                }
            } catch (MultiTransferException $e) {
                throw new SwiftException(sprintf('Could not delete objects. Reason: %s', $e->getMessage()));
            }
        }

        return $numRemoved;
    }

    /**
     * @param \TreeHouse\Swift\Object $object
     * @param Container               $toContainer
     * @param string                  $name
     *
     * @throws SwiftException
     *
     * @return \TreeHouse\Swift\Object
     */
    public function copyObject(Object $object, Container $toContainer, $name)
    {
        $destination = sprintf('/%s/%s', $toContainer->getName(), $name);
        $headers = array('Destination' => $destination);

        try {
            $response = $this->copy($object->getPath(), null, $headers);

            if ($response->isSuccessful()) {
                return $this->getObject($toContainer, $name);
            }
        } catch (\Exception $e) {
            throw new SwiftException(
                sprintf('Error copying object "%s" to "%s": %s', $object->getPath(), $destination, $e->getMessage())
            );
        }

        return null;
    }
}
