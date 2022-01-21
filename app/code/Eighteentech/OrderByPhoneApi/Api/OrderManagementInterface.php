<?php
/**
 * @category   Eighteentech
 * @package    Eighteentech_OrderByPhoneApi
 * @author     https://www.18thdigitech.com/
 */
namespace Eighteentech\OrderByPhoneApi\Api;

interface OrderManagementInterface
{
    /**
     * GET for Post api
     * @param string $telephone
     * @return rest/V1/orders/ output
     */
    public function getOrderback($telephone);
}
