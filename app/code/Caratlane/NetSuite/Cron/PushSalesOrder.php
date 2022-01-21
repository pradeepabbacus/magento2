<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2020 18th DigiTech (https://www.18thdigitech.com)
 * @package Caratlane_NetSuite
 */

namespace Caratlane\NetSuite\Cron;

class PushSalesOrder
{

     /**
      * @var helper
      */
    private $helper;

    /**
     * helper helper.
     * @param \Caratlane\NetSuite\Helper\Data $helper
     */
    public function __construct(\Caratlane\NetSuite\Helper\Data $helper)
    {
        $this->helper = $helper;
    }
    
    /**
     * Execute cron method
     */
    public function execute()
    {
        
        if ($this->helper->getNetSuiteIsEnabled() && $this->helper->getNetSuiteOrderPushEnabled()) {
            $orderCollection = $this->helper->getOrderCollection();
            if ($orderCollection) {
                $this->pushOrders($orderCollection);
            }
        }
        return true;
    }
    
    /**
     * call curl method to push order
     */
    public function pushOrders($ordersJsonData)
    {
        $this->helper->pushCurlData($ordersJsonData);
    }
}
