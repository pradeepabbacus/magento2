<?php
/**
 * @category   Eighteentech
 * @package    Eighteentech_OrderByPhoneApi
 * @author     https://www.18thdigitech.com/
 */
 
namespace Eighteentech\OrderByPhoneApi\Model;

use Eighteentech\OrderByPhoneApi\Api\OrderManagementInterface;
use Psr\Log\LoggerInterface;

class OrderManagement implements OrderManagementInterface
{
     /**
      * @var Psr\Log\LoggerInterface
      */
    protected $logger;
     /**
      * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
      */
    protected $_orderCollectionFactory;
     /**
      * @var \Magento\Store\Model\StoreManagerInterface
      */
    public $_storeManager;
    
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $request;
    
     /**
      * @param logger $logger
      * @param orderCollectionFactory $orderCollectionFactory
      * @param storeManager $storeManager
      */
    public function __construct(
        LoggerInterface $logger,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Webapi\Rest\Request $request
    ) {
        $this->logger=$logger;
        $this->_storeManager=$storeManager;
        $this->_orderCollectionFactory=$orderCollectionFactory;
        $this->request = $request;
    }

    /**
      * Retrieve loaded order rest api with searchCriteria
    */
    public function getOrderback($telephone)
    {
        try {
            //get the order entity_id  using phone number
            $collection=$this->getOrderCollection($telephone);
            $entity=[];
            foreach ($collection as $orders) {
                   $entity[] =  $orders->getData('entity_id');
            }
            //conver arry to string
            $orderidList = implode(',', $entity);
            $URL=$this->_storeManager->getStore()->getBaseUrl(); // your magento base url
            $tokens = $this->getAuthToken();
            //send api request to get multiple order id
            //header("Access-Control-Allow-Origin: *");
            //header("Content-Type: application/json; charset=UTF-8");
            /*header("Access-Control-Allow-Methods: POST");
            header("Access-Control-Max-Age: 3600");
            header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");*/
            
            // It will return all params which will pass from body of postman.
            $currentPage = $this->request->getParam('currentPage');
            if (!empty($currentPage)) {
                $currentPage;
            } else {
                $currentPage=1;
            }
            $pageSize = $this->request->getParam('pageSize');
            if (!empty($pageSize)) {
                $pageSize;
            } else {
                $pageSize=5;
            }
            
            $ch = curl_init($URL."rest/V1/orders/?searchCriteria[filter_groups][0][filters][0][field]=entity_id&searchCriteria[filter_groups][0][filters][0][value]=$orderidList&searchCriteria[filter_groups][0][filters][0][condition_type]=in&searchCriteria[sortOrders][1][field]=entity_id&searchCriteria[sortOrders][1][direction]=DESC&searchCriteria[currentPage]=$currentPage&searchCriteria[pageSize]=$pageSize");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer " . $tokens]);
            echo   $result = curl_exec($ch);
            exit;
        } catch (\Exception $e) {
            $response=[];
            $response = ['message' => "some thing went wrong", 'trace' => $e->getMessage()];
            $this->logger->critical('Error message', ['exception' =>$e->getMessage()]);
        }
    }
    
    /**
     * Retrieve Order Collection using telephone
     */
    public function getOrderCollection($telephone)
    {
        $telephoneOne = substr($telephone, 3);
        $telephoneTwo = substr($telephone, 2);
        $telephoneThree = substr($telephone, 1);
        
        $collection = $this->_orderCollectionFactory->create()
        /* Joined with `sales_order_address` to get telephone  */
        ->join(
            'sales_order_address',
            'sales_order_address.parent_id=main_table.entity_id',
            'sales_order_address.telephone'
        )
        /* Added `telephone` Filter */
        ->addFieldToFilter(
            'telephone',
            [
                ['eq' => $telephoneOne],
                ['eq' => $telephoneTwo],
                ['eq' => $telephoneThree],
                ['eq' => $telephone]
            ]
        )
        //filter telephone using billing and shipping
        ->addFieldToFilter(
            'address_type',
            [
                ['eq' => 'billing'],
                ['eq' => 'shipping']
            ]
        )
        ->distinct(true);
        return $collection;
    }
    
     /**
      * Retrieve Access Token
      */
    public function getAuthToken()
    {
        $token = false;
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        $authorizationBearer = '';
        if (isset($headers['Authorization'])) {
            $authorizationBearer = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authorizationBearer = $headers['authorization'];
        } else {
            $authorizationBearer = "";
        }
        $authorizationBearerArr = explode(' ', $authorizationBearer);
        if (isset($authorizationBearerArr[0]) &&
         trim($authorizationBearerArr[0]) == 'Bearer' && isset($authorizationBearerArr[1])) {
            $token = $authorizationBearerArr[1];
        }
        return $token;
    }
}

