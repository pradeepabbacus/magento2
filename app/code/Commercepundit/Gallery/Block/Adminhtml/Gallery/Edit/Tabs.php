<?php

namespace Commercepundit\Gallery\Block\Adminhtml\Gallery\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     *  Tab title set
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('gallery_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Gallery'));
    }
}
