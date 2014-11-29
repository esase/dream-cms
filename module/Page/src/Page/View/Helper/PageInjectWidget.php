<?php
namespace Page\View\Helper;
 
use Acl\Model\AclBase as AclBaseModel;
use Page\Exception\PageException;
use Page\View\Widget\IPageWidget;
use User\Service\UserIdentity as UserIdentityService;
use Zend\View\Helper\AbstractHelper;

class PageInjectWidget extends AbstractHelper
{
    /**
     * Widgets
     * @var array
     */
    protected $widgets = [];

    /**
     * Layout path
     * @var string
     */
    protected $layoutPath = 'page/layout-widget/';

    /**
     * Class constructor
     *
     * @param array $menu
     */
    public function __construct(array $widgets = [])
    {
        $this->widgets = $widgets;
    }

    /**
     * Inject widget
     *
     * @param string $position
     * @param integer $pageId
     * @return text
     */
    public function __invoke($position, $pageId = '')
    {
        $result = null;

        // get widgets by specified position
        if (!empty($this->widgets[$pageId][$position])) {
            $userRole = UserIdentityService::getCurrentUserIdentity()['role'];

            // call widgets
            foreach ($this->widgets[$pageId][$position] as $widgetInfo) {
                // check a widget visibility
                if ($userRole != AclBaseModel::DEFAULT_ROLE_ADMIN) {
                    if (!empty($widgetInfo['hidden']) && in_array($userRole, $widgetInfo['hidden'])) {
                        continue;
                    }
                }

                // call the widget
                $widget = $this->getView()->{$widgetInfo['widget_name']}();

                // check the widget
                if (!$widget instanceof IPageWidget) {
                    throw new PageException(sprintf($widgetInfo['widget_name'] . ' must be an object implementing IPageWidget'));
                }

                // init the widget
                $widget->setPageId($pageId)
                    ->setWidgetConnectionId($widgetInfo['widget_connection_id']);

                if (false !== ($widgetContent = $widget->getContent())) {
                    // add the widget's layout
                    if (!empty($widgetInfo['widget_layout'])) {
                        $result .= $this->getView()->partial($this->layoutPath . $widgetInfo['widget_layout'], [
                            'title' => $this->getView()->pageWidgetTitle($widgetInfo),
                            'content' => $widgetContent
                        ]);

                        continue;
                    }

                    $result .= $widgetContent;
                }
            }
        }

        return $result;
    }
}