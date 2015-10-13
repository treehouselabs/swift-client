<?php

namespace TreeHouse\Swift\Tests\Swift;

use Symfony\Component\HttpFoundation\HeaderBag;
use TreeHouse\Swift\Container;
use TreeHouse\Swift\Metadata\Metadata;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $container = new Container('foo');

        $this->assertInstanceOf(Container::class, $container);
        $this->assertSame('foo', $container->getName());
        $this->assertInternalType('array', $container->getHeaders());
        $this->assertEmpty($container->getHeaders());
        $this->assertInstanceOf(HeaderBag::class, $container->getHeaderBag());
        $this->assertInstanceOf(Metadata::class, $container->getMetadata());
        $this->assertEmpty($container->getMetadata());
    }

    public function testPublicPrivate()
    {
        $container = new Container('foo');

        $this->assertTrue($container->isPrivate(), 'New containers are private by default');
        $this->assertFalse($container->isPublic());

        $container->setPublic();
        $this->assertTrue($container->isPublic());
        $this->assertFalse($container->isPrivate());

        $container->setPrivate();
        $this->assertTrue($container->isPrivate());
        $this->assertFalse($container->isPublic());
    }

    public function testHeaders()
    {
        $container = new Container('foo');
        $container->setHeaders(['foo' => 'bar']);
        $container->getMetadata()->set('bar', 'baz');

        $this->assertEquals('bar', $container->getHeader('foo'));
        $this->assertEquals(
            [
                'foo' => ['bar'],
                'X-Container-Meta-Bar' => 'baz',
            ],
            $container->getHeaders()
        );
    }

    public function testHeaderWithPrefixedKey()
    {
        $container = new Container('foo');
        $container->setHeaders(['foo' => 'bar', 'X-Container-Meta-Bar' => 'baz']);

        $this->assertEquals(
            [
                'foo' => ['bar'],
                'X-Container-Meta-Bar' => 'baz',
            ],
            $container->getHeaders()
        );
    }

    public function testObjectCount()
    {
        $container = new Container('foo');

        $this->assertCount(0, $container);
        $this->assertNull($container->getObjectCount());

        $container->setObjectCount(10);
        $this->assertCount(10, $container);
        $this->assertSame(10, $container->getObjectCount());
        $this->assertFalse($container->isEmpty());

        $container->setObjectCount(0);
        $this->assertTrue($container->isEmpty());
    }

    public function testBytesUsed()
    {
        $container = new Container('foo');

        $this->assertNull($container->getBytesUsed());

        $container->setBytesUsed(10);
        $this->assertSame(10, $container->getBytesUsed());
    }

    public function testFactoryMethod()
    {
        $container = Container::create('foo');

        $this->assertInstanceOf(Container::class, $container);
    }

    public function testFactoryMethodWithHeaders()
    {
        $container = Container::create('foo', [
            'X-Container-Meta-Read' => '.r:*',
            'X-Container-Object-Count' => '10',
            'X-Container-Bytes-Used' => '1024',
        ]);

        $this->assertTrue($container->isPublic());
        $this->assertCount(10, $container);
        $this->assertSame(10, $container->getObjectCount());
        $this->assertSame(1024, $container->getBytesUsed());
    }
}
