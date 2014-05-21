<?php

namespace TreeHouse\Swift\Tests\Swift;

use Symfony\Component\HttpFoundation\File\File;
use TreeHouse\Swift\Container;
use TreeHouse\Swift\ObjectStore;

class ObjectStoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectStore
     */
    protected $store;

    /**
     * @var Container
     */
    protected $testContainer;

    /**
     * @var string
     */
    protected $testContainerName = 'test';

    /**
     * @var array
     */
    protected $tempfiles = [];

    public function testCreateContainer()
    {
        $container = $this->store->createContainer($this->testContainerName);
        $this->assertNotNull($container);
    }

    public function testPrivateContainer()
    {
        $container = $this->store->createContainer($this->testContainerName, false);
        $this->assertFalse($container->isPrivate());

        $container->setPrivate();
        $this->store->updateContainer($container);
        $this->assertTrue($container->isPrivate());
    }

    public function testNewContainerPrivateByDefault()
    {
        $container = $this->store->createContainer($this->testContainerName);
        $this->assertTrue($container->isPrivate());
    }

    public function testGetContainer()
    {
        $this->store->createContainer($this->testContainerName);
        $this->store->clear();

        // found
        $container = $this->store->getContainer($this->testContainerName);
        $this->assertNotNull($container);

        // not found
        $container = $this->store->getContainer('shouldnotexist');
        $this->assertNull($container);
    }

    public function testDeleteContainer()
    {
        $container = $this->getTestContainer();
        $this->store->deleteContainer($container);

        $this->assertNull($this->store->getContainer($this->testContainerName));
    }

    public function testCreateObject()
    {
        $container = $this->getTestContainer();

        $object = $this->store->createObject($container, 'foo');
        $this->assertNotNull($object);

        $file = $this->getTempFile();
        $object->setLocalFile(new File($file));
        $this->store->updateObject($object);
        unlink($file);

        // file should now exist
        $this->assertNotNull($this->store->getObject($container, 'foo'));
    }

    /**
     * @expectedException        \TreeHouse\Swift\Exception\SwiftException
     * @expectedExceptionMessage Cannot update a new object without a body
     */
    public function testCreateObjectWithoutBody()
    {
        $container = $this->getTestContainer();

        $object = $this->store->createObject($container, 'foo');
        $this->store->updateObject($object);
    }

    public function testCreateObjectCascadesContainer()
    {
        $container = new Container($this->testContainerName);

        $object = $this->store->createObject($container, 'foo');

        $file = $this->getTempFile();
        $object->setLocalFile(new File($file));
        $this->store->updateObject($object);
        unlink($file);

        // container should have been created
        $this->assertTrue($this->store->containerExists($container));
    }

    public function testCreateObjectWithSpecificName()
    {
        $container = $this->getTestContainer();

        $name = 'foobar.tmp';
        $file = $this->getTempFile();

        $object = $this->store->createObject($container, $name);
        $object->setLocalFile(new File($file));
        $this->store->updateObject($object);
        unlink($file);

        // clear first
        $this->store->clear();

        // object should have the defined name
        $this->assertEquals($name, $this->store->getObject($container, $name)->getName());
    }

    public function testGetObject()
    {
        $container = $this->getTestContainer();

        $object = $this->store->createObject($container, 'foo');

        $file = $this->getTempFile();
        $object->setLocalFile(new File($file));
        $this->store->updateObject($object);
        unlink($file);

        $this->store->clear();

        $this->assertNotNull($this->store->getObject($container, 'foo'));
        $this->assertNull($this->store->getObject($container, 'foobar'));
    }

    public function testGetObjects()
    {
        $container = $this->getTestContainer();

        $file = $this->getTempFile();

        $sizes = array(
            '100/100',
            '100/150',
            '100/200',
            '200/100',
            '200/150',
            '200/200',
            'g/100/150',
            'g/100/200',
            'g/200/100',
            'g/200/150',
            'g/200/200',
        );

        foreach ($sizes as $size) {
            $object = $this->store->createObject($container, $size);
            $object->setLocalFile(new File($file));
            $this->store->updateObject($object);
        }

        unlink($file);

        // all objects
        $objects = $this->store->getObjects($container);
        $this->assertEquals(sizeof($sizes), sizeof($objects));

        // prefix
        $this->assertEquals(3, sizeof($this->store->getObjects($container, '100')));
        $this->assertEquals(5, sizeof($this->store->getObjects($container, 'g')));
        $this->assertEquals(3, sizeof($this->store->getObjects($container, 'g/200')));

        // delimiter
        $this->assertEquals(3, sizeof($this->store->getObjects($container, null, '/')));
        $this->assertEquals(3, sizeof($this->store->getObjects($container, '200', '/')));
        $this->assertEquals(2, sizeof($this->store->getObjects($container, 'g', '/')));

        // limit
        $limit = 4;
        $objects = $this->store->getObjects($container, null, null, $limit);
        $this->assertEquals($limit, sizeof($objects));

        // marker
        $this->assertEquals(8, sizeof($this->store->getObjects($container, null, null, null, '200')));
        $this->assertEquals(5, sizeof($this->store->getObjects($container, null, null, null, null, '200/200')));
        $this->assertEquals(2, sizeof($this->store->getObjects($container, 'g', null, null, null, 'g/200/100')));
        $this->assertEquals(6, sizeof($this->store->getObjects($container, null, null, null, '100', 'g')));
    }

    public function testContainerMetadata()
    {
        $container = $this->store->createContainer('test');
        $container->getMetadata()->set('Foo', 'Bar');

        // save
        $this->assertTrue($this->store->updateContainer($container));

        // fetch again
        $this->store->clear();
        $newContainer = $this->store->getContainer('test');

        // meta data should have been saved
        $this->assertEquals('Bar', $newContainer->getMetadata()->get('Foo'));
    }

    public function testObjectMetadata()
    {
        $container = $this->getTestContainer();

        $object = $this->store->createObject($container, 'foo');
        $object->getMetadata()->set('foo', 'bar');

        $file = $this->getTempFile();
        $object->setLocalFile(new File($file));
        $this->store->updateObject($object);
        unlink($file);

        // clear
        $this->store->clear();

        // fetch again
        $newObject = $this->store->getObject($container, 'foo');
        $this->assertEquals('bar', $object->getMetadata()->get('foo'));

        // change
        $object->getMetadata()->set('foo', 'foobar');
        $this->assertTrue($this->store->updateObjectMetadata($object));

        // fetch again
        $this->store->clear();
        $newObject = $this->store->getObject($container, 'foo');

        // meta data should have been saved
        $this->assertEquals('foobar', $newObject->getMetadata()->get('foo'));
    }

    public function testGetObjectFetchesMetadata()
    {
        $container = $this->getTestContainer();

        $object = $this->store->createObject($container, 'foo');
        $object->getMetadata()->set('foo', 'bar');

        $file = $this->getTempFile();
        $object->setLocalFile(new File($file));
        $this->store->updateObject($object);
        unlink($file);

        $this->store->clear();

        $object = $this->store->getObject($container, 'foo');
        $this->assertEquals('bar', $object->getMetadata()->get('foo'));
    }

    public function testGetObjectsFetchesMetadata()
    {
        $container = $this->getTestContainer();

        $object = $this->store->createObject($container, 'foo');
        $object->getMetadata()->set('foo', 'bar');

        $file = $this->getTempFile();
        $object->setLocalFile(new File($file));
        $this->store->updateObject($object);
        unlink($file);

        $this->store->clear();

        // get objects
        $objects = $this->store->getObjects($container);
        $this->assertEquals('bar', $objects[0]->getMetadata()->get('foo'));
    }

    public function testDeleteObject()
    {
        $container = $this->getTestContainer();

        $object = $this->store->createObject($container, 'foo');

        $file = $this->getTempFile();
        $object->setLocalFile(new File($file));
        $this->store->updateObject($object);
        unlink($file);

        $this->store->clear();

        // object should exist
        $this->assertNotNull($this->store->getObject($container, 'foo'));

        $this->store->deleteObject($object);
        $this->store->clear();

        // object should not exist anymore
        $this->assertNull($this->store->getObject($container, 'foo'));
    }

    public function testDeleteObjects()
    {
        $container = $this->getTestContainer();

        // create two objects
        $object = $this->store->createObject($container, 'foo');

        $file = $this->getTempFile();
        $object->setLocalFile(new File($file));
        $this->store->updateObject($object);
        unlink($file);

        $object = $this->store->createObject($container, 'foo2');

        $file = $this->getTempFile();
        $object->setLocalFile(new File($file));
        $this->store->updateObject($object);
        unlink($file);

        $this->store->deleteObjects($this->store->getObjects($container));
        $this->store->clear();

        // object should not exist anymore
        $this->assertNull($this->store->getObject($container, 'foo'));
        $this->assertNull($this->store->getObject($container, 'foo2'));
    }

    public function testCopyObject()
    {
        $container = $this->getTestContainer();

        $object = $this->store->createObject($container, 'foo');

        $file = $this->getTempFile();
        $object->setLocalFile(new File($file));
        $this->store->updateObject($object);
        unlink($file);

        // copy to new container
        $newContainerName = 'container2';
        $newContainer = $this->store->createContainer($newContainerName);

        $newObject = $this->store->copyObject($object, $newContainer);

        // returned object must exist
        $this->assertNotNull($newObject);

        // when fetched again, it must exist
        $this->store->clear();
        $this->assertNotNull($this->store->getObject($newContainer, $newObject->getName()));

        // test rename
        $newObject = $this->store->copyObject($newObject, $newContainer, 'newname');
        $this->assertEquals('newname', $newObject->getName());
    }

    /**
     * @expectedException        \TreeHouse\Swift\Exception\SwiftException
     * @expectedExceptionMessage Destination is same as source
     */
    public function testCopyCircularReference()
    {
        $container = $this->getTestContainer();
        $object = $this->store->createObject($container, 'foo');

        $file = $this->getTempFile();
        $object->setLocalFile(new File($file));
        $this->store->updateObject($object);
        unlink($file);

        // copy to same container, same name
        $this->store->copyObject($object, $object->getContainer());
    }

    protected function setUp()
    {
        $this->markTestIncomplete('Mock object store first');
//        $this->store = new ObjectStore();
//        $this->store->clear();
    }

    protected function tearDown()
    {
        foreach ($this->tempfiles as $tempfile) {
            if (file_exists($tempfile)) {
                unlink($tempfile);
            }
        }

        if (null !== $this->testContainer) {
            $this->deleteTestContainer();
        }

        parent::tearDown();
    }

    protected function getTempFile()
    {
        $this->tempfiles[] = $tempfile = tempnam(sys_get_temp_dir(), 'cdn-test-file');

        return $tempfile;
    }

    protected function getTestContainer()
    {
        if (null === $this->testContainer) {
            $container = $this->store->getContainer($this->testContainerName);
            if (is_null($container)) {
                $container = $this->store->createContainer($this->testContainerName);
            }

            $this->testContainer = $container;
        }

        return $this->testContainer;
    }

    protected function deleteTestContainer()
    {
        $container = $this->store->getContainer($this->testContainerName);
        if ($container) {
            $this->store->deleteContainer($container);
            $this->testContainer = null;
        }
    }
}
