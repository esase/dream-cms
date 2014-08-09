<?php
namespace Page\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Page\Exception\PageException;
use Page\View\Widget\IWidget;

class PageInjectWidget extends AbstractHelper
{
    /**
     * Widgets
     * @var array
     */
    protected $widgets = [];

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
            // call widgets
            foreach ($this->widgets[$pageId][$position] as $widgetInfo) {
                // call a widget
                $widget = $this->getView()->{$widgetInfo['widget_name']}();

                // check the widget
                if (!$widget instanceof IWidget) {
                    throw new PageException(sprintf($widgetInfo['widget_name'] . ' must be an object implementing IWidget'));
                }

                // init the widget
                $widget->setPageId($pageId)
                    ->setWidgetConnectionId($widgetInfo['widget_connection_id']);

                if (false !== ($widgetContent = $widget->getContent())) {
                    // add the widget's layout
                    if (!empty($widgetInfo['widget_layout'])) {
                        $result .= $this->getView()->partial($widgetInfo['widget_layout'], [
                            'title' => $widget->getTitle(), 
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