<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace Page\Test\Model;

use Page\Model\PageNestedSet;
use Localization\Service\Localization as LocalizationService;
use Page\Test\PageBootstrap;
use PHPUnit_Framework_TestCase;

class PageNestedSetTest extends PHPUnit_Framework_TestCase
{
    /**
     * Service locator
     *
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $serviceLocator;

    /**
     * Model
     *
     * @var \Page\Model\PageNestedSet
     */
    protected $model;

    /**
     * Pages Ids
     *
     * @var array
     */
    protected $pagesIds = [];

    /**
     * Current language
     *
     * @var string
     */
    protected $language;

    /**
     * Setup
     */
    protected function setUp()
    {
        // get service manager
        $this->serviceLocator = PageBootstrap::getServiceLocator();

        // get base model instance
       $this->model = $this->serviceLocator->get('Page\Model\PageNestedSet');
       $this->language = current(LocalizationService::getLocalizations())['language'];
    }

    /**
     * Test insert and move pages into the pages tree
     */
    public function testInsertPages()
    {
        // create a home page
        $pageOptions = [
            'slug' => 'home',
            'module' => 5,
            'language' => $this->language,
            'layout' => 1
        ];

        $homePageId = $this->model->addPage(PageNestedSet::ROOT_LEVEl,
                PageNestedSet::ROOT_LEFT_KEY, PageNestedSet::ROOT_RIGHT_KEY, $pageOptions, $this->language);

        $this->assertTrue(is_numeric($homePageId));
        $this->pagesIds[] = $homePageId;

        // get the home page info
        $homePageInfo = $this->model->getNodeInfo($homePageId);

        // check the page's left and right keys
        $this->assertEquals($homePageInfo['left_key'], 1);
        $this->assertEquals($homePageInfo['right_key'], 2);
        $this->assertEquals($homePageInfo['level'], 1);

        // add a first sub page into the home (at the end)
        $pageOptions = [
            'slug' => 'sub_page_1',
            'module' => 5,
            'language' => $this->language,
            'layout' => 1
        ];

        $subPageId1 = $this->model->addPage($homePageInfo['level'],
                $homePageInfo['left_key'], $homePageInfo['right_key'], $pageOptions, $this->language);

        $this->assertTrue(is_numeric($subPageId1));
        $this->pagesIds[] = $subPageId1;

        // get the first sub page info
        $subPageInfo1 = $this->model->getNodeInfo($subPageId1);
        $this->assertEquals($subPageInfo1['parent_id'], $homePageInfo['id']);

        // check the first sub page's left and right keys
        $this->assertEquals($subPageInfo1['left_key'], 2);
        $this->assertEquals($subPageInfo1['right_key'], 3);
        $this->assertEquals($subPageInfo1['level'], 2);

        // get the updated home page info
        $homePageInfo = $this->model->getNodeInfo($homePageInfo['id']);

        // check the home page's left and right keys
        $this->assertEquals($homePageInfo['left_key'], 1);
        $this->assertEquals($homePageInfo['right_key'], 4);
        $this->assertEquals($homePageInfo['level'], 1);

        // add a second sub page into the home (at the start)
        $pageOptions = [
            'slug' => 'sub_page_2',
            'module' => 5,
            'language' => $this->language,
            'layout' => 1
        ];

        $subPageId2 = $this->model->addPage($homePageInfo['level'],
                $homePageInfo['left_key'], $homePageInfo['right_key'], $pageOptions, $this->language, $subPageId1, 'before');

        $this->assertTrue(is_numeric($subPageId2));
        $this->pagesIds[] = $subPageId2;

        // get the second sub page info
        $subPageInfo2 = $this->model->getNodeInfo($subPageId2);
        $this->assertEquals($subPageInfo2['parent_id'], $homePageInfo['id']);

        // check the second sub page's left and right keys
        $this->assertEquals($subPageInfo1['left_key'], 2);
        $this->assertEquals($subPageInfo1['right_key'], 3);
        $this->assertEquals($subPageInfo1['level'], 2);

        // get the updated home page info
        $homePageInfo = $this->model->getNodeInfo($homePageInfo['id']);

        // check the home page's left and right keys
        $this->assertEquals($homePageInfo['left_key'], 1);
        $this->assertEquals($homePageInfo['right_key'], 6);
        $this->assertEquals($homePageInfo['level'], 1);

        // get the first updated sub page info
        $subPageInfo1 = $this->model->getNodeInfo($subPageId1);

        // check the first updated sub page's left and right keys (it should be below than the second sub page)
        $this->assertEquals($subPageInfo1['left_key'], 4);
        $this->assertEquals($subPageInfo1['right_key'], 5);
        $this->assertEquals($subPageInfo1['level'], 2);

        // add a third sub page into the home (after the second sub page)
        $pageOptions = [
            'slug' => 'sub_page_3',
            'module' => 5,
            'language' => $this->language,
            'layout' => 1
        ];

        $subPageId3 = $this->model->addPage($homePageInfo['level'],
                $homePageInfo['left_key'], $homePageInfo['right_key'], $pageOptions, $this->language, $subPageId2, 'after');

        $this->assertTrue(is_numeric($subPageId3));
        $this->pagesIds[] = $subPageId3;

        // get the third sub page info
        $subPageInfo3 = $this->model->getNodeInfo($subPageId3);
        $this->assertEquals($subPageInfo3['parent_id'], $homePageInfo['id']);

        // check the third sub page's left and right keys
        $this->assertEquals($subPageInfo3['left_key'], 4);
        $this->assertEquals($subPageInfo3['right_key'], 5);
        $this->assertEquals($subPageInfo3['level'], 2);

        // get the first updated sub page info
        $subPageInfo1 = $this->model->getNodeInfo($subPageId1);

        // check the first updated sub page's left and right keys (it should be below than the third sub page)
        $this->assertEquals($subPageInfo1['left_key'], 6);
        $this->assertEquals($subPageInfo1['right_key'], 7);
        $this->assertEquals($subPageInfo1['level'], 2);

        // get the updated home page info
        $homePageInfo = $this->model->getNodeInfo($homePageInfo['id']);

        // check the home page's left and right keys
        $this->assertEquals($homePageInfo['left_key'], 1);
        $this->assertEquals($homePageInfo['right_key'], 8);
        $this->assertEquals($homePageInfo['level'], 1);

        // -- test moving created pages -- //

        // move the first sub page into the second sub page (at the start)
        $moveResult = $this->model->movePage($subPageInfo1, $subPageInfo2, $this->language);
        $this->assertTrue($moveResult);

        // get the second updated sub page info
        $subPageInfo2 = $this->model->getNodeInfo($subPageId2);

        // check the second updated sub page's left and right keys (it should contain the first sub page as a child)
        $this->assertEquals($subPageInfo2['left_key'], 2);
        $this->assertEquals($subPageInfo2['right_key'], 5);
        $this->assertEquals($subPageInfo2['level'], 2);

        // get the first updated sub page info
        $subPageInfo1 = $this->model->getNodeInfo($subPageId1);

        // check the first updated sub page's left and right keys
        $this->assertEquals($subPageInfo1['left_key'], 3);
        $this->assertEquals($subPageInfo1['right_key'], 4);
        $this->assertEquals($subPageInfo1['level'], 3);

        // get the third updated sub page info
        $subPageInfo3 = $this->model->getNodeInfo($subPageId3);

        // check the third updated sub page's left and right keys
        $this->assertEquals($subPageInfo3['left_key'], 6);
        $this->assertEquals($subPageInfo3['right_key'], 7);
        $this->assertEquals($subPageInfo3['level'], 2);

        // move the third sub page into the second sub page (at the start before the first sub page)
        $moveResult = $this->model->movePage($subPageInfo3, $subPageInfo2, $this->language, $subPageId1, 'before');
        $this->assertTrue($moveResult);

        // get the second updated sub page info
        $subPageInfo2 = $this->model->getNodeInfo($subPageId2);

        // check the second updated sub page's left and right keys (it should contain 2 sub pages)
        $this->assertEquals($subPageInfo2['left_key'], 2);
        $this->assertEquals($subPageInfo2['right_key'], 7);
        $this->assertEquals($subPageInfo2['level'], 2);

        // get the third updated sub page info
        $subPageInfo3 = $this->model->getNodeInfo($subPageId3);

        // check the third updated sub page's left and right keys
        $this->assertEquals($subPageInfo3['left_key'], 3);
        $this->assertEquals($subPageInfo3['right_key'], 4);
        $this->assertEquals($subPageInfo3['level'], 3);

        // get the first updated sub page info
        $subPageInfo1 = $this->model->getNodeInfo($subPageId1);

        // check the first updated sub page's left and right keys
        $this->assertEquals($subPageInfo1['left_key'], 5);
        $this->assertEquals($subPageInfo1['right_key'], 6);
        $this->assertEquals($subPageInfo1['level'], 3);

        // try to move the second sub page into the third sub page (it's impossible!!
        $moveResult = $this->model->movePage($subPageInfo2, $subPageInfo3, $this->language);
        $this->assertEquals('Node is not movable', $moveResult);
    }

    /**
     * Tear down
     */
    protected function tearDown()
    {
        // delete test pages
        if ($this->pagesIds) {
            foreach ($this->pagesIds as $pageId) {
                $this->model->tableGateway->delete([
                    'id' => $pageId
                ]);
            }

            $this->pagesIds = [];
        }
    }
}