<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Application\Utility\Cache as CacheUtility;

class Localization extends Base
{
    /**
     * LTR language direction
     */
    const LTR_LANGUAGE = 'ltr';

    /**
     * RTL language direction
     */
    const RTL_LANGUAGE = 'rtl';

    /**
     * Localization cache
     */
    const CACHE_LOCALIZATIONS = 'Application_Localizations';

    /**
     * Localization data cache tag
     */
    const CACHE_LOCALIZATIONS_DATA_TAG = 'Application_Localizations_Data_Tag';

    /**
     * Get all localizations
     *
     * @return array
     */
    public function getAllLocalizations()
    {
        // generate cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_LOCALIZATIONS);

        // check data in cache
        if (null === ($localizations = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('application_localization')
                ->columns(array(
                    'language',
                    'locale',
                    'description',
                    'default',
                    'direction'
                ))
                ->order('default desc');

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            // process localizations
            if ($resultSet) {
                foreach ($resultSet as $localization) {
                    $localizations[$localization['language']] = array(
                        'language' => $localization['language'],
                        'locale' => $localization['locale'],
                        'description' => $localization['description'],
                        'default' => $localization['default'],
                        'direction' => $localization['direction']
                    );
                }
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $localizations);
            $this->staticCacheInstance->setTags($cacheName, array(self::CACHE_LOCALIZATIONS_DATA_TAG));
        }

        return $localizations;
    }
}