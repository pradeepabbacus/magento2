<?php
namespace Nalli\Weeklyreport\Controller\Index;

class Instock extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;

    protected $formKeyValidator;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\CatalogInventory\Helper\Stock $stockFilter,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Filesystem\DirectoryList $dir
    ) {
        $this->_pageFactory = $pageFactory;
        $this->_stockFilter = $stockFilter;
        $this->formKeyValidator = $formKeyValidator;
        $this->_dir = $dir;
        return parent::__construct($context);
    }

    private function validateSession()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        if ($customerSession->isLoggedIn()) {
            $customeremail = $customerSession->getCustomer()->getEmail();
            $allowed_user = $objectManager->create('Nalli\Weeklyreport\Helper\Data')->getConfig('weeklyreport/general/allowed_users');
            $screenusers = array_map('trim', explode(',', $allowed_user));
            if (in_array($customeremail, $screenusers)) {
                return true;
            }
        }
        return false;
    }

    public function execute()
    {
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $this->_redirect('*/*');
        }
        if (!$this->validateSession()) {
            print_r("You do not have access to this report");
            die;
        }
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productCollectionFactory = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
        $productCollection = $productCollectionFactory->create();
        $productCollection->addAttributeToSelect('*');
        $productCollection->addAttributeToFilter('status', 1);
        $productCollection->addAttributeToFilter('visibility', ['4']);
        $this->_stockFilter->addInStockFilterToCollection($productCollection);
        $sku = 'ES';
        $productCollection->addAttributeToFilter('sku', [
                    ['like' => '%'.$sku.'%'], //spaces on each side
                    ['like' => '%'.$sku], //space before and ends with $needle
                    ['like' => $sku.'%'] // starts with needle and space after
        ]);

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


        $subquery = new \Zend_Db_Expr('(SELECT sku, SUM(quantity) as salableqty FROM inventory_reservation GROUP BY sku)');
        $joinConditions = 'e.sku = reservetable.sku';
        $productCollection->getSelect()->joinLeft(
            [ 'reservetable' => $subquery ],
            $joinConditions,
            []
        )->columns("(IFNULL(reservetable.salableqty,0) + stocktable.qty) AS final_qty")
                     ->where("(IFNULL(reservetable.salableqty,0) + stocktable.qty) > 0");

        $productsrow = [];
        
        $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');

        foreach ($productCollection as $product) {
            
            // $stockItem = $product->getExtensionAttributes()->getStockItem();
            
            $indproduct = [];
            // $indproduct['id'] = $product->getId();
            $indproduct['sku'] = $product->getSku();
            $indproduct['name'] = $product->getName();
            $indproduct['category_ids'] = implode(',', $product->getCategoryIds());
            // $indproduct['article_type'] = $product->getArticleType();
            $indproduct['fabric_purity'] = $product->getAttributeText('fabric_purity');
            $indproduct['color'] = $product->getAttributeText('color');
            $indproduct['material'] = $product->getAttributeText('material');
            $indproduct['qty'] = $StockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
            $indproduct['price'] = round($product->getPrice(), 2);
            $indproduct['upload_date'] = $product->getCreatedAt();
            $indproduct['status'] = '';
            $indproduct['is_in_stock'] = '';
            $indproduct['pattern'] = $product->getAttributeText('pattern');
            $indproduct['border'] = $product->getAttributeText('border');
            $indproduct['zari_type'] = $product->getAttributeText('zari_type');
            $indproduct['store_code'] = $product->getAttributeText('store_code');
            // $indproduct['views'] = $product->getNoOfViews();
            // $indproduct['impressions'] = $product->getGaimpressions();
            $productsrow[] = $indproduct;
        }

        $keys = array_keys($productsrow['0']);

        array_unshift($productsrow, $keys);

        // echo "<pre>";
        // print_r($productsrow); die;

        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "instock.csv";
        $mage_csv->saveData($file_path, $productsrow);
        $filename = "instock.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);

        die;
    }
}
