<?php

namespace Page\Controller;

use Application\Controller\ApplicationAbstractBaseController;
use Zend\View\Model\ViewModel;

class PageAjaxController extends ApplicationAbstractBaseController
{
    /**
     * Get embed page manager
     */
    public function ajaxGetEmbedPageManagerAction()
    {
        return new ViewModel([
            'page_id' => $this->params()->fromQuery('page_id', -1)
        ]);
    }
}