<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2020 18th DigiTech (https://www.18thdigitech.com)
 * @package Caratlane_NetSuite
 */
namespace Caratlane\NetSuite\Model\ResourceModel;

class NetSuite extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /*public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }*/

    protected function _construct()
    {
        $this->_init('netsuite_orders', 'entity_id'); //entity_id : Primary key of your database table
    }
}
