<?php
namespace Nalli\Weeklyreport\Model\ResourceModel;

class Weeklyreport extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('weeklyreport', 'weeklyreport_id');
        //$this->_init('table name', 'primary key column name');
    }
}
