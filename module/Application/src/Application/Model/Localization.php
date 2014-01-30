<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Application\Utility\Cache as CacheUtilities;
use Exception;

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
        }

        return $localizations;
    }
}