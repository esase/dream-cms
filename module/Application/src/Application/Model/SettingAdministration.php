<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;
use Exception;

class SettingAdministration extends Setting
{
    /**
     * Save settings
     *
     * @param array $settingsList
     * @param array $settingsValues
     * @param string $currentlanguage
     * @return boolean|string
     */
    public function saveSettings(array $settingsList, array $settingsValues, $currentlanguage)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // save settings
            foreach ($settingsList as $setting) {
                if (array_key_exists($setting['name'], $settingsValues)) {
                    // remove previously value
                    $query = $this->delete('settings_values')
                        ->where(array(
                            'setting_id' => $setting['id']
                        ))
                        ->where((!$setting['language_sensitive'] ? 'language is null' : array('language' => $currentlanguage)));

                    $statement = $this->prepareStatementForSqlObject($query);
                    $statement->execute();

                    // insert new value
                    $extraValues = $setting['language_sensitive']
                        ? array('language' => $currentlanguage)
                        : array();

                    $value = is_array($settingsValues[$setting['name']])
                        ? implode(self::SETTINGS_ARRAY_DEVIDER, $settingsValues[$setting['name']])
                        : (null != $settingsValues[$setting['name']] ? $settingsValues[$setting['name']] : '');

                    $query = $this->insert('settings_values')
                        ->values(array_merge(array(
                           'setting_id' => $setting['id'],
                           'value' => $value
                        ), $extraValues));

                    $statement = $this->prepareStatementForSqlObject($query);
                    $statement->execute();
                }
            }

            // clear cache
            $this->removeSettingsCache($currentlanguage);
            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            
            return $e->getMessage();
        }

        return true;
    }

    /**
     * Get settings list
     *
     * @param string $module
     * @param string $language
     * @return array|boolean
     */
    public function getSettingsList($module, $language)
    {
        // get module info
        if (null != ($moduleInfo = $this->getModuleInfo($module))) {
            $subQuery= $this->select();
            $subQuery->from(array('c' => 'settings_values'))
                ->columns(array(
                    'id'
                ))
                ->order('c.language desc')
                ->limit(1)
                ->where(array('a.id' => new Expression('c.setting_id')))
                ->where
                    ->and->isNull('c.language')
                ->where
                    ->or->equalTo('a.id', new Expression('c.setting_id'))
                    ->and->equalTo('c.language', $language);

            $mainSelect = $this->select();
            $mainSelect->from(array('a' => 'settings'))
                ->columns(array(
                    'id',
                    'name',
                    'label',
                    'type',
                    'required',
                    'language_sensitive',
                    'values_provider',
                    'check',
                    'check_message'
                ))
                ->join(
                    array('b' => 'settings_values'),
                    new Expression('b.id = (' .$this->getSqlStringForSqlObject($subQuery) . ')'),
                    array(
                        'value'
                    ),
                    'left'
                )
                ->join(
                    array('d' => 'settings_categories'),
                    new Expression('a.category = d.id'),
                    array(
                        'category_name' => new Expression('d.name')
                    ),
                    'left'
                )
                ->order('a.order')
                ->where(array('a.module' => $moduleInfo['id']))
                ->where
                    ->and->notEqualTo('a.type', self::SYS_SETTINGS_FLAG);

            $statement = $this->prepareStatementForSqlObject($mainSelect);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            // processing settings list
            $settings = array();
            foreach ($resultSet as $setting) {
                // convert an array
                $settingValue = $this->convertString($setting->type, $setting->value);

                $settings[$setting->id] = array(
                    'id' => $setting->id,
                    'category' => $setting->category_name,
                    'name' => $setting->name,
                    'label' => $setting->label,
                    'type' => $setting->type,
                    'required' => $setting->required,
                    'language_sensitive'  => $setting->language_sensitive,
                    'value' => $settingValue,
                    'values_provider' => $setting->values_provider
                );

                // add extra validators
                if ($setting->check) {
                    $settings[$setting->id]['validators'][] = array(
                        'name' => 'callback',
                        'options' => array(
                            'message' => $setting->check_message,
                            'callback' => function($value) use ($setting) {
                                return eval(str_replace('{value}', $value, $setting->check));
                            }
                        )
                    );
                }
            }

            // get list of predefined values
            $select = $this->select();
            $select->from('settings_predefined_values')
                ->columns(array(
                    'setting_id',
                    'value'
                ))
                ->where->in('setting_id', array_keys($settings));

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            // processing predefined list of values
            foreach ($resultSet as $values) {
                $settings[$values->setting_id]['values'][$values->value] = $values->value;
            }

            return $settings;
        }

        return false;
    }
}