<?php

namespace Payment\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Payment\Model\Base as BaseModel;
use Application\Utility\Locale as LocaleUtility;

class PaymentItemExtraOptions extends AbstractHelper
{
    /**
     * Model instance
     * @var object
     */
    protected $model;

    /**
     * Class constructor
     *
     * @param object $model
     */
    public function __construct(BaseModel $model)
    {
        $this->model = $model;
    }

    /**
     * Payment item's extra options
     *
     * @param array $info
     *      integer id
     *      string title
     *      float cost
     *      float discount
     *      integer count
     *      integer active
     *      integer available
     *      integer deleted
     *      string slug
     *      string view_controller
     *      string view_action
     *      integer countable
     *      integer must_login
     *      string extra_options
     *      string handler
     *      integer object_id
     *      integer module_extra_options
     * @return object - fluent interface
     */
    public function __invoke($info)
    {
        if ($info['module_extra_options'] == BaseModel::MODULE_EXTRA_OPTIONS && !empty($info['extra_options'])) {
            // get list of available extra options
            if (null != ($extraOptionsFields = $this->model->
                    getPaymentHandlerInstance($info['handler'])->getItemExtraOptions($info['object_id']))) {

                $extraOptions = unserialize($info['extra_options']);

                // process extra options
                $content = '<dl>__content__</dl>';
                $contentBody = null;

                foreach ($extraOptions as $fieldName => $fieldValue) {
                    // check the field existing
                    if (empty($extraOptionsFields[$fieldName])) {
                        continue;
                    }

                    // get list of predefined values
                    $predefinedValues = isset($extraOptionsFields[$fieldName]['values'])
                        ? $extraOptionsFields[$fieldName]['values']
                        : array();

                    // check values
                    if ($predefinedValues) {
                        if (is_array($fieldValue)) {
                            $processedValues = array();
                            foreach ($fieldValue as $arrayField => $arrayValue) {
                                if (!in_array($arrayValue, $predefinedValues)) {
                                    continue;
                                }

                                $processedValues[$arrayField] = $arrayValue;
                            }

                            $fieldValue = $processedValues;
                        }
                        else {
                            $fieldValue = in_array($fieldValue, $predefinedValues)
                                ? $fieldValue
                                : null;
                        }
                    }

                    if (!$fieldValue
                            || (!$predefinedValues && isset($extraOptionsFields[$fieldName]['values']))) {

                        continue;
                    }

                    // draw values
                    $contentBody .= '<dt>' . $this->getView()->translate($fieldName) . '</dt>';

                    if (!is_array($fieldValue)) {
                        $contentBody .= '<dd>' . $this->getView()->
                                translate(LocaleUtility::convertToLocalizedValue($fieldValue, $extraOptionsFields[$fieldName]['type'])) . '</dd>';
                    }
                    else {
                        // process array values
                        $contentBody .= '<dd>';

                        foreach ($fieldValue as $arrayValue) {
                            $contentBody .= $this->getView()->translate($arrayValue) . ', ';
                        }

                        $contentBody = rtrim($contentBody, ', ') . '</dd>';
                    }
                }

                return $contentBody
                    ? str_replace('__content__', $contentBody, $content)
                    : null;
            }
        }
    }
}
