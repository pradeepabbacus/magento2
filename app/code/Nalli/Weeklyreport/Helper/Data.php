<?php
namespace Nalli\Weeklyreport\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class Data extends AbstractHelper
{

    /**
     * @var directory
     */
    protected $directory;

    /**
     * Data constructor.
     * @param Context $context
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        \Magento\CatalogInventory\Helper\Stock $stockFilter
    ) {
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->_stockFilter = $stockFilter;
        parent::__construct($context);
    }

    public function getConfig($config_path)
    {
          return $this->scopeConfig->getValue(
              $config_path,
              \Magento\Store\Model\ScopeInterface::SCOPE_STORE
          );
    }
   
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

    public function mapcounters($category_ids, $pattern, $material, $zaritype, $border)
    {
        $countername = '';
        $cat_kanchipuram = ['5','6','7','8'];
        $cat_banaras = ['9','10','11','12','13','14','15','16','17','18'];
        $cat_cotton = ['19','20','21','22','23','24','25','26','27','28','29','30'];
        $cat_crepe = ['52','53','54'];
        $cat_chiffon = '51';
        $cat_cmk = ['45','46','65'];
        $cat_georgette = '50';
        $cat_bangalore = ['58','59'];
        $cat_tussar = ['31','32','33','34','35','36'];
        $cat_coimbatore = '60';
        // $cat_silkcotton = array('143','23','147','27','146','287','381','470');
        $cat_silkcotton = ['24','47','48','49','420'];
        $cat_lko = ['55','64','63','420'];
        $cat_raw = '62';
        $cat_ikats = ['38','39','40'];
        $cat_region = ['43','44','41','42'];
        $cat_jute = '116';
        $artslk=['419'];
        $dhoti=['455','456'];
        $pattern_j = "Jacquard";
        $pattern_bb = ["Butta", "Thread Butta", "Zari and Thread Butta", "Zari Butta", "Brocade", "Thread Brocade", "Zari Brocade"];
        $pattern_pcsh = ["Plain Body", "Checks", "Stripes", "Half and Half", "Veldhari"];
        $pattern_jbb = ["Jacquard", "Butta", "Thread Butta", "Zari and Thread Butta", "Zari Butta", "Brocade", "Thread Brocade", "Zari Brocade", "Ikat"];
        
        if (count(array_intersect($cat_kanchipuram, $category_ids)) > 0 && $pattern == $pattern_j && $material != "Cotton" && ($zaritype == "Half-Fine Zari" || $zaritype == '') && $border != "Without Border") {
            $countername = "Kanchipuram Jacquard - Half Fine/Without Zari";
        } elseif (count(array_intersect($cat_kanchipuram, $category_ids)) > 0 && in_array($pattern, $pattern_bb) && $material != "Cotton" && ($zaritype == "Half-Fine Zari" || $zaritype == '') && $border != "Without Border") {
            $countername = "Kanchipuram Butta and Brocade - Half Fine/Without Zari";
        } elseif (count(array_intersect($cat_kanchipuram, $category_ids)) > 0 && in_array($pattern, $pattern_pcsh) && $material != "Cotton" && ($zaritype == "Half-Fine Zari" || $zaritype == '') && $border != "Without Border") {
            $countername = "Kanchipuram Plain Body, Koadu Kattam and Half & Half - Half Fine/Without Zari";
        } elseif (count(array_intersect($cat_kanchipuram, $category_ids)) > 0 && in_array($pattern, $pattern_jbb) && $material != "Cotton" && $zaritype == "Pure Zari" && $border != "Without Border") {
            $countername = "Kanchipuram Jacquard, Butta and Brocade - Pure Zari";
        } elseif (count(array_intersect($cat_kanchipuram, $category_ids)) > 0 && in_array($pattern, $pattern_pcsh) && $material != "Cotton" && $zaritype == "Pure Zari" && $border != "Without Border") {
            $countername = "Kanchipuram Plain Body, Koadu Kattam and Half & Half - Pure Zari";
        } elseif (count(array_intersect($cat_kanchipuram, $category_ids)) > 0 && $material != "Cotton" && $border == "Without Border") {
            $countername = "Kanchipuram Without Border";
        } elseif (count(array_intersect($cat_cotton, $category_ids)) > 0 || $material == "Cotton Silk") {
            $countername = "Cotton (All Cottons but not Linen or Silk Cottons)";
        } elseif (count(array_intersect($cat_banaras, $category_ids)) > 0 && $material != "Cotton") {
            $countername = "Banarasi Silks (Includes all subcategories)";
        } elseif (count(array_intersect($cat_crepe, $category_ids)) > 0) {
            $countername = "Crepe";
        } elseif (in_array($cat_chiffon, $category_ids) && count(array_intersect($cat_banaras, $category_ids)) == 0) {
            $countername = "Chiffon (Excludes Banaras Chiffons)";
        } elseif (count(array_intersect($cat_cmk, $category_ids)) > 0 && $material == "Silk") {
            $countername = "Chanderi, Maheshwari and Kota (Silk)";
        } elseif (in_array($cat_georgette, $category_ids) && count(array_intersect($cat_banaras, $category_ids)) == 0) {
            $countername = "Georgette (Excludes Banaras Georgette)";
        } elseif (count(array_intersect($cat_bangalore, $category_ids)) > 0 && $material == "Silk") {
            $countername = "Bangalore Silk";
        } elseif (count(array_intersect($cat_tussar, $category_ids)) > 0 && ($material == "Silk" || $material == "Tussar") && count(array_intersect($cat_banaras, $category_ids)) == 0) {
            $countername = "Tussar Silk";
        } elseif (in_array($cat_coimbatore, $category_ids) && $material == "Silk") {
            $countername = "Soft Silk";
        } elseif (count(array_intersect($cat_silkcotton, $category_ids)) > 0 && $material == "Silk Cotton") {
            $countername = "Silk Cotton";
        } elseif (count(array_intersect($cat_lko, $category_ids)) > 0 && count(array_intersect($cat_banaras, $category_ids)) == 0) {
            $countername = "Linen, Kora and Organza (Excluding Banaras Kora)";
        } elseif (in_array($cat_raw, $category_ids) && count(array_intersect($cat_banaras, $category_ids)) == 0) {
            $countername = "Raw Silk (Excluding Banaras Raw Silk)";
        } elseif (count(array_intersect($cat_ikats, $category_ids)) > 0 && $material != "Cotton") {
            $countername = "Ikats (Pochampally, Pattola, Orissa)";
        } elseif (count(array_intersect($cat_region, $category_ids)) > 0 && $material == "Silk") {
            $countername = "Regional weaves (Baluchari, Gadwal, Uppada, Paithani - Silks only)";
        } elseif (in_array($cat_jute, $category_ids) && $material != "Cotton" && count(array_intersect($cat_banaras, $category_ids)) == 0) {
            $countername = "Jute (Excludes Banaras Jute)";
        } elseif (count(array_intersect($artslk, $category_ids)) > 0) {
            $countername = "Art Silk";
        } elseif (count(array_intersect($dhoti, $category_ids)) > 0) {
            $countername = "Dhoti";
        }

        return $countername;
    }
    
    public function customsoldproducts($start, $end)
    {
        
        $from = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($start))));
        $to = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($end))));
        $to_end = date('Y-m-d H:i:s', (strtotime('+1 days', strtotime($to) - 1)));

        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "customsoldproducts.csv";
        $products_row = [];

        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $orders = $objectManager->get('Magento\Sales\Model\Order')->getCollection();
        $orders->getSelect()->joinLeft('ipdetails', 'main_table.entity_id = ipdetails.order_id', ['country_id', 'state', 'country', 'city']);
        $orders->getSelect()->group('entity_id');
        $orders->addAttributeToFilter('created_at', ['from'=>$from, 'to'=>$to_end]);
        $orders->addAttributeToFilter('status', ['processing', 'shipped', 'complete']);
        
        foreach ($orders as $order) {

            try {
                $items = $order->getAllVisibleItems();
                foreach ($items as $item) {

                    $product = $objectManager->create('Magento\Catalog\Model\Product')->load($item->getProductId());

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
                        
                        if ($product->getArticleType()) {
                            $article_type = $product->getArticleType();
                        }
                        if ($product->getData('zari_type')) {
                            $zari_type = $product->getResource()->getAttribute('zari_type')->getFrontend()->getValue($product);
                        }
                        if ($product->getData('border')) {
                            $border = $product->getResource()->getAttribute('border')->getFrontend()->getValue($product);
                        }
                        
                        $upload_date = $product->getMagentooneUpload() ? $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($product->getMagentooneUpload(), \IntlDateFormatter::SHORT, false) : $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($product->getCreatedAt(), \IntlDateFormatter::SHORT, false);
                    }
                    if ($order->getGiftMessageId()) {
                        $hasgiftmsg = "yes";
                    } else {
                        $hasgiftmsg = "no";
                    }

                    $details = [];
                    $details['order_date'] = $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($order->getCreatedAt(), \IntlDateFormatter::SHORT, false);
                    $details['orderid'] = $order->getIncrementId();
                    $details['hasgiftmsg'] = $hasgiftmsg;
                    $details['product_id'] = $item->getProductId();
                    $details['sku'] = $item->getSku();
                    $details['name'] = $item->getName();
                    $details['category_ids'] = $category_ids;
                    $details['fabric_purity'] = $fabricpurity;
                    $details['color'] = $color;
                    $details['material'] = $material;
                    $details['qty'] = $item->getQtyOrdered();
                    $details['price'] = $item->getBasePrice();
                    $details['created_at'] = $upload_date;
                    $details['customer_email'] = $order->getCustomerEmail();
                    $details['pattern'] = $pattern;
                    $details['category_name'] = $article_type;
                    $details['zari_type'] = $zari_type;
                    $details['border'] = $border;
                    
                    $details['store_code'] = $product->getAttributeText('store_code');
                    $details['sold_age'] = round((strtotime($order->getCreatedAt()) - strtotime($upload_date)) / 86400);
                    
                    
                    $atc_count = $product->getAtcCount() + $product->getMagentooneAtc();
                    $views = $product->getNoOfViews() + $product->getMagentooneViews();
                    $impressions = $product->getTotalImpressions() + $product->getMagentooneTotalimpressions();
                    
                    $details['atc_count'] = $atc_count;
                    $details['views'] = $views;
                    $details['impressions'] = $impressions;
                    if ($views > 0 && $impressions > 0) {
                        $details['views_per_1000_impressions'] = round(($views / $impressions) * 1000, 2);
                    } else {
                        $details['views_per_1000_impressions'] = 0;
                    }
                    
                    
                    
                    $details['pricebucket'] = $this->assignpricebuckets($item->getBasePrice());
                    
                    $details['counter'] = $this->mapcounters($product->getCategoryIds(), $product->getAttributeText('pattern'), $product->getAttributeText('material'), $product->getAttributeText('zari_type'), $product->getAttributeText('border'));
                    
                    $details['billing_country'] = $objectManager->create('\Magento\Directory\Model\Country')->load($order->getBillingAddress()->getCountryId())->getName();
                    $details['billing_state'] = $order->getBillingAddress()->getRegion();
                    $details['billing_city'] = $order->getBillingAddress()->getCity();
                    $details['billing_country_iso2'] = $order->getBillingAddress()->getCountryId();
                    
                    $details['shipping_country'] = $objectManager->create('\Magento\Directory\Model\Country')->load($order->getShippingAddress()->getCountryId())->getName();
                    $details['shipping_state'] = $order->getShippingAddress()->getRegion();
                    $details['shipping_city'] = $order->getShippingAddress()->getCity();
                    $details['shipping_country_iso2'] = $order->getShippingAddress()->getCountryId();
                    
                    $details['ip_country'] = $order->getData('country');
                    $details['ip_state'] = $order->getData('state');
                    $details['ip_state'] = $order->getData('city');
                    $details['ip_country_iso2'] = $order->getData('country_id');
                    
                    $details['be_code'] = $product->getBeCode();
                    
                    $details['store_code'] = $product->getAttributeText('store_code');
                    $details['supplier_code'] = $product->getSupplierCode();
                    $details['consignment_id'] = $product->getConsignmentId();
                    
                    $details['zari_color'] = $product->getAttributeText('zari_color');
                    $details['Primary 1 Color Family'] = $product->getAttributeText('primary1colorfamily');
                    $products_row[] = $details;

                }

            } catch (exception $e) {
                $e->getMessage();
            }
        }
        
        $keys = array_keys($products_row['0']);
        array_unshift($products_row, $keys);

        $mage_csv->saveData($file_path, $products_row);
        $filename = "customsoldproducts.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);

        die;
    }
    
    public function ordergiftmsg($start, $end)
    {
        
        $from = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($start))));
        $to = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($end))));
        $to_end = date('Y-m-d H:i:s', (strtotime('+1 days', strtotime($to) - 1)));

        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "ordergiftmsg.csv";
        $products_row = [];

        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $orders = $objectManager->get('Magento\Sales\Model\Order')->getCollection();
        $orders->getSelect()->joinLeft('ipdetails', 'main_table.entity_id = ipdetails.order_id', ['country_id', 'state', 'country', 'city']);
        $orders->getSelect()->group('entity_id');
        $orders->getSelect()->joinLeft('ordercount', 'main_table.increment_id = ordercount.increment_id', ['count']);
        $orders->getSelect()->joinLeft('ordersurvey', 'main_table.increment_id = ordersurvey.orderid', ['rating_score']);
        $orders->addAttributeToFilter('created_at', ['from'=>$from, 'to'=>$to_end]);
        $orders->addAttributeToFilter('status', ['processing', 'shipped', 'complete']);
        
        $counter = 1;
        
        foreach ($orders as $order) {
            
            try {
                if ($order->getGiftMessageId()) {
                    $hasgiftmsg = "yes";
                    $giftmsg = $objectManager->get('\Magento\GiftMessage\Model\Message')->load($order->getGiftMessageId());
                    $gift_sender = $giftmsg->getData('sender');
                    $gift_recipient = $giftmsg->getData('recipient');
                    $gift_message = $giftmsg->getData('message');
                    // $gift_sender = "";
                    // $gift_recipient = "";
                    // $gift_message = "";
                } else {
                    $hasgiftmsg = "no";
                    $gift_sender = "";
                    $gift_recipient = "";
                    $gift_message = "";
                }
                
                $billstreet = $order->getBillingAddress()->getStreet();
                $shipstreet = $order->getShippingAddress()->getStreet();

                $productcount = [];
                $productprice = [];
                $noncustom = ['ES', 'ET', 'GV', 'EV'];
                $productcount['ES'] = 0;
                $productcount['ET'] = 0;
                $productcount['GV'] = 0;
                $productcount['EV'] = 0;
                $productcount['custom'] = 0;
                $productprice['ES'] = 0;
                $productprice['ET'] = 0;
                $productprice['GV'] = 0;
                $productprice['EV'] = 0;
                $productprice['custom'] = 0;
                foreach ($order->getAllVisibleItems() as $fields) {
                    $cat = substr($fields->getSku(), 0, 2);
                    if (in_array($cat, $noncustom)) {
                        $productcount[$cat] += $fields->getQtyOrdered();
                        $productprice[$cat] += $fields->getBasePrice() * $fields->getQtyOrdered();
                    } else {
                        $productcount['custom'] += $fields->getQtyOrdered();
                        $productprice['custom'] += $fields->getBasePrice() * $fields->getQtyOrdered();
                    }
                    
                }
                
                $customertype = "";
                
                if ($order->getData('count') == 1) {
                    $customertype = "New User";
                } elseif ($order->getData('count') > 1) {
                    $customertype = "Repeat User";
                }
                
                $ordershipments = [];
                if ($order->hasShipments()) {

                    $tracksCollection = $order->getTracksCollection();
                
                    foreach ($tracksCollection as $track) {
                        $ordershipments[] = $track->getTitle() ."-" .$track->getTrackNumber();
                    }
                }
                
                $details = [];
                $details['slno'] = $counter;
                $details['orderid'] = $order->getIncrementId();
                $details['status'] = $order->getStatus();
                $details['order_date'] = $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($order->getCreatedAt(), \IntlDateFormatter::SHORT, false);
                $details['order_time'] = $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($order->getCreatedAt(), \IntlDateFormatter::SHORT, true);
                $details['hasgiftmsg'] = $hasgiftmsg;
                $details['gift_sender'] = $gift_sender;
                $details['gift_recipient'] = $gift_recipient;
                $details['gift_message'] = $gift_message;
                $details['customer_id'] = $order->getCustomerId();
                $details['customer_email'] = $order->getCustomerEmail();
                $details['customer_type'] = $customertype;
                $details['sareecount'] = $productcount['ES'] ? $productcount['ES'] : '0';
                $details['etnischecount'] = $productcount['ET'] ? $productcount['ET'] : '0';
                $details['gvcount'] = $productcount['GV'] ? $productcount['GV'] : '0';
                $details['dothicount'] = $productcount['EV'] ? $productcount['EV'] : '0';
                $details['customcount'] = $productcount['custom'] ? $productcount['custom'] : '0';
                $details['basketsize'] = $order->getData('total_item_count');
                $details['saree_revenue'] = $productprice['ES'] ? $productprice['ES'] : '0';
                $details['etnische_revenue'] = $productprice['ET'] ? $productprice['ET'] : '0';
                $details['gv_revenue'] = $productprice['GV'] ? $productprice['GV'] : '0';
                $details['dothi_revenue'] = $productprice['EV'] ? $productprice['EV'] : '0';
                $details['custom_revenue'] = $productprice['custom'] ? $productprice['custom'] : '0';
                $details['subtotal'] = $order->getBaseSubtotal();
                $details['shipping'] = $order->getBaseShippingInclTax();
                $details['overpriceshipping'] = $order->getBaseFee();
                $details['grand_total'] = $order->getBaseGrandTotal();
                $details['billingcountry'] = $order->getBillingAddress()->getCountryId();
                $details['billingstate'] = $order->getBillingAddress()->getRegion();
                $details['billingcity'] = $order->getBillingAddress()->getCity();
                $details['billingpostcode'] = $order->getBillingAddress()->getPostcode();
                $details['billingstreet1'] = $billstreet['0'];
                $details['billingstreet2'] = '';
                $details['billingtelephone'] = str_replace('+', '', $order->getBillingAddress()->getTelephone());
                // $details['billingtelephone'] = '';
                $details['shippingcountry'] = $order->getShippingAddress()->getCountryId();
                $details['shippingstate'] = $order->getShippingAddress()->getRegion();
                $details['shippingcity'] = $order->getShippingAddress()->getCity();
                $details['shippingpostcode'] = $order->getShippingAddress()->getPostcode();
                $details['shippingstreet1'] = $shipstreet['0'];
                $details['shippingstreet2'] = '';
                $details['shippingtelephone'] = str_replace('+', '', $order->getShippingAddress()->getTelephone());
                // $details['shippingtelephone'] = '';
                
                $details['remote_ip'] = $order->getRemoteIp();
                $details['weight'] = $order->getWeight();
                $details['country'] = $order->getData('country');
                $details['state'] = $order->getData('state');
                $details['city'] = $order->getData('city');
                $details['country_id'] = $order->getData('country_id');
                
                $details['awb'] = implode('|', $ordershipments);
                $details['nps_rating'] = $order->getData('rating_score');
                

                $products_row[] = $details;
                // print_r($details); die;

                $counter++;
            
            } catch (exception $e) {
                print_r($e->getMessage());
                die;
            }
            
        }
        $keys = [];
        if(!empty($products_row)){
            $keys = array_keys($products_row['0']);
        }
        array_unshift($products_row, $keys);

        $mage_csv->saveData($file_path, $products_row);
        $filename = "ordergiftmsg.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);

        die;
    }
    
    
    public function customuploads($start, $end)
    {
        try {
        
            $from = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($start))));
            $to = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($end))));
            $to_end = date('Y-m-d H:i:s', (strtotime('+1 days', strtotime($to) - 1)));

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            // $productCollectionFactory = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
            // $productCollectionFactory = $objectManager->get('\Magento\Catalog\Model\ProductFactory');
            // $productCollection = $productCollectionFactory->create()->getCollection();
            // $productCollection->addAttributeToSelect('*');
            // $productCollection->addAttributeToFilter('created_at', array('from'=>$from, 'to'=>$to_end));
            // $sku = 'ES';
            // $productCollection->addAttributeToFilter('sku', array(
                        // array('like' => '%'.$sku.'%'), //spaces on each side
                        // array('like' => '%'.$sku), //space before and ends with needle
                        // array('like' => $sku.'%') // starts with needle and space after
            // ));
            
            // print_r($productCollection->getSelect()->__toString()); die;
            
            
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            
            //$productsql = "SELECT `e`.* FROM `catalog_product_entity` AS `e` WHERE (`e`.`created_at` >= '".$from."' AND `e`.`created_at` <= '".$to_end."') AND (((`e`.`sku` LIKE '%ES%') OR (`e`.`sku` LIKE '%ES') OR (`e`.`sku` LIKE 'ES%')))";
            $productsql = "SELECT `e`.* FROM `catalog_product_entity` AS `e` WHERE (`e`.`created_at` >= '".$from."' AND `e`.`created_at` <= '".$to_end."')";

            $productlist = $connection->fetchAll($productsql);

            $productsrow = [];
            
            // $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');

            if (count($productlist)) {

                foreach ($productlist as $item) {
                    
                    $product = $objectManager->create('Magento\Catalog\Model\Product')->load($item['entity_id']);
                    
                    if ($product) {
                    
                        $stockItem = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem($product->getId());
                        
                        $indproduct = [];
                        $indproduct['id'] = $product->getId();
                        $indproduct['sku'] = $product->getSku();
                        $indproduct['name'] = $product->getName();
                        $indproduct['category_ids'] = implode(',', $product->getCategoryIds());
                        $indproduct['category'] = $product->getArticleType();
                        $indproduct['fabric_purity'] = $product->getAttributeText('fabric_purity');
                        $indproduct['color'] = $product->getAttributeText('color');
                        $indproduct['material'] = $product->getAttributeText('material');
                        $indproduct['qty'] = $stockItem->getQty();
                        $indproduct['price'] = round($product->getPrice(), 2);
                        
                        $upload_date = $product->getMagentooneUpload() ? $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($product->getMagentooneUpload(), \IntlDateFormatter::SHORT, false) : $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($product->getCreatedAt(), \IntlDateFormatter::SHORT, false);
                
                        $indproduct['upload_date'] = $upload_date;
                        
                        $indproduct['status'] = $product->getStatus();
                        $indproduct['is_in_stock'] = $stockItem->getIsInStock() ? $stockItem->getIsInStock() : 0;
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
                        $indproduct['counter'] = $this->mapcounters($product->getCategoryIds(), $product->getAttributeText('pattern'), $product->getAttributeText('material'), $product->getAttributeText('zari_type'), $product->getAttributeText('border'));
                        $indproduct['be_code'] = $product->getBeCode();
                        $indproduct['zari_color'] = $product->getAttributeText('zari_color');
                        $indproduct['Primary 1 Color Family'] = $product->getAttributeText('primary1colorfamily');
                        $productsrow[] = $indproduct;
                    }
                }
            }
            
            if (count($productsrow)) {

                $keys = array_keys($productsrow['0']);
                array_unshift($productsrow, $keys);

                $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
                $file_path = "customuploads.csv";
                $mage_csv->saveData($file_path, $productsrow);
                $filename = "customuploads.csv";
                header('Content-Disposition: attachment; filename='.$filename);
                header('Content-Type: application/csv');
                header('Pragma: no-cache');
                readfile($file_path);
            } else {
                print_r("There are no items to download");
            }

            die;
        } catch (Exception $e) {
            print_r($e);
            die;
        }
    }
    
    public function shoppingadsindia()
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        
        
        $productCollectionFactory = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
        $productCollection = $productCollectionFactory->create();
        $productCollection->addAttributeToSelect('*');
        $productCollection->addAttributeToFilter('status', 1);
        $productCollection->addAttributeToFilter('visibility', ['4']);
        $objectManager->get('\Magento\CatalogInventory\Helper\Stock')->addInStockFilterToCollection($productCollection);
        /*$sku = 'ES';
        $productCollection->addAttributeToFilter('sku', array(
                    array('like' => '%'.$sku.'%'), //spaces on each side
                    array('like' => '%'.$sku), //space before and ends with $needle
                    array('like' => $sku.'%') // starts with needle and space after
        ));*/

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
            $indproduct = [];
            $indproduct['id'] = $product->getSku();
            $indproduct['title'] = $product->getName();
            $indproduct['ios_url'] = '';
            $indproduct['ios_app_store_id'] = '';
            $indproduct['ios_app_name'] = '';
            $indproduct['android_url'] = '';
            $indproduct['android_package'] = '';
            $indproduct['android_app_name'] = '';
            $indproduct['windows_phone_url'] = '';
            $indproduct['windows_phone_app_id'] = '';
            $indproduct['windows_phone_app_name'] = '';
            $indproduct['description'] = $product->getDescription();
            $indproduct['google_product_category'] = "Apparel & Accessories > Clothing > Traditional & Ceremonial Clothing > Saris & Lehengas";
            $indproduct['product_type'] = "Saree > ".$product->getData('article_type');
            $indproduct['link'] = $product->getProductUrl();
            $indproduct['image_link'] = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'catalog/product'. $product->getData('image');
            $indproduct['condition'] = "new";
            $indproduct['availability'] = 'in stock';
            $indproduct['price'] = round($product->getPrice())." INR";
            $indproduct['sale_price'] = '';
            $indproduct['sale_price_effective_date'] = '';
            $indproduct['gtin'] = '';
            $indproduct['brand'] = 'Nalli Silks';
            $indproduct['mpn'] = '';
            $indproduct['item_group_id'] = '';
            $indproduct['gender'] = 'Female';
            $indproduct['age_group'] = '';
            $indproduct['color'] = $product->getAttributeText('color');
            $indproduct['size'] = '';
            $indproduct['shipping'] = '';
            $indproduct['custom_label_0'] = '';
            $productsrow[] = $indproduct;
        }

        $keys = array_keys($productsrow['0']);
        array_unshift($productsrow, $keys);

        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "shoppingadsindia.csv";
        $mage_csv->saveData($file_path, $productsrow);
        $filename = "shoppingadsindia.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);

        die;
    }
    
    public function shoppingadsusa()
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        
        
        $productCollectionFactory = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
        $productCollection = $productCollectionFactory->create();
        $productCollection->addAttributeToSelect('*');
        $productCollection->addAttributeToFilter('status', 1);
        $productCollection->addAttributeToFilter('visibility', ['4']);
        $objectManager->get('\Magento\CatalogInventory\Helper\Stock')->addInStockFilterToCollection($productCollection);
        /*$sku = 'ES';
        $productCollection->addAttributeToFilter('sku', array(
                    array('like' => '%'.$sku.'%'), //spaces on each side
                    array('like' => '%'.$sku), //space before and ends with $needle
                    array('like' => $sku.'%') // starts with needle and space after
        ));*/

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
            
            $prodweight = $product->getWeight();
            
            if ($prodweight > 0 && $prodweight <= 1) {
                $prodship = "1739.56";
            } elseif ($prodweight > 1 && $prodweight <= 1.5) {
                $prodship = "2149.59";
            } elseif ($prodweight > 1.5 && $prodweight <= 2) {
                $prodship = "2559.63‬";
            } elseif ($prodweight > 2 && $prodweight <= 2.5) {
                $prodship = "2969.67‬";
            } elseif ($prodweight > 2.5 && $prodweight <= 3) {
                $prodship = "3292.73";
            } else {
                $prodship = "3292.73";
            }
            
            
            $indproduct = [];
            $indproduct['id'] = $product->getSku();
            $indproduct['title'] = $product->getName();
            $indproduct['ios_url'] = '';
            $indproduct['ios_app_store_id'] = '';
            $indproduct['ios_app_name'] = '';
            $indproduct['android_url'] = '';
            $indproduct['android_package'] = '';
            $indproduct['android_app_name'] = '';
            $indproduct['windows_phone_url'] = '';
            $indproduct['windows_phone_app_id'] = '';
            $indproduct['windows_phone_app_name'] = '';
            $indproduct['description'] = $product->getDescription();
            $indproduct['google_product_category'] = "Apparel & Accessories > Clothing > Traditional & Ceremonial Clothing > Saris & Lehengas";
            $indproduct['product_type'] = "Saree > ".$product->getData('article_type');
            $indproduct['link'] = $product->getProductUrl() ."?currencycode=USD";
            $indproduct['image_link'] = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'catalog/product'. $product->getData('image');
            $indproduct['condition'] = "new";
            $indproduct['availability'] = 'in stock';
            $indproduct['price'] = round($storeManager->getStore()->getBaseCurrency()->convert($product->getPrice(), 'USD'), 2)." USD";
            $indproduct['sale_price'] = '';
            $indproduct['sale_price_effective_date'] = '';
            $indproduct['gtin'] = '';
            $indproduct['brand'] = 'Nalli Silks';
            $indproduct['mpn'] = '';
            $indproduct['item_group_id'] = '';
            $indproduct['gender'] = 'Female';
            $indproduct['age_group'] = 'adult';
            $indproduct['color'] = $product->getAttributeText('color');
            $indproduct['size'] = 'one size';
            $indproduct['shipping'] = "US:::" .round($storeManager->getStore()->getBaseCurrency()->convert($prodship, 'USD'), 2)." USD";
            $indproduct['tax'] = 'US::0.00:no';
            $indproduct['custom_label_0'] = '';
            $productsrow[] = $indproduct;
        }

        $keys = array_keys($productsrow['0']);
        array_unshift($productsrow, $keys);

        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "shoppingadsusa.csv";
        $mage_csv->saveData($file_path, $productsrow);
        $filename = "shoppingadsusa.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);

        die;
    }
    
    public function shoppingadscanada()
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        
        
        $productCollectionFactory = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
        $productCollection = $productCollectionFactory->create();
        $productCollection->addAttributeToSelect('*');
        $productCollection->addAttributeToFilter('status', 1);
        $productCollection->addAttributeToFilter('visibility', ['4']);
        $objectManager->get('\Magento\CatalogInventory\Helper\Stock')->addInStockFilterToCollection($productCollection);
        /*$sku = 'ES';
        $productCollection->addAttributeToFilter('sku', array(
                    array('like' => '%'.$sku.'%'), //spaces on each side
                    array('like' => '%'.$sku), //space before and ends with $needle
                    array('like' => $sku.'%') // starts with needle and space after
        ));*/

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
            
            $prodweight = $product->getWeight();
            
            if ($prodweight > 0 && $prodweight <= 1) {
                $prodship = "1739.56";
            } elseif ($prodweight > 1 && $prodweight <= 1.5) {
                $prodship = "2149.59";
            } elseif ($prodweight > 1.5 && $prodweight <= 2) {
                $prodship = "2559.63‬";
            } elseif ($prodweight > 2 && $prodweight <= 2.5) {
                $prodship = "2969.67‬";
            } elseif ($prodweight > 2.5 && $prodweight <= 3) {
                $prodship = "3292.73";
            } else {
                $prodship = "3292.73";
            }
            
            
            $indproduct = [];
            $indproduct['id'] = $product->getSku();
            $indproduct['title'] = $product->getName();
            $indproduct['ios_url'] = '';
            $indproduct['ios_app_store_id'] = '';
            $indproduct['ios_app_name'] = '';
            $indproduct['android_url'] = '';
            $indproduct['android_package'] = '';
            $indproduct['android_app_name'] = '';
            $indproduct['windows_phone_url'] = '';
            $indproduct['windows_phone_app_id'] = '';
            $indproduct['windows_phone_app_name'] = '';
            $indproduct['description'] = $product->getDescription();
            $indproduct['google_product_category'] = "Apparel & Accessories > Clothing > Traditional & Ceremonial Clothing > Saris & Lehengas";
            $indproduct['product_type'] = "Saree > ".$product->getData('article_type');
            $indproduct['link'] = $product->getProductUrl()."?currencycode=CAD";
            $indproduct['image_link'] = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'catalog/product'. $product->getData('image');
            $indproduct['condition'] = "new";
            $indproduct['availability'] = 'in stock';
            $indproduct['price'] = round($storeManager->getStore()->getBaseCurrency()->convert($product->getPrice(), 'CAD'), 2)." CAD";
            $indproduct['sale_price'] = '';
            $indproduct['sale_price_effective_date'] = '';
            $indproduct['gtin'] = '';
            $indproduct['brand'] = 'Nalli Silks';
            $indproduct['mpn'] = '';
            $indproduct['item_group_id'] = '';
            $indproduct['gender'] = 'Female';
            $indproduct['age_group'] = 'adult';
            $indproduct['color'] = $product->getAttributeText('color');
            $indproduct['size'] = 'one size';
            $indproduct['shipping'] = "CAN:::" .round($storeManager->getStore()->getBaseCurrency()->convert($prodship, 'CAD'), 2)." CAD";
            $indproduct['tax'] = 'CAN::0.00:no';
            $indproduct['custom_label_0'] = '';
            $productsrow[] = $indproduct;
        }

        $keys = array_keys($productsrow['0']);
        array_unshift($productsrow, $keys);

        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "shoppingadscanada.csv";
        $mage_csv->saveData($file_path, $productsrow);
        $filename = "shoppingadscanada.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);

        die;
    }
    
    public function accountssales($start, $end)
    {
        

        $from = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($start))));
        $to = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($end))));
        $to_end = date('Y-m-d H:i:s', (strtotime('+1 days', strtotime($to) - 1)));

        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "accountsales.csv";
        $products_row = [];

        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $orders = $objectManager->get('Magento\Sales\Model\Order')->getCollection();
        $orders->getSelect()->joinLeft('ipdetails', 'main_table.entity_id = ipdetails.order_id', ['country_id', 'state', 'country', 'city']);
        $orders->addAttributeToFilter('created_at', ['from'=>$from, 'to'=>$to_end]);
        $orders->addAttributeToFilter('status', ['processing', 'shipped', 'complete']);

        foreach ($orders as $order) {

            try {
                $items = $order->getAllVisibleItems();
                
                $shipstreet = implode(',', $order->getShippingAddress()->getStreet());
                
                foreach ($items as $item) {

                    $product = $objectManager->create('Magento\Catalog\Model\Product')->load($item->getProductId());

                    $details = [];
                    $details['billing_name'] = $order->getBillingAddress()->getFirstname() ." " .$order->getBillingAddress()->getLastname();
                    $details['shipping_address'] = $shipstreet .", " .$order->getShippingAddress()->getCity() .", " .$order->getShippingAddress()->getRegion() ." - " .$order->getShippingAddress()->getPostcode() .", " .$order->getShippingAddress()->getCountryId() ." T: " .$order->getShippingAddress()->getTelephone();
                    $details['order_date'] = $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($order->getCreatedAt(), \IntlDateFormatter::SHORT, false);
                    $details['orderid'] = $order->getIncrementId();
                    $details['shipping_country'] = $order->getShippingAddress()->getCountryId();
                    $details['shipping_state'] = $order->getShippingAddress()->getRegion();
                    $details['shipping_postcode'] = $order->getShippingAddress()->getPostcode();
                    
                    $details['subtotal'] = $order->getBaseSubtotal();
                    $details['shipping_cost'] = $order->getBaseShippingAmount() + $order->getBaseShippingTaxAmount();
                    $details['packaging_cost'] = $order->getBaseFee();
                    $details['grand_total'] = $order->getBaseGrandTotal();
                    
                    $details['sku'] = $item->getSku();
                    $details['be_code'] = $product->getBeCode();
                    $details['price'] = $item->getBasePrice();
                    $details['qty'] = $item->getQtyOrdered();

                    $products_row[] = $details;

                }

            } catch (exception $e) {

            }
        }
        $keys = [];
        if(!empty($products_row)){
            $keys = array_keys($products_row['0']);
        }
        array_unshift($products_row, $keys);

        $mage_csv->saveData($file_path, $products_row);
        $filename = "accountsales.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);

        die;
    }
    
    public function customabandoned($start, $end)
    {
        

        $from = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($start))));
        $to = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($end))));
        $to_end = date('Y-m-d H:i:s', (strtotime('+1 days', strtotime($to) - 1)));

        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "customabandoned.csv";
        $products_row = [];
        
        try {
            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
            $quotecollection = $objectManager->create('Magento\Quote\Model\Quote')->getCollection();
            $quotecollection->addFieldToFilter('created_at', ['from'=>$from, 'to'=>$to_end]);
            // $quotecollection->addFieldToFilter('is_active', 1);
            $quotecollection->addFieldToFilter('customer_email', ['notnull' => true]);

            foreach ($quotecollection as $quote) {
                

                foreach ($quote->getAllVisibleItems() as $item) {

                    $product = $objectManager->create('Magento\Catalog\Model\Product')->load($item->getProductId());
                    
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
                            $createdat = $product->getMagentooneUpload() ? $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($product->getMagentooneUpload(), \IntlDateFormatter::SHORT, false) : $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($product->getCreatedAt(), \IntlDateFormatter::SHORT, false);
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
                    
                    $details = [];
                    $details['quoteid'] = $quote->getId();
                    //$details['created_at'] = $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($quote->getCreatedAt(),\IntlDateFormatter::SHORT, false);
                    //$details['last_updated_at'] = $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($quote->getUpdatedAt(),\IntlDateFormatter::SHORT, false);
                    $details['created_at'] = $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDateTime($quote->getCreatedAt());
                    $details['last_updated_at'] = $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDateTime($quote->getUpdatedAt());
                    $details['email'] = $quote->getCustomerEmail();
                    $details['is_active'] = $quote->getIsActive();
                    
                    $details['sku'] = $item->getSku();
                    $details['name'] = $item->getName();
                    $details['category_ids'] = $category_ids;
                    $details['fabric_purity'] = $fabricpurity;
                    $details['color'] = $color;
                    $details['material'] = $material;
                    $details['price'] = $item->getBasePrice();
                    $details['product_created_at'] = $createdat;
                    $details['pattern'] = $pattern;
                    $details['category_name'] = $article_type;
                    $details['zari_type'] = $zari_type;
                    $details['border'] = $border;
                    // $details['counter'] = $this->mapcounters($product->getCategoryIds(), $product->getAttributeText('pattern'), $product->getAttributeText('material'), $product->getAttributeText('zari_type'), $product->getAttributeText('border'));
                    $details['remote_ip'] = $quote->getRemoteIp();
                    $details['qty'] = $item->getQty();
                    
                    $products_row[] = $details;
                }

                
            }
        } catch (exception $e) {
            print_r($e);
            die;
        }
        $keys = [];
        if(!empty($products_row)){
            $keys = array_keys($products_row['0']);
        }
        array_unshift($products_row, $keys);

        $mage_csv->saveData($file_path, $products_row);
        $filename = "customabandoned.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);

        die;
    }
    
    public function ordertracking($start, $end)
    {
        

        $from = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($start))));
        $to = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($end))));
        $to_end = date('Y-m-d H:i:s', (strtotime('+1 days', strtotime($to) - 1)));

        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "ordertracking.csv";
        $products_row = [];

        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $shipments = $objectManager->create('Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection');
        $shipments->getSelect()->joinLeft('sales_order', 'main_table.order_id = sales_order.entity_id', ['order_increment_id' =>'increment_id', 'order_created_at' => 'created_at']);
        $shipments->addAttributeToFilter('sales_order.created_at', ['from'=>$from, 'to'=>$to_end]);

        foreach ($shipments as $shipment) {

            try {

                $details = [];
                $details['orderid'] = $shipment->getOrderIncrementId();
                $details['carrier'] = $shipment->getTitle();
                $details['awb'] = $shipment->getTrackNumber();
                // $details['order_date'] = $shipment->getOrderCreatedAt();

                $products_row[] = $details;
                

            } catch (exception $e) {

            }
        }
        $keys = [];
        if(!empty($products_row)){
            $keys = array_keys($products_row['0']);
        }
        array_unshift($products_row, $keys);

        $mage_csv->saveData($file_path, $products_row);
        $filename = "ordertracking.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);

        die;
    }
    
    public function detectuseragent()
    {
        echo "<pre>";
        print_r($_SERVER['HTTP_USER_AGENT']);
        echo "<br><br><br>";
        // Safari (in-app)
        if ((strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile/') !== false) && (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari/') == false)) {
            echo 'Safari (in-app)';
        }
        // Android (in-app)
        $androidwebview = ['com.facebook.katana', 'com.instagram.android', 'com.google.talk'];
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && in_array($_SERVER['HTTP_X_REQUESTED_WITH'], $androidwebview)) {
            echo 'Android (in-app)';
        }
        echo "</pre>";
        
        echo "<pre>";
        print_r($_SERVER);
        echo "</pre>";

        die;
    }
    
    public function followedcategories()
    {
        
        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "followedcategories.csv";
        $products_row = [];
        
        try {

            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
            
            $categoryCollection = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory');
            $categories = $categoryCollection->create();
            $categories->addAttributeToSelect('*');
            
            $allcategories = [];
              
            foreach ($categories as $category) {
                $allcategories[$category->getId()] = $category->getName();
            }
            
            $collection = $objectManager->create('\Nalli\Toppicks\Model\Toppicks')->getCollection();
            
            $customerFactory = $objectManager->get('\Magento\Customer\Model\CustomerFactory')->create();
            
            foreach ($collection as $item) {
                $details = [];
                $details['category_id'] = $item->getData('category_id');
                $details['category_name'] = $allcategories[$item->getCategoryId()];
                $details['customer_id'] = $item->getData('customer_id');
                
                $customer = $customerFactory->load($item->getCustomerId());
                
                $details['customer_email'] = $item->getCustomerId();
                $details['created_at'] = $item->getData('created_at');
                $details['updated_at'] = $item->getData('updated_at');
                $details['status'] = $item->getData('status');
                
                $products_row[] = $details;
            }
        
        } catch (exception $e) {
            print_r($e);
            die;
        }

        $keys = array_keys($products_row['0']);
        array_unshift($products_row, $keys);

        $mage_csv->saveData($file_path, $products_row);
        $filename = "followedcategories.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);

        die;
    }
    
    public function categorymaster()
    {
        
        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "categorymaster.csv";
        $products_row = [];
        
        try {

            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
            
            $categoryCollection = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory');
            $categories = $categoryCollection->create();
            $categories->addAttributeToSelect('*');
            
              
            foreach ($categories as $category) {
                $details = [];
                $details['category_id'] = $category->getId();
                $details['category_name'] = $category->getName();
                $products_row[] = $details;
            }
            
            
        
        } catch (exception $e) {
            print_r($e);
            die;
        }

        $keys = array_keys($products_row['0']);
        array_unshift($products_row, $keys);

        $mage_csv->saveData($file_path, $products_row);
        $filename = "categorymaster.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);

        die;
    }
    
    public function mothersdaycontest()
    {
        
        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "mothersdaycontest.csv";
        $products_row = [];
        
        try {

            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            
            $collection = $objectManager->create('\Nalli\Mothersdaycontest\Model\Mothersdaycontest')->getCollection();
            
            foreach ($collection as $item) {
                $details = [];
                $order = $objectManager->create('\Magento\Sales\Model\Order')->load($item->getData('order_id'));
                $details['order_date'] = $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($order->getCreatedAt(), \IntlDateFormatter::SHORT, false);
                $details['order_id'] = $item->getData('increment_id');
                $details['is_gift'] = $item->getData('is_gift');
                $details['mobile'] = $item->getData('mobile');
                $details['comment'] = $item->getData('comment');
                $details['imageone'] = $item->getData('imageone') ? $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).$item->getData('imageone') : '';
                $details['imagetwo'] = $item->getData('imagetwo') ? $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).$item->getData('imagetwo') : '';
                $details['imagethree'] = $item->getData('imagethree') ? $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).$item->getData('imagethree') : '';
                $details['created_time'] = $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($item->getData('created_time'), \IntlDateFormatter::SHORT, false);
                
                $products_row[] = $details;
            }
        
        } catch (exception $e) {
            print_r($e);
            die;
        }

        $keys = array_keys($products_row['0']);
        array_unshift($products_row, $keys);

        $mage_csv->saveData($file_path, $products_row);
        $filename = "mothersdaycontest.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);

        die;
    }
    
    public function customwishlistnew($start, $end)
    {
        // print_r("hahah"); die;
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $quotecollection = $objectManager->create('\Magento\Wishlist\Model\Wishlist')->getCollection();
        foreach ($quotecollection as $quote) {
            print_r($quote->getData());
            die;
        }
    }
    
    public function customwishlist($start, $end)
    {
        

        $from = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($start))));
        $to = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($end))));
        $to_end = date('Y-m-d H:i:s', (strtotime('+1 days', strtotime($to) - 1)));

        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "customwishlist.csv";
        $products_row = [];
        
        try {
            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
            $quotecollection = $objectManager->create('\Magento\Wishlist\Model\Wishlist')->getCollection();
            $quotecollection->addFieldToFilter('updated_at', ['from'=>$from, 'to'=>$to_end]);
            // $quotecollection->addFieldToFilter('is_active', 1);
            // $quotecollection->addFieldToFilter('customer_email', array('notnull' => true));
            
            $customerFactory = $objectManager->get('\Magento\Customer\Model\CustomerFactory')->create();

            foreach ($quotecollection as $quote) {
                
                // print_r($quote->getWishlistId()); die;
                $customer = $customerFactory->load($quote->getCustomerId());
                
                $customer_email = $customer->getEmail();
                

                foreach ($quote->getItemCollection() as $item) {

                    $product = $objectManager->create('Magento\Catalog\Model\Product')->load($item->getProductId());
                    
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
                            $createdat = $product->getMagentooneUpload() ? $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($product->getMagentooneUpload(), \IntlDateFormatter::SHORT, false) : $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($product->getCreatedAt(), \IntlDateFormatter::SHORT, false);
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
                    
                    $details = [];
                    $details['wishlist_id'] = $quote->getId();
                    // $details['created_at'] = $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($quote->getCreatedAt(),\IntlDateFormatter::SHORT, false);
                    $details['last_updated_at'] = $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($quote->getUpdatedAt(), \IntlDateFormatter::SHORT, false);
                    $details['email'] = $customer_email;
                    // $details['is_active'] = $quote->getIsActive();
                    
                    $details['sku'] = $product->getSku();
                    $details['name'] = $product->getName();
                    $details['category_ids'] = $category_ids;
                    $details['fabric_purity'] = $fabricpurity;
                    $details['color'] = $color;
                    $details['material'] = $material;
                    $details['price'] = $product->getPrice();
                    $details['product_created_at'] = $createdat;
                    $details['pattern'] = $pattern;
                    $details['category_name'] = $article_type;
                    $details['zari_type'] = $zari_type;
                    $details['border'] = $border;
                    // $details['counter'] = $this->mapcounters($product->getCategoryIds(), $product->getAttributeText('pattern'), $product->getAttributeText('material'), $product->getAttributeText('zari_type'), $product->getAttributeText('border'));
                    // $details['remote_ip'] = $quote->getRemoteIp();
                    // $details['qty'] = $item->getQty();
                    
                    $products_row[] = $details;
                }

                
            }
        } catch (exception $e) {
            print_r($e);
            die;
        }
        $keys = [];
        if(!empty($products_row)){
            $keys = array_keys($products_row['0']);
        }
        array_unshift($products_row, $keys);

        $mage_csv->saveData($file_path, $products_row);
        $filename = "customwishlist.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);

        die;
    }

    /*
     * generate today customsoldproducts
     */
    public function todayCustomSoldProducts()
    {
        

        $name = date('m_d_Y_H_i_s');
        $filepath = 'export/Instock' . $name . '.csv';
        $this->directory->create('export');
        /* Open file */
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productCollectionFactory = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
        $productCollection = $productCollectionFactory->create();
        $productCollection->addAttributeToSelect('*');
        $productCollection->addAttributeToFilter('status', 1);
        $productCollection->addAttributeToFilter('visibility', ['4']);
        $this->_stockFilter->addInStockFilterToCollection($productCollection);
        /*$sku = 'ES';
        $productCollection->addAttributeToFilter('sku', array(
                    array('like' => '%'.$sku.'%'), //spaces on each side
                    array('like' => '%'.$sku), //space before and ends with $needle
                    array('like' => $sku.'%') // starts with needle and space after
        ));*/

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
              $indproduct = [];
            $indproduct['id'] = $product->getId();
            $indproduct['sku'] = $product->getSku();
            $indproduct['name'] = $product->getName();
            $indproduct['category_ids'] = implode(',', $product->getCategoryIds());
            $indproduct['category'] = $product->getArticleType();
            $indproduct['fabric_purity'] = $product->getAttributeText('fabric_purity');
            $indproduct['color'] = $product->getAttributeText('color');
            $indproduct['material'] = $product->getAttributeText('material');
            $indproduct['qty'] = $StockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
            $indproduct['price'] = round($product->getPrice(), 2);
            
            $upload_date = $product->getMagentooneUpload() ? $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($product->getMagentooneUpload(), \IntlDateFormatter::SHORT, false) : $objectManager->create('Magento\Framework\Stdlib\DateTime\TimezoneInterface')->formatDate($product->getCreatedAt(), \IntlDateFormatter::SHORT, false);
            
            
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
            $indproduct['counter'] = $objectManager->create('Nalli\Weeklyreport\Helper\Data')->mapcounters($product->getCategoryIds(), $product->getAttributeText('pattern'), $product->getAttributeText('material'), $product->getAttributeText('zari_type'), $product->getAttributeText('border'));
            $indproduct['be_code'] = $product->getBeCode();
            $indproduct['supplier_code'] = $product->getSupplierCode();
            $indproduct['occasion'] = $product->getAttributeText('occasion');
            $indproduct['consignment_id'] = $product->getConsignmentId();
            $indproduct['zari_color'] = $product->getAttributeText('zari_color');
            $indproduct['Primary 1 Color Family'] = $product->getAttributeText('primary1colorfamily');
            $productsrow[] = $indproduct;
        }
        
          $keys = array_keys($productsrow['0']);
        array_unshift($productsrow, $keys);
        
        foreach ($productsrow as $key => $row) {
            $stream->writeCsv($row);
        }

        $stream->unlock();

        return $filepath;
    }
}
