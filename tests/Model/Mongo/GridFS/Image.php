<?php

namespace Model\Mongo\GridFS;

use Reach\Mongo\Document\File;

class Image extends File
{

    /** @var string */
    public $author = '';

    /** @var int */
    public $downloads = 0;

    /** @var array */
    public $tags = [];

    public static function getCollectionName()
    {
        return 'image';
    }
}