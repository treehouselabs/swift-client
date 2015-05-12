<?php

namespace TreeHouse\Swift\Tests\Swift;

use GuzzleHttp\Message\Response;
use Symfony\Component\HttpFoundation\File\File;
use TreeHouse\Swift\Container;
use TreeHouse\Swift\Driver\DriverInterface;
use TreeHouse\Swift\Object as SwiftObject;
use TreeHouse\Swift\ObjectStore;

class ObjectStoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectStore
     */
    protected $store;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    protected $driver;

    protected function setUp()
    {
        $this->driver = $this->getMockForAbstractClass(DriverInterface::class);
        $this->store  = new ObjectStore($this->driver);
    }

    /**
     * @dataProvider httpMethodsProvider
     */
    public function testHttpMethod($method, array $args)
    {
        $response = new Response(200);

        $mock = $this->driver
            ->expects($this->once())
            ->method($method)
            ->willReturn($response)
        ;

        call_user_func_array([$mock, 'with'], $args);

        $this->assertSame($response, call_user_func_array([$this->store, $method], $args));
    }

    public function httpMethodsProvider()
    {
        $path    = '/foo';
        $query   = ['bar' => 'baz'];
        $headers = ['baz' => 'qux'];
        $body    = 'foobar';

        return [
            ['head',   [$path, $query, $headers]],
            ['get',    [$path, $query, $headers]],
            ['post',   [$path, $query, $headers, $body]],
            ['put',    [$path, $query, $headers, $body]],
            ['delete', [$path, $query, $headers, $body]],
            ['copy',   [$path, $query, $headers]],
        ];
    }

    /**
     * @expectedException        \TreeHouse\Swift\Exception\SwiftException
     * @expectedExceptionMessage Object container is private
     */
    public function testGetObjectUrlOnPrivateContainer()
    {
        $object = new SwiftObject(new Container('foo'), 'bar');
        $this->store->getObjectUrl($object);
    }

    public function testGetObjectUrl()
    {
        $container = new Container('foo');
        $container->setPublic();

        $url    = 'http://swift.example.org/container/object';
        $object = new SwiftObject($container, 'bar');

        $this->driver
            ->expects($this->once())
            ->method('getObjectUrl')
            ->with($object)
            ->willReturn($url);

        $this->assertEquals($url, $this->store->getObjectUrl($object));
    }

    public function testCreateContainer()
    {
        $name = 'foo';

        $this->driver
            ->expects($this->once())
            ->method('createContainer')
            ->willReturn(true);

        $container = $this->store->createContainer($name);

        $this->assertInstanceOf(Container::class, $container);
        $this->assertEquals($name, $container->getName());
        $this->assertTrue($container->isPrivate(), 'New containers are private by default');

        // containers are stored in an in-memory cache, this call should not
        // call createContainer on the driver again
        $this->store->createContainer($name);
    }

    public function testCreatePublicContainer()
    {
        $this->driver
            ->expects($this->once())
            ->method('createContainer')
            ->willReturn(true);

        $container = $this->store->createContainer('foo', false);
        $this->assertTrue($container->isPublic());
    }

    /**
     * @expectedException        \TreeHouse\Swift\Exception\SwiftException
     * @expectedExceptionMessage Could not create container foo
     */
    public function testCreateContainerFailure()
    {
        $this->driver
            ->expects($this->once())
            ->method('createContainer')
            ->willReturn(false);

        $this->store->createContainer('foo');
    }

    public function testContainerExists()
    {
        $container = new Container('foo');

        $this->driver
            ->expects($this->once())
            ->method('containerExists')
            ->with($container)
            ->willReturn(true);

        $this->assertTrue($this->store->containerExists($container));
    }

    public function testGetContainer()
    {
        $name      = 'foo';
        $container = new Container($name);

        $this->driver
            ->expects($this->once())
            ->method('getContainer')
            ->with($name)
            ->willReturn($container);

        $this->assertSame($container, $this->store->getContainer($name));

        // a second call should return a cached instance
        $this->store->getContainer($name);
    }

    public function testClear()
    {
        $name      = 'foo';
        $container = new Container($name);

        $this->driver
            ->expects($this->exactly(2))
            ->method('getContainer')
            ->with($name)
            ->willReturn($container);

        // first call, container will be cached
        $this->assertSame($container, $this->store->getContainer($name));

        // clear the store
        $this->store->clear();

        // a second call should fetch the container again
        $this->assertSame($container, $this->store->getContainer($name));
    }

    public function testGetContainerNotFound()
    {
        $this->driver
            ->expects($this->once())
            ->method('getContainer')
            ->willReturn(null);

        $this->assertNull($this->store->getContainer('foo'));
    }

    public function testUpdateContainer()
    {
        $container = new Container('foo');

        $this->driver
            ->expects($this->once())
            ->method('updateContainer')
            ->with($container)
            ->willReturn(true);

        $this->assertTrue($this->store->updateContainer($container));
    }

    public function testDeleteContainer()
    {
        $container = new Container('foo');

        $this->driver
            ->expects($this->once())
            ->method('deleteContainer')
            ->with($container)
            ->willReturn(true);

        $this->assertTrue($this->store->deleteContainer($container));
    }

    public function testDeleteContainerFails()
    {
        $container = new Container('foo');

        $this->driver
            ->expects($this->once())
            ->method('deleteContainer')
            ->with($container)
            ->willReturn(false);

        $this->assertFalse($this->store->deleteContainer($container));
    }

    public function testObjectExists()
    {
        $object = new SwiftObject(new Container('foo'), 'bar');

        $this->driver
            ->expects($this->once())
            ->method('objectExists')
            ->with($object)
            ->willReturn(true);

        $this->assertTrue($this->store->objectExists($object));
    }

    public function testCreateObject()
    {
        $name      = 'bar';
        $container = new Container('foo');
        $object    = new SwiftObject($container, $name);

        $this->driver
            ->expects($this->once())
            ->method('containerExists')
            ->with($container)
            ->willReturn(false);

        $this->driver
            ->expects($this->once())
            ->method('createContainer')
            ->with($container)
            ->willReturn(true);

        $this->driver
            ->expects($this->once())
            ->method('createObject')
            ->with($container, $name)
            ->willReturn($object);

        $this->assertSame($object, $this->store->createObject($container, $name));
    }

    public function testCreateObjectOnExistingContainer()
    {
        $name      = 'bar';
        $container = new Container('foo');
        $object    = new SwiftObject($container, $name);

        $this->driver
            ->expects($this->once())
            ->method('containerExists')
            ->with($container)
            ->willReturn(true);

        $this->driver
            ->expects($this->never())
            ->method('createContainer')
            ->with($container)
            ->willReturn(true);

        $this->driver
            ->expects($this->once())
            ->method('createObject')
            ->with($container, $name)
            ->willReturn($object);

        $this->assertSame($object, $this->store->createObject($container, $name));
    }

    public function testGetObject()
    {
        $name      = 'bar';
        $container = new Container('foo');
        $object    = new SwiftObject($container, $name);

        $this->driver
            ->expects($this->once())
            ->method('getObject')
            ->with($container, $name)
            ->willReturn($object);

        $this->assertSame($object, $this->store->getObject($container, $name));
    }

    public function testGetObjectNotFound()
    {
        $this->driver
            ->expects($this->once())
            ->method('getObject')
            ->willReturn(null);

        $this->assertNull($this->store->getObject(new Container('foo'), 'bar'));
    }

    public function testGetObjects()
    {
        $name      = 'bar';
        $container = new Container('foo');
        $object    = new SwiftObject($container, $name);

        $this->driver
            ->expects($this->once())
            ->method('getObjects')
            ->with($container)
            ->willReturn([$object]);

        $this->assertSame([$object], $this->store->getObjects($container));
    }

    public function testGetObjectsWithArguments()
    {
        $name      = 'bar';
        $container = new Container('foo');
        $object    = new SwiftObject($container, $name);

        $prefix    = 'foo';
        $delimiter = '/';
        $limit     = 10;
        $start     = 0;
        $end       = 5;

        $this->driver
            ->expects($this->once())
            ->method('getObjects')
            ->with($container, $prefix . $delimiter, $delimiter, $limit, $start, $end)
            ->willReturn([$object]);

        $this->assertSame([$object], $this->store->getObjects($container, $prefix, $delimiter, $limit, $start, $end));
    }

    public function testGetObjectContent()
    {
        $container = new Container('foo');
        $object    = new SwiftObject($container, 'bar');
        $asString  = true;
        $headers   = ['bar' => 'baz'];
        $content   = 'foobar';

        $this->driver
            ->expects($this->once())
            ->method('getObjectContent')
            ->with($object, $asString, $headers)
            ->willReturn($content);

        $this->assertSame($content, $this->store->getObjectContent($object, $asString, $headers));
    }

    public function testUpdateNewObject()
    {
        $container = new Container('foo');
        $object    = new SwiftObject($container, 'bar');

        $this->driver
            ->expects($this->once())
            ->method('updateObject')
            ->with($object)
            ->willReturn(true);

        $object->setLocalFile(new File(__FILE__));

        $this->assertTrue($this->store->updateObject($object));
    }

    public function testUpdateExistingObject()
    {
        $container = new Container('foo');
        $object    = new SwiftObject($container, 'bar');

        $this->driver
            ->expects($this->once())
            ->method('updateObject')
            ->with($object)
            ->willReturn(true);

        $this->driver
            ->expects($this->once())
            ->method('objectExists')
            ->with($object)
            ->willReturn(true);

        $this->assertTrue($this->store->updateObject($object));
    }

    /**
     * @expectedException        \TreeHouse\Swift\Exception\SwiftException
     * @expectedExceptionMessage Cannot update a new object without a body
     */
    public function testUpdateObjectWithoutLocalFile()
    {
        $container = new Container('foo');
        $object    = new SwiftObject($container, 'bar');

        $this->driver
            ->expects($this->once())
            ->method('objectExists')
            ->with($object)
            ->willReturn(false);

        $this->store->updateObject($object);
    }

    public function testUpdateObjectMetadata()
    {
        $container = new Container('foo');
        $object    = new SwiftObject($container, 'bar');

        $this->driver
            ->expects($this->once())
            ->method('updateObjectMetadata')
            ->with($object)
            ->willReturn(true);

        $this->assertTrue($this->store->updateObjectMetadata($object));
    }

    public function testDeleteObject()
    {
        $container = new Container('foo');
        $object    = new SwiftObject($container, 'bar');

        $this->driver
            ->expects($this->once())
            ->method('deleteObject')
            ->with($object)
            ->willReturn(true);

        $this->assertTrue($this->store->deleteObject($object));
    }

    public function testDeleteObjects()
    {
        $container = new Container('foo');
        $object    = new SwiftObject($container, 'bar');

        $this->driver
            ->expects($this->once())
            ->method('deleteObjects')
            ->with([$object])
            ->willReturn(true);

        $this->assertTrue($this->store->deleteObjects([$object]));
    }

    public function testCopyObject()
    {
        $container   = new Container('foo');
        $object      = new SwiftObject($container, 'baz');
        $destination = new Container('bar');
        $newName     = 'qux';
        $newObject   = new SwiftObject($destination, $newName);

        $this->driver
            ->expects($this->once())
            ->method('copyObject')
            ->with($object, $destination, $newName)
            ->willReturn($newObject);

        $this->assertSame($newObject, $this->store->copyObject($object, $destination, $newName));
    }

    public function testCopyObjectDefaultName()
    {
        $container   = new Container('foo');
        $object      = new SwiftObject($container, 'baz');
        $destination = new Container('bar');
        $newObject   = new SwiftObject($destination, $object->getName());

        $this->driver
            ->expects($this->once())
            ->method('copyObject')
            ->with($object, $destination, $object->getName())
            ->willReturn($newObject);

        $this->assertSame($newObject, $this->store->copyObject($object, $destination));
    }

    public function testCopyObjectToSameContainer()
    {
        $container = new Container('foo');
        $object    = new SwiftObject($container, 'bar');
        $newName   = 'baz';
        $newObject = new SwiftObject($container, $newName);

        $this->driver
            ->expects($this->once())
            ->method('copyObject')
            ->with($object, $container, $newName)
            ->willReturn($newObject);

        $this->assertSame($newObject, $this->store->copyObject($object, $container, $newName));
    }

    /**
     * @expectedException        \TreeHouse\Swift\Exception\SwiftException
     * @expectedExceptionMessage Destination is same as source
     */
    public function testCopyObjectCircularReference()
    {
        $container = new Container('foo');
        $object    = new SwiftObject($container, 'baz');

        $this->driver
            ->expects($this->never())
            ->method('copyObject');

        $this->store->copyObject($object, $container);
    }
}
