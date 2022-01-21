<?php
namespace Commercepundit\Gallery\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
            
class Gallery extends AbstractDb
{
    /**
     * Define table and primary key
     */
    protected function _construct()
    {
        $this->_init('commercepundit_gallery', 'gallery_id');
    }
}
