<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2020 18th DigiTech (https://www.18thdigitech.com)
 * @package Caratlane_NetSuite
 */
namespace Caratlane\NetSuite\Model;

class NetSuite extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'netsuite_order_log';

    protected $_cacheTag = 'netsuite_order_log';

    protected $_eventPrefix = 'netsuite_order_log';

    protected function _construct()
    {
        $this->_init(\Caratlane\NetSuite\Model\ResourceModel\NetSuite::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }
}
