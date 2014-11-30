<?php
namespace Layout\Test\View\Helper;

use PHPUnit_Framework_TestCase;
use Layout\View\Helper\LayoutHeadLink as HeadLink;
use ReflectionMethod;

class LayoutHeadLinkTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test unification of css imports
     */
    public function testUnificationCssImports()
    {
        $unification = new ReflectionMethod('Layout\View\Helper\LayoutHeadLink', 'unificationCssImports');
        $unification->setAccessible(true);

        $equals = [];
        $equals[] = [
            'import' =>  '@import "../test.css";',
            'result' =>  '@import url("../test.css");'
        ];

        $equals[] = [
            'import' =>  '@import \'../test.css\';',
            'result' =>  '@import url("../test.css");'
        ];

        $equals[] = [
            'import' =>  '@import url "../test.css";',
            'result' =>  '@import url("../test.css");'
        ];

        $equals[] = [
            'import' =>  '@import url("../test.css");',
            'result' =>  '@import url("../test.css");'
        ];

        $equals[] = [ // wrong syntax for import, url must be wrapped with quotes
            'import' =>  '@import url (../test.css);',
            'result' =>  '@import url (../test.css);'
        ];

        $equals[] = [
            'import' =>  '@importurl"http://test.com/assets/test.css";',
            'result' =>  '@import url("http://test.com/assets/test.css");'
        ];

        $equals[] = [ // wrong syntax for import, url string must be wrapped with semicolon at the end
            'import' =>  '@import url (../test.css)',
            'result' =>  '@import url (../test.css)'
        ];

        // test equals result
        foreach ($equals as $importInfo) {
            $this->assertEquals($unification->
                    invoke(new HeadLink(), $importInfo['import']), $importInfo['result']);
        }

        $notEquals = [];
    }

    /**
     * Test replace Css Rel Urls To Abs
     */
    public function testReplaceCssRelUrlsToAbs()
    {
        $replace = new ReflectionMethod('Layout\View\Helper\LayoutHeadLink', 'replaceCssRelUrlsToAbs');
        $replace->setAccessible(true);

        $baseUrl = "base/test_directory";
        $equals = [];
        $equals[] = [
            'url' =>  'url ("../test.css")',
            'result' => 'url("' . $baseUrl . '/../test.css")'
        ];

        $equals[] = [
            'url' =>  'url "../test.css"',
            'result' => 'url("' . $baseUrl . '/../test.css")'
        ];

        $equals[] = [
            'url' =>  'url          \'../test.css\'',
            'result' => 'url("' . $baseUrl . '/../test.css")'
        ];

        $equals[] = [
            'url' =>  'url ../test.css',  // wrong syntax, the are no quotes
            'result' => 'url ../test.css'
        ];

        // test equals result
        foreach ($equals as $urlInfo) {
            $this->assertEquals($replace->
                    invoke(new HeadLink(), $urlInfo['url'], $baseUrl), $urlInfo['result']);
        }

        $notEquals = [];
    }
}