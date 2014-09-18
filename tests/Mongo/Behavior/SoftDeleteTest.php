<?php

namespace Mongo\Behavior;

use Model\Mongo\Behavior\TestSoftDelete;

class SoftDeleteTest extends \PhactoryTestCase
{

    public function testDeleting()
    {
        $model = new TestSoftDelete(['title' => 'Title1']);
        $model->save();
        //$model->delete();
        $model->remove();

        $model = TestSoftDelete::findOne(['title' => 'Title1']);

        $model->restore();
    }

}
