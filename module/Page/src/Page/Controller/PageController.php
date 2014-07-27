<?php
namespace Page\Controller;

use Application\Controller\AbstractBaseController;

class PageController extends AbstractBaseController
{
    /**
     * Index page
     */
    public function indexAction()
    {
    echo __METHOD__ , '<br>';
    print_r(explode('/', $this->getPageName()));
        // check received page
        /*$currentPage = array_filter(explode('/', $this->getPageName()));
        //print_r(array_filter($currentPage));
        $currentPage = end($currentPage);
        
        echo $currentPage , '<br>';;
        echo $this->getPage('page'), '<br>';
        echo $this->getSlug('page'), '<br>';
        return false;
        // Запрашиваю только конец, затем с нестед сета поднимаю текущую страницу и ивсех предков
        // срванива. путь если совпал то все ok если не совпал 404, если запрашиваемой страницы нет 404
        */
       // echo $this->request->getUri();;
        echo '<br>'.$this->params()->fromRoute('page_name') , '<br>';
        echo $this->getPage(), '<br>';
        echo $this->getPerPage(), '<br>';
        echo $this->getOrderBy(), '<br>';
        echo $this->getSlug(), '<br>';
        echo $this->getExtra(), '<br>';
        return false;
    }
}