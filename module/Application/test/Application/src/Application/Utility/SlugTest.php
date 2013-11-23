<?php

namespace Application\Test\Service;

use Application\Test\ApplicationBootstrap;
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
        $this->assertEquals('a_lublu_php', Slug::slugify('Я люблю PHP'));
        $this->assertEquals('sihaphpga_da_haoki', Slug::slugify('私はPHPが大好き'));
        $this->assertEquals('1_test', Slug::slugify('test asdsadsadsadsadas', 1, 6));
    }
}
