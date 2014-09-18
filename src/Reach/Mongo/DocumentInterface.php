<?php

namespace Reach\Mongo;

interface DocumentInterface
{

    public static function instantiate($document = null);

    public function getIsNew();

    public function setIsNew($value);

    public function hasAttribute($attribute);

    public function commit();

    public function getRawDocument(array $attributes = null);

    public static function getPrimaryKey();

    public function save(array $options = []);
}