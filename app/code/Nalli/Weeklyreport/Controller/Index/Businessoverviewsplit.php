<?php
namespace Nalli\Weeklyreport\Controller\Index;

class Businessoverviewsplit extends \Magento\Framework\App\Action\Action
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

    protected function printGaReports($reports, $type)
    {

        $reportdata = [];

        for ($reportIndex = 0; $reportIndex < count($reports); $reportIndex++) {
            $report = $reports[ $reportIndex ];
            $header = $report->getColumnHeader();
            $dimensionHeaders = $header->getDimensions();
            $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
            $rows = $report->getData()->getRows();

            for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
                $reportitem = [];
                $row = $rows[ $rowIndex ];
                $dimensions = $row->getDimensions();
                $metrics = $row->getMetrics();
                if (is_array($dimensionHeaders) && is_array($dimensions)) {
                    for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
                        // print($dimensionHeaders[$i] . ": " . $dimensions[$i] . "\n");
                        $dheader = $dimensionHeaders[$i];
                        $reportitem[$dheader] = $dimensions[$i];
                    }
                }
                if (is_array($metrics)) {
                    for ($j = 0; $j < count($metrics); $j++) {
                        $values = $metrics[$j]->getValues();
                        for ($k = 0; $k < count($values); $k++) {
                            $entry = $metricHeaders[$k];
                            // print($entry->getName() . ": " . $values[$k] . "<br>");
                            $reportitem[$entry->getName()] = $values[$k];
                        }
                    }
                }

                $reportdata[] = $reportitem;
            }
        }

        return $reportdata;
    }

    public function execute()
    {
        require_once $this->_dir->getPath('lib_internal').'/Google/helloanalytics.php';

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
        
        
        $start = $data['from'];
        $end = $data['to'];

        $datediff = ((strtotime($end) - strtotime($start)) / (60*60*24)) + 1;

        $from = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($start))));
        $to = date('Y-m-d H:i:s', strtotime('-30 minute', strtotime('-5 hour', strtotime($end))));
        $to_end = date('Y-m-d H:i:s', (strtotime('+1 days', strtotime($to) - 1)));

        $from_last = date('Y-m-d H:i:s', (strtotime($from) - 1));


        // Establish DB connection
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        // Order Sum and Count
        $ordersql = "SELECT sum(base_grand_total) as totalsales, COUNT(increment_id) as ordercount, COUNT(DISTINCT(customer_id)) as distinctcustomers FROM `sales_order` WHERE `status` IN ('processing','shipped','complete') AND `created_at` BETWEEN '".$from."' AND '".$to_end ."'";

        $orderlist = $connection->fetchAll($ordersql);

        // Order Sum and Count - India
        $ordersql_india = "SELECT sum(sales_order.base_grand_total) as totalsales, COUNT(sales_order.increment_id) as ordercount, COUNT(DISTINCT(sales_order.customer_id)) as distinctcustomers FROM `sales_order` LEFT JOIN ipdetails on sales_order.entity_id = ipdetails.order_id WHERE `sales_order`.`status` IN ('processing','shipped','complete') AND `sales_order`.`created_at` BETWEEN '".$from."' AND '".$to_end ."' and ipdetails.country_id = 'IN'";

        $orderlist_india = $connection->fetchAll($ordersql_india);

        // Order Sum and Count - ROW
        $ordersql_row = "SELECT sum(sales_order.base_grand_total) as totalsales, COUNT(sales_order.increment_id) as ordercount, COUNT(DISTINCT(sales_order.customer_id)) as distinctcustomers FROM `sales_order` LEFT JOIN ipdetails on sales_order.entity_id = ipdetails.order_id WHERE `sales_order`.`status` IN ('processing','shipped','complete') AND `sales_order`.`created_at` BETWEEN '".$from."' AND '".$to_end ."' and ipdetails.country_id != 'IN'";

        $orderlist_row = $connection->fetchAll($ordersql_row);

        // Products ES, ET, etc
        $productsql = "SELECT LEFT(sku,2) as CAT, sum(qty_ordered) as qty, sum(base_price * qty_ordered) as pricetotal FROM sales_order_item LEFT JOIN sales_order on sales_order_item.order_id = sales_order.entity_id where sales_order.status in ('processing','shipped','complete') and sales_order_item.parent_item_id is NULL AND sales_order_item.created_at BETWEEN '".$from."' AND '".$to_end ."' group by CAT";

        $productlist = $connection->fetchAll($productsql);

        $productcount = $producttotal = [];
        $productcount['custom'] = 0;
        $producttotal['custom'] = 0;
        $productcount['total'] = 0;
        $producttotal['total'] = 0;
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

        // Products ES, ET, etc
        $productsql_india = "SELECT LEFT(sku,2) as CAT, sum(qty_ordered) as qty, sum(base_price * qty_ordered) as pricetotal FROM sales_order_item LEFT JOIN sales_order on sales_order_item.order_id = sales_order.entity_id LEFT JOIN ipdetails on sales_order.entity_id = ipdetails.order_id where sales_order.status in ('processing','shipped','complete') AND sales_order_item.parent_item_id is NULL AND ipdetails.country_id = 'IN' AND sales_order_item.created_at BETWEEN '".$from."' AND '".$to_end ."' group by CAT";

        $productlist_india = $connection->fetchAll($productsql_india);

        $productcount_india = $producttotal_india = [];
        $noncustom_india = ['ES', 'ET', 'GV'];
        $productcount_india['custom'] = 0;
        $producttotal_india['custom'] = 0;
        $productcount_india['total'] = 0;
        $producttotal_india['total'] = 0;
        foreach ($productlist_india as $fields_india) {
            $cat_india = $fields_india['CAT'];
            if (in_array($cat_india, $noncustom_india)) {
                $productcount_india[$cat_india] = $fields_india['qty'];
                $producttotal_india[$cat_india] = $fields_india['pricetotal'];
            } else {
                $productcount_india['custom'] += $fields_india['qty'];
                $producttotal_india['custom'] += $fields_india['pricetotal'];
            }

            $productcount_india['total'] += $fields_india['qty'];
            $producttotal_india['total'] += $fields_india['pricetotal'];
        }

        // Products ES, ET, etc
        $productsql_row = "SELECT LEFT(sku,2) as CAT, sum(qty_ordered) as qty, sum(base_price * qty_ordered) as pricetotal FROM sales_order_item LEFT JOIN sales_order on sales_order_item.order_id = sales_order.entity_id LEFT JOIN ipdetails on sales_order.entity_id = ipdetails.order_id where sales_order.status in ('processing','shipped','complete') AND sales_order_item.parent_item_id is NULL AND ipdetails.country_id != 'IN' AND sales_order_item.created_at BETWEEN '".$from."' AND '".$to_end ."' group by CAT";

        $productlist_row = $connection->fetchAll($productsql_row);

        $productcount_row = $producttotal_row = [];
        $noncustom_row = ['ES', 'ET', 'GV'];
        
        $productcount_row['custom'] = 0;
        $producttotal_row['custom'] = 0;
        $productcount_row['total'] = 0;
        $producttotal_row['total'] = 0;
        
        foreach ($productlist_row as $fields_row) {
            $cat_row = $fields_row['CAT'];
            if (in_array($cat_row, $noncustom_row)) {
                $productcount_row[$cat_row] = $fields_row['qty'];
                $producttotal_row[$cat_row] = $fields_row['pricetotal'];
            } else {
                $productcount_row['custom'] += $fields_row['qty'];
                $producttotal_row['custom'] += $fields_row['pricetotal'];
            }

            $productcount_row['total'] += $fields_row['qty'];
            $producttotal_row['total'] += $fields_row['pricetotal'];
        }

        // AD Orders, Revenue from First time Customers

        $firsttime = "SELECT sum(base_grand_total) as totalsales, COUNT(increment_id) as ordercount, COUNT(DISTINCT(customer_id)) as distinctcustomers FROM `sales_order` WHERE increment_id in (SELECT increment_id FROM `sales_order` WHERE `created_at` BETWEEN '".$from."' AND '".$to_end."' AND STATUS in ('processing', 'shipped', 'complete') and customer_id not in (select customer_id FROM `sales_order` WHERE `created_at` BETWEEN '2016-01-01 00:00:00.000000' AND '".$from_last."' AND STATUS in ('processing', 'shipped', 'complete')))";

        $firsttimelist = $connection->fetchAll($firsttime);

        // AD Orders, Revenue from First time Customers - India

        $firsttime_india = "SELECT sum(sales_order.base_grand_total) as totalsales, COUNT(sales_order.increment_id) as ordercount, COUNT(DISTINCT(sales_order.customer_id)) as distinctcustomers FROM `sales_order` LEFT JOIN ipdetails on sales_order.entity_id = ipdetails.order_id WHERE sales_order.increment_id in (SELECT increment_id FROM `sales_order` WHERE `created_at` BETWEEN '".$from."' AND '".$to_end."' AND STATUS in ('processing', 'shipped', 'complete') and customer_id not in (select customer_id FROM `sales_order` WHERE `created_at` BETWEEN '2016-01-01 00:00:00.000000' AND '".$from_last."' AND STATUS in ('processing', 'shipped', 'complete'))) and ipdetails.country_id = 'IN'";

        $firsttimelist_india = $connection->fetchAll($firsttime_india);

        // AD Orders, Revenue from First time Customers - ROW

        $firsttime_row = "SELECT sum(sales_order.base_grand_total) as totalsales, COUNT(sales_order.increment_id) as ordercount, COUNT(DISTINCT(sales_order.customer_id)) as distinctcustomers FROM `sales_order` LEFT JOIN ipdetails on sales_order.entity_id = ipdetails.order_id WHERE sales_order.increment_id in (SELECT increment_id FROM `sales_order` WHERE `created_at` BETWEEN '".$from."' AND '".$to_end."' AND STATUS in ('processing', 'shipped', 'complete') and customer_id not in (select customer_id FROM `sales_order` WHERE `created_at` BETWEEN '2016-01-01 00:00:00.000000' AND '".$from_last."' AND STATUS in ('processing', 'shipped', 'complete'))) and ipdetails.country_id != 'IN'";

        $firsttimelist_row = $connection->fetchAll($firsttime_row);

        // AD Orders, Revenue from Repeat Customers
        
        $repeat = "SELECT sum(base_grand_total) as totalsales, COUNT(increment_id) as ordercount FROM `sales_order` WHERE increment_id in (SELECT increment_id FROM `sales_order` WHERE `created_at` BETWEEN '".$from."' AND '".$to_end."' AND STATUS in ('processing', 'shipped', 'complete') and customer_id in (select customer_id FROM `sales_order` WHERE `created_at` BETWEEN '2016-01-01 00:00:00.000000' AND '".$from_last."' AND STATUS in ('processing', 'shipped', 'complete')))";

        $repeatlist = $connection->fetchAll($repeat);

        // AD Orders, Revenue from Repeat Customers - India

        $repeat_india = "SELECT sum(sales_order.base_grand_total) as totalsales, COUNT(sales_order.increment_id) as ordercount FROM `sales_order` LEFT JOIN ipdetails on sales_order.entity_id = ipdetails.order_id WHERE sales_order.increment_id in (SELECT increment_id FROM `sales_order` WHERE `created_at` BETWEEN '".$from."' AND '".$to_end."' AND STATUS in ('processing', 'shipped', 'complete') and customer_id in (select customer_id FROM `sales_order` WHERE `created_at` BETWEEN '2016-01-01 00:00:00.000000' AND '".$from_last."' AND STATUS in ('processing', 'shipped', 'complete'))) and ipdetails.country_id = 'IN'";

        $repeatlist_india = $connection->fetchAll($repeat_india);

        // AD Orders, Revenue from Repeat Customers - ROW

        $repeat_row = "SELECT sum(sales_order.base_grand_total) as totalsales, COUNT(sales_order.increment_id) as ordercount FROM `sales_order` LEFT JOIN ipdetails on sales_order.entity_id = ipdetails.order_id WHERE sales_order.increment_id in (SELECT increment_id FROM `sales_order` WHERE `created_at` BETWEEN '".$from."' AND '".$to_end."' AND STATUS in ('processing', 'shipped', 'complete') and customer_id in (select customer_id FROM `sales_order` WHERE `created_at` BETWEEN '2016-01-01 00:00:00.000000' AND '".$from_last."' AND STATUS in ('processing', 'shipped', 'complete'))) and ipdetails.country_id != 'IN'";

        $repeatlist_row = $connection->fetchAll($repeat_row);

        // GA Data
        $analytics = initializeAnalytics();

        $VIEW_ID = "115854344";

        // Create the DateRange object.
        $dateRange = daterange();
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

        $dimensionfilterclause_india = dimensionFilterClause();
        $dimensionfilterclause_india->setOperator("AND");

        $dimensionfilter_india = dimensionFilter();
        $dimensionfilter_india->setDimensionName("ga:countryIsoCode");
        $dimensionfilter_india->setNot(false);
        $dimensionfilter_india->getOperator("EXACT");
        $dimensionfilter_india->setExpressions(["IN"]);
        $dimensionfilter_india->setCaseSensitive(false);

        $dimensionfilterclause_india->setFilters([$dimensionfilter_india]);


        // Create the ReportRequest object.
        $request_india = reportRequest();
        $request_india->setViewId($VIEW_ID);
        $request_india->setDateRanges($dateRange);
        $request_india->setMetrics([$avgSessionDuration, $users, $newUsers, $bounceRate]);
        $request_india->setDimensionFilterClauses([$dimensionfilterclause_india]);
        $request_india->setPageSize(10000);

        $body_india = getReportsRequest();
        $body_india->setReportRequests([ $request_india]);
        $reports_india = $analytics->reports->batchGet($body_india);

        $gareports_india = $this->printGaReports($reports_india, $type=null);

        $dimensionfilterclause_row = dimensionFilterClause();
        $dimensionfilterclause_row->setOperator("AND");

        $dimensionfilter_row = dimensionFilter();
        $dimensionfilter_row->setDimensionName("ga:countryIsoCode");
        $dimensionfilter_row->setNot(true);
        $dimensionfilter_row->getOperator("EXACT");
        $dimensionfilter_row->setExpressions(["IN"]);
        $dimensionfilter_row->setCaseSensitive(false);

        $dimensionfilterclause_row->setFilters([$dimensionfilter_row]);


        // Create the ReportRequest object.
        $request_row = reportRequest();
        $request_row->setViewId($VIEW_ID);
        $request_row->setDateRanges($dateRange);
        $request_row->setMetrics([$avgSessionDuration, $users, $newUsers, $bounceRate]);
        $request_row->setDimensionFilterClauses([$dimensionfilterclause_row]);
        $request_row->setPageSize(10000);

        $body_row = getReportsRequest();
        $body_row->setReportRequests([ $request_row]);
        $reports_row = $analytics->reports->batchGet($body_row);

        $gareports_row = $this->printGaReports($reports_row, $type=null);
        
        $business = [];
        $business['ad_sale'] = $orderlist[0]['totalsales'] / $datediff;
        $business['ad_orders'] = $orderlist[0]['ordercount'] / $datediff;
        if(array_key_exists("ES",$productcount)){
            $business['ad_saree'] = $productcount['ES'] / $datediff;
        }
        // echo "<pre>"; print_r($orderlist); exit;
        $business['ad_etnische'] = 0;
        if($orderlist[0]['totalsales'] != ''){
            $business['ad_spend'] = $orderlist[0]['totalsales'] / $orderlist[0]['ordercount'];
        }
        $business['ad_sessiontime'] = $gareports['0']['avgSessionDuration'];
        $business['ad_revenue_first'] = $firsttimelist[0]['totalsales'] / $datediff;
        $business['ad_orders_first'] = $firsttimelist[0]['ordercount'] / $datediff;
        $business['ad_revenue_repeat'] = $repeatlist[0]['totalsales'] / $datediff;
        $business['ad_orders_repeat'] = $repeatlist[0]['ordercount'] / $datediff;
        if($repeatlist[0]['totalsales'] != ''){
            $business['ad_orders_repeat_percent'] = ($repeatlist[0]['totalsales'] / $orderlist[0]['totalsales']) * 100;
        }
        $business['ad_newusers'] = $gareports['0']['newUsers'] / $datediff;
        $business['ad_repeatusers'] = ($gareports['0']['users'] - $gareports['0']['newUsers']) / $datediff;
        $business['bounce_rate'] = $gareports['0']['bounceRate'];
        $business['ad_newbuyers'] = $firsttimelist[0]['distinctcustomers'] / $datediff;

        //India
        $business['ad_sale_india'] = $orderlist_india[0]['totalsales'] / $datediff;
        $business['ad_orders_india'] = $orderlist_india[0]['ordercount'] / $datediff;
        if(array_key_exists("ES",$productcount_india)){
            $business['ad_saree_india'] = $productcount_india['ES'] / $datediff;
        }
        $business['ad_etnische_india'] = 0;
         if($orderlist_india[0]['totalsales'] != ''){
            $business['ad_spend_india'] = $orderlist_india[0]['totalsales'] / $orderlist_india[0]['ordercount'];
         }
        $business['ad_sessiontime_india'] = $gareports_india['0']['avgSessionDuration'];
        $business['ad_revenue_first_india'] = $firsttimelist_india[0]['totalsales'] / $datediff;
        $business['ad_orders_first_india'] = $firsttimelist_india[0]['ordercount'] / $datediff;
        $business['ad_revenue_repeat_india'] = $repeatlist_india[0]['totalsales'] / $datediff;
        $business['ad_orders_repeat_india'] = $repeatlist_india[0]['ordercount'] / $datediff;
        if($repeatlist_india[0]['totalsales'] != ''){
            $business['ad_orders_repeat_percent_india'] = ($repeatlist_india[0]['totalsales'] / $orderlist_india[0]['totalsales']) * 100;
        }
        $business['ad_newusers_india'] = $gareports_india['0']['newUsers'] / $datediff;
        $business['ad_repeatusers_india'] = ($gareports_india['0']['users'] - $gareports_india['0']['newUsers']) / $datediff;
        $business['bounce_rate_india'] = $gareports_india['0']['bounceRate'];
        $business['ad_newbuyers_india'] = $firsttimelist_india[0]['distinctcustomers'] / $datediff;

        //ROW
        $business['ad_sale_row'] = $orderlist_row[0]['totalsales'] / $datediff;
        $business['ad_orders_row'] = $orderlist_row[0]['ordercount'] / $datediff;
        if(array_key_exists("ES",$productcount_row)){
                    $business['ad_saree_row'] = $productcount_row['ES'] / $datediff;
        }
        $business['ad_etnische_row'] = 0;
        if($orderlist_row[0]['totalsales'] != ''){
            $business['ad_spend_row'] = $orderlist_row[0]['totalsales'] / $orderlist_row[0]['ordercount'];
        }
        $business['ad_sessiontime_row'] = $gareports_row['0']['avgSessionDuration'];
        $business['ad_revenue_first_row'] = $firsttimelist_row[0]['totalsales'] / $datediff;
        $business['ad_orders_first_row'] = $firsttimelist_row[0]['ordercount'] / $datediff;
        $business['ad_revenue_repeat_row'] = $repeatlist_row[0]['totalsales'] / $datediff;
        $business['ad_orders_repeat_row'] = $repeatlist_row[0]['ordercount'] / $datediff;
        if($repeatlist_row[0]['totalsales'] != ''){
            $business['ad_orders_repeat_percent_row'] = ($repeatlist_row[0]['totalsales'] / $orderlist_row[0]['totalsales']) * 100;
        }
        $business['ad_newusers_row'] = $gareports_row['0']['newUsers'] / $datediff;
        $business['ad_repeatusers_row'] = ($gareports_row['0']['users'] - $gareports_row['0']['newUsers']) / $datediff;
        $business['bounce_rate_row'] = $gareports_row['0']['bounceRate'];
        $business['ad_newbuyers_row'] = $firsttimelist_row[0]['distinctcustomers'] / $datediff;

        $businessfinal = [];
        $businessfinal[] = ['label' => 'label', 'value' => 'value'];
        foreach ($business as $businesskey => $businessvalue) {
            $businessind = [];
            $businessind['label'] = $businesskey;
            $businessind['value'] = $businessvalue;
            $businessfinal[] = $businessind;
        }

        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "businessoverviewsplit.csv";
        $mage_csv->saveData($file_path, $businessfinal);
        $filename = "businessoverviewsplit.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);

        die;
    }
}
