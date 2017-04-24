<?php
namespace PharIo\Phive;

use PharIo\FileSystem\File;

interface Hash {

    /**
     * @return string
     */
    public function asString();

    /**
     * @param string $content
     *
     * @return Hash
     */
    public static function forContent($content);

    /**
     * @param Hash $otherHash
     *
     * @return bool
     */
    public function equals(Hash $otherHash);
}
