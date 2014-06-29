<?php

namespace Application\Test\Service;

use PHPUnit_Framework_TestCase;
use Application\Utility\Slug;

class SlugTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test slugify function
     */
    public function testSlugify()
    {
        $this->assertEquals('test', Slug::slugify('test'));
        $this->assertEquals('', Slug::slugify(''));
        $this->assertEquals('a-lublu-php', Slug::slugify('Я люблю PHP'));
        $this->assertEquals('sihaphpga-da-haoki', Slug::slugify('私はPHPが大好き'));
        $this->assertEquals('1-test', Slug::slugify('test asdsadsadsadsadas', 6, '-', 1));
        $this->assertEquals('test-test', Slug::slugify('test test'));
        $this->assertEquals('123-testtest-asd', Slug::slugify('123! test-test_$ asd'));
        $this->assertEquals('test', Slug::slugify('&test!=....'));
    }
}
