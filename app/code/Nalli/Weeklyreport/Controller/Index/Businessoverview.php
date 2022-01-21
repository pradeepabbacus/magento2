<?php
namespace Nalli\Weeklyreport\Controller\Index;

/**
 * business overview report data Controller
 */

class Businessoverview extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */

    protected $_pageFactory;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */

    protected $formKeyValidator;

    /**
     * Upload constructor.
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\CatalogInventory\Helper\Stock $stockFilter,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Customer\Model\Session $session,
        \Nalli\Weeklyreport\Helper\Data $helperdata,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
    ) {
        $this->_pageFactory = $pageFactory;
        $this->_stockFilter = $stockFilter;
        $this->formKeyValidator = $formKeyValidator;
        $this->_dir = $dir;
        $this->_customerSession = $session;
        $this->helperdata = $helperdata;
        $this->messageManager = $messageManager;
        $this->_resource = $resource;
        $this->productCollectionFactory = $productCollectionFactory;
        return parent::__construct($context);
    }
    /**
     * check customer sessionn
     * @return bool
     */
    private function validateSession()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $this->_customerSession;
        if ($customerSession->isLoggedIn()) {
            $customeremail = $customerSession->getCustomer()->getEmail();
            $allowed_user = $this->helperdata->getConfig('weeklyreport/general/allowed_users');
            $screenusers = array_map('trim', explode(',', $allowed_user));
            if (in_array($customeremail, $screenusers)) {
                return true;
            }
        }
        return false;
    }

    /**
     * fetch Gareports data.
     * @param array $reports
     * @param null $type
     * @return array
     */
    protected function printGaReports($reports, $type)
    {

        $reportdata = [];
        $reportscount = count($reports);
        for ($reportIndex = 0; $reportIndex < $reportscount; $reportIndex++) {
            $report = $reports[ $reportIndex ];
            $header = $report->getColumnHeader();
            $dimensionHeaders = $header->getDimensions();
            $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
            $rows = $report->getData()->getRows();
            $rowscount = count($rows);
            for ($rowIndex = 0; $rowIndex < $rowscount; $rowIndex++) {
                $reportitem = [];
                $row = $rows[ $rowIndex ];
                $dimensions = $row->getDimensions();
                $metrics = $row->getMetrics();
                if (is_array($dimensionHeaders) && is_array($dimensions)) {
                    $dimensionscnt = count($dimensions);
                    $dimensionHeaderscnt = count($dimensionHeaders);
                    for ($i = 0; $i < $dimensionHeaderscnt && $i < $dimensionscnt; $i++) {
                        // print($dimensionHeaders[$i] . ": " . $dimensions[$i] . "\n");
                        $dheader = $dimensionHeaders[$i];
                        $reportitem[$dheader] = $dimensions[$i];
                    }
                }
                if (is_array($metrics)) {
                    $metricscount = count($metrics);
                    for ($j = 0; $j < $metricscount; $j++) {
                        $values = $metrics[$j]->getValues();
                        $valuescount = count($values);
                        for ($k = 0; $k < $valuescount; $k++) {
                            $entry = $metricHeaders[$k];
                            // print($entry->getName() . ": " . $values[$k] . "<br>");
                            $reportitem[$entry->getName()] = $values[$k];
                        }
                    }
                }

                $reportdata[] = $reportitem;
            }
        }
        
        // print_r($reportdata); die;

        return $reportdata;
    }

    /**
     * fetch reports data.
     *
     * @param $analytics
     * @param $abandontype
     * @param string start
     * @param string end
     * @return array
     *
     */

    protected function fetchAbandonedGaReports($analytics, $abandontype, $start, $end)
    {

        $VIEW_ID = "115854344";

        // Create the DateRange object.
        $dateRange = dateRange();
        $dateRange->setStartDate($start);
        $dateRange->setEndDate($end);

        // Create the Metrics object.
        $sessions = metric();
        $sessions->setExpression("ga:sessions");
        $sessions->setAlias("sessions");

        // Create Segment Dimension
        $segmentDimensions = dimension();
        $segmentDimensions->setName("ga:segment");

        // $segment = new Google_Service_AnalyticsReporting_Segment();
        // $segment->setSegmentId("gaid::SMW5vsa-QAi0ir99MGDkew");
        $dimensionFilter = segmentDimensionFilter();
        $dimensionFilter->setDimensionName("ga:shoppingStage");
        $dimensionFilter->setOperator("EXACT");
        $dimensionFilter->setExpressions([$abandontype]);

        // Create Segment Filter Clause.
        $segmentFilterClause = segmentFilterClause();
        $segmentFilterClause->setDimensionFilter($dimensionFilter);

        // Create the Or Filters for Segment.
        $orFiltersForSegment = orFiltersForSegment();
        $orFiltersForSegment->setSegmentFilterClauses([$segmentFilterClause]);

        // Create the Simple Segment.
        $simpleSegment = simpleSegment();
        $simpleSegment->setOrFiltersForSegment([$orFiltersForSegment]);

        // Create the Segment Filters.
        $segmentFilter = segmentFilter();
        $segmentFilter->setSimpleSegment($simpleSegment);

        // Create the Segment Definition.
        $segmentDefinition = segmentDefinition();
        $segmentDefinition->setSegmentFilters([$segmentFilter]);

        // Create the Dynamic Segment.
        $dynamicSegment = dynamicSegment();
        $dynamicSegment->setSessionSegment($segmentDefinition);
        $dynamicSegment->setName($abandontype);

        // Create the Segments object.
        $segment = Segment();
        $segment->setDynamicSegment($dynamicSegment);

        // Create the ReportRequest object.
        $request = reportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics([$sessions]);
        $request->setDimensions([$segmentDimensions]);
        $request->setSegments([$segment]);

        $request->setPageSize(10000);

        $body = getReportsRequest();
        $body->setReportRequests([ $request]);
        // print_r($body); die;
        $reports = $analytics->reports->batchGet($body);

        return $reports;
    }
    
    /**
     * download reports data.
     *
     * @return array
     *
     */

    public function execute()
    {
        require_once $this->_dir->getPath('lib_internal').'/Google/helloanalytics.php';
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $this->_redirect('*/*');
        }
        if (!$this->validateSession()) {
            $message = __("You do not have access to this report");
            $this->messageManager->addErrorMessage($message);
            return $this->_redirect('*/*');
        }

        $data = $this->getRequest()->getParams();
        if (!$data['from'] || !$data['to']) {
            $message = __('Please enter a from and to date');
            $this->messageManager->addErrorMessage($message);
            return $this->_redirect('*/*');
        }
        
        $start = $data['from'];
        $end = $data['to'];

        $datediff = ((strtotime($end) - strtotime($start)) / (60*60*24)) + 1;

        $from = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($start))));
        $to = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($end))));
        $to_end = date('Y-m-d H:i:s', (strtotime('+1 days', strtotime($to) - 1)));

        $from_last = date('Y-m-d H:i:s', (strtotime($from) - 1));

        // Establish DB connection
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $connection = $this->_resource->getConnection();

        // Order Sum and Count
        $ordersql = "SELECT sum(base_grand_total) as totalsales, COUNT(increment_id) as ordercount,
         COUNT(DISTINCT(customer_id)) as distinctcustomers FROM `sales_order` WHERE `status` IN 
         ('processing','shipped','complete') AND `created_at` BETWEEN '".$from."' AND '".$to_end ."'";

        $orderlist = $connection->fetchAll($ordersql);

        // Products ES, ET, etc
        $productsql = "SELECT LEFT(sku,2) as CAT, sum(qty_ordered) as qty, sum(base_price * qty_ordered) as pricetotal FROM sales_order_item LEFT JOIN sales_order on sales_order_item.order_id = sales_order.entity_id where sales_order.status in ('processing','shipped','complete') and sales_order_item.parent_item_id is NULL AND sales_order_item.created_at BETWEEN '".$from."' AND '".$to_end ."' group by CAT";

        $productlist = $connection->fetchAll($productsql);

        $productcount = $producttotal = [];
        $productcount['custom'] = $producttotal['custom'] = $productcount['total'] = $producttotal['total'] = 0;
        $noncustom = ['ES', 'ET', 'GV'];
        foreach ($productlist as $fields) {
            $cat = $fields['CAT'];
            if (in_array($cat, $noncustom)) {
                $productcount[$cat] = $fields['qty'];
                $producttotal[$cat] = $fields['pricetotal'];
            } else {
                $productcount['custom'] += $fields['qty'];
                $producttotal['custom'] += $fields['pricetotal'];
            }

            $productcount['total'] += $fields['qty'];
            $producttotal['total'] += $fields['pricetotal'];
        }

        // AD SOS

        $adsos_sql = "SELECT AVG(sos) as avgsos FROM `sosupdate` WHERE `date` BETWEEN '".$start."' AND '".$end ."'";
        $adsos_list = $connection->fetchAll($adsos_sql);

        // AD Orders, Revenue from First time Customers

        $firsttime = "SELECT sum(base_grand_total) as totalsales, COUNT(increment_id) as ordercount, COUNT(DISTINCT(customer_id)) as distinctcustomers FROM `sales_order` WHERE increment_id in (SELECT increment_id FROM `sales_order` WHERE `created_at` BETWEEN '".$from."' AND '".$to_end."' AND STATUS in ('processing', 'shipped', 'complete') and customer_id not in (select customer_id FROM `sales_order` WHERE `created_at` BETWEEN '2016-01-01 00:00:00.000000' AND '".$from_last."' AND STATUS in ('processing', 'shipped', 'complete')))";

        $firsttimelist = $connection->fetchAll($firsttime);

        // AD Orders, Revenue from Repeat Customers

        $repeat = "SELECT sum(base_grand_total) as totalsales, COUNT(increment_id) as ordercount FROM `sales_order` WHERE increment_id in (SELECT increment_id FROM `sales_order` WHERE `created_at` BETWEEN '".$from."' AND '".$to_end."' AND STATUS in ('processing', 'shipped', 'complete') and customer_id in (select customer_id FROM `sales_order` WHERE `created_at` BETWEEN '2016-01-01 00:00:00.000000' AND '".$from_last."' AND STATUS in ('processing', 'shipped', 'complete')))";

        $repeatlist = $connection->fetchAll($repeat);

        // AD New registrations

        $customers = $objectManager->create('Magento\Customer\Model\Customer')->getCollection()
                    ->addAttributeToSelect('created_at')
                    ->addAttributeToSelect('confirmation')
                    ->addAttributeToFilter('created_at', ['from'=>$from, 'to'=>$to_end])
                    ->addAttributeToFilter(
                        [
                            ['attribute'=> 'confirmation','null' => true],
                            ['attribute'=> 'confirmation','eq' => ''],
                            ['attribute'=> 'confirmation','eq' => 'NO FIELD']
                        ],
                        '',
                        'left'
                    );

        $confirmed_customers = count($customers);

        //AD Uploads

        $productCollectionFactory = $this->productCollectionFactory;
        $productCollection = $productCollectionFactory->create();
        $productCollection->addAttributeToSelect('*');
        $productCollection->addAttributeToFilter('created_at', ['from'=>$from, 'to'=>$to_end]);
        $sku = 'ES';
        $productCollection->addAttributeToFilter('sku', [
                    ['like' => '%'.$sku.'%'], //spaces on each side
                    ['like' => '%'.$sku], //space before and ends with $needle
                    ['like' => $sku.'%'] // starts with needle and space after
        ]);

        $uploads = count($productCollection);

        // Avg PDP Views

        $ad_pdp_sql = "select avg(PDP_Count) as avg_PDP from (select a.session_id, count(a.category) as PDP_Count from `reportpageviews` as a inner join (SELECT session_id, count(category) as tot_views FROM `reportpageviews` where created_time BETWEEN '".$from."' AND '".$to_end ."' group by 1) as b on a.session_id=b.session_id and b.tot_views>1 where a.category='PDP' and created_time BETWEEN '".$from."' AND '".$to_end ."' group by 1) as t";

        // $ad_pdp_list = $connection->fetchAll($ad_pdp_sql);

        //Avg PDP views per Conversion

        $ad_pdp_conv_sql = "select avg(PDP_Count) as avg_PDP from (select a.session_id, count(a.category) as PDP_Count from `reportpageviews` as a inner join (SELECT distinct session_id FROM `reportpageviews` where page_id = 'mergeinfo-onepage-success' and created_time BETWEEN '".$from."' AND '".$to_end ."') as b on a.session_id=b.session_id where a.category='PDP' and created_time BETWEEN '".$from."' AND '".$to_end ."' group by 1) as t";

        // $ad_pdp_conv_list = $connection->fetchAll($ad_pdp_conv_sql);

        // print_r("now here"); die;


        // Avg Daily Add to Cart

        $quoteitemsql = "SELECT COUNT(item_id) as itemcount FROM `quote_item` WHERE `created_at` BETWEEN '".$from."' AND '".$to_end ."'";
        $quoteitemlist = $connection->fetchAll($quoteitemsql);

        // Average time in cart berfore conversion

        $diffsumsql = "SELECT AVG(TIMESTAMPDIFF(SECOND, quote_item.created_at, sales_order_item.created_at)) as diffsum FROM `sales_order_item` LEFT JOIN sales_order ON sales_order_item.order_id = sales_order.entity_id LEFT JOIN quote_item ON sales_order_item.quote_item_id = quote_item.item_id WHERE sales_order.status in ('processing', 'shipped', 'complete') AND `sales_order`.`created_at` BETWEEN '".$from."' AND '".$to_end ."'";

        $diffsumlist = $connection->fetchAll($diffsumsql);

        // 30 Day Rolling Repeat (Purchase Rate)

        $thirtyrolling_purc_sql = "SELECT count(distinct a.customer_id) as all_cust, count(distinct b.customer_id) as rep_cust FROM `sales_order` AS a left join `sales_order` AS b on a.customer_id = b.customer_id and b.created_at BETWEEN date_sub(a.created_at, interval 1 month) and date_sub(a.created_at, interval 1 day) and b.status in ('processing', 'shipped', 'complete') WHERE a.created_at BETWEEN '".$from."' AND '".$to_end ."' and a.status in ('processing', 'shipped', 'complete');";

        $thirtyrolling_purc_list = $connection->fetchAll($thirtyrolling_purc_sql);

        // Successful Retry Percent

        $succretry_india_sql = "SELECT COUNT(DISTINCT(sales_order.customer_id)) as distinctcustomers FROM sales_order LEFT JOIN ipdetails on sales_order.entity_id = ipdetails.order_id where sales_order.status in ('processing','shipped','complete') AND sales_order.created_at BETWEEN '".$from."' AND '".$to_end ."' AND ipdetails.country_id = 'IN' AND sales_order.customer_id in (SELECT DISTINCT(sales_order.customer_id)  FROM `sales_order_status_history` LEFT JOIN sales_order on sales_order_status_history.parent_id = sales_order.entity_id WHERE sales_order_status_history.`comment` LIKE '%reorder%' AND sales_order_status_history.created_at BETWEEN '".$from."' AND '".$to_end ."')";

        $succretry_india_list = $connection->fetchAll($succretry_india_sql);

        // Successful Retry Percent

        $succretry_row_sql = "SELECT COUNT(DISTINCT(sales_order.customer_id)) as distinctcustomers FROM sales_order LEFT JOIN ipdetails on sales_order.entity_id = ipdetails.order_id where sales_order.status in ('processing','shipped','complete') AND sales_order.created_at BETWEEN '".$from."' AND '".$to_end ."' AND ipdetails.country_id != 'IN' AND sales_order.customer_id in (SELECT DISTINCT(sales_order.customer_id)  FROM `sales_order_status_history` LEFT JOIN sales_order on sales_order_status_history.parent_id = sales_order.entity_id WHERE sales_order_status_history.`comment` LIKE '%reorder%' AND sales_order_status_history.created_at BETWEEN '".$from."' AND '".$to_end ."')";

        $succretry_row_list = $connection->fetchAll($succretry_row_sql);

        // SOS
        $productCollectionFactory = $this->productCollectionFactory;
        $products = $productCollectionFactory->create();
        $products->addAttributeToSelect('*');
        $products->addAttributeToFilter('status', 1);
        $products->addAttributeToFilter('visibility', ['4']);
        $this->_stockFilter->addInStockFilterToCollection($products);
        $sku = 'ES';
        $products->addAttributeToFilter('sku', [
                    ['like' => '%'.$sku.'%'], //spaces on each side
                    ['like' => '%'.$sku], //space before and ends with $needle
                    ['like' => $sku.'%'] // starts with needle and space after
        ]);
        
        $products->getSelect()->joinLeft(
            [ 'stocktable' => 'cataloginventory_stock_status' ],
            'e.entity_id = stocktable.product_id',
            []
        );

        $subquery = new \Zend_Db_Expr('(SELECT sku, SUM(quantity) as salableqty FROM inventory_reservation GROUP BY sku)');
        $joinConditions = 'e.sku = reservetable.sku';
        $products->getSelect()->joinLeft(
            [ 'reservetable' => $subquery ],
            $joinConditions,
            []
        )->columns("(IFNULL(reservetable.salableqty,0) + stocktable.qty) AS final_qty")
                     ->where("(IFNULL(reservetable.salableqty,0) + stocktable.qty) > 0");

        // Fresh Stock (<=15 Days)

        $products_gteq15 = $productCollectionFactory->create();
        $products_gteq15->addAttributeToFilter('status', 1);
        $products_gteq15->addAttributeToFilter('visibility', ['4']);
        $this->_stockFilter->addInStockFilterToCollection($products_gteq15);
        $sku = 'ES';
        $products_gteq15->addAttributeToFilter('sku', [
                    ['like' => '%'.$sku.'%'], //spaces on each side
                    ['like' => '%'.$sku], //space before and ends with $needle
                    ['like' => $sku.'%'] // starts with needle and space after
        ]);
        $products_gteq15->addAttributeToFilter('created_at', ['from' => date('Y-m-d', strtotime('-15 day'))]);
        
        $products_gteq15->getSelect()->joinLeft(
            [ 'stocktable' => 'cataloginventory_stock_status' ],
            'e.entity_id = stocktable.product_id',
            []
        );

        $subquery = new \Zend_Db_Expr('(SELECT sku, SUM(quantity) as salableqty FROM inventory_reservation GROUP BY sku)');
        $joinConditions = 'e.sku = reservetable.sku';
        $products_gteq15->getSelect()->joinLeft(
            [ 'reservetable' => $subquery ],
            $joinConditions,
            []
        )->columns("(IFNULL(reservetable.salableqty,0) + stocktable.qty) AS final_qty")
                     ->where("(IFNULL(reservetable.salableqty,0) + stocktable.qty) > 0");

        // old stock (> 60 days)

        $product_60days = $productCollectionFactory->create();
        $product_60days->addAttributeToFilter('status', 1);
        $product_60days->addAttributeToFilter('visibility', ['4']);
        $this->_stockFilter->addInStockFilterToCollection($product_60days);
        $sku = 'ES';
        $product_60days->addAttributeToFilter('sku', [
                    ['like' => '%'.$sku.'%'], //spaces on each side
                    ['like' => '%'.$sku], //space before and ends with $needle
                    ['like' => $sku.'%'] // starts with needle and space after
        ]);
        $product_60days->addAttributeToFilter('created_at', ['to' => date('Y-m-d', strtotime('-60 day'))]);
        
        $product_60days->getSelect()->joinLeft(
            [ 'stocktable' => 'cataloginventory_stock_status' ],
            'e.entity_id = stocktable.product_id',
            []
        );

        $subquery = new \Zend_Db_Expr('(SELECT sku, SUM(quantity) as salableqty FROM inventory_reservation GROUP BY sku)');
        $joinConditions = 'e.sku = reservetable.sku';
        $product_60days->getSelect()->joinLeft(
            [ 'reservetable' => $subquery ],
            $joinConditions,
            []
        )->columns("(IFNULL(reservetable.salableqty,0) + stocktable.qty) AS final_qty")
                     ->where("(IFNULL(reservetable.salableqty,0) + stocktable.qty) > 0");

        // high value old stock (gteq 45 lteq 60 days)

        $highvalue_product_45to60days = $productCollectionFactory->create();
        $highvalue_product_45to60days->addAttributeToFilter('status', 1);
        $highvalue_product_45to60days->addAttributeToFilter('visibility', ['4']);
        $highvalue_product_45to60days->addAttributeToFilter('price', ['gteq' => '30000']);
        $this->_stockFilter->addInStockFilterToCollection($highvalue_product_45to60days);
        $sku = 'ES';
        $highvalue_product_45to60days->addAttributeToFilter('sku', [
                    ['like' => '%'.$sku.'%'], //spaces on each side
                    ['like' => '%'.$sku], //space before and ends with $needle
                    ['like' => $sku.'%'] // starts with needle and space after
        ]);
        $highvalue_product_45to60days->addAttributeToFilter('created_at', ['from' => date('Y-m-d', strtotime('-59 day')), 'to' => date('Y-m-d', strtotime('-45 day'))]);
        
        $highvalue_product_45to60days->getSelect()->joinLeft(
            [ 'stocktable' => 'cataloginventory_stock_status' ],
            'e.entity_id = stocktable.product_id',
            []
        );

        $subquery = new \Zend_Db_Expr('(SELECT sku, SUM(quantity) as salableqty FROM inventory_reservation GROUP BY sku)');
        $joinConditions = 'e.sku = reservetable.sku';
        $highvalue_product_45to60days->getSelect()->joinLeft(
            [ 'reservetable' => $subquery ],
            $joinConditions,
            []
        )->columns("(IFNULL(reservetable.salableqty,0) + stocktable.qty) AS final_qty")
                     ->where("(IFNULL(reservetable.salableqty,0) + stocktable.qty) > 0");

        // high value old stock (gteq 45 lteq 60 days)
        $highvalue_product_60days = $productCollectionFactory->create();
        $highvalue_product_60days->addAttributeToFilter('status', 1);
        $highvalue_product_60days->addAttributeToFilter('visibility', ['4']);
        $highvalue_product_60days->addAttributeToFilter('price', ['gteq' => '30000']);
        $this->_stockFilter->addInStockFilterToCollection($highvalue_product_60days);
        $sku = 'ES';
        $highvalue_product_60days->addAttributeToFilter('sku', [
                    ['like' => '%'.$sku.'%'], //spaces on each side
                    ['like' => '%'.$sku], //space before and ends with $needle
                    ['like' => $sku.'%'] // starts with needle and space after
        ]);
        $highvalue_product_60days->addAttributeToFilter('created_at', ['to' => date('Y-m-d', strtotime('-60 day'))]);
        
        $highvalue_product_60days->getSelect()->joinLeft(
            [ 'stocktable' => 'cataloginventory_stock_status' ],
            'e.entity_id = stocktable.product_id',
            []
        );

        $subquery = new \Zend_Db_Expr('(SELECT sku, SUM(quantity) as salableqty FROM inventory_reservation GROUP BY sku)');
        $joinConditions = 'e.sku = reservetable.sku';
        $highvalue_product_60days->getSelect()->joinLeft(
            [ 'reservetable' => $subquery ],
            $joinConditions,
            []
        )->columns("(IFNULL(reservetable.salableqty,0) + stocktable.qty) AS final_qty")
                     ->where("(IFNULL(reservetable.salableqty,0) + stocktable.qty) > 0");

        // GA Data
        $analytics = initializeAnalytics();

        $VIEW_ID = "115854344";

        // Create the DateRange object.
        $dateRange = dateRange();
        $dateRange->setStartDate($start);
        $dateRange->setEndDate($end);

        // Create the Metrics object.
        $avgSessionDuration = metric();
        $avgSessionDuration->setExpression("ga:avgSessionDuration");
        $avgSessionDuration->setAlias("avgSessionDuration");

        $users = metric();
        $users->setExpression("ga:users");
        $users->setAlias("users");

        $newUsers = metric();
        $newUsers->setExpression("ga:newUsers");
        $newUsers->setAlias("newUsers");

        $bounceRate = metric();
        $bounceRate->setExpression("ga:bounceRate");
        $bounceRate->setAlias("bounceRate");

        // Create the ReportRequest object.
        $request = reportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics([$avgSessionDuration, $users, $newUsers, $bounceRate]);
        $request->setPageSize(10000);

        $body = getReportsRequest();
        $body->setReportRequests([ $request]);
        $reports = $analytics->reports->batchGet($body);

        $gareports = $this->printGaReports($reports, $type=null);
        
        // 30 day rolling repeat - user rate

        $thirty_day_start = date('Y-m-d', strtotime('-30 days', strtotime($end)));
        
        // print_r($thirty_day_start); die;
        
        $thirty_day_dateRange = dateRange();
        $thirty_day_dateRange->setStartDate($thirty_day_start);
        $thirty_day_dateRange->setEndDate($end);

        $userType = dimension();
        $userType->setName("ga:userType");

        $thirty_day_request = reportRequest();
        $thirty_day_request->setViewId($VIEW_ID);
        $thirty_day_request->setDateRanges($thirty_day_dateRange);
        $thirty_day_request->setMetrics([$users]);
        $thirty_day_request->setDimensions([$userType]);
        $thirty_day_request->setPageSize(10000);

        $thirty_day_body = getReportsRequest();
        $thirty_day_body->setReportRequests([ $thirty_day_request]);
        $thirty_day_reports = $analytics->reports->batchGet($thirty_day_body);
        // print_r($thirty_day_reports); die;
        $thirty_day_gareports = $this->printGaReports($thirty_day_reports, $type=null);
        // print_r("hah");
        // print_r($thirty_day_gareports); die;

        foreach ($thirty_day_gareports as $thirtyreport) {
            if ($thirtyreport['ga:userType'] == "New Visitor") {
                $thirty_new_visitor = $thirtyreport['users'];
            } elseif ($thirtyreport['ga:userType'] == "Returning Visitor") {
                $thirty_returning_visitor = $thirtyreport['users'];
            }
        }

        // echo "<pre>";
        // print_r($thirty_day_gareports);
        // die;

        // Cart and Checkout Abandonment
        // $abandontypes = array("ADD_TO_CART", "CART_ABANDONMENT", "CHECKOUT", "CHECKOUT_ABANDONMENT");

        // $abandonitems = array();

        // foreach ($abandontypes as $abandontype) {
            // $abandonreports = $this->fetchAbandonedGaReports($analytics, $abandontype, $start, $end);
            // $report_print = $this->printGaReports($abandonreports, $type=null);
            // $abandonitems[$abandontype] = $report_print[0]['sessions'];
        // }

        // echo "<pre>";
        // print_r($abandonitems); die;

        $business = [];
        $business['ad_sale'] = $orderlist[0]['totalsales'] / $datediff;
        $business['ad_orders'] = $orderlist[0]['ordercount'] / $datediff;
        $business['ad_sos'] = $adsos_list[0]['avgsos'];
        $business['ad_saree'] = $productcount['ES'] / $datediff;
        $business['ad_etnische'] = 0;
        $business['ad_spend'] = $orderlist[0]['totalsales'] / $orderlist[0]['ordercount'];
        $business['ad_sessiontime'] = $gareports['0']['avgSessionDuration'];
        $business['ad_newcustomers'] = $confirmed_customers / $datediff;
        $business['ad_revenue_first'] = $firsttimelist[0]['totalsales'] / $datediff;
        $business['ad_orders_first'] = $firsttimelist[0]['ordercount'] / $datediff;
        $business['ad_revenue_repeat'] = $repeatlist[0]['totalsales'] / $datediff;
        $business['ad_orders_repeat'] = $repeatlist[0]['ordercount'] / $datediff;
        // $business['ad_orders_repeat_precent'] = ($business['ad_revenue_repeat'] / $business['ad_sale']) * 100;
        $business['ad_orders_repeat_precent'] = ($repeatlist[0]['totalsales'] / $orderlist[0]['totalsales']) * 100;
        $business['ad_upload'] = $uploads / $datediff;
        // $business['ad_pdp'] = $ad_pdp_list[0]['avg_PDP'];
        // $business['ad_pdp_conversion'] = $ad_pdp_conv_list[0]['avg_PDP'];
        $business['ad_pdp'] = '';
        $business['ad_pdp_conversion'] = '';
        $business['ad_atc'] = $quoteitemlist[0]['itemcount'] / $datediff;
        $business['ad_newusers'] = $gareports['0']['newUsers'] / $datediff;
        $business['ad_repeatusers'] = ($gareports['0']['users'] - $gareports['0']['newUsers']) / $datediff;
        $business['atc_order_time'] = $diffsumlist[0]['diffsum'] / 3600;
        $business['thirty_rolling_user'] = ($thirty_returning_visitor / ($thirty_returning_visitor + $thirty_new_visitor)) * 100;
        $business['thirty_rolling_purchase'] = $thirtyrolling_purc_list[0]['rep_cust'] / $thirtyrolling_purc_list[0]['all_cust'];
        $business['bounce_rate'] = $gareports['0']['bounceRate'];
        $business['downtime'] = '';
        $business['pgfailure_india'] = '';
        $business['pgfailure_international'] = '';
        $business['succretry_india'] = ($succretry_india_list[0]['distinctcustomers'] / $orderlist[0]['distinctcustomers']) * 100;
        $business['succretry_international'] = (
            $succretry_row_list[0]['distinctcustomers'] / $orderlist[0]['distinctcustomers']
        ) * 100;
        // $business['abandonedcart'] = ($abandonitems['CART_ABANDONMENT'] / $abandonitems['ADD_TO_CART'])*100;
        // $business['abandonedcheckout'] = ($abandonitems['CHECKOUT_ABANDONMENT'] / $abandonitems['CHECKOUT'])*100;
        $business['abandonedcart'] = '';
        $business['abandonedcheckout'] = '';
        $business['fresh_stock_lt15days'] = (count($products_gteq15) / count($products))*100;
        $business['old_stock_gt60days'] = (count($product_60days) / count($products))*100;
        $business['old_stock_highvalue_45to60days'] = (count($highvalue_product_45to60days) / count($products))*100;
        $business['old_stock_highvalue_gt60days'] = (count($highvalue_product_60days) / count($products))*100;
        $business['ad_newbuyers'] = $firsttimelist[0]['distinctcustomers'] / $datediff;
        $business['class_a_categories'] = '';
        $business['class_b_categories'] = '';
        $business['class_n_categories'] = '';
        $business['nps_rating'] = '';
        $business['total_returns'] = '';

        $businessfinal = [];
        $businessfinal[] = ['label' => 'label', 'value' => 'value'];
        foreach ($business as $businesskey => $businessvalue) {
            $businessind = [];
            $businessind['label'] = $businesskey;
            $businessind['value'] = $businessvalue;
            $businessfinal[] = $businessind;
        }

        // echo "<pre>";
        // print_r($businessfinal); die;

        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "businessoverview.csv";
        $mage_csv->saveData($file_path, $businessfinal);
        $filename = "businessoverview.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);

        die;
    }
}
