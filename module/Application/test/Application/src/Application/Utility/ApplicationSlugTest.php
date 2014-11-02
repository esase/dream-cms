<?php
namespace Application\Test\Utility;

use PHPUnit_Framework_TestCase;
use Application\Utility\ApplicationSlug as ApplicationSlugUtility;

class ApplicationSlugUtilityTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test slugify function
     */
    public function testSlugify()
    {
        $this->assertEquals('test', ApplicationSlugUtility::slugify('test'));
        $this->assertEquals('', ApplicationSlugUtility::slugify(''));
        $this->assertEquals('a-lublu-php', ApplicationSlugUtility::slugify('Я люблю PHP'));
        $this->assertEquals('sihaphpga-da-haoki', ApplicationSlugUtility::slugify('私はPHPが大好き'));
        $this->assertEquals('1-test', ApplicationSlugUtility::slugify('test asdsadsadsadsadas', 6, '-', 1));
        $this->assertEquals('test-test', ApplicationSlugUtility::slugify('test test'));
        $this->assertEquals('123-testtest-asd', ApplicationSlugUtility::slugify('123! test-test_$ asd'));
        $this->assertEquals('test', ApplicationSlugUtility::slugify('&test!=....'));
    }
}
