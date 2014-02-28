<?php

namespace FileMangager\Test\Model;

use FileMangager\Test\ApplicationBootstrap;
use PHPUnit_Framework_TestCase;
use FileManager\Model\Base as BaseFileManagerModel;

class BaseTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test of procerss directory path
     */
    public function testProcessDirectoryPath()
    {
        $this->assertEquals('home', BaseFileManagerModel::processDirectoryPath('home/'));
        $this->assertEquals('home', BaseFileManagerModel::processDirectoryPath('../home/'));
        $this->assertEquals('_home90-', BaseFileManagerModel::processDirectoryPath('@@!!...\\\////_home90-MMMM(((**&&&'));
        $this->assertEquals('home/test', BaseFileManagerModel::processDirectoryPath('../home/....&&&/test'));
        $this->assertEquals('', BaseFileManagerModel::processDirectoryPath('....////\\\\\\'));
        $this->assertEquals('', BaseFileManagerModel::processDirectoryPath('//////////////////////////////'));
    }
}
