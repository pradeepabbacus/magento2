<?php
/**
 * Caratlane
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Caratlane.com license that is
 * available through the world-wide-web at this URL:
 * https://www.Caratlane.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to any file if you wish to upgrade this extension
 * version in the future.
 *
 * @category    Caratlane
 * @package     Caratlane_NetSuite
 * @copyright   Copyright (c) Caratlane (https://www.Caratlane.com/)
 * @license     https://www.Caratlane.com/LICENSE.txt
 */

namespace Caratlane\NetSuite\Model\Order;

use Magento\Framework\Webapi\Rest\Request;
use Caratlane\NetSuite\Api\OrderUpdateInterface;
use Caratlane\NetSuite\Model\NetSuiteOrdersUpdateFactory;
use Caratlane\NetSuite\Model\NetSuiteConstants;
use Caratlane\NetSuite\Helper\Logger;
use Caratlane\NetSuite\Helper\Data;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\Data\OrderInterface;

class OrderUpdate implements OrderUpdateInterface
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @var \Magento\Sales\Api\Data\OrderInterface
     */
    private $orderInterface;

    /**
     * @var \Caratlane\NetSuite\Helper\Logger
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    private $request;

    /**
     * @var \Caratlane\NetSuite\Model\NetSuiteOrdersUpdateFactory
     */
    private $netSuiteOrdersUpdate;

    /**
     * @var \Caratlane\NetSuite\Helper\Data
     */
    private $helper;

    /**
     * @param ResourceConnection $resource
     * @param OrderInterface $orderInterface
     * @param Data $helper
     * @param Logger $logger
     * @param Request $request
     * @param NetSuiteOrdersUpdateFactory $netSuiteOrdersUpdate
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        Request $request,
        ResourceConnection $resource,
        OrderInterface $orderInterface,
        NetSuiteOrdersUpdateFactory $netSuiteOrdersUpdate
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->request = $request;
        $this->resource = $resource;
        $this->orderInterface = $orderInterface;
        $this->netSuiteOrdersUpdate = $netSuiteOrdersUpdate;
    }

    public function getOrderUpdate()
    {

        $responseData = [];
        $responseData['success'] = NetSuiteConstants::NETSUITE_DEFAULT_ERROR_CODE;
        $responseData['message'] = NetSuiteConstants::NETSUITE_DISABLE_MESSAGE;

        if ($this->helper->getNetSuiteIsEnabled() && $this->helper->getNetsuiteOrdersUpdateEnabled()) {

            $responseData['message'] = NetSuiteConstants::NETSUITE_EMPTY_MESSAGE;
            $postData = $this->request->getBodyParams();
            $this->logger->initLog(NetSuiteConstants::NETSUITE_ORDER_UPODATE_LOG_FILENAME);
            $this->logger->writeLog('----------------------------');
            $this->logger->writeLog('Order Update Started');
            $this->logger->writeLog('----------------------------');
            $this->logger->writeLog('');

            try {
                if (!empty($postData)) {
                    
                    if ($postData['ns_order_id']) {

                        $this->logger->writeLog('Magento Order Id: '.$postData['magento_order_id']);
                        if (!$postData['ns_order_id'] || !$postData['magento_order_id'] || !$postData['order_status']) {

                            $this->logger->writeLog('Please add required fields');
                            $responseData['success'] = false;
                            $responseData['message'] = "Please add required fields";
                            return [$responseData];
                        }

                        $incrementOrderId = $this->getOrderId($postData['magento_order_id']);

                        if ($incrementOrderId != "") {

                            $jsondata = json_encode($postData);
                            $netSuiteOrderUpdateModel = $this->netSuiteOrdersUpdate->create();
                                
                            $netSuiteOrderUpdateModel->setMagentoOrderId($postData['magento_order_id']);
                            $netSuiteOrderUpdateModel->setNetsuiteOrderId($postData['ns_order_id']);
                            $netSuiteOrderUpdateModel->setNetsuiteOrderStatus($postData['order_status']);
                            $netSuiteOrderUpdateModel->setStatus("completed");
                            $netSuiteOrderUpdateModel->setJsonData($jsondata);
                            $orderstatusmsg = NetSuiteConstants::NETSUITE_MIGRATION_ORDER_INDERTED_SUCCESS_MESSAGE;
                            $netSuiteOrderUpdateModel->setMessages($orderstatusmsg);
                            $netSuiteOrderUpdateModel->save();

                            //update data for shipment table
                            $connection  = $this->resource->getConnection();
                            $data = [];
                            $data['netsuite_order_status'] = $postData['order_status'];
                            $where = ['increment_id = ?' => $postData['magento_order_id']];

                            /* update sales_shipment_grid */
                            $tableName = $connection->getTableName("sales_order_grid");
                            $connection->update($tableName, $data, $where);

                            /* update sales_shipment */
                            $tableName = $connection->getTableName("sales_order");
                            $connection->update($tableName, $data, $where);

                            $this->logger->writeLog('Order Status: '.$postData['order_status']);

                            /* update sales_order_item */
                            $orderId = $this->getOrderId($postData['magento_order_id']);
                            $itemarray = $postData['items'];

                            $this->updateOrderItem($itemarray, $orderId);

                            $this->logger->writeLog('Order updated successfully');

                            $responseData['success'] = true;
                            $responseData['message'] = "Order updated successfully";
                            $responseData['magento_order_id'] = $postData['magento_order_id'];
                                
                        } else {
                            $this->logger->writeLog('Order id not found in system ='.$postData['magento_order_id']);
                            $responseData['success'] = false;
                            $responseData['message'] = "Order id not found in system";
                        }

                    } else {
                        $this->logger->writeLog('ns_order_id field can\'t be empty');
                        $responseData['success'] = false;
                        $responseData['message'] = "ns_order_id field can't be empty";
                    }
                    
                } else {
                    $this->logger->writeLog('Order Update Data Not Found');
                }
            } catch (Exception $e) {
                $this->logger->writeLog('Exception: '.$e->getMessage());
                $responseData['success'] = false;
                $responseData['message'] = 'Exception : '.$e->getMessage();
            }

            $this->logger->writeLog('');
            $this->logger->writeLog('----------------------------');
            $this->logger->writeLog('Order Update Stopped');
            $this->logger->writeLog('----------------------------');
        }
        return [$responseData];
    }

    private function updateOrderItem($itemarray, $orderId)
    {
        if (!empty($itemarray)) {
            foreach ($itemarray as $key => $orderitem) {
                $dataitem = [];
                $itemconnection  = $this->resource->getConnection();
                $dataitem['qty_shipped'] = $orderitem['qty_shipped'];
                $dataitem['qty_canceled'] = $orderitem['qty_cancelled'];
                $dataitem['qty_refunded'] = $orderitem['qty_refunded'];
                $dataitem['qty_delivered'] = $orderitem['qty_delivered'];
                $dataitem['netsuite_item_status'] = $orderitem['item_status'];
                $itemwhere = ['order_id = ?' => (int)$orderId,'sku = ?' => $orderitem['item_sku']];
                    
                $itemtableName = $itemconnection->getTableName("sales_order_item");
                $itemconnection->update($itemtableName, $dataitem, $itemwhere);

                $this->logger->writeLog('Order Item Sku: '.$orderitem['item_sku']);
                $this->logger->writeLog('Order Item Status: '.$orderitem['item_status']);
            }
        }
    }
    
    public function getOrderId(string $incrementOrderId)
    {
        $orderId = 0;
        try {
            $orderInfo = $this->orderInterface->loadByIncrementId($incrementOrderId);
            $orderId = $orderInfo->getId();
            return $orderId;
        } catch (Exception $e) {
            return $orderId;
        }
    }
}
