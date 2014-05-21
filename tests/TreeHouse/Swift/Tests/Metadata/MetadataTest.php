<?php

namespace TreeHouse\Swift\Tests\Swift\Metadata;

use TreeHouse\Swift\Metadata\Metadata;

class MetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $metadata = new ConcreteMetadata(['foo' => 'bar']);
        $this->assertTrue($metadata->has('foo'));
    }

    public function testToStringNull()
    {
        $metadata = new ConcreteMetadata();
        $this->assertEquals('', $metadata->__toString());
    }

    public function testToStringNotNull()
    {
        $metadata = new ConcreteMetadata(['foo' => 'bar']);
        $this->assertEquals("Foo: bar\r\n", $metadata->__toString());
    }

    public function testKeys()
    {
        $metadata = new ConcreteMetadata(['foo' => 'bar', 'bar' => 'baz']);
        $keys = $metadata->keys();
        $this->assertEquals("foo", $keys[0]);
        $this->assertEquals("bar", $keys[1]);
    }

    public function testAll()
    {
        $metadata = new ConcreteMetadata(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $metadata->all(), '->all() gets all the input');

        $metadata = new ConcreteMetadata(['FOO' => 'BAR']);
        $this->assertEquals(['foo' => 'BAR'], $metadata->all(), '->all() gets all the input key are lower case');
    }

    public function testReplace()
    {
        $metadata = new ConcreteMetadata(['foo' => 'bar']);

        $metadata->replace(['NOPE' => 'BAR']);
        $this->assertEquals(['nope' => 'BAR'], $metadata->all(), '->replace() replaces the input with the argument');
        $this->assertFalse($metadata->has('foo'), '->replace() overrides previously set the input');
    }

    public function testGet()
    {
        $metadata = new ConcreteMetadata(['foo' => 'bar', 'fuzz' => 'bizz']);
        $this->assertEquals('bar', $metadata->get('foo'), '->get return current value');
        $this->assertEquals('bar', $metadata->get('FoO'), '->get key in case insensitive');

        // defaults
        $this->assertNull($metadata->get('none'), '->get unknown values returns null');
        $this->assertEquals('default', $metadata->get('none', 'default'), '->get unknown values returns default');
    }

    public function testSetArray()
    {
        $metadata = new ConcreteMetadata();
        $metadata->set('foo', ['value']);
        $this->assertSame('value', $metadata->get('foo'));

        $metadata->set('foo', ['bad-assoc-index' => 'value']);
        $this->assertSame('value', $metadata->get('foo'));
    }

    public function testHas()
    {
        $metadata = new ConcreteMetadata();
        $metadata->set('foo', 'value');
        $this->assertTrue($metadata->has('foo'));
        $this->assertTrue($metadata->has('FoO'), '->has key is case insensitive');
    }

    public function testRemove()
    {
        $metadata = new ConcreteMetadata(['foo' => 'bar', 'bar' => 'baz']);
        $this->assertTrue($metadata->has('bar'));
        $metadata->remove('bar');
        $this->assertFalse($metadata->has('bar'));
    }

    public function testGetHeaders()
    {
        $metadata = ['foo' => 'bar', 'bar' => 'baz'];
        $headers  = ['X-Meta-Foo' => 'bar', 'X-Meta-Bar' => 'baz'];

        $bag = new ConcreteMetadata($metadata);

        $this->assertEquals($headers, $bag->getHeaders());
    }

    public function testGetIterator()
    {
        $metadata   = ['foo' => 'bar', 'hello' => 'world', 'third' => 'charm'];
        $metadataBag = new ConcreteMetadata($metadata);

        $i = 0;
        foreach ($metadataBag as $key => $val) {
            $i++;
            $this->assertEquals($metadata[$key], $val);
        }

        $this->assertEquals(count($metadata), $i);
    }

    public function testCount()
    {
        $metadata   = ['foo' => 'bar', 'HELLO' => 'WORLD'];
        $metadataBag = new ConcreteMetadata($metadata);

        $this->assertEquals(count($metadata), count($metadataBag));
    }
}

class ConcreteMetadata extends Metadata
{
    public function getPrefix()
    {
        return 'X-Meta-';
    }
}
