<?php
namespace Nalli\Weeklyreport\Controller\Index;

class Custominstock extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;

    protected $formKeyValidator;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\CatalogInventory\Helper\Stock $stockFilter,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Customer\Model\Session $session,
        \Nalli\Weeklyreport\Helper\Data $helperdata,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->_pageFactory = $pageFactory;
        $this->_stockFilter = $stockFilter;
        $this->formKeyValidator = $formKeyValidator;
        $this->_dir = $dir;
        $this->_customerSession = $session;
        $this->helperdata = $helperdata;
        $this->messageManager = $messageManager;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->stockState = $stockState;
        $this->timezone = $timezone;
        return parent::__construct($context);
    }

    /**
     * check customer session.
     * @return boolean
     */
    
    private function validateSession()
    {
        $customerSession = $this->_customerSession;
        $data = $this->getRequest()->getParams();
        if ($customerSession->isLoggedIn()) {
            $customeremail = $customerSession->getCustomer()->getEmail();
            $allowed_user = $this->helperdata->getConfig('weeklyreport/general/allowed_users');
            $screenusers = array_map('trim', explode(',', $allowed_user));
            if (isset($data['international']) && $customeremail=="stalin@nalli.com") {
                return true;
            }
            if (in_array($customeremail, $screenusers)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Assign Price Bucket.
     * @param $price
     * @return int
     */
    protected function assignpricebuckets($price)
    {
        $pricebucket = '';
        if ($price) {
            if ($price > 0 && $price <= 2000) {
                $pricebucket = "0 - 2000";
            } elseif ($price > 2000 && $price <= 4000) {
                $pricebucket = "2000 - 4000";
            } elseif ($price > 4000 && $price <= 6000) {
                $pricebucket = "4000 - 6000";
            } elseif ($price > 6000 && $price <= 8000) {
                $pricebucket = "6000 - 8000";
            } elseif ($price > 8000 && $price <= 10000) {
                $pricebucket = "8000 - 10000";
            } elseif ($price > 10000 && $price <= 12500) {
                $pricebucket = "10000 - 12500";
            } elseif ($price > 12500 && $price <= 15000) {
                $pricebucket = "12500 - 15000";
            } elseif ($price > 15000 && $price <= 20000) {
                $pricebucket = "15000 - 20000";
            } elseif ($price > 20000 && $price <= 30000) {
                $pricebucket = "20000 - 30000";
            } elseif ($price > 30000 && $price <= 50000) {
                $pricebucket = "30000 - 50000";
            } elseif ($price > 50000) {
                $pricebucket = "50000+";
            }
        }
        return $pricebucket;
    }

    /**
     * Reads the specified number of bytes from the current position.
     *
     * @return string
     * @throws Exception
     */
    public function execute()
    {

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $this->_redirect('*/*');
        }
        if (!$this->validateSession()) {
            $message = __('You do not have access to this report');
            $this->messageManager->addErrorMessage($message);
            return $this->_redirect('*/*');
        }
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToSelect('*');
            $productCollection->addAttributeToFilter('status', 1);
            $this->_stockFilter->addInStockFilterToCollection($productCollection);
            $data = $this->getRequest()->getParams();
            if (isset($data['international'])) {
                $productCollection->addAttributeToFilter('international', ['eq' => 1]);
            } else {
                $productCollection->addAttributeToFilter('visibility', ['4']);
                $sku = 'ES';
                $productCollection->addAttributeToFilter('sku', [
                            ['like' => '%'.$sku.'%'], //spaces on each side
                            ['like' => '%'.$sku], //space before and ends with $needle
                            ['like' => $sku.'%'] // starts with needle and space after
                ]);
            }
            // $productCollection->joinField(
                // 'qty',
                // 'cataloginventory/stock_item',
                // 'qty',
                // 'product_id=entity_id',
                // '{{table}}.stock_id=1',
                // 'left'
            // );
            
            $productCollection->getSelect()->joinLeft(
                [ 'stocktable' => 'cataloginventory_stock_status' ],
                'e.entity_id = stocktable.product_id',
                []
            );
            $subquery = new \Zend_Db_Expr(
                '(SELECT sku, SUM(quantity) as salableqty 
                FROM inventory_reservation GROUP BY sku)'
            );
            $joinConditions = 'e.sku = reservetable.sku';
            $productCollection->getSelect()->joinLeft(
                [ 'reservetable' => $subquery ],
                $joinConditions,
                []
            )->columns("(IFNULL(reservetable.salableqty,0) + stocktable.qty) AS final_qty")
                         ->where("(IFNULL(reservetable.salableqty,0) + stocktable.qty) > 0");

            $productsrow = [];
            
            // $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
            if (isset($data['international'])) {
                foreach ($productCollection as $product) {
                    $indproduct = [];
                    $indproduct['id'] = $product->getId();
                    $indproduct['sku'] = $product->getSku();
                    $indproduct['Store'] = $product->getAttributeText('international_store_filter');
                    $indproduct['enabled']=1;
                    $productsrow[] = $indproduct;
                }
            } else {
                foreach ($productCollection as $product) {
                    $indproduct = [];
                    $indproduct['id'] = $product->getId();
                    $indproduct['sku'] = $product->getSku();
                    $indproduct['name'] = $product->getName();
                    $indproduct['category_ids'] = implode(',', $product->getCategoryIds());
                    $indproduct['category'] = $product->getArticleType();
                    $indproduct['fabric_purity'] = $product->getAttributeText('fabric_purity');
                    $indproduct['color'] = $product->getAttributeText('color');
                    $indproduct['material'] = $product->getAttributeText('material');
                  // $indproduct['qty'] = round($product->getQty());
                    $indproduct['qty'] = $this->stockState->getStockQty(
                        $product->getId(),
                        $product->getStore()->getWebsiteId()
                    );
                    $indproduct['price'] = round($product->getPrice(), 2);
                    $upload_date = $product->getMagentooneUpload() ?
                    $this->timezone->formatDate(
                        $product->getMagentooneUpload(),
                        \IntlDateFormatter::SHORT,
                        false
                    ) :
                    $this->timezone->formatDate(
                        $product->getCreatedAt(),
                        \IntlDateFormatter::SHORT,
                        false
                    );
                    $indproduct['upload_date'] = $upload_date;
                    $indproduct['pattern'] = $product->getAttributeText('pattern');
                    $indproduct['border'] = $product->getAttributeText('border');
                    $indproduct['zari_type'] = $product->getAttributeText('zari_type');
                    $indproduct['store_code'] = $product->getAttributeText('store_code');
                    $indproduct['age'] = round((strtotime(date('Y-m-d')) - strtotime($upload_date)) / 86400);
                    $atc_count = $product->getAtcCount() + $product->getMagentooneAtc();
                    $views = $product->getNoOfViews() + $product->getMagentooneViews();
                    $impressions = $product->getTotalImpressions() + $product->getMagentooneTotalimpressions();
                    $indproduct['atc_count'] = $atc_count;
                    $indproduct['views'] = $views;
                    $indproduct['impressions'] = $impressions;
                    if ($views > 0 && $impressions > 0) {
                        $indproduct['views_per_1000_impressions'] = round(($views / $impressions) * 1000, 2);
                    } else {
                        $indproduct['views_per_1000_impressions'] = 0;
                    }
                    $indproduct['pricebucket'] = $this->assignpricebuckets($product->getPrice());
                    $indproduct['counter'] = $this->helperdata->mapcounters(
                        $product->getCategoryIds(),
                        $product->getAttributeText('pattern'),
                        $product->getAttributeText('material'),
                        $product->getAttributeText('zari_type'),
                        $product->getAttributeText('border')
                    );
                    $indproduct['be_code'] = $product->getBeCode();
                    $indproduct['supplier_code'] = $product->getSupplierCode();
                    $indproduct['occasion'] = $product->getAttributeText('occasion');
                    $indproduct['consignment_id'] = $product->getConsignmentId();
                    $indproduct['zari_color'] = $product->getAttributeText('zari_color');
                  //$indproduct['Primary 1 Color Family'] = $product->getAttributeText('primary1colorfamily');
                  // echo "<pre>";
                  // print_r($indproduct); die;
                    $productsrow[] = $indproduct;
                }
            }
            $keys = array_keys($productsrow['0']);
            array_unshift($productsrow, $keys);

            $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
            $file_path = "custominstock.csv";
            $mage_csv->saveData($file_path, $productsrow);
            $filename = "custominstock.csv";
            header('Content-Disposition: attachment; filename='.$filename);
            header('Content-Type: application/csv');
            header('Pragma: no-cache');
            readfile($file_path);
            return $this->_redirect('*/*');
            die;
        } catch (exception $e) {
            $message = $e->getMessage();
            $this->messageManager->addErrorMessage($message);
            return $this->_redirect('*/*');
        }
    }
}
