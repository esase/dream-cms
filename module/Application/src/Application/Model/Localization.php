<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Application\Utility\Cache as CacheUtilities;

class Localization extends Base
{
    /**
     * Localization cache
     */
    const CACHE_LOCALIZATIONS = 'Application_Localizations';

    /**
     * Get all localizations
     *
     * @return array
     */
    public function getAllLocalizations()
    {
        // generate cache name
        $cacheName = CacheUtilities::getCacheName(self::CACHE_LOCALIZATIONS);

        // check data in cache
        if (null === ($localizations = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('localizations')
                ->columns(array(
                    'language',
                    'locale',
                    'default'
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
                        'default' => $localization['default']
                    );
                }
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $localizations);
        }

        return $localizations;
    }
}