<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2020 18th DigiTech (https://www.18thdigitech.com)
 * @package Caratlane_NetSuite
 */
namespace Caratlane\NetSuite\Model\ResourceModel\NetSuite;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'netsuite_order_log_collection';
    protected $_eventObject = 'netsuite_order_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Caratlane\NetSuite\Model\NetSuite::class,
            \Caratlane\NetSuite\Model\ResourceModel\NetSuite::class
        );
    }
}
