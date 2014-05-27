<?php

namespace Membership\Model;

use Exception;
use Application\Utility\ErrorLogger;
use Zend\Db\Sql\Predicate\Predicate as Predicate;
use Zend\Db\ResultSet\ResultSet;

class MembershipConsole extends Base
{
    /**
     * Activte the membership connection
     *
     * @param integer $connectionId
     * @param integer $lifeTime
     * @return boolean
     */
    public function activateMembershipConnection($connectionId, $lifeTime)
    {
        $update = $this->update()
            ->table('membership_level_connection')
            ->set(array(
                'active' => self::MEMBERSHIP_LEVEL_ACTIVE,
                'expire' => time() + ($lifeTime * self::SECONDS_IN_DAY)
            ))
            ->where(array(
               'id' => $connectionId
            ));

        $statement = $this->prepareStatementForSqlObject($update);
        $result = $statement->execute();

        return $result->count() ? true : false;
    }

    /**
     * Get all expired memberships connections
     *
     * @return object
     */
    public function getExpiredMembershipsConnections()
    {
        $predicate = new Predicate();
        $select = $this->select();
        $select->from(array('a' => 'membership_level_connection'))
            ->columns(array(
                'id',
                'user_id'
            ))
            ->join(
                array('b' => 'membership_level'),
                'a.membership_id = b.id',
                array(
                    'role_id'
                )
            )
            ->join(
                array('c' => 'user'),
                'a.user_id = c.user_id',
                array(
                    'nick_name',
                    'email',
                    'language',
                )
            )
            ->where(array(
                'a.active' => self::MEMBERSHIP_LEVEL_ACTIVE,
                $predicate->lessThanOrEqualTo('a.expire', time())
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        return $resultSet->initialize($statement->execute());
    }

    /**
     * Delete the membership connection
     *
     * @param integer $connectionId
     * @return boolean|string
     */
    public function deleteMembershipConnection($connectionId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('membership_level_connection')
                ->where(array(
                    'id' => $connectionId
                ));

            $statement = $this->prepareStatementForSqlObject($delete);
            $result = $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

            return $e->getMessage();
        }

        return $result->count() ? true : false;
    }
}