<?php
namespace Nalli\Weeklyreport\Model\ResourceModel\Weeklyreport;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'weeklyreport_id';
    protected $_eventPrefix = 'nalli_weeklyreport_weeklyreport_collection';
    protected $_eventObject = 'weeklyreport_collection';

    /**
     * Define resource model
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Nalli\Weeklyreport\Model\Weeklyreport::class,
            \Nalli\Weeklyreport\Model\ResourceModel\Weeklyreport::class
        );
    }
}
