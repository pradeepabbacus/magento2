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
use Caratlane\NetSuite\Api\UpdateInvoicePdfInterface;
use Caratlane\NetSuite\Model\NetsuiteUpdateinvoicepdfFactory;
use Caratlane\NetSuite\Model\NetSuiteConstants;
use Caratlane\NetSuite\Helper\Logger;
use Caratlane\NetSuite\Helper\Data;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order\Shipment;

class UpdatePdf implements UpdateInvoicePdfInterface
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @var \Magento\Sales\Model\Order\Shipment
     */
    private $ordershipment;

    /**
     * @var \Caratlane\NetSuite\Helper\Logger
     */
    protected $logger;

    /**
     * @var \Caratlane\NetSuite\Model\NetsuiteUpdateinvoicepdfFactory
     */
    private $netsuiteupdateinvoicepdf;

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
     * @param ResourceConnection $resource
     * @param Shipment $ordershipment
     * @param Data $helper
     * @param Json $json
     * @param Logger $logger
     * @param NetsuiteUpdateinvoicepdfFactory $netsuiteupdateinvoicepdf
     * @param Request $request
     */
    
    public function __construct(
        Request $request,
        ResourceConnection $resource,
        Shipment $ordershipment,
        Data $helper,
        Json $json,
        Logger $logger,
        NetsuiteUpdateinvoicepdfFactory $netsuiteupdateinvoicepdf
    ) {
        $this->request = $request;
        $this->resource = $resource;
        $this->ordershipment = $ordershipment;
        $this->helper = $helper;
        $this->json = $json;
        $this->logger = $logger;
        $this->netsuiteupdateinvoicepdf = $netsuiteupdateinvoicepdf;
    }

    public function updateInvoicePdf()
    {
        //Added line on dev
        $responseData = [];
        $responseData['success'] = NetSuiteConstants::NETSUITE_DEFAULT_ERROR_CODE;
        $responseData['message'] = NetSuiteConstants::NETSUITE_DISABLE_MESSAGE;
        
        if ($this->helper->getNetSuiteIsEnabled() && $this->helper->getNetsuiteUpdateInvoicepdfEnabled()) {
 
            $this->logger->initLog(NetSuiteConstants::NETSUITE_CREATE_UPDATEPDF_LOG_FILENAME);
            $this->logger->writeLog('----------------------------');
            $this->logger->writeLog('Update invoice pdf Started');
            $this->logger->writeLog('----------------------------');
            $this->logger->writeLog('');

            $postData = $this->request->getBodyParams();
            
            try {
                if ($postData['magento_shipment_id']) {

                    $this->logger->writeLog('Magento Shipment Id: '.$postData['magento_shipment_id']);

                    $postDataarray = [];
                    $postDataarray['ns_fulfillment_id'] = $postData['ns_fulfillment_id'];
                    $postDataarray['ns_fulfillment_number'] = $postData['ns_fulfillment_number'];
                    $postDataarray['magento_shipment_id'] = $postData['magento_shipment_id'];
                    $postDataarray['invoice_url'] = $postData['invoice_url'];

                    foreach ($postDataarray as $key => $postvalue) {
                        if ($postvalue == "") {
                            $this->logger->writeLog('Please add required fields');
                            $responseData['success'] = false;
                            $responseData['message'] = "Please add required fields";
                            return [$responseData];
                        }
                    }

                    $getshipmentid = $this->getordershipment($postData['magento_shipment_id']);

                    if ($getshipmentid != "") {
                        $netsuiteShipmentsModel = $this->netsuiteupdateinvoicepdf->create();
                        $netsuiteShipmentsModel->setMagentoShipmentId($postData['magento_shipment_id']);
                        $netsuiteShipmentsModel->setFulfillmentId($postData['ns_fulfillment_id']);
                        $netsuiteShipmentsModel->setFulfillmentNumber($postData['ns_fulfillment_number']);
                        $netsuiteShipmentsModel->setInvoiceUrl($postData['invoice_url']);
                        $netsuiteShipmentsModel->setStatus("completed");
                        $jsonData = $this->json->serialize($postData);
                        $netsuiteShipmentsModel->setJsonData($jsonData);
                        $netsuiteShipmentsModel->setMessages("Update Invoice PDF successfully.");
                                    
                        $netsuiteShipmentsModel->save();

                        //update data for shipment table
                        $connection  = $this->resource->getConnection();
                        $data = [];
                        $data['ns_invoice_url'] = $postData['invoice_url'];
                        $where = ['increment_id = ?' => $postData['magento_shipment_id']];

                        /* update sales_shipment_grid */
                        $tableName = $connection->getTableName("sales_shipment_grid");
                        $connection->update($tableName, $data, $where);

                        /* update sales_shipment */
                        $tableName = $connection->getTableName("sales_shipment");
                        $connection->update($tableName, $data, $where);
                            
                        $this->logger->writeLog('Invoice URL updated successfully');
                        $responseData['success'] = true;
                        $responseData['message'] = "Invoice URL updated successfully";
                        $responseData['magento_shipment_id'] = $postData['magento_shipment_id'];

                    } else {
                        $this->logger->writeLog('Shipment id not found in system ='.$postData['magento_shipment_id']);
                        $responseData['success'] = false;
                        $responseData['message'] = "Shipment id not found in system";
                    }

                } else {
                    $this->logger->writeLog('magento_shipment_id field can\'t be empty');
                    $responseData['success'] = false;
                    $responseData['message'] = "magento_shipment_id field can't be empty";
                }

            } catch (Exception $e) {
                $responseData['success'] = false;
                $responseData['message'] = 'Exception : '.$e->getMessage();
                $this->logger->writeLog('Exception: '.$e->getMessage());
            }

            $this->logger->writeLog('');
            $this->logger->writeLog('----------------------------');
            $this->logger->writeLog('Update invoice pdf Stopped');
            $this->logger->writeLog('----------------------------');
        }
        return [$responseData];
    }

    public function getordershipment(string $incrementshipId)
    {
        $shipId = 0;
        try {
            $shipment = $this->ordershipment->loadByIncrementId($incrementshipId);
            $shipId = $shipment->getId();
            return $shipId;
        } catch (Exception $e) {
            return $shipId;
        }
    }
}
