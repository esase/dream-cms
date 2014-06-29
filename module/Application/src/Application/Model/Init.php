<?php

namespace Application\Model;

class Init extends Base
{
    /**
     * Init time zone
     *
     * @param string $time
     * @return void
     */
    public function initTimeZone($time)
    {
        $this->adapter->query('SET TIME_ZONE = ?', array($time));
    }
}