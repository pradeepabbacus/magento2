<?php
namespace Nalli\Weeklyreport\Controller\Index;

class Soldproducts extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;

    protected $formKeyValidator;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Filesystem\DirectoryList $dir
    ) {
        $this->_pageFactory = $pageFactory;
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

        $data = $this->getRequest()->getParams();
        if (!$data['from'] || !$data['to']) {
            print_r("Please enter a from and to date");
            die;
        }
        $start = date('Y-m-d H:i:s', strtotime('-31 days', strtotime($data['to'])));
        $end = $data['to'];

        // print_r($start); die;

        $from = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($start))));
        $to = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($end))));
        $to_end = date('Y-m-d H:i:s', (strtotime('+1 days', strtotime($to) - 1)));

        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "soldproducts.csv";
        $products_row = [];

        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $orders = $objectManager->get('Magento\Sales\Model\Order')->getCollection();
        $orders->getSelect()->joinLeft('ipdetails', 'main_table.entity_id = ipdetails.order_id', ['country_id', 'state']);
        $orders->addAttributeToFilter('created_at', ['from'=>$from, 'to'=>$to_end]);
        $orders->addAttributeToFilter('status', ['processing', 'shipped', 'complete']);


        foreach ($orders as $order) {

            try {

                $items = $order->getAllVisibleItems();
                
                foreach ($items as $item) {

                    $product = $objectManager->get('Magento\Catalog\Model\Product')->load($item->getProductId());

                    $fabricpurity = "";
                    $color = "";
                    $material = "";
                    $category_ids = "";
                    $createdat = "";
                    $pattern = "";
                    $image = "";
                    $article_type = "";
                    $zari_type = "";
                    $border = "";

                    if ($product) {
                        if ($product->getData('fabric_purity')) {
                            $fabricpurity = $product->getResource()->getAttribute('fabric_purity')->getFrontend()->getValue($product);
                        }
                        if ($product->getData('color')) {
                            $color = $product->getResource()->getAttribute('color')->getFrontend()->getValue($product);
                        }
                        if ($product->getData('material')) {
                            $material = $product->getResource()->getAttribute('material')->getFrontend()->getValue($product);
                        }
                        if ($product->getData('pattern')) {
                            $pattern = $product->getResource()->getAttribute('pattern')->getFrontend()->getValue($product);
                        }
                        if ($product->getCategoryIds()) {
                            $category_ids = implode(',', $product->getCategoryIds());
                        }
                        if ($product->getCreatedAt()) {
                            $createdat = $product->getCreatedAt();
                        }
                        if ($product->getArticleType()) {
                            $article_type = $product->getArticleType();
                        }
                        if ($product->getData('zari_type')) {
                            $zari_type = $product->getResource()->getAttribute('zari_type')->getFrontend()->getValue($product);
                        }
                        if ($product->getData('border')) {
                            $border = $product->getResource()->getAttribute('border')->getFrontend()->getValue($product);
                        }
                    }

                    if ($order->getGiftMessageId()) {
                        $hasgiftmsg = "yes";
                    } else {
                        $hasgiftmsg = "no";
                    }

                    $details = [];
                    $details['order_date'] = $order->getCreatedAt();
                    $details['orderid'] = $order->getIncrementId();
                    $details['country_iso2'] = $order->getData('country_id');
                    $details['state'] = $order->getData('state');
                    $details['hasgiftmsg'] = $hasgiftmsg;
                    $details['sku'] = $item->getSku();
                    $details['name'] = $item->getName();
                    $details['category_ids'] = $category_ids;
                    $details['fabric_purity'] = $fabricpurity;
                    $details['color'] = $color;
                    $details['material'] = $material;
                    $details['qty'] = $item->getQtyOrdered();
                    $details['price'] = $item->getBasePrice();
                    $details['created_at'] = $createdat;
                    $details['customer_email'] = $order->getCustomerId();
                    $details['pattern'] = $pattern;
                    $details['category_name'] = $article_type;
                    $details['zari_type'] = $zari_type;
                    $details['border'] = $border;
                    $products_row[] = $details;
                }

            } catch (exception $e) {

            }
        }

        $keys = array_keys($products_row['0']);
        array_unshift($products_row, $keys);
        $mage_csv->saveData($file_path, $products_row);
        $filename = "soldproducts.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);

        die;
    }
}
