<?php

namespace Application\Test\View\Helper;

use Application\Test\ApplicationBootstrap;
use PHPUnit_Framework_TestCase;
use Application\View\Helper\HeadLink as HeadLink;
use ReflectionMethod;

class HeadLinkTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test unification of css imports
     */
    public function testUnificationCssImports()
    {
        $unification = new ReflectionMethod('Application\View\Helper\HeadLink', 'unificationCssImports');
        $unification->setAccessible(true);

        $equals = array();
        $equals[] = array(
            'import' =>  '@import "../test.css";',
            'result' =>  '@import url("../test.css");'
        );

        $equals[] = array(
            'import' =>  '@import \'../test.css\';',
            'result' =>  '@import url("../test.css");'
        );

        $equals[] = array(
            'import' =>  '@import url "../test.css";',
            'result' =>  '@import url("../test.css");'
        );

        $equals[] = array(
            'import' =>  '@import url("../test.css");',
            'result' =>  '@import url("../test.css");'
        );

        $equals[] = array( // wrong syntax for import, url must be wrapped with quotes
            'import' =>  '@import url (../test.css);',
            'result' =>  '@import url (../test.css);'
        );

        $equals[] = array(
            'import' =>  '@importurl"http://test.com/assets/test.css";',
            'result' =>  '@import url("http://test.com/assets/test.css");'
        );

        $equals[] = array( // wrong syntax for import, url string must be wrapped with semicolon in the end
            'import' =>  '@import url (../test.css)',
            'result' =>  '@import url (../test.css)'
        );

        // test equals result
        foreach ($equals as $importInfo) {
            $this->assertEquals($unification->
                    invoke(new HeadLink(), $importInfo['import']), $importInfo['result']);
        }

        $notEquals = array();
    }

    /**
     * Test replace Css Rel Urls To Abs
     */
    public function testReplaceCssRelUrlsToAbs()
    {
        $replace = new ReflectionMethod('Application\View\Helper\HeadLink', 'replaceCssRelUrlsToAbs');
        $replace->setAccessible(true);

        $baseUrl = "base/test_directory";
        $equals = array();
        $equals[] = array(
            'url' =>  'url ("../test.css")',
            'result' => 'url("' . $baseUrl . '/../test.css")'
        );

        $equals[] = array(
            'url' =>  'url "../test.css"',
            'result' => 'url("' . $baseUrl . '/../test.css")'
        );

        $equals[] = array(
            'url' =>  'url          \'../test.css\'',
            'result' => 'url("' . $baseUrl . '/../test.css")'
        );

        $equals[] = array(
            'url' =>  'url ../test.css',  // wrong syntax, the are no quotes
            'result' => 'url ../test.css'
        );

        // test equals result
        foreach ($equals as $urlInfo) {
            $this->assertEquals($replace->
                    invoke(new HeadLink(), $urlInfo['url'], $baseUrl), $urlInfo['result']);
        }

        $notEquals = array();
    }
}
