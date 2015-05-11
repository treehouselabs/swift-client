<?php

namespace TreeHouse\Swift\Metadata;

abstract class Metadata implements \IteratorAggregate, \Countable
{
    /**
     * @var array
     */
    protected $metadata;

    /**
     * @return string
     */
    abstract public function getPrefix();

    /**
     * Constructor.
     *
     * @param array $metadata
     */
    public function __construct(array $metadata = [])
    {
        $this->replace($metadata);
    }

    /**
     * Returns the metadata as a string.
     *
     * @return string
     */
    public function __toString()
    {
        if (!$this->metadata) {
            return '';
        }

        $max     = max(array_map('strlen', array_keys($this->metadata))) + 1;
        $content = '';
        ksort($this->metadata);
        foreach ($this->metadata as $name => $value) {
            $name = implode('-', array_map('ucfirst', explode('-', $name)));
            $content .= sprintf("%-{$max}s %s\r\n", $name . ':', $value);
        }

        return $content;
    }

    /**
     * Returns the metadata.
     *
     * @return array
     */
    public function all()
    {
        return $this->metadata;
    }

    /**
     * Returns the metadata keys.
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->metadata);
    }

    /**
     * Replaces the current metadata by a new set.
     *
     * @param array $metadata
     */
    public function replace(array $metadata = [])
    {
        $this->metadata = [];
        $this->add($metadata);
    }

    /**
     * Adds new metadata to the current set.
     *
     * @param array $metadata
     */
    public function add(array $metadata)
    {
        foreach ($metadata as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Returns a metadata value by name.
     *
     * @param string $key     The header name
     * @param mixed  $default The default value
     *
     * @return string
     */
    public function get($key, $default = null)
    {
        $key = $this->normalizeKey($key);

        if (!array_key_exists($key, $this->metadata)) {
            return $default;
        }

        return $this->metadata[$key];
    }

    /**
     * Sets a metadata value. If the given value is an array (as it sometimes is
     * when setting from headers), only the first entry is used.
     *
     * @param string $key
     * @param string $value
     */
    public function set($key, $value)
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        $key                  = $this->normalizeKey($key);
        $this->metadata[$key] = $value;
    }

    /**
     * Returns true if the key is defined.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($this->normalizeKey($key), $this->metadata);
    }

    /**
     * Removes a metadata value.
     *
     * @param string $key
     */
    public function remove($key)
    {
        $key = $this->normalizeKey($key);

        unset($this->metadata[$key]);
    }

    /**
     * Returns the metadata as an array, normalized as prefixed headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        $headers = [];

        foreach ($this->all() as $key => $value) {
            $name           = $this->normalizeHeader($key);
            $headers[$name] = $value;
        }

        return $headers;
    }

    /**
     * Implementation for IteratorAggregate.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->metadata);
    }

    /**
     * Implementation for Countable.
     *
     * @return int
     */
    public function count()
    {
        return sizeof($this->metadata);
    }

    /**
     * Returns whether the given key is prefixed.
     *
     * @param string $key
     *
     * @return bool
     */
    public function isPrefixedKey($key)
    {
        return preg_match('/^' . preg_quote($this->getPrefix()) . '/i', $key);
    }

    /**
     * Returns the prefixed version of a key.
     *
     * Example: Foo returns X-Meta-Foo
     *
     * @param string $key
     *
     * @return string
     */
    protected function getPrefixedKey($key)
    {
        return $this->getPrefix() . $this->getUnprefixedKey($key);
    }

    /**
     * Returns the unprefixed version of a key.
     *
     * Example: X-Meta-Foo returns Foo
     *
     * @param string $key
     *
     * @return string
     */
    protected function getUnprefixedKey($key)
    {
        return preg_replace('/^' . preg_quote($this->getPrefix()) . '/i', '', $key);
    }

    /**
     * Normalizes a key by lowercasing it and correcting underscores to dashes.
     * It also removes the metadata prefix if it's set.
     *
     * Example: X_META_foo-bar => x-meta-foo-bar
     *
     * @param string $key
     *
     * @return string
     */
    protected function normalizeKey($key)
    {
        return strtr(strtolower($this->getUnprefixedKey($key)), '_', '-');
    }

    /**
     * Normalizes a header by concatenating each word with a dash and
     * uppercasing the first letter of each word.
     *
     * Example: x-meta-foo-bar => X-Meta-Foo-Bar
     *
     * @param string $header
     *
     * @return string
     */
    protected function normalizeHeader($header)
    {
        return str_replace(' ', '-', ucwords(strtr(strtolower($this->getPrefixedKey($header)), '-', ' ')));
    }
}
