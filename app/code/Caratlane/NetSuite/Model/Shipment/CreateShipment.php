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

namespace Caratlane\NetSuite\Model\Shipment;

use Magento\Framework\Webapi\Rest\Request;
use Caratlane\NetSuite\Api\CreateShipmentInterface;
use Caratlane\NetSuite\Model\NetsuiteShipmentsFactory;
use Caratlane\NetSuite\Model\NetSuiteConstants;
use Caratlane\NetSuite\Helper\Logger;
use Caratlane\NetSuite\Helper\Data;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Shipping\Model\ShipmentNotifier;
use Magento\Sales\Model\Convert\Order;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Framework\Serialize\Serializer\Json;

class CreateShipment implements CreateShipmentInterface
{
    /**
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    private $trackFactory;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @var \Magento\Sales\Model\Convert\Order
     */
    private $convertOrder;

    /**
     * @var \Magento\Shipping\Model\ShipmentNotifier
     */
    private $shipmentNotifier;

    /**
     * @var \Magento\Sales\Api\Data\OrderInterface
     */
    private $orderInterface;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    private $invoiceService;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    private $transaction;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var \Caratlane\NetSuite\Helper\Logger
     */
    protected $logger;

    /**
     * @var \Caratlane\NetSuite\Model\NetsuiteShipmentsFactory
     */
    private $netsuiteShipments;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $json;

    /**
     * @var \Caratlane\NetSuite\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    private $request;

    /**
     * @param TrackFactory $trackFactory
     * @param ResourceConnection $resource
     * @param Data $helper
     * @param Json $json
     * @param Order $convertOrder
     * @param ShipmentNotifier $shipmentNotifier
     * @param OrderInterface $orderInterface
     * @param OrderRepositoryInterface $orderRepository
     * @param InvoiceService $invoiceService
     * @param InvoiceSender $invoiceSender
     * @param Transaction $transaction
     * @param Logger $logger
     * @param NetsuiteShipmentsFactory $netsuiteShipments
     * @param Request $request
     */
    
    public function __construct(
        Request $request,
        ResourceConnection $resource,
        Data $helper,
        Json $json,
        TrackFactory $trackFactory,
        Order $convertOrder,
        ShipmentNotifier $shipmentNotifier,
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        Transaction $transaction,
        OrderInterface $orderInterface,
        Logger $logger,
        NetsuiteShipmentsFactory $netsuiteShipments
    ) {
        $this->request = $request;
        $this->resource = $resource;
        $this->helper = $helper;
        $this->json = $json;
        $this->trackFactory = $trackFactory;
        $this->convertOrder = $convertOrder;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
        $this->orderInterface = $orderInterface;
        $this->logger = $logger;
        $this->netsuiteShipments = $netsuiteShipments;
    }

    public function createNewShipment()
    {
        //git added in dev branch
        //revert dev brach
        $responseData = [];
        $responseData['success'] = NetSuiteConstants::NETSUITE_DEFAULT_ERROR_CODE;
        $responseData['message'] = NetSuiteConstants::NETSUITE_DISABLE_MESSAGE;

        if ($this->helper->getNetSuiteIsEnabled() && $this->helper->getNetsuiteCreateShipmentEnabled()) {
         
            $this->logger->initLog(NetSuiteConstants::NETSUITE_CREATE_SHIPMENT_LOG_FILENAME);
            $this->logger->writeLog('----------------------------');
            $this->logger->writeLog('Create Shipment Started');
            $this->logger->writeLog('----------------------------');
            $this->logger->writeLog('');

            $postData = $this->request->getBodyParams();

            try {
                
                if ($postData['ns_fulfillment_id']) {

                    $postDataarray = [];
                    $postDataarray['ns_fulfillment_id'] = $postData['ns_fulfillment_id'];
                    $postDataarray['ns_fulfillment_number'] = $postData['ns_fulfillment_number'];
                    $postDataarray['ns_order_id'] = $postData['ns_order_id'];
                    $postDataarray['magento_order_id'] = $postData['magento_order_id'];
                    $postDataarray['tracking_number'] = $postData['tracking_number'];
                    $postDataarray['shipping_carrier'] = $postData['shipping_carrier'];
                    $postDataarray['shipped_date'] = $postData['shipped_date'];
                    $postDataarray['delivery_status'] = $postData['delivery_status'];

                    foreach ($postDataarray as $key => $postvalue) {
                        if ($postvalue == "") {
                            $this->logger->writeLog('Please add required fields');
                            $responseData['success'] = false;
                            $responseData['message'] = "Please add required fields";
                            return [$responseData];
                        }
                    }
                    
                    $incrementOrderId = $this->getOrderId($postData['magento_order_id']);
                    //echo $incrementOrderId; exit();
                    if ($incrementOrderId != "") {
                        $this->logger->writeLog('Magento Order Id: '.$postData['magento_order_id']);
                        $jsonData = $this->json->serialize($postData);

                        $order = $this->orderRepository->get($incrementOrderId);
                            
                        if (!$order->canShip()) {
                            //Shipment have already created
                            $this->logger->writeLog('Shipment already created Or Not create a shipment');
                            $responseData['success'] = false;
                            $responseData['message'] = "Shipment already created Or Not create a shipment";

                            $netsuiteShipmentsModel = $this->netsuiteShipments->create();
                            $netsuiteShipmentsModel->setMagentoOrderId($postData['magento_order_id']);
                            $netsuiteShipmentsModel->setNetsuiteOrderId($postData['ns_order_id']);
                            $netsuiteShipmentsModel->setFulfillmentId($postData['ns_fulfillment_id']);
                            $netsuiteShipmentsModel->setFulfillmentNumber($postData['ns_fulfillment_number']);
                            $netsuiteShipmentsModel->setTrackingNumber($postData['tracking_number']);
                            $netsuiteShipmentsModel->setShippingCarrier($postData['shipping_carrier']);
                            $netsuiteShipmentsModel->setShippedDate($postData['shipped_date']);
                            $netsuiteShipmentsModel->setDeliveryStatus($postData['delivery_status']);
                            $netsuiteShipmentsModel->setStatus("failed");
                            $jsonData = $this->json->serialize($postData);
                            $netsuiteShipmentsModel->setJsonData($jsonData);
                            $netsuiteShipmentsModel->setMessages("Shipment already created Or Not create a shipment");
                            $netsuiteShipmentsModel->save();

                        } else {
                                
                            $shipmentdata = $this->getCreateShipmentByOrderId($incrementOrderId, $jsonData);
                                                      
                            $resposeShipmentflag = '';
                            $resposeShipmentflag = $this->updateNetsuiteShipments($shipmentdata, $postData);
                            if ($resposeShipmentflag) {
                                
                                $shipids = explode(":", $shipmentdata);
                                $incrementId = $shipids[0];
                                $shipmentId = $shipids[1];

                                $responseData['success'] = true;
                                $responseData['message'] = "Shipment created successfully!";
                                $responseData['magento_shipment_id'] = $shipmentId;
                                $responseData['magento_shipment_no'] = $incrementId;
                            } else {
                                $responseData['success'] = false;
                                $responseData['message'] = "Not create shipment";
                            }

                        }

                    } else {
                        $this->logger->writeLog('Order id not found in system ='.$postData['magento_order_id']);
                        $responseData['success'] = false;
                        $responseData['message'] = "Order id not found in system";
                    }
                } else {
                    $this->logger->writeLog('ns_fulfillment_id field can\'t be empty');
                    $responseData['success'] = false;
                    $responseData['message'] = "ns_fulfillment_id field can't be empty";
                }
                
            } catch (Exception $e) {
                $responseData['success'] = false;
                $responseData['message'] = 'Exception : '.$e->getMessage();
                $this->logger->writeLog('Exception: '.$e->getMessage());
            }

            $this->logger->writeLog('');
            $this->logger->writeLog('----------------------------');
            $this->logger->writeLog('Create Shipment Stopped');
            $this->logger->writeLog('----------------------------');
        }
        return [$responseData];
    }

    private function updateNetsuiteShipments($shipmentdata, $postData)
    {
        $shipids = explode(":", $shipmentdata);
        
        if ($shipids[0]) {
            $incrementId = $shipids[0];
            $shipmentId = $shipids[1];

            $netsuiteShipmentsModel = $this->netsuiteShipments->create();
            $netsuiteShipmentsModel->setMagentoOrderId($postData['magento_order_id']);
            $netsuiteShipmentsModel->setNetsuiteOrderId($postData['ns_order_id']);
            $netsuiteShipmentsModel->setFulfillmentId($postData['ns_fulfillment_id']);
            $netsuiteShipmentsModel->setFulfillmentNumber($postData['ns_fulfillment_number']);
            $netsuiteShipmentsModel->setTrackingNumber($postData['tracking_number']);
            $netsuiteShipmentsModel->setShippingCarrier($postData['shipping_carrier']);
            $netsuiteShipmentsModel->setShippedDate($postData['shipped_date']);
            $netsuiteShipmentsModel->setShipmentId($incrementId);
            $netsuiteShipmentsModel->setDeliveryStatus($postData['delivery_status']);
            $netsuiteShipmentsModel->setStatus(NetSuiteConstants::NETSUITE_MIGRATION_STATUS);
            $jsonData = $this->json->serialize($postData);
            $netsuiteShipmentsModel->setJsonData($jsonData);
            $shipsuccessmsg = NetSuiteConstants::NETSUITE_MIGRATION_SHIPMENT_SUCCESS_MESSAGE;
            $netsuiteShipmentsModel->setMessages($shipsuccessmsg);
            $netsuiteShipmentsModel->save();

            $this->logger->writeLog('Shipment Id: '.$incrementId);
            $this->logger->writeLog('Shipment created successfully');

            return true;

        } else {

            $netsuiteShipmentsModel = $this->netsuiteShipments->create();
            $netsuiteShipmentsModel->setMagentoOrderId($postData['magento_order_id']);
            $netsuiteShipmentsModel->setNetsuiteOrderId($postData['ns_order_id']);
            $netsuiteShipmentsModel->setFulfillmentId($postData['ns_fulfillment_id']);
            $netsuiteShipmentsModel->setFulfillmentNumber($postData['ns_fulfillment_number']);
            $netsuiteShipmentsModel->setTrackingNumber($postData['tracking_number']);
            $netsuiteShipmentsModel->setShippingCarrier($postData['shipping_carrier']);
            $netsuiteShipmentsModel->setShippedDate($postData['shipped_date']);
            $netsuiteShipmentsModel->setDeliveryStatus($postData['delivery_status']);
            $netsuiteShipmentsModel->setStatus("failed");
            $jsonData = $this->json->serialize($postData);
            $netsuiteShipmentsModel->setJsonData($jsonData);
            $netsuiteShipmentsModel->setMessages("Cannot create shipment");
            $netsuiteShipmentsModel->save();

            $m_oid = $postData['magento_order_id'];
            $logmsg = 'Cannot create shipment for Order increment id: ='.$m_oid;
            $this->logger->writeLog($logmsg);
             
            return false;
        }
    }

    public function getCreateInvoiceByOrderId($orderId, $jsonData)
    {
        $order = $this->orderRepository->get($orderId);
        if ($order->canInvoice()) {
            $this->logger->initLog(NetSuiteConstants::NETSUITE_CREATE_SHIPMENT_LOG_FILENAME);
            try {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->register();
                $invoice->save();
                $transactionSave = $this->transaction->addObject($invoice)
                ->addObject($invoice->getOrder());
                $transactionSave->save();
                $this->invoiceSender->send($invoice);

                $invoiceId = $invoice->getId();
                $this->updateInvoiceCustomDetails($invoiceId, $jsonData);

                //Send Invoice mail to customer
                $order->addStatusHistoryComment(
                    __('Notified customer about invoice creation #%1.', $invoice->getId())
                )
                ->setIsCustomerNotified(false)
                ->save();

                $this->logger->writeLog('Invoice created successfully');
            } catch (Exception $e) {
                $this->logger->writeLog('Invoice not created');
            }
        }
    }

    public function getCustomShipQty($jsonData, $sku)
    {
        //$jsonData = (object)$this->json->unserialize($jsonData);
        $jsonData = $this->json->unserialize($jsonData);

        if (!empty($jsonData['items'])) {
            foreach ($jsonData['items'] as $itesmData) {
                $itesmData = (object)$itesmData;

                if ($itesmData->item_sku == $sku) {
                    return $itesmData->item_qty;
                }
            }
        }

        return $itesmData = 0;
    }

    public function updateShipmentCustomDetails($shipmentId, $jsonData)
    {
        //$jsonData = (object)$this->json->unserialize($jsonData);
        $jsonData = $this->json->unserialize($jsonData);

        $connection  = $this->resource->getConnection();
        $data['ns_fulfillment_id'] = $jsonData['ns_fulfillment_id'];
        $data['ns_fulfillment_number'] = $jsonData['ns_fulfillment_number'];
        $data['ns_tracking_number'] = $jsonData['tracking_number'];
        $data['ns_delivery_status'] = $jsonData['delivery_status'];
        $data['ns_shipping_carrier'] = $jsonData['shipping_carrier'];
        $data['ns_shipped_date'] = date("Y-m-d h:i:sa");
        $where = ['entity_id = ?' => (int)$shipmentId];

        /* update sales_shipment_grid */
        $tableName = $connection->getTableName("sales_shipment_grid");
        $connection->update($tableName, $data, $where);

        /* update sales_shipment */
        $tableName = $connection->getTableName("sales_shipment");
        $connection->update($tableName, $data, $where);

        /* update sales_shipment_item */
        $itemarray = $jsonData['items'];
        if (!empty($itemarray)) {
            foreach ($itemarray as $key => $shipitem) {
                $dataitem = [];
                $itemconnection  = $this->resource->getConnection();
                $dataitem['ns_expected_delivery_from_date'] = $shipitem['expected_delivery_from_date'];
                $dataitem['ns_expected_delivery_to_date'] = $shipitem['expected_delivery_to_date'];
                $dataitem['ns_serial_no'] = $shipitem['serial_no'];
                $dataitem['ns_certificate'] = $shipitem['certificate'];
                $itemwhere = ['parent_id = ?' => (int)$shipmentId,'sku = ?' => $shipitem['item_sku']];
                
                $itemtableName = $itemconnection->getTableName("sales_shipment_item");
                $itemconnection->update($itemtableName, $dataitem, $itemwhere);

                $this->logger->writeLog('Shipment Item Sku: '.$shipitem['item_sku']);
                $this->logger->writeLog('Shipment Item Qty: '.$shipitem['item_qty']);
            }
        }
    }

    public function updateInvoiceCustomDetails($invoiceId, $jsonData)
    {
        $jsonData = (object)$this->json->unserialize($jsonData);
        $connection  = $this->resource->getConnection();
        $data['ns_fulfillment_id'] = $jsonData->fulfillment_id;
        $data['ns_fulfillment_number'] = $jsonData->fulfillment_number;
        $where = ['entity_id = ?' => (int)$invoiceId];

        /* update sales_invoice_grid */
        $tableName = $connection->getTableName("sales_invoice_grid");
        $connection->update($tableName, $data, $where);

        /* update sales_invoice */
        $tableName = $connection->getTableName("sales_invoice");
        $connection->update($tableName, $data, $where);
    }

    public function getCreateShipmentByOrderId($orderId, $jsonData)
    {
        $order = $this->orderRepository->get($orderId);

        // check if it's possible to ship the items
        if ($order->canShip()) {
            $this->logger->initLog(NetSuiteConstants::NETSUITE_CREATE_SHIPMENT_LOG_FILENAME);
            $shipment = $this->convertOrder->toShipment($order);
            foreach ($order->getAllItems() as $orderItem) {
                // Check if the order item has Quantity to ship or is virtual
                if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                    continue;
                }

                $getshipCustomQty = $this->getCustomShipQty($jsonData, $orderItem->getSku());

                $qtyShipped = $orderItem->getQtyToShip();
             
                if (($getshipCustomQty > 0 ) && ($getshipCustomQty <= $qtyShipped )) {

                    // Create Shipment Item with Quantity
                    $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($getshipCustomQty);
                    // Add Shipment Item to Shipment
                    $shipment->addItem($shipmentItem);
                }
            }

            $track = $this->setOrderTrackFactoryData($jsonData);
            $shipment->addTrack($track);
            // Register Shipment
            $shipment->register();
            $shipment->getOrder()->setIsInProcess(true);
            try {
                $shipment->getExtensionAttributes()->setSourceCode('default');
                // Save created Shipment and Order
                $shipment->save();
                
                $shipmentId = $shipment->getId();
                $incrementId = $shipment->getIncrementId();
                $shipment->getOrder()->save();
                // send shipping confirmation e-mail to customer
                
                $this->shipmentNotifier->notify($shipment);
                $this->updateShipmentCustomDetails($shipmentId, $jsonData);
                return $incrementId.':'.$shipmentId;

            } catch (\Exception $e) {
                $this->logger->writeLog($e->getMessage());
                $this->logger->writeLog('Fail to created Shipment');
            }
        }
    }

    public function setOrderTrackFactoryData($jsonData)
    {
        
        //$jsonData = (object)$this->json->unserialize($jsonData);
        $jsonData = $this->json->unserialize($jsonData);
        $track = $this->trackFactory->create();
        $track->setTrackNumber($jsonData['tracking_number']);
        $track->setCarrierCode(NetSuiteConstants::NETSUITE_TRACKING_CODE);
        $track->setTitle($jsonData['shipping_carrier']);
        return $track;
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
