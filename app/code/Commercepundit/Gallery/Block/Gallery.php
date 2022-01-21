<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Commercepundit\Gallery\Block;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Main gallery form block
 */
class Gallery extends \Magento\Catalog\Block\Product\AbstractProduct
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var \Commercepundit\Gallery\Model\ResourceModel\Gallery\CollectionFactory
     */
    protected $galleryFactory;

    /**
     * Gallery constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Commercepundit\Gallery\Model\ResourceModel\Gallery\CollectionFactory $galleryFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Registry $registry,
        \Commercepundit\Gallery\Model\ResourceModel\Gallery\CollectionFactory $galleryFactory,
        array $data = []
    ) {
        $this->_registry = $registry;
        $this->galleryFactory = $galleryFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get Gallery Images collection
     * @return mixed
     */
    public function getGallery()
    {
        $teamcollection = $this->galleryFactory->create();
        $teamcollection->setOrder('orderby', 'DESC');
        return $teamcollection;
    }
}
