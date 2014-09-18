<?php

namespace Model\Mongo;

use Reach\Mongo\Query;

class TestSchemaQuery extends Query
{

    /**
     * @return $this
     */
    public function title1()
    {
        $this->add(['title' => 'Title1']);
        return $this;
    }
}
