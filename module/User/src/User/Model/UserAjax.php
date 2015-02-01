<?php
namespace User\Model;

use User\Event\UserEvent;
use Application\Utility\ApplicationErrorLogger;
use Exception;

class UserAjax extends UserBase
{
    /**
     * Select layout
     * 
     * @param integer $layoutId
     * @param integer $userId
     * @return boolean|string
     */
    public function selectLayout($layoutId, $userId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('user_list')
                ->set([
                    'layout' => $layoutId
                ])
                ->where([
                    'user_id' => $userId
                ]);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // clear a cache
            $this->removeUserCache($userId);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the edit user event
        UserEvent::fireUserEditEvent($userId, true);
        return true;
    }
}