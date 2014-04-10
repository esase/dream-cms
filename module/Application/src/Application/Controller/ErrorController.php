<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\View\Model\ViewModel;
use Zend\Http\Response;

class ErrorController extends AbstractBaseController
{
    /**
     * Forbidden page
     */
    public function forbiddenAction()
    {
        $this->layout('layout/error');
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_403);
    }
}
