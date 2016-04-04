<?php
namespace PharIo\Phive;

use InvalidArgumentException;

class JsonData {

    /**
     * @var string
     */
    private $raw;

    /**
     * @var \StdClass
     */
    private $parsed;

    /**
     * JsonData constructor.
     *
     * @param string $raw
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($raw) {
        $this->raw = $raw;
        $parsed = json_decode($raw, false, 512, JSON_BIGINT_AS_STRING);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(json_last_error_msg(), json_last_error());
        }
        if (!$parsed instanceof \StdClass) {
            throw new InvalidArgumentException('Given JSON string does not parse into object');
        }
        $this->parsed = $parsed;
    }

    /**
     * @return string
     */
    public function getRaw() {
        return $this->raw;
    }

    /**
     * @return array
     */
    public function getParsed() {
        return $this->parsed;
    }

}
