<?php

namespace TreeHouse\Swift\Tests\Swift\Driver;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\File\File;
use TreeHouse\Swift\Container;
use TreeHouse\Swift\Driver\SwiftDriver;
use TreeHouse\Swift\Object as SwiftObject;

class SwiftDriverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $client;

    /**
     * @var SwiftDriver
     */
    protected $driver;

    /**
     * @var MockHandler
     */
    protected $handler;

    /**
     * @var string
     */
    protected $url = 'http://swift.example.org';

    protected function setUp()
    {
        $this->handler = new MockHandler();
        $this->client = new Client(['base_uri' => $this->url, 'handler' => $this->handler]);
        $this->driver = new SwiftDriver($this->client);
    }

    protected function tearDown()
    {
        if ($this->handler->count() > 0) {
            $this->fail('Not all responses were given');
        }
    }

    public function testGetClient()
    {
        $this->assertSame($this->client, $this->driver->getClient());
    }

    public function testGetBaseUrl()
    {
        $this->assertSame($this->url, (string) $this->driver->getBaseUri());
    }

    /**
     * @dataProvider httpMethodsProvider
     *
     * @param string $method
     * @param string $path
     * @param array  $query
     * @param array  $headers
     * @param null   $body
     */
    public function testHttpMethod($method, $path, array $query, array $headers, $body = null)
    {
        $url = $this->url . $path . '?' . http_build_query($query);

        $response = new Response(200);

        $this->mockClientRequest($method, $url, $headers, $body, $response);

        $this->assertSame($response, $this->driver->$method($path, $query, $headers, $body));
    }

    /**
     * @return array
     */
    public function httpMethodsProvider()
    {
        $path = '/foo';
        $query = ['bar' => 'baz'];
        $headers = ['baz' => 'qux'];
        $body = 'foobar';

        return [
            ['head',   $path, $query, $headers],
            ['get',    $path, $query, $headers],
            ['post',   $path, $query, $headers, $body],
            ['put',    $path, $query, $headers, $body],
            ['delete', $path, $query, $headers, $body],
        ];
    }

    /**
     * @expectedException \TreeHouse\Swift\Exception\SwiftException
     */
    public function testRequestWithoutResponse()
    {
        $exception = $this->getBadResponseExceptionMock();

        $this->mockClientRequest('get', '/foo', [], null, $exception);
        $this->driver->get('/foo');
    }

    public function testGetObjectUrl()
    {
        $container = new Container('foo');
        $object = new SwiftObject($container, 'bar');

        $this->assertEquals($this->url . '/foo/bar', $this->driver->getObjectUrl($object));
    }

    public function testContainerExists()
    {
        $container = new Container('foo');

        $this->mockClientRequest('head', $container->getName(), [], null, new Response(204));
        $this->assertTrue($this->driver->containerExists($container));
    }

    public function testContainerNotExists()
    {
        $container = new Container('foo');

        $this->mockClientRequest('head', $container->getName(), [], null, new Response(404));
        $this->assertFalse($this->driver->containerExists($container));
    }

    public function testCreateContainer()
    {
        $name = 'foo';
        $headers = ['foo' => ['bar']];

        $this->mockClientRequest('put', $name, $headers, null, new Response(201));

        $container = new Container($name);
        $container->setHeaders($headers);

        $this->assertTrue($this->driver->createContainer($container));
    }

    public function testCreateContainerAlreadyExists()
    {
        $name = 'foo';
        $headers = ['foo' => ['bar']];

        $this->mockClientRequest('put', $name, $headers, null, new Response(202));

        $container = new Container($name);
        $container->setHeaders($headers);

        $this->assertTrue($this->driver->createContainer($container));
    }

    /**
     * @expectedException \TreeHouse\Swift\Exception\SwiftException
     */
    public function testCreateContainerFailure()
    {
        $name = 'foo';
        $headers = ['foo' => ['bar']];

        $this->mockClientRequest('put', $name, $headers, null, new Response(500));

        $container = new Container($name);
        $container->setHeaders($headers);

        $this->assertTrue($this->driver->createContainer($container));
    }

    public function testCreatePublicContainer()
    {
        $name = 'foo';
        $headers = ['foo' => ['bar']];

        $this->mockClientRequest('put', $name, array_merge($headers, ['X-Container-Meta-Read' => '.r:*']), null, new Response(201));

        $container = new Container($name);
        $container->setPublic();
        $container->setHeaders($headers);

        $this->assertTrue($this->driver->createContainer($container));
    }

    public function testGetContainer()
    {
        $name = 'foo';

        $this->mockClientRequest('head', $name, [], null, new Response(204, ['X-Container-Meta-Read' => '.r:*']));

        $container = $this->driver->getContainer($name);

        $this->assertInstanceOf(Container::class, $container);
        $this->assertEquals($name, $container->getName());
        $this->assertTrue($container->isPublic());
    }

    public function testGetContainerNotFound()
    {
        $name = 'foo';

        $this->mockClientRequest('head', $name, [], null, new Response(404));
        $this->assertNull($this->driver->getContainer($name));
    }

    public function testUpdateContainer()
    {
        $name = 'foo';
        $container = new Container($name);
        $container->setPublic();

        $this->mockClientRequest('post', $name, ['X-Container-Meta-Read' => '.r:*'], null, new Response(204));

        $this->assertTrue($this->driver->updateContainer($container));
    }

    public function testDeleteContainer()
    {
        $name = 'foo';
        $container = new Container($name);

        $this->mockClientRequest('get', $name, [], null, new Response(200));
        $this->mockClientRequest('delete', $name, [], null, new Response(204));

        $this->assertTrue($this->driver->deleteContainer($container));
    }

    public function testDeleteContainerAlsoDeletesObjects()
    {
        $name = 'foo';
        $container = new Container($name);

        $this->mockClientRequest('get',    $name,          [], null, new Response(200, [], "foo\nbar"));
        $this->mockClientRequest('head',   $name . '/foo', [], null, new Response(204));
        $this->mockClientRequest('head',   $name . '/bar', [], null, new Response(204));
        $this->mockClientRequest('delete', $name . '/foo', [], null, new Response(204));
        $this->mockClientRequest('delete', $name . '/bar', [], null, new Response(204));
        $this->mockClientRequest('delete', $name,          [], null, new Response(204));

        $this->assertTrue($this->driver->deleteContainer($container));
    }

    public function testDeleteContainerNotFound()
    {
        $name = 'foo';
        $container = new Container($name);

        $this->mockClientRequest('get', $name, [], null, new Response(404));
        $this->mockClientRequest('delete', $name, [], null, new Response(404));

        $this->assertTrue($this->driver->deleteContainer($container));
    }

    public function testObjectExists()
    {
        $object = new SwiftObject(new Container('foo'), 'bar');

        $this->mockClientRequest('head', $object->getPath(), [], null, new Response(204));
        $this->assertTrue($this->driver->objectExists($object));
    }

    public function testObjectNotExists()
    {
        $object = new SwiftObject(new Container('foo'), 'bar');

        $this->mockClientRequest('head', $object->getPath(), [], null, new Response(404));
        $this->assertFalse($this->driver->objectExists($object));
    }

    public function testCreateObject()
    {
        $container = new Container('foo');
        $object = $this->driver->createObject($container, 'bar');

        $this->assertSame($container, $object->getContainer());
        $this->assertSame('bar', $object->getName());
    }

    public function testCreateObjectFromResponse()
    {
        $container = new Container('foo');
        $response = new Response(200, ['foo' => 'bar', 'X-Object-Meta-Bar' => 'baz'], 'test');
        $object = $this->driver->createObject($container, 'bar', $response);

        $this->assertSame($container, $object->getContainer());
        $this->assertSame('bar', $object->getName());
        $this->assertSame(['bar'], $object->getHeaders()['foo']);
        $this->assertSame('baz', $object->getMetadata()->get('bar'));
    }

    public function testGetObject()
    {
        $name = 'foo';
        $container = new Container($name);

        $this->mockClientRequest('head', $name . '/bar', [], null, new Response(204, ['foo' => 'bar', 'X-Object-Meta-Bar' => 'baz']));

        $object = $this->driver->getObject($container, 'bar');

        $this->assertSame($container, $object->getContainer());
        $this->assertSame('bar', $object->getName());
        $this->assertSame(['bar'], $object->getHeaders()['foo']);
        $this->assertSame('baz', $object->getMetadata()->get('bar'));
    }

    public function testGetObjectNotFound()
    {
        $name = 'foo';
        $container = new Container($name);

        $this->mockClientRequest('head', $name . '/bar', [], null, new Response(404));

        $this->assertNull($this->driver->getObject($container, 'bar'));
    }

    public function testGetObjects()
    {
        $name = 'bar';
        $container = new Container($name);

        $this->mockClientRequest('get',    $name,          [], null, new Response(200, [], "foo\nbar"));
        $this->mockClientRequest('head',   $name . '/foo', [], null, new Response(204));
        $this->mockClientRequest('head',   $name . '/bar', [], null, new Response(204));

        /** @var SwiftObject[] $objects */
        $objects = $this->driver->getObjects($container);

        $this->assertCount(2, $objects);
        $this->assertInstanceOf(SwiftObject::class, $objects[0]);
        $this->assertSame($container, $objects[0]->getContainer());
        $this->assertSame('foo', $objects[0]->getName());
        $this->assertSame('bar', $objects[1]->getName());
    }

    public function testGetObjectsWithDirectories()
    {
        $name = 'foo';
        $container = new Container($name);
        $contents = <<<EOT
bar/
bar/baz
bar/qux
EOT;

        $this->mockClientRequest('get',    $name,          [], null, new Response(200, [], $contents));
        $this->mockClientRequest('head',   $name . '/bar/baz', [], null, new Response(204));
        $this->mockClientRequest('head',   $name . '/bar/qux', [], null, new Response(204));

        /** @var SwiftObject[] $objects */
        $objects = $this->driver->getObjects($container, 'bar/', '/', 10, 0, 5);

        $this->assertCount(3, $objects);
        $this->assertInstanceOf(SwiftObject::class, $objects[0]);
        $this->assertSame($container, $objects[0]->getContainer());
        $this->assertTrue($objects[0]->isPseudoDir());
        $this->assertSame('foo/bar/', $objects[0]->getPath());
        $this->assertSame('foo/bar/baz', $objects[1]->getPath());
        $this->assertSame('foo/bar/qux', $objects[2]->getPath());
    }

    /**
     * @expectedException \TreeHouse\Swift\Exception\SwiftException
     */
    public function testGetObjectsWithFailures()
    {
        $name = 'bar';
        $container = new Container($name);

        $exception = $this->getBadResponseExceptionMock();

        $this->mockClientRequest('get',    $name,          [], null, new Response(200, [], "foo\nbar/\nbar/baz\nbar/qux\n"));
        $this->mockClientRequest('head',   $name . '/foo', [], null, new Response(204));
        $this->mockClientRequest('head',   $name . '/bar/baz', [], null, $exception);
        $this->mockClientRequest('head',   $name . '/bar/qux', [], null, new Response(204));

        $this->driver->getObjects($container);
    }

    public function testGetObjectContent()
    {
        $container = new Container('foo');
        $object = new SwiftObject($container, 'bar');

        $this->mockClientRequest('get', $object->getPath(), [], null, new Response(200, [], 'test'));

        $this->assertSame('test', $this->driver->getObjectContent($object));
    }

    /**
     * @expectedException \TreeHouse\Swift\Exception\SwiftException
     */
    public function testGetObjectContentNotFound()
    {
        $container = new Container('foo');
        $object = new SwiftObject($container, 'bar');

        $this->mockClientRequest('get', $object->getPath(), [], null, new Response(404));

        $this->driver->getObjectContent($object);
    }

    public function testUpdateObjectWithoutContentModification()
    {
        $container = new Container('foo');
        $object = new SwiftObject($container, 'bar');

        $this->mockClientRequest('post', $container->getName(), [], null, new Response(204));
        $this->mockClientRequest('post', $object->getPath(), [], null, new Response(202));

        $this->assertTrue($this->driver->updateObject($object));
    }

    public function testUpdateModifiedObject()
    {
        $container = new Container('foo');
        $object = new SwiftObject($container, 'bar');
        $object->setLocalFile(new File(__FILE__));

        $this->mockClientRequest('post', $container->getName(), [], null, new Response(204));
        $this->mockClientRequest('put', $object->getPath(), [], null, new Response(201));

        $this->assertTrue($this->driver->updateObject($object));
    }

    /**
     * @expectedException \TreeHouse\Swift\Exception\SwiftException
     */
    public function testUpdateObjectUnexpectedResponse()
    {
        $container = new Container('foo');
        $object = new SwiftObject($container, 'bar');
        $object->setLocalFile(new File(__FILE__));

        $this->mockClientRequest('post', $container->getName(), [], null, new Response(204));
        $this->mockClientRequest('put', $object->getPath(), [], null, new Response(411));

        $this->driver->updateObject($object);
    }

    public function testUpdateObjectMetadata()
    {
        $container = new Container('foo');
        $object = new SwiftObject($container, 'bar');

        $this->mockClientRequest('post', $object->getPath(), [], null, new Response(202));

        $this->assertTrue($this->driver->updateObjectMetadata($object));
    }

    public function testDeleteObject()
    {
        $container = new Container('foo');
        $object = new SwiftObject($container, 'bar');

        $this->mockClientRequest('delete', $object->getPath(), [], null, new Response(204));

        $this->assertTrue($this->driver->deleteObject($object));
    }

    public function testDeleteNotFoundObject()
    {
        $container = new Container('foo');
        $object = new SwiftObject($container, 'bar');

        $this->mockClientRequest('delete', $object->getPath(), [], null, new Response(404));

        $this->assertTrue($this->driver->deleteObject($object));
    }

    public function testDeleteObjects()
    {
        $container = new Container('foo');

        /** @var SwiftObject[] $objects */
        $objects = [
            new SwiftObject($container, 'foo/'),
            new SwiftObject($container, 'foo/bar'),
            new SwiftObject($container, 'foo/baz'),
        ];

        $this->mockClientRequest('delete', $objects[1]->getPath(), [], null, new Response(204));
        $this->mockClientRequest('delete', $objects[2]->getPath(), [], null, new Response(204));

        $this->assertSame(2, $this->driver->deleteObjects($objects));
    }

    /**
     * @expectedException \TreeHouse\Swift\Exception\SwiftException
     */
    public function testDeleteObjectsWithFailure()
    {
        $container = new Container('foo');

        /** @var SwiftObject[] $objects */
        $objects = [
            new SwiftObject($container, 'bar'),
            new SwiftObject($container, 'baz'),
        ];

        $exception = $this->getBadResponseExceptionMock();

        $this->mockClientRequest('delete', $objects[0]->getPath(), [], null, new Response(204));
        $this->mockClientRequest('delete', $objects[1]->getPath(), [], null, $exception);

        $this->driver->deleteObjects($objects);
    }

    public function testDeleteObjectsWithNotFounds()
    {
        $container = new Container('foo');

        /** @var SwiftObject[] $objects */
        $objects = [
            new SwiftObject($container, 'bar'),
            new SwiftObject($container, 'baz'),
        ];

        $exception = new BadResponseException('', new Request('GET', $this->url), new Response(404));

        $this->mockClientRequest('delete', $objects[0]->getPath(), [], null, new Response(204));
        $this->mockClientRequest('delete', $objects[1]->getPath(), [], null, $exception);

        $this->assertSame(1, $this->driver->deleteObjects($objects));
    }

    public function testCopyObject()
    {
        $container = new Container('foo');
        $object = new SwiftObject($container, 'baz');
        $destination = new Container('bar');
        $newName = 'qux';

        $this->mockClientRequest('copy', $container->getName(), [], null, new Response(201));
        $this->mockClientRequest('head', $container->getName(), [], null, new Response(204, ['foo' => 'bar', 'X-Object-Meta-Bar' => 'baz']));

        $newObject = $this->driver->copyObject($object, $destination, $newName);

        $this->assertInstanceOf(SwiftObject::class, $newObject);
        $this->assertSame($destination, $newObject->getContainer());
        $this->assertSame($newName, $newObject->getName());
        $this->assertSame(['bar'], $newObject->getHeaders()['foo']);
        $this->assertSame('baz', $newObject->getMetadata()->get('bar'));
    }

    /**
     * @param string                       $method
     * @param string                       $url
     * @param array                        $headers
     * @param mixed                        $body
     * @param \Exception|ResponseInterface $responseOrException
     */
    protected function mockClientRequest($method, $url, array $headers, $body = null, $responseOrException)
    {
        // TODO it would be nice if we could assert the request arguments as well
        $this->handler->append($responseOrException);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|BadResponseException
     */
    protected function getBadResponseExceptionMock()
    {
        return $this->getMockBuilder(BadResponseException::class)->disableOriginalConstructor()->getMock();
    }
}
