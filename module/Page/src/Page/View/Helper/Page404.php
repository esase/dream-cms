<?php
namespace Page\View\Helper;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Localization\Service\Localization as LocalizationService;
use User\Service\UserIdentity as UserIdentityService;
use Page\Event\PageEvent;
use Zend\View\Helper\AbstractHelper;
use Zend\Http\Response;

class Page404 extends AbstractHelper
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Custom 404 page
     */
    const CUSTOM_404_PAGE = '404';

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = ServiceLocatorService::getServiceLocator()->get('Page\Model\PageNestedSet');
        }

        return $this->model;
    }

    /**
     * Page 404
     * 
     * @return string|boolean
     */
    public function __invoke()
    {
        $language = LocalizationService::getCurrentLocalization()['language'];

        // get a custom 404 page's url
        if (false !== ($page404 = $this->
                getView()->pageUrl(self::CUSTOM_404_PAGE, [], $language, true))) {

            $userRole = UserIdentityService::getCurrentUserIdentity()['role'];

            if (false == ($pageInfo = $this->
                    getModel()->getActivePageInfo(self::CUSTOM_404_PAGE, $userRole, $language))) {

                return false;
            }

            // fire the page show event
            PageEvent::firePageShowEvent($pageInfo['slug'], $language);

            // check for redirect
            if ($pageInfo['redirect_url']) {
                $response = ServiceLocatorService::getServiceLocator()->get('Response');
                $response->getHeaders()->addHeaderLine('Location', $pageInfo['redirect_url']);
                $response->setStatusCode(Response::STATUS_CODE_301);
                $response->sendHeaders();

                return false;
            }

            // get the page's breadcrumb
            $breadcrumb = $this->getModel()->
                getActivePageParents($pageInfo['left_key'], $pageInfo['right_key'], $userRole, $language);

            return $this->getView()->partial($this->getModel()->getLayoutPath() . $pageInfo['layout'], [
                'page' => $pageInfo,
                'breadcrumb' => $breadcrumb
            ]);
        }

        return $page404;
    }
}