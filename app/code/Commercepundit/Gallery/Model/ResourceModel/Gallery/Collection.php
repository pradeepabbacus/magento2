<?php
namespace Commercepundit\Gallery\Model\ResourceModel\Gallery;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'gallery_id';

    /**
     * Define Resource model
     */
    protected function _construct()
    {
        $this->_init('Commercepundit\Gallery\Model\Gallery', 'Commercepundit\Gallery\Model\ResourceModel\Gallery');
    }

    /**
     * @param $attribute
     * @param null $condition
     * @return $this
     */
    public function addAttributeToFilter($attribute, $condition = null)
    {
       
        $this->getSelect()->where($this->_getConditionSql($attribute, $condition));
        return $this;
    }
}
