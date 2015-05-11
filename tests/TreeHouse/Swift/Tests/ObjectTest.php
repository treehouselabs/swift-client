<?php

namespace TreeHouse\Swift\Tests\Swift;

use Symfony\Component\HttpFoundation\File\File;
use TreeHouse\Swift\Container;
use TreeHouse\Swift\Metadata\Metadata;
use TreeHouse\Swift\Object as SwiftObject;

class ObjectTest extends \PHPUnit_Framework_TestCase
{
    protected $container;

    protected function setUp()
    {
        $this->container = new Container('foo');
    }

    public function testConstructor()
    {
        $object = new SwiftObject($this->container, 'bar/baz.jpg');

        $this->assertInstanceOf(SwiftObject::class, $object);
        $this->assertSame($this->container, $object->getContainer());
        $this->assertSame('bar/baz.jpg', $object->getName());
        $this->assertSame('baz.jpg', $object->getBaseName());
        $this->assertSame('baz', $object->getBaseName('.jpg'));
        $this->assertSame('foo/bar/baz.jpg', $object->getPath());
        $this->assertSame('jpg', $object->getExtension());
        $this->assertInternalType('array', $object->getHeaders());
        $this->assertEmpty($object->getHeaders());
        $this->assertInstanceOf(Metadata::class, $object->getMetadata());
        $this->assertEmpty($object->getMetadata());
    }

    public function testGettersAndSetters()
    {
        $object = new SwiftObject($this->container, 'bar/baz.jpg');
        $object->setBody('test');
        $this->assertSame('test', $object->getBody());

        $object->setContentLength(4);
        $this->assertSame(4, $object->getContentLength());

        $object->setContentType('text/plain');
        $this->assertSame('text/plain', $object->getContentType());

        $object->setETag('1234567890');
        $this->assertSame('1234567890', $object->getETag());

        $date = new \DateTime('-1 hour');
        $object->setLastModifiedDate($date);
        $this->assertEquals($date, $object->getLastModifiedDate());
    }

    public function testHeaders()
    {
        $object = new SwiftObject($this->container, 'bar/baz.jpg');
        $object->setHeaders(['foo' => 'bar']);
        $object->getMetadata()->set('bar', 'baz');

        $this->assertEquals(
            [
                'foo'               => ['bar'],
                'X-Object-Meta-Bar' => 'baz',
            ],
            $object->getHeaders()
        );
    }

    public function testHeaderWithPrefixedKey()
    {
        $object = new SwiftObject($this->container, 'bar/baz.jpg');
        $object->setHeaders(['foo' => 'bar', 'X-Object-Meta-Bar' => 'baz']);

        $this->assertEquals(
            [
                'foo'               => ['bar'],
                'X-Object-Meta-Bar' => 'baz',
            ],
            $object->getHeaders()
        );
    }

    public function testUpdateHeaders()
    {
        $object = new SwiftObject($this->container, 'bar/baz.jpg');
        $object->setContentType('text/plain');
        $object->setContentLength(1024);
        $object->setETag('qwerty');
        $object->setHeaders([
            'foo' => 'bar',
        ]);
        $object->getMetadata()->set('bar', 'baz');

        $this->assertEquals(
            [
                'foo'               => ['bar'],
                'X-Object-Meta-Bar' => 'baz',
            ],
            $object->getUpdateHeaders()
        );
    }

    public function testFactoryMethod()
    {
        $object = SwiftObject::create($this->container, 'foo');

        $this->assertInstanceOf(SwiftObject::class, $object);
    }

    public function testFactoryMethodWithHeaders()
    {
        $object = SwiftObject::create($this->container, 'foo', [
            'foo'               => 'bar',
            'X-Object-Meta-Bar' => 'baz',
        ]);

        $this->assertEquals(
            [
                'foo'               => ['bar'],
                'X-Object-Meta-Bar' => 'baz',
            ],
            $object->getHeaders()
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testContentLengthWithInvalidLength()
    {
        $object = SwiftObject::create($this->container, 'foo');
        $object->setContentLength('asd');
    }

    public function testPseudoDir()
    {
        $object = SwiftObject::create($this->container, 'foo');
        $this->assertFalse($object->isPseudoDir());

        $object = SwiftObject::create($this->container, 'foo/');
        $this->assertTrue($object->isPseudoDir());
    }

    public function testLocalFile()
    {
        $file = new File(__FILE__);

        $object = SwiftObject::create($this->container, 'bar');
        $object->setLocalFile($file);

        $this->assertSame(file_get_contents($file->getPathname()), $object->getBody());
        $this->assertSame($file->getMimeType(), $object->getContentType());
        $this->assertSame($file->getSize(), $object->getContentLength());
        $this->assertSame(md5_file($file->getPathname()), $object->getETag());
    }

    public function testEquals()
    {
        $object1 = SwiftObject::create($this->container, 'foo');
        $object1->setETag('qwerty');

        $object2 = SwiftObject::create($this->container, 'bar');
        $object2->setETag('qwerty');

        $object3 = SwiftObject::create($this->container, 'baz');
        $object3->setETag('foobar');

        $this->assertTrue($object1->equals($object2));
        $this->assertFalse($object1->equals($object3));
        $this->assertFalse($object2->equals($object3));
    }
}
