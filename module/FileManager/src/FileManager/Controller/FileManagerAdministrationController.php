<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace FileManager\Controller;

use Zend\View\Model\ViewModel;

class FileManagerAdministrationController extends FileManagerBaseController
{
    /**
     * Default action
     */
    public function indexAction()
    {
        // redirect to list action
        return $this->redirectTo('files-manager-administration', 'list');
    }

    /**
     * List of files and directories
     */
    public function listAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->checkPermission())) {
            return $result;
        }

        return new ViewModel($this->getListFiles());
    }

    //TODO:
    //1. add upload filter by extgension
    ///2. limit neststed directories
    //3. Limit directory's name length
    //4. Limit the file size!!!
}
