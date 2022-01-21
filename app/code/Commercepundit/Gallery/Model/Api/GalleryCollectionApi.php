<?php

namespace Commercepundit\Gallery\Model\Api;

use Commercepundit\Gallery\Api\GalleryManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;
use Magento\Catalog\Model\ProductRepository;

/**
 * Class GalleryCollectionApi
 * @package Commercepundit\Gallery\Model\Api
 */
class GalleryCollectionApi implements GalleryManagerInterface
{
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $_storeManager;
       
    /**
     * @var \Commercepundit\Gallery\Model\ResourceModel\Gallery\CollectionFactory
     */
    public $galleryFactory;

    /**
     * GalleryCollectionApi constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Commercepundit\Gallery\Model\ResourceModel\Gallery\CollectionFactory $galleryFactory
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Commercepundit\Gallery\Model\ResourceModel\Gallery\CollectionFactory $galleryFactory
    ) {
        $this->_storeManager = $storeManager;
        $this->galleryFactory = $galleryFactory;
    }
    
    /**
     * Returns Get Gallery Images
     * @return string[]
     */
    public function getGalleryImages()
    {
        $responseArray = [];
        $gallerycollection = $this->galleryFactory->create();
        $gallerycollection->addFieldToFilter('status', '1');
        $gallerycollection->setOrder('orderby', 'DESC');
        $responseArray[0]['media_url'] = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $responseArray[1]['gallery_images'] = $gallerycollection->getData();
        return $responseArray;
    }
}
