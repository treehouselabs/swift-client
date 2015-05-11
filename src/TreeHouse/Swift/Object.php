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
     * @param Container $container
     * @param string    $name
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
     *
     * @return static
     */
    public static function create(Container $container, $name, array $headers = [], $body = null)
    {
        $object = new static($container, $name);
        $object->setHeaders($headers);
        $object->setBody($body);

        return $object;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param null $suffix
     *
     * @return string
     */
    public function getBasename($suffix = null)
    {
        return basename($this->name, $suffix);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return sprintf('%s/%s', $this->container->getName(), $this->getName());
    }

    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param array $headers
     */
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

    /**
     * @return ObjectMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        $this->headers->set('Content-Type', $contentType);
    }

    /**
     * @return string|null
     */
    public function getContentType()
    {
        return $this->headers->get('Content-Type');
    }

    /**
     * @param integer $bytes
     */
    public function setContentLength($bytes)
    {
        if (!is_numeric($bytes)) {
            throw new \InvalidArgumentException('setContentLength expects an integer');
        }

        $this->headers->set('Content-Length', (int) $bytes);
    }

    /**
     * @return integer|null
     */
    public function getContentLength()
    {
        $length = $this->headers->get('Content-Length');

        if (is_numeric($length)) {
            $length = (int) $length;
        }

        return $length;
    }

    /**
     * @param string $etag
     */
    public function setETag($etag)
    {
        $this->headers->set('ETag', $etag);
    }

    /**
     * @return string
     */
    public function getETag()
    {
        return $this->headers->get('ETag');
    }

    /**
     * @param \DateTime $lastModified
     */
    public function setLastModifiedDate(\DateTime $lastModified)
    {
        $this->headers->set('Last-Modified', $lastModified->format(DATE_RFC2822));
    }

    /**
     * @return \DateTime
     */
    public function getLastModifiedDate()
    {
        return \DateTime::createFromFormat(DATE_RFC2822, $this->headers->get('Last-Modified'));
    }

    /**
     * @return boolean
     */
    public function isPseudoDir()
    {
        return substr($this->name, -1) === '/';
    }

    /**
     * @param File $file
     */
    public function setLocalFile(File $file)
    {
        $this->localFile = $file;
        $this->body = file_get_contents($file->getPathname());
        $this->setContentType($file->getMimeType());
        $this->setContentLength($file->getSize());
        $this->setETag(md5_file($file->getPathname()));
    }

    /**
     * @return File
     */
    public function getLocalFile()
    {
        return $this->localFile;
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        return pathinfo($this->getName(), PATHINFO_EXTENSION);
    }

    /**
     * @param self $object
     *
     * @return boolean
     */
    public function equals(Object $object)
    {
        return $this->getETag() === $object->getETag();
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return array_merge($this->headers->all(), $this->metadata->getHeaders());
    }

    /**
     * @return array
     */
    public function getUpdateHeaders()
    {
        // filter normal headers to contain only updateable keys
        $keys = ['etag', 'content-length', 'content-type'];
        $headers = array_diff_key($this->headers->all(), array_flip($keys));

        return array_merge(
            $this->metadata->getHeaders(),
            $headers
        );
    }
}
