<?php

namespace Model\Mongo;

use Reach\Mongo\Document\Schema;
use Reach\Mongo\Behavior\Relation;

class TestRelDocument extends Schema
{

    /** @var string */
    public $title = '';

    /** @var \Model\Mongo\TestSchema */
    public $testSchemaRefPk;

    /** @var \Model\Mongo\TestSchema */
    protected $testSchemaRefOne;

    /** @var \Model\Mongo\TestSchema[] */
    protected $testSchemaRefMany = [];

    /** @var \stdClass */
    protected $stdClassEmbedOne;

    /** @var \Model\Mongo\TestSchema */
    protected $testSchemaEmbedOne;

    /** @var \Model\Mongo\TestSchema[] */
    protected $testSchemaEmbedMany = [];

    /** @var \MongoDate */
    public $created;

    /** @var bool */
    public $init_test = false;

    public $not_stored = true;

    public static function getCollectionName()
    {
        return 'test_model_rel_document';
    }

    public function behaviors()
    {
        return [
            'testSchemaRefPk'     => ['class' => Relation::REF_PK, 'ref' => '\Model\Mongo\TestSchema', 'key' => 'test_schema_id'],
            'testSchemaRefOne'    => ['class' => Relation::REF_ONE, 'ref' => '\Model\Mongo\TestSchema', 'key' => 'ref_one'],
            'testSchemaRefMany'   => ['class' => Relation::REF_MANY, 'ref' => '\Model\Mongo\TestSchema', 'key' => 'ref_many'],
            'testSchemaEmbedOne'  => ['class' => Relation::EMBED_ONE, 'ref' => '\Model\Mongo\TestSchema', 'key' => 'embed_one'],
            'stdClassEmbedOne'    => ['class' => Relation::EMBED_ONE, 'ref' => '\stdClass', 'key' => 'std_embed_one'],
            'testSchemaEmbedMany' => ['class' => Relation::EMBED_MANY, 'ref' => '\Model\Mongo\TestSchema', 'key' => 'embed_many'],
            //'testSchemaLang'      => ['class' => '\Model\Mongo\BehaviorLang', 'ref' => '\Model\Mongo\TestSchema'],
        ];
    }
}
