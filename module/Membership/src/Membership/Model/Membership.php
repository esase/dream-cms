<?php
namespace Membership\Model;

class Membership extends Base
{
    /**
     * Get membership connection info
     *
     * @param integer $id
     * @param integer $userId
     * @return array
     */
    public function getMembershipConnectionInfo($id, $userId)
    {
        $select = $this->select();
        $select->from(array('a' => 'membership_level_connection'))
            ->columns(array(
                'id',
                'active'
            ))
            ->join(
                array('b' => 'user_list'),
                'a.user_id = b.user_id',
                array(
                    'language',
                    'email',
                    'nick_name',
                    'user_id',
                )
            )
            ->where(array(
                'a.id' => $id,
                'a.user_id' => $userId
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }
}