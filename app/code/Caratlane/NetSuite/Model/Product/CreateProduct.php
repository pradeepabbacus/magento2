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

namespace Caratlane\NetSuite\Model\Product;

use Magento\Framework\Webapi\Rest\Request;
use Caratlane\NetSuite\Api\CreateProductInterface;
use Caratlane\NetSuite\Model\NetsuiteUpdateinvoicepdfFactory;
use Caratlane\NetSuite\Model\NetSuiteConstants;
use Caratlane\NetSuite\Helper\Logger;
use Caratlane\NetSuite\Helper\Data;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order\Shipment;

class CreateProduct implements CreateProductInterface
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

    public function createProduct()
    {
        if ($this->helper->getNetSuiteIsEnabled()) {
 
            $this->logger->initLog(NetSuiteConstants::NETSUITE_CREATE_UPDATEPDF_LOG_FILENAME);
            $this->logger->writeLog('----------------------------');
            $this->logger->writeLog('Create Product Started');
            $this->logger->writeLog('----------------------------');
            $this->logger->writeLog('');

            $postData = $this->request->getBodyParams();
            
            try {
                if ($postData['sku']) {

                    $this->logger->writeLog('Product Sku: '.$postData['sku']);

                    if (!$postData['sku'] || !$postData['name'] || !$postData['price']) {

                        $this->logger->writeLog('Please add required fields');
                        $responseData['success'] = false;
                        $responseData['message'] = "Please add required fields";
                        return [$responseData];
                    }

                } else {
                    $this->logger->writeLog('Sku field can\'t be empty');
                    $responseData['success'] = false;
                    $responseData['message'] = "Sku field can't be empty";
                }

            } catch (Exception $e) {
                $responseData['success'] = false;
                $responseData['message'] = 'Exception : '.$e->getMessage();
                $this->logger->writeLog('Exception: '.$e->getMessage());
            }

            $this->logger->writeLog('');
            $this->logger->writeLog('----------------------------');
            $this->logger->writeLog('Create Product Stopped');
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
