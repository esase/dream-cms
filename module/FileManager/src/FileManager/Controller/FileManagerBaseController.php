<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace FileManager\Controller;

use Application\Controller\AbstractBaseController;
use FileManager\Model\Base as FileManagerBaseModel;

abstract class FileManagerBaseController extends AbstractBaseController
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('FileManager\Model\Base');
        }

        return $this->model;
    }

    /**
     * List action
     *
     * @retun array 
     */
    protected function getListFiles()
    {
        $filters = array();

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('FileManager\Form\FileFilter');

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get current user directories structure
        $userDirectories = $this->getModel()->getUserDirectories();

        // get list of files and directories in specified directory
        $userPath = null != $this->getRequest()->getQuery('path', null)
            ? $this->getRequest()->getQuery('path')
            : FileManagerBaseModel::getHomeDirectoryName();

        $paginator = false;
        if (false !== ($currentDirectory = $this->getModel()->getUserDirectory($userPath))) {
            // get data
            $paginator = $this->getModel()->getFiles($currentDirectory,
                $this->getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);
        }

        return array(
            'filters' => $filters,
            'filter_form' => $filterForm->getForm(),
            'path' => $userPath,
            'userDirectories' => $userDirectories,
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        );
    }
}
