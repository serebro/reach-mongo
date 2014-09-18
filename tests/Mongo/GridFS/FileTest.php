<?php

namespace Mongo\GridFS;

use Model\Mongo\GridFS\Image;

class FileTest extends \PhactoryTestCase
{

    public function testCreateAndSave()
    {
        $image = new Image(
            [
                'author' => 'John',
                'tags'   => ['animals', 'jpg'],
            ]
        );

        $filename = __DIR__ . '/../../Model/Mongo/GridFS/cat.jpg';
        $image->setContent(Image::SOURCE_FILENAME, $filename);
        $this->assertTrue($image->save());
    }

    public function testFind()
    {
        //$images = Image::find(['tags' => 'jpg']);
        /** @var Image $image */
//        $image = $images->first();
//        $data = $image->getContent();
    }
}
