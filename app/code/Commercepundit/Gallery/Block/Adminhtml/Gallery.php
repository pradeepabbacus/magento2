<?php
namespace Commercepundit\Gallery\Block\Adminhtml;
 
class Gallery extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Set header text and button label of grid in admin
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Commercepundit_Gallery';
        $this->_controller = 'adminhtml_index';
        $this->_headerText = __('Add New Gallery Image');
        $this->_addButtonLabel = __('Add New Gallery Image');
        parent::_construct();
    }
}
