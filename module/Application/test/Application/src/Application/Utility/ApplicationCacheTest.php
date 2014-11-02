<?php
namespace Application\Test\Utility;

use PHPUnit_Framework_TestCase;
use Application\Utility\ApplicationCache;

class ApplicationCacheTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test cache name generation
     */
    public function testCacheNameGeneration()
    {
        $this->assertEquals('599211e2143c831fa11c418e6c862baf', ApplicationCache::getCacheName('test', [
            'test' => 'test',
            'value' => [
                'id' => 45
            ]
        ]));

        $this->assertEquals('098f6bcd4621d373cade4e832627b4f6', ApplicationCache::getCacheName('test'));
        $this->assertEquals('1d3ad489ae10c71f75b4bcd8d56e884e', ApplicationCache::getCacheName('test', [
            'name' => '1',
            'value' => 0,
            'role' => 'admin'
        ]));
    }
}
