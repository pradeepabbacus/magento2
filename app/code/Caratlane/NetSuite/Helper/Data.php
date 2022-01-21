<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2020 18th DigiTech (https://www.18thdigitech.com)
 * @package Caratlane_NetSuite
 */
namespace Caratlane\NetSuite\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{

    const XML_PATH_NETSUITE_ENABLE   = 'netsuite/general/enable';
    const XML_PATH_OAUTH_ORDER_PUSH_ENABLE   = 'netsuite/cronScheduled/enable';
    const XML_PATH_NETSUITE_COUNT_FROM   = 'netsuite/cronScheduled/netsuite_count_from';
    const XML_PATH_NETSUITE_MAX_ATTEMPT   = 'netsuite/cronScheduled/netsuite_max_attempt';
    const XML_PATH_TRACK_SHIPMENT_ENABLE   = 'netsuite/setTrackShipmentsFrequency/enable';
    const XML_PATH_CREATE_SHIPMENT_ENABLE   = 'netsuite/setCreateShipmentsFrequency/enable';
    const XML_PATH_ORDER_UPDATE_ENABLE   = 'netsuite/setOrdersUpdateFrequency/enable';
    const XML_PATH_UPDATE_INVOICEPDF_ENABLE   = 'netsuite/setUpdateInvoicepdfFrequency/enable';
    const ORDER_STATUS   = 'pending';
    /**
     * @var storeManager
     */
     protected $storeManager;
     
     /**
      * @var scopeConfig
      */
     protected $scopeConfig;
     
     /**
      * @var orderCollectionFactory
      */
     protected $orderCollectionFactory;
     
      /**
       * @var date
       */
     protected $_date;
     
      /**
       * @var customerFactory
       */
     protected $customerFactory;
     
     /**
      * @var curl
      */
     protected $curl;
     
     /**
      * @var \Caratlane\NetSuite\Logger\Logger
      */
    protected $_logger;
    
     /**
      * @var netSuiteFactory
      */
    protected $netSuiteFactory;
    
     /**
      * @var messageFactory
      */
    protected $messageFactory;
     
     /**
      * @param \Magento\Store\Model\StoreManagerInterface $storeManager
      * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
      * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
      * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date
      * @param \Magento\Customer\Model\CustomerFactory $customerFactory
      * @param \Magento\Framework\HTTP\Client\Curl $curl
      * @param \Caratlane\NetSuite\Logger\Logger $logger
      * @param \Caratlane\NetSuite\Model\NetSuiteFactory $netSuiteFactory
      * @param \Caratlane\NetSuite\Model\Oauth $oauth
      * @param \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactions
      * @param \Magento\GiftMessage\Model\MessageFactory $messageFactory
      * @param \Magento\Directory\Model\CountryFactory $countryFactory
      **/
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Caratlane\NetSuite\Logger\Logger $logger,
        \Caratlane\NetSuite\Model\NetSuiteFactory $netSuiteFactory,
        \Caratlane\NetSuite\Model\Oauth $oauth,
        \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactions,
        \Magento\GiftMessage\Model\MessageFactory $messageFactory,
        \StripeIntegration\Payments\Helper\Generic $helper,
        \StripeIntegration\Payments\Helper\Api $api,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->store_manager = $storeManager;
        $this->scope_config = $scopeConfig;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->_date = $date;
        $this->customerFactory = $customerFactory;
        $this->curl = $curl;
        $this->_logger = $logger;
        $this->netSuiteFactory = $netSuiteFactory;
        $this->oauth = $oauth;
        $this->transactions = $transactions;
        $this->messageFactory = $messageFactory;
        $this->helper = $helper;
        $this->api = $api;
        $this->_countryFactory = $countryFactory;
        $this->productRepository = $productRepository;
    }
    
    /**
     * Get NetSuite API URL
     *
     * @return string
     */
    public function getApiUrl()
    {
        return $this->getConfigValue(self::XML_PATH_NETSUITE_API_URL);
    }
    
    /**
     * Get NetSuite
     *
     * @return Int
     */
    public function getNetSuiteIsEnabled()
    {
        return $this->getConfigValue(self::XML_PATH_NETSUITE_ENABLE);
    }
    
     /**
      * Get NetSuite Order push enable
      *
      * @return Int
      */
    public function getNetSuiteOrderPushEnabled()
    {
        return $this->getConfigValue(self::XML_PATH_OAUTH_ORDER_PUSH_ENABLE);
    }

    /**
     * Get NetSuite create shipment enable
     *
     * @return Int
     */

    public function getNetsuiteCreateShipmentEnabled()
    {
        return $this->getConfigValue(self::XML_PATH_CREATE_SHIPMENT_ENABLE);
    }

    /**
     * Get NetSuite track shipment enable
     *
     * @return Int
     */

    public function getNetsuiteTrackSHipmentEnabled()
    {
        return $this->getConfigValue(self::XML_PATH_TRACK_SHIPMENT_ENABLE);
    }

    /**
     * Get NetSuite track Order enable
     *
     * @return Int
     */

    public function getNetsuiteOrdersUpdateEnabled()
    {
        return $this->getConfigValue(self::XML_PATH_ORDER_UPDATE_ENABLE);
    }

    /**
     * Get NetSuite track Order enable
     *
     * @return Int
     */

    public function getNetsuiteUpdateInvoicepdfEnabled()
    {
        return $this->getConfigValue(self::XML_PATH_UPDATE_INVOICEPDF_ENABLE);
    }
    
    /**
     * Get XML_PATH_NETSUITE_MAX_ATTEMPT
     *
     * @return int
     */
    public function getNetSuiteMaxAttempt()
    {
        return $this->getConfigValue(self::XML_PATH_NETSUITE_MAX_ATTEMPT);
    }
    
    /**
     * Get calculate date for order fatch
     *
     * @return date time
     */
    public function getNetSuiteCountFromDate()
    {
        $dayes = $this->getConfigValue(self::XML_PATH_NETSUITE_COUNT_FROM);
        $currenctDate = $this->_date->date('Y-m-d H:i:s');
        return $this->_date->date('Y-m-d H:i:s', (strtotime('-'.$dayes.' day', strtotime($currenctDate))));
    }
    
    /**
     * Get calculate date for order fatch
     *
     * @return date time
     */
    public function getCurrentDate()
    {
        return $this->_date->date('Y-m-d H:i:s');
    }
    
    /**
     * Get NetSuite Config value
     *
     * @return string
     */
    public function getConfigValue($path)
    {
        $store = $this->getStoreId();
        $config_data = $this->scope_config->getValue($path, ScopeInterface::SCOPE_STORE, $store);
        return $config_data;
    }
    
    /**
     * Get store id
     * @return int
     */
    public function getStoreId()
    {
        return $this->store_manager->getStore()->getStoreId();
    }
    
   /**
    * Get order collection
    * @return Collection
    */
    public function getOrderCollection()
    {
        $collection = $this->orderCollectionFactory->create();
        $metSuiteMaxAttempt = $this->getNetSuiteMaxAttempt();
        $startDate = $this->getNetSuiteCountFromDate();
        $endDate = $this->_date->date('Y-m-d H:i:s');
        
        $collection->addAttributeToFilter('created_at', ['from'=>$startDate, 'to'=>$endDate]);
        $collection->getSelect()->where('(push_to_netsuite!=1 or `netsuite_id` IS NULL) and netsuite_max_attempt < '.$metSuiteMaxAttempt .' and status="'. self::ORDER_STATUS .'"');
        return  $collection;
    }
    
   /**
    * set curl data push data
    */
    public function pushCurlData($collection)
    {
        $ordersDetails = [];
        foreach ($collection as $order) {
            $ordersDetails['Customer_Details'] = $this->setCustomerDetails($order);
            $ordersDetails['Order_Details'] = $this->setOrderDetails($order);
            $jsonData = json_encode($ordersDetails);
            $this->curl($jsonData, $order);
        }
    }
    
     /**
      * set customer detail in array
      *
      * @return array
      */
    public function setCustomerDetails($order)
    {
        $customer = $this->customerFactory->create()->load($order->getCustomerId());
        $shippingAdd = $order->getShippingAddress();
        $billingAdd = $order->getBillingAddress();
        $billingStreet = $billingAdd->getStreet();
        $shippingStreet = $shippingAdd->getStreet();
        $billingStreet2 = isset($billingStreet[1])?$billingStreet[1]:'';
        $shippingStreet2 = isset($shippingStreet[1])?$shippingStreet[1]:'';
        return ['CL_Magento_Id'=>$order->getCustomerId(),
          'CL_Customer_FName'=>$order->getCustomerFirstname(),
          'CL_Customer_LName'=>$order->getCustomerLastname(),
          'CL_Customer_Email'=>$order->getCustomerEmail(),
          'CL_Customer_Mobile_No'=>$billingAdd['telephone'],
          'CL_Netsuite_ID'=>$customer->getNetsuiteId(),
          'CL_Billingaddress'=>[
                  [
                    'CL_Billing_isdefault'=>true,
                    'CL_Billing_Addr_Line_1'=>$billingStreet[0],
                    'CL_Billing_Addr_Line_2'=>$billingStreet2,
                    'CL_Billing_Pincode'=>$billingAdd->getPostcode(),
                    'CL_Billing_City'=>$billingAdd->getCity(),
                    'CL_Billing_State'=>$billingAdd->getRegion(),
                    'CL_Billing_Country'=>$this->getCountryName($billingAdd->getCountryId())
                  ]
          ]
          ,
          'CL_Shippingaddress'=>[
              [
                'CL_Shipping_isdefault'=>true,
                'CL_Shipping_Addr_Line_1'=>$shippingStreet[0],
                'CL_Shipping_Addr_Line_2'=>$shippingStreet2,
                'CL_Shipping_Pincode'=>$shippingAdd->getPostcode(),
                'CL_Shipping_City'=>$shippingAdd->getCity(),
                'CL_Shipping_State'=>$shippingAdd->getRegion(),
                'CL_Shipping_Country'=>$this->getCountryName($shippingAdd->getCountryId())
              ]
          ]
        ];
    }
    
 /**
  * set order detail in array
  * @return array
  */
    public function setOrderDetails($order)
    {
        $items = $this->getOrderItems($order);
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $methodTitle = $method->getTitle();
        $methodcode = $method->getCode();
        $message = '';
        if ($order->getGiftMessageId()) {
            $message =  $this->messageFactory->create()->load($order->getGiftMessageId())->getMessage();
        }
        
        $paymentMethodType = 'Cash';
        $cardTypeId = '';
        if ($methodcode=='stripe_payments') {
            $paymentMethodType = 'Stripe';
            $cardTypeId = $this->getBrand($payment); /// card type id
        } elseif ($methodcode=='paypal_express') {
            $paymentMethodType = 'Paypal';
            $cardTypeId = 'Paypal';
        }
        $transactionId = $payment->getLastTransId()?$payment->getLastTransId():'';
        $shippingAmount = (float)$order->getShippingAmount();

        return    [
          "CL_Order_date"=>$this->_date->date('j-F-Y', strtotime($order->getCreatedAt())),
          "CL_Magento_Order_ID"=>$order->getIncrementId(), //order increment id
          "CL_Freight_Charges"=> $shippingAmount, //static zero
          "CL_Freight_Charges_Tax"=> 0, //static zero
          "CL_Freight_Rate_Tax"=> 0, //static zero
          "CL_Customer_Pays"=>$order->getGrandTotal(), //Order grand total
          "CL_Payment_Method"=>$paymentMethodType,//$methodTitle, //Need to define mapping
          "CL_UTR_Number"=>$transactionId, //keep blank
          "CL_Payment_amount"=>$order->getGrandTotal(), //Order grand total
          "CL_NS_Coupon_Discount"=>abs($order->getDiscountAmount()), //coupon discount total
          "CL_Total_Tax"=> $order->getTaxAmount(),
          "CL_Card_Type"=>$cardTypeId,
          "CL_Source_From"=>'Magento',
          "CL_NS_Gift_Message"=>$message, //gift msg need to pass here
          //"CL_Expected_Delivery_date"=>$order->getCreatedAt(), //gift msg need to pass here
          "CL_NS_Items"=> $items
        ];
    }
    
    /**
     * get order items
     *
     * @return array
     */
    public function getOrderItems($order)
    {
        $items = $order->getAllVisibleItems();
        $orderItems = [];
        foreach ($items as $item) {
            $fromdate = $item->getDeliveryFromDate();
             $deliveryFromDate = $fromdate?$this->_date->date('j-F-Y', strtotime($fromdate)):'';
            $todate = $item->getDeliveryToDate();
             $deliveryToDate = $todate?$this->_date->date('j-F-Y', strtotime($todate)):'';
            $itemsProduct = $this->loadMyProduct($item->getSku());
            $orderItems[] = [
                            "CL_NS_SKU"=> $item->getSku(), //product simple sku
                            "CL_NS_ID" => $itemsProduct->getNetsuiteId(),
                            "CL_NS_Qty" => $item->getQtyOrdered(), //ordered qty
                            "CL_NS_Selling_Price" =>$item->getOriginalPrice(),
                            "CL_NS_Strike_Discount" => $item->getOriginalPrice() - $item->getPrice(),
                            //original price - final price = discount
                            'CL_Expected_Delivery_From_Date'=>$deliveryFromDate,
                            'CL_Expected_Delivery_To_Date'=>$deliveryToDate
            ];
        }
        
        return $orderItems;
    }
    
    /*
    *get product
    * @return string
    */
    
    public function loadMyProduct($sku)
    {
        return $this->productRepository->get($sku);
    }
    
     /**
      * spush order detail to netsuite
      *
      */
    public function curl($jsonData, $order)
    {
        $url = $this->oauth->getApiUrl().'?script='.$this->oauth->getScript().'&deploy=1';
        $oauthCredentials = $this->oauth->getOauthParams();
        $requestData = $jsonData;
        $this->_logger->writeLog('Request: '.$requestData);
        
        try {
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->addHeader("Authorization", $oauthCredentials);
            $this->curl->addHeader("Cookie", "NS_ROUTING_VERSION=LAGGING");
            $this->curl->post($url, $jsonData);
            $response = $this->curl->getBody();
            $this->_logger->writeLog('Response: '.$response);
            $response =  json_decode($response, true);
            $this->updatedOrdersCustomerAndNetSuite($requestData, $response, $order);
            
        } catch (\Exception $e) {
            $this->_logger->writeLog("error: ". $e->getMessage(), 'error');
        }
    }
    
    /**
     * update order and customer table and create net entry sales_order table
     *
     */
    public function updatedOrdersCustomerAndNetSuite($requestData, $response, $order)
    {
       
        $cutomerRes = isset($response[0])?$response[0]:'';
        $orderRes = isset($response[1])?$response[1]:'';
        $cMessage = '';
        $oMessage = '';
        $cNsCustId= '';
        $oMagentoId='';
        $oStatus = false;
        $cStatus = false;
        $oNSSalesOrderID='';
        $status = false;
        if (!empty($cutomerRes)) {
            $cOStatus = $cutomerRes['Customer_Response'][0]['Status'];
            if ($cOStatus=='Success') {
                $cStatus = true;
            }
                $cMessage = $cutomerRes['Customer_Response'][0]['Message'];
                $cNsCustId = $cutomerRes['Customer_Response'][0]['NS_CustID'];
        }
        if (!empty($orderRes)) {
            $oOStatus = $orderRes['Order_Response'][0]['Status'];
            if ($oOStatus=='Success') {
                $oStatus = true;
            }
                $oMessage = $orderRes['Order_Response'][0]['Message'];
                $oMagentoId = $orderRes['Order_Response'][0]['Magento_ID'];
                $oNSSalesOrderID = $orderRes['Order_Response'][0]['NS_SalesOrderID'];
            
        }
        if ($oStatus && $cStatus) {
            $status = true;
        }
       
        /************************* update order and customer table  *****************************/
        try {
                $netSuite = $status?1:0;
                $status =$status?1:0;
                $order->setPushToNetsuite($netSuite);
                $message = $cMessage.', '.$oMessage;
                $order->setNetsuiteId($oNSSalesOrderID);
                $customer = $this->customerFactory->create()->load($order->getCustomerId());
                $customer->setNetsuiteId($cNsCustId)->save();
                $maxAttempt = $order->getNetsuiteMaxAttempt() + 1;
                $order->setNetsuiteMaxAttempt($maxAttempt);
                $order->setNetsuiteCreatedAt($this->getCurrentDate());
                $order->save();
                
                /********************* sales grid ***********************/
              
        } catch (exception $e) {
            $this->_logger->writeLog("error: ". $e->getMessage(), 'error');
        }
        /************************* Log entry on netsuite_orders *****************************/
        
        $netSuiteModel = $this->netSuiteFactory->create();
        try {
            
            $netSuiteModel->setOrderId($order->getIncrementId());
            $netSuiteModel->setNsOrderId($oNSSalesOrderID);
            $netSuiteModel->setCustomerId($order->getCustomerId());
            $netSuiteModel->setNsCustomerId($cNsCustId);
            $netSuiteModel->setRequestInfo($requestData);
            $netSuiteModel->setResponseInfo(json_encode($response));
            $netSuiteModel->setMessages(trim($message));
            $netSuiteModel->setStatus($status);
            $netSuiteModel->setCreatedAt($this->getCurrentDate());
            $netSuiteModel->save();
            
        } catch (exception $e) {
            $this->_logger->writeLog("error: ". $e->getMessage(), 'error');
        }
    }
    
    /*
    *get card type
    * return @string
    */
    public function getBrand($payment)
    {
        $card = $this->getCard($payment);

        if (empty($card)) {
            return null;
        }

        return $this->helper->cardType($card->brand);
    }
    
    /*
    *get card type
    * return @string
    */
    public function getCard($payment)
    {
        $charge = $this->getCharge($payment);

        if (empty($charge)) {
            return null;
        }

        if (!empty($charge->source)) {
            if (isset($charge->source->object) && $charge->source->object == 'card') {
                return $charge->source;
            }

            if (isset($charge->source->type) && $charge->source->type == 'three_d_secure') {
                $cardId = $charge->source->three_d_secure->card;
                if (isset($this->cards[$cardId])) {
                    return $this->cards[$cardId];
                }

                $card = new \stdClass();
                $card = $charge->source->three_d_secure;
                $this->cards[$cardId] = $card;

                return $this->cards[$cardId];
            }
        }

        // Payment Methods API
        if (!empty($charge->payment_method_details->card)) {
            return $charge->payment_method_details->card;
        }

        // Sources API
        if (!empty($charge->source->card)) {
            return $charge->source->card;
        }

        return null;
    }
    
    /*
    *get card type
    * return @string
    */
    public function getCharge($payment)
    {
        return $this->charge = $this->retrieveCharge($payment->getLastTransId());
    }
    
    /*
    *get card type
    * return @string
    */
    public function retrieveCharge($chargeId)
    {
        try {
            $token = $this->helper->cleanToken($chargeId);

            return $this->api->retrieveCharge($token);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * get country name
     * return @string
     */
    public function getCountryName($countryCode)
    {
        $country = $this->_countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }
}
