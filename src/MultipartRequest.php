<?php
/**
 * @file
 * Contains \LushDigital\MicroserviceAggregatorTransport\MultipartRequest.
 */

namespace LushDigital\MicroserviceAggregatorTransport;

/**
 * Models a multi-part request to a service.
 *
 * @package LushDigital\MicroserviceAggregatorTransport
 */
final class MultipartRequest
{
    /**
     * The form field name
     * @var string
     */
    protected $name;

    /**
     * The data to use in the form element.
     *
     * @var string
     */
    protected $contents;

    /**
     * Optional array of custom headers to use with the form element.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Optional string to send as the filename in the part.
     *
     * @var string|null
     */
    protected $filename = null;

    /**
     * MultipartRequest constructor.
     *
     * @param string $name
     * @param string $contents
     * @param array $headers
     * @param null|string $filename
     */
    public function __construct($name, $contents, array $headers = [], $filename = null)
    {
        $this->name = $name;
        $this->contents = $contents;
        $this->headers = $headers;
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * @param string $contents
     */
    public function setContents($contents)
    {
        $this->contents = $contents;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    /**
     * @return null|string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param null|string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }
}