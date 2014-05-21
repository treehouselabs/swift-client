<?php

namespace TreeHouse\Swift;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\HeaderBag;
use TreeHouse\Swift\Metadata\ObjectMetadata;

class Object
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $path;

    /**
     * @var mixed
     */
    private $body;

    /**
     * @var HeaderBag
     */
    private $headers;

    /**
     * @var ObjectMetadata
     */
    private $metadata;

    /**
     * @var File
     */
    private $localFile;

    /**
     * @param Container       $container
     * @param string          $name
     */
    public function __construct(Container &$container, $name)
    {
        $this->container =& $container;
        $this->name      = $name;
        $this->headers   = new HeaderBag();
        $this->metadata  = new ObjectMetadata();
    }

    /**
     * Factory method
     *
     * @param  Container       $container
     * @param  string          $name
     * @param  array           $headers
     * @param  mixed           $body
     * @return Object
     */
    public static function create(Container $container, $name, array $headers = array(), $body = null)
    {
        $object = new static($container, $name);
        $object->setHeaders($headers);

        if (!is_null($body)) {
            $object->setBody($body);
        }

        return $object;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function getBasename($suffix = null)
    {
        return basename($this->name, $suffix);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPath()
    {
        return sprintf('%s/%s', $this->container->getName(), $this->getName());
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setHeaders(array $headers)
    {
        foreach ($headers as $name => $values) {
            // make sure we store an array
            $values = (array) $values;

            if ($this->metadata->isPrefixedKey($name)) {
                $this->metadata->set($name, $values);
            } else {
                $this->headers->set($name, $values);
            }
        }
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setContentType($contentType)
    {
        $this->headers->set('Content-Type', $contentType);
    }

    public function getContentType()
    {
        return $this->headers->get('Content-Type');
    }

    public function setContentLength($bytes)
    {
        if (!is_numeric($bytes)) {
            throw new \InvalidArgumentException('setContentLength expects an integer');
        }

        $this->headers->set('Content-Length', (int) $bytes);
    }

    public function getContentLength()
    {
        return $this->headers->get('Content-Length');
    }

    public function setETag($etag)
    {
        $this->headers->set('ETag', $etag);
    }

    public function getETag()
    {
        return $this->headers->get('ETag');
    }

    public function setLastModifiedDate(\DateTime $lastModified)
    {
        $this->headers->set('Last-Modified', $lastModified->format(DATE_RFC2822));
    }

    public function getLastModifiedDate()
    {
        return \DateTime::createFromFormat(DATE_RFC2822, $this->headers->get('Last-Modified'));
    }

    public function isPseudoDir()
    {
        return substr($this->name, -1) === '/';
    }

    public function setLocalFile(File $file)
    {
        $this->localFile = $file;
        $this->body = file_get_contents($file->getPathname());
        $this->setContentType($file->getMimeType());
        $this->setContentLength($file->getSize());
        $this->setETag(md5_file($file->getPathname()));
    }

    public function getLocalFile()
    {
        return $this->localFile;
    }

    public function getExtension()
    {
        return pathinfo($this->getName(), PATHINFO_EXTENSION);
    }

    public function equals(Object $object)
    {
        return $this->getETag() === $object->getETag();
    }

    public function getHeaders()
    {
        return array_merge($this->headers->all(), $this->metadata->getHeaders());
    }

    public function getUpdateHeaders()
    {
        // filter normal headers to contain only updateable keys
        $keys = array('etag', 'content-length', 'content-type');
        $headers = array_diff_key($this->headers->all(), array_flip($keys));

        return array_merge(
            $this->metadata->getHeaders(),
            $headers
        );
    }
}
