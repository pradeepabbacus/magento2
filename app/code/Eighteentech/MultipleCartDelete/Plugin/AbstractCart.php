<?php
/**
 * AbstractCart Plugin Class
 *
 * Copyright (c) 2021. 18th DigiTech Team. All rights reserved.
 * @author  18th Digitech <info@18thdigitech.com>
 * @package Eighteentech_MultipleCartDelete
 */
namespace Eighteentech\MultipleCartDelete\Plugin;

class AbstractCart
{
    /**
     * @param \Magento\Checkout\Block\Cart\AbstractCart $subject
     * @param $result
     * @return mixed
     */
    public function afterGetItemRenderer(\Magento\Checkout\Block\Cart\AbstractCart $subject, $result)
    {
        $result->setTemplate('Eighteentech_MultipleCartDelete::cart/item/default.phtml');
        return $result;
    }
}
