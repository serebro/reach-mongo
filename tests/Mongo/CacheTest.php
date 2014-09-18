<?php

namespace Mongo;

use Reach\Mongo\Cache;

class CacheTest extends \PhactoryTestCase
{

    public function testMongoCache()
    {
        $cache = new Cache();
        $cache->initCollection();

        $this->assertFalse($cache->exists('key'));
        $this->assertTrue($cache->set('key', ['info' => 123]));
        $this->assertTrue($cache->set('key', ['info' => 321], 3));
        $this->assertEquals(['info' => 321], $cache->get('key'));
//        sleep(2);
//        $this->assertFalse($cache->get('key'));

        $obj = new \stdClass();
        $obj->property = 'test';
        $this->assertTrue($cache->set('key', $obj));
        $this->assertEquals($obj, $cache->get('key'));

        $this->assertTrue($cache->exists('key'));
        $this->assertTrue($cache->delete('key'));
        $this->assertNull($cache->get('key'));

        $this->assertTrue($cache->set($obj, 123));
        $this->assertEquals(123, $cache->get($obj));

        $this->assertTrue($cache->delete($obj));
        $this->assertNull($cache->get($obj));
    }
}
