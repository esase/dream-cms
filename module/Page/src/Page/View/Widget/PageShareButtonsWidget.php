<?php
namespace Page\View\Widget;
 
class PageShareButtonsWidget extends PageAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        $buttons = $this->getWidgetSetting('page_share_buttons_visible_list');

        if (!is_array($buttons)) {
            $buttons = [$buttons];
        }

        sort($buttons, SORT_STRING);
        return $this->getView()->partial('page/widget/share-buttons', [
            'buttons' => $buttons,
            'extra_buttons' => (int) $this->getWidgetSetting('page_share_buttons_show_extra')
        ]);
    }
}