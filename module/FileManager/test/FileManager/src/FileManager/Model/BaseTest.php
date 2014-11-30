<?php
namespace FileMangager\Test\Model;

use PHPUnit_Framework_TestCase;
use FileManager\Model\FileManagerBase as FileManagerBaseModel;

class BaseTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test of procerss directory path
     */
    public function testProcessDirectoryPath()
    {
        $this->assertEquals('home', FileManagerBaseModel::processDirectoryPath('home/'));
        $this->assertEquals('home', FileManagerBaseModel::processDirectoryPath('../home/'));
        $this->assertEquals('_home90_', FileManagerBaseModel::processDirectoryPath('@@!!...\\\////_home90-MMMM(((**&&&_'));
        $this->assertEquals('home/test', FileManagerBaseModel::processDirectoryPath('../home/....&&&/test'));
        $this->assertEquals('', FileManagerBaseModel::processDirectoryPath('....////\\\\\\'));
        $this->assertEquals('', FileManagerBaseModel::processDirectoryPath('//////////////////////////////'));
    }
}