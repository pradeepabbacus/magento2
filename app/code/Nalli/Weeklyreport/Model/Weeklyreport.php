<?php
namespace Nalli\Weeklyreport\Model;

class Weeklyreport extends \Magento\Framework\Model\AbstractModel implements
    \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'nalli_weeklyreport_weeklyreport';

    protected $_cacheTag = 'nalli_weeklyreport_weeklyreport';

    protected $_eventPrefix = 'nalli_weeklyreport_weeklyreport';

    protected function _construct()
    {
        $this->_init(\Nalli\Weeklyreport\Model\ResourceModel\Weeklyreport::class);
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
