<?php

namespace Application\Model;

use Zend\Db\Adapter\Adapter;

class ApplicationInit extends ApplicationBase
{
    /**
     * Init strict sql mode
     *
     * @return void
     */
    public function setStrictSqlMode()
    {
        $this->adapter->query('SET sql_mode="STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE"', Adapter::QUERY_MODE_EXECUTE);
    }

    /**
     * Set time zone
     *
     * @param string $time
     * @return void
     */
    public function setTimeZone($time)
    {
        $this->adapter->query('SET TIME_ZONE = ?', [$time]);
    }
}