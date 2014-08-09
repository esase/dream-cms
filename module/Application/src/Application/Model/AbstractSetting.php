<?php
namespace Application\Model;

abstract class AbstractSetting extends Base
{
    /**
     * Array fields
     * @var array
     */
    protected $arrayFields = ['multiselect', 'multicheckbox'];

    /**
     * System settings flag
     */
    const SYS_SETTINGS_FLAG = 'system';

    /**
     * Settings array devider
     */
    const SETTINGS_ARRAY_DEVIDER = ';';

    /**
     * Convert string
     *
     * @param string $type
     * @param string $value
     * @return string|array
     */
    protected function convertString($type, $value)
    {
        if (in_array($type, $this->arrayFields)) {
            $value = explode(self::SETTINGS_ARRAY_DEVIDER, $value);
            return count($value) == 1 // check is array or not
                ? current($value)
                : $value;
        }

        return $value;
    }
}