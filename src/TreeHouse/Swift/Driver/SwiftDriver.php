<?php

namespace TreeHouse\Swift\Driver;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TreeHouse\Swift\Container;
use TreeHouse\Swift\Exception\SwiftException;
use TreeHouse\Swift\Object as SwiftObject;

class SwiftDriver implements DriverInterface
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ClientInterface $client
     * @param LoggerInterface $logger
     */
    public function __construct(ClientInterface $client, LoggerInterface $logger = null)
    {
        $this->client = $client;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * @return ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return UriInterface
     */
    public function getBaseUri()
    {
        return new Uri($this->client->getConfig('token_pool')->getPublicUrl());
    }

    /**
     * @inheritdoc
     */
    public function head($path, array $query = null, array $headers = [])
    {
        return $this->request('head', $path, $query, $headers);
    }

    /**
     * @inheritdoc
     */
    public function get($path, array $query = null, array $headers = [])
    {
        return $this->request('get', $path, $query, $headers);
    }

    /**
     * @inheritdoc
     */
    public function put($path, array $query = null, array $headers = [], $body = null)
    {
        return $this->request('put', $path, $query, $headers, $body);
    }

    /**
     * @inheritdoc
     */
    public function post($path, array $query = null, array $headers = [], $body = null)
    {
        return $this->request('post', $path, $query, $headers, $body);
    }

    /**
     * @inheritdoc
     */
    public function copy($path, array $query = null, array $headers = [])
    {
        return $this->request('copy', $path, $query, $headers);
    }

    /**
     * @inheritdoc
     */
    public function delete($path, array $query = null, array $headers = [], $body = null)
    {
        return $this->request('delete', $path, $query, $headers, $body);
    }

    /**
     * @inheritdoc
     */
    public function getObjectUrl(SwiftObject $object)
    {
        return Uri::resolve($this->getBaseUri(), $object->getPath());
    }

    /**
     * @inheritdoc
     */
    public function containerExists(Container $container)
    {
        $response = $this->head($container->getName());

        return $this->assertResponse($response, [
            204 => true,
            404 => false,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function createContainer(Container $container)
    {
        // make readable for public
        if ($container->isPublic()) {
            $container->getMetadata()->set('Read', '.r:*');
        }

        $response = $this->put($container->getName(), null, $container->getHeaders());

        return $this->assertResponse($response, [
            201 => true,
            202 => true,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getContainer($name)
    {
        $response = $this->head($name);

        return $this->assertResponse($response, [
            204 => Container::create($name, $response->getHeaders()),
            404 => null,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function updateContainer(Container $container)
    {
        $this->logger->info(sprintf('Updating container "%s"', $container->getName()));

        // make readable for public
        if ($container->isPublic()) {
            $container->getMetadata()->set('Read', '.r:*');
        }

        $response = $this->post($container->getName(), null, $container->getHeaders());

        return $this->assertResponse($response, [
            204 => true,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function deleteContainer(Container $container)
    {
        $this->logger->info(sprintf('Deleting container "%s"', $container->getName()));

        // empty container first
        foreach ($this->getObjects($container) as $object) {
            $this->deleteObject($object);
        }

        $response = $this->delete($container->getName());

        return $this->assertResponse($response, [
            204 => true,
            404 => true,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function objectExists(SwiftObject $object)
    {
        $response = $this->head($object->getPath());

        return $this->assertResponse($response, [
            204 => true,
            404 => false,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function createObject(Container $container, $name, ResponseInterface $response = null)
    {
        $headers = $response ? $response->getHeaders() : [];

        return SwiftObject::create($container, $name, $headers);
    }

    /**
     * @inheritdoc
     */
    public function getObject(Container $container, $name)
    {
        $response = $this->head(sprintf('%s/%s', $container->getName(), $name));

        return $this->assertResponse($response, [
            204 => $this->createObject($container, $name, $response),
            404 => null,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getObjectContent(SwiftObject $object, $asString = true, array $headers = [])
    {
        $response = $this->get($object->getPath(), null, $headers);

        // make sure the response is correct
        $this->assertResponse($response, [200 => true]);

        $body = $response->getBody();

        if ($asString) {
            $body = $body->getContents();
        }

        return $body;
    }

    /**
     * @inheritdoc
     */
    public function getObjects(Container $container, $prefix = null, $delimiter = null, $limit = null, $start = null, $end = null)
    {
        $query = [];

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

        $this->logger->info(sprintf('Listing objects in container "%s"', $container->getName()), $query);

        $result = [];

        $response = $this->get($container->getName(), $query);
        $content = trim($response->getBody()->getContents());

        if ($content !== '') {
            $requests = [];
            foreach (explode("\n", $content) as $path) {
                // if path ends with delimiter, it's a pseudo-dir
                if (!is_null($delimiter) && (substr($path, -1) === $delimiter)) {
                    $object = SwiftObject::create($container, $path);
                    $object->setContentType('application/directory');
                    $result[$path] = $object;

                    $this->logger->debug(sprintf('=> "%s"', $object->getPath()));
                } else {
                    $objectPath = sprintf('%s/%s', $container->getName(), $path);
                    $requests[] = $this->createRequest('head', $objectPath);
                    $result[$path] = null;

                    $this->logger->debug(sprintf('=> "%s"', $objectPath));
                }
            }

            $this->logger->info('Getting objects metadata');

            if (!empty($requests)) {
                $results = Pool::batch($this->client, $requests);

                $failures = [];
                foreach ($results as $index => $response) {
                    /** @var RequestInterface $request */
                    $request = $requests[$index];

                    if ($response instanceof ResponseInterface) {
                        $path = $request->getUri()->getPath();
                        list(, $name) = explode('/', ltrim($path, '/'), 2);

                        $result[$name] = $this->createObject($container, $name, $response);
                    } elseif ($response instanceof \Exception) {
                        $failures[] = $response;
                    }
                }

                if (!empty($failures)) {
                    $messages = array_map(function (\Exception $e) {
                        return $e->getMessage();
                    }, $failures);

                    throw new SwiftException(
                        sprintf(
                            'Could not get all objects for container "%s" with params %s. Failed requests:%s',
                            $container->getName(),
                            json_encode($query),
                            PHP_EOL . implode(PHP_EOL, $messages)
                        )
                    );
                }
            }
        }

        return array_values($result);
    }

    /**
     * @inheritdoc
     */
    public function updateObject(SwiftObject $object)
    {
        // persist container
        $this->updateContainer($object->getContainer());

        // see if local file is specified
        if (is_null($object->getLocalFile())) {
            // just update the headers
            return $this->updateObjectMetadata($object);
        }

        // timestamp the object
        $object->setLastModifiedDate(new \DateTime());

        $this->logger->info(sprintf('Updating object "%s"', $object->getPath()));

        $response = $this->put($object->getPath(), null, $object->getUpdateHeaders(), $object->getBody());

        return $this->assertResponse($response, [
            201 => true,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function updateObjectMetadata(SwiftObject $object)
    {
        $this->logger->info(sprintf('Updating metadata for "%s"', $object->getPath()));

        $response = $this->post($object->getPath(), null, $object->getUpdateHeaders());

        return $this->assertResponse($response, [
            202 => true,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function deleteObject(SwiftObject $object)
    {
        $this->logger->info(sprintf('Deleting "%s"', $object->getPath()));

        $response = $this->delete($object->getPath());

        return $this->assertResponse($response, [
            204 => true,
            404 => true,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function deleteObjects(array $objects)
    {
        $requests = [];

        /** @var SwiftObject $object */
        foreach ($objects as $object) {
            if ($object->isPseudoDir()) {
                continue;
            }

            $requests[] = $this->createRequest('delete', $object->getPath());

            $this->logger->debug(sprintf('Deleting "%s"', $object->getPath()));
        }

        $numRemoved = 0;

        if (!empty($requests)) {
            $results = Pool::batch($this->client, $requests);

            $error = false;
            foreach ($results as $index => $response) {
                if ($response instanceof ResponseInterface) {
                    if ($this->assertResponse($response, [204 => true])) {
                        ++$numRemoved;
                    }
                } elseif ($response instanceof \Exception) {
                    // skip 404s
                    if ($response instanceof BadResponseException && ($inner = $response->getResponse()) && ($inner->getStatusCode() === 404)) {
                        continue;
                    }

                    $error = true;
                    $this->logger->error(sprintf('Error deleting: %s', $response->getMessage()));
                }
            }

            if ($error) {
                throw new SwiftException('Could not delete all objects.');
            }
        }

        $this->logger->info(sprintf('Deleted %d objects', $numRemoved));

        return $numRemoved;
    }

    /**
     * @inheritdoc
     */
    public function copyObject(SwiftObject $object, Container $toContainer, $name)
    {
        $destination = sprintf('/%s/%s', $toContainer->getName(), $name);
        $headers = ['Destination' => $destination];

        $this->logger->info(sprintf('Copying "%s" => "%s"', $object->getPath(), $destination));

        $response = $this->copy($object->getPath(), null, $headers);

        return $this->assertResponse($response, [
            201 => function () use ($toContainer, $name) {
                return $this->getObject($toContainer, $name);
            },
        ]);
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
    protected function createRequest($method, $path, array $query = null, array $headers = [], $body = null)
    {
        if (!empty($query)) {
            $path .= (false === strpos($path, '?') ? '?' : '&') . http_build_query($query);
        }

        return new Request($method, $path, $headers, $body);
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
     * @return ResponseInterface
     */
    protected function request($method, $path, array $query = null, array $headers = [], $body = null)
    {
        $request = $this->createRequest($method, $path, $query, $headers, $body);

        $this->logger->debug((string) $request->getUri());

        try {
            return $this->client->send($request);
        } catch (BadResponseException $e) {
            // If we got a response, let the caller handle this.
            // This is because possible 'faulty' response codes, such as 404 or
            // 411 can be handled properly.
            if ($e->hasResponse()) {
                return $e->getResponse();
            }

            // ok got no response, this is bad
            $this->logger->error($e->getMessage());

            throw new SwiftException($e->getMessage(), null, $e);
        } catch (TransferException $e) {
            // these are other exceptions: connection failures, parse errors, etc.
            $this->logger->error($e->getMessage());

            throw new SwiftException($e->getMessage(), null, $e);
        }
    }

    /**
     * Checks response for any matching status, and returns that outcome.
     *
     * @param ResponseInterface $response
     * @param array             $statuses
     *
     * @throws SwiftException When none of the expected statuses matched the response
     *
     * @return mixed
     */
    protected function assertResponse(ResponseInterface $response, array $statuses)
    {
        $statusCode = $response->getStatusCode();

        if (array_key_exists($statusCode, $statuses)) {
            $result = $statuses[$statusCode];

            if (is_callable($result)) {
                $result = $result();
            }

            return $result;
        }

        throw new SwiftException(
            sprintf(
                'Expected status to be one of %s, but got %s',
                json_encode(array_keys($statuses)),
                $statusCode
            )
        );
    }
}
