<?php

namespace Reach\Mongo\Behavior;

interface SerializableInterface {

    public function serialize();

    public function unserialize($serialized);
}