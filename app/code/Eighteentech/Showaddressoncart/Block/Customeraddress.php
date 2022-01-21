<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Eighteentech\Showaddressoncart\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Main Customeraddress form block
 */
class Customeraddress extends Template
{
    /**
     * @var array
     */
    private $data;
    
    /**
     * Define constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $registry
     * @param \Magento\Customer\Api\AccountManagementInterface $accountManagement
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Customer\Model\Session $session,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        array $data = []
    ) {
        $this->customerSession = $session;
        $this->accountManagement = $accountManagement;
        $this->_addressConfig = $addressConfig;
        $this->addressMapper = $addressMapper;
        parent::__construct($context, $data);
    }

    /**
     * Get Customer Session
     * @return mixed
     */
    public function isLoggedIn() // You can use this function in phtml file
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * Get Customer Session Data
     * @return mixed
     */
    public function getCustomerId() // You can use this function in phtml file
    {
        return $this->customerSession->getCustomerId();
    }

    /**
     * $customerId
     */
    public function getDefaultShippingAddress($customerId)
    {
        try {
            $address = $this->accountManagement->getDefaultBillingAddress($customerId);
        } catch (NoSuchEntityException $e) {
            return __('You have not set a default shipping address.');
        }
        return $address;
    }
 
    /**
     * $customerId
     */
    public function getDefaultBillingAddress($customerId)
    {
        try {
            $address = $this->accountManagement->getDefaultBillingAddress($customerId);
        } catch (NoSuchEntityException $e) {
            return __('You have not set a default billing address.');
        }
        return $address;
    }
    
    /* Html Format */
    public function getDefaultShippingAddressHtml($address) {
        if ($address) {
            return $this->_getAddressHtml($address);
        } else {
            return __('You have not set a default Shipping address.');
        }
    }

    /* Html Format */
    public function getDefaultBillingAddressHtml($address) {
        if ($address) {
            return $this->_getAddressHtml($address);
        } else {
            return __('You have not set a default billing address.');
        }
    } 
 
    /**
     * Render an address as HTML and return the result
     *
     * @param AddressInterface $address
     * @return string
     */
    protected function _getAddressHtml($address)
    {
        /** @var \Magento\Customer\Block\Address\Renderer\RendererInterface $renderer */
        $renderer = $this->_addressConfig->getFormatByCode('html')->getRenderer();
        return $renderer->renderArray($this->addressMapper->toFlatArray($address));
    }
    
}
