<?php
namespace Nalli\Weeklyreport\Controller\Index;

class Monthly extends \Magento\Framework\App\Action\Action
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
        $ordersql = "SELECT sum(base_grand_total) as totalsales, COUNT(increment_id) as ordercount FROM `sales_order` WHERE `status` IN ('processing','shipped','complete') AND `created_at` BETWEEN '".$from."' AND '".$to_end ."'";

        // Products ES, ET, etc
        $productsql = "SELECT LEFT(sku,2) as CAT, sum(qty_ordered) as qty, sum(base_price * qty_ordered) as pricetotal FROM sales_order_item LEFT JOIN sales_order on sales_order_item.order_id = sales_order.entity_id where sales_order.status in ('processing','shipped','complete') and sales_order_item.parent_item_id is NULL AND sales_order_item.created_at BETWEEN '".$from."' AND '".$to_end ."' group by CAT";
         // echo $productsql; die();
        $firsttime = "SELECT sum(base_grand_total) as totalsales, COUNT(increment_id) as ordercount FROM `sales_order` WHERE increment_id in (SELECT increment_id FROM `sales_order` WHERE `created_at` BETWEEN '".$from."' AND '".$to_end."' AND STATUS in ('processing', 'shipped', 'complete') and customer_id not in (select customer_id FROM `sales_order` WHERE `created_at` BETWEEN '2016-01-01 00:00:00.000000' AND '".$from_last."' AND STATUS in ('processing', 'shipped', 'complete')))";

        $repeat = "SELECT sum(base_grand_total) as totalsales, COUNT(increment_id) as ordercount FROM `sales_order` WHERE increment_id in (SELECT increment_id FROM `sales_order` WHERE `created_at` BETWEEN '".$from."' AND '".$to_end."' AND STATUS in ('processing', 'shipped', 'complete') and customer_id in (select customer_id FROM `sales_order` WHERE `created_at` BETWEEN '2016-01-01 00:00:00.000000' AND '".$from_last."' AND STATUS in ('processing', 'shipped', 'complete')))";

        $orderlist = $connection->fetchAll($ordersql);
        $productlist = $connection->fetchAll($productsql);
        $firsttimelist = $connection->fetchAll($firsttime);
        $repeatlist = $connection->fetchAll($repeat);

        $indiasales_sql = "SELECT SUM(base_grand_total) as totalrevenue FROM sales_order LEFT JOIN ipdetails on sales_order.entity_id = ipdetails.order_id where sales_order.status in ('processing','shipped','complete') AND sales_order.created_at BETWEEN  '".$from."' AND '".$to_end."' AND ipdetails.country_id = 'IN' ";
        $indiasales_list = $connection->fetchAll($indiasales_sql);

        // print_r($indiasales_list); die;

        $rowsales_sql = "SELECT SUM(base_grand_total) as totalrevenue FROM sales_order LEFT JOIN ipdetails on sales_order.entity_id = ipdetails.order_id where sales_order.status in ('processing','shipped','complete') AND sales_order.created_at BETWEEN  '".$from."' AND '".$to_end."' AND ipdetails.country_id != 'IN' ";
        $rowsales_list = $connection->fetchAll($rowsales_sql);

        $productcount = $producttotal = [];
        $noncustom = ['ES', 'ET', 'GV'];
        
        
        $productcount['total'] = 0;
        $producttotal['total'] = 0;
        $productcount['custom'] = 0;
        $producttotal['custom'] = 0;
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

        // GA Data
        $analytics = initializeAnalytics();

        //echo "<pre>";
        // print_r(test()); die;

        $VIEW_ID = "115854344";

        // Create the DateRange object.
        $dateRange = dateRange();
        $dateRange->setStartDate($start);
        $dateRange->setEndDate($end);

        // Create the Metrics object.
        $avgSessionDuration = metric();
        $avgSessionDuration->setExpression("ga:avgSessionDuration");
        $avgSessionDuration->setAlias("avgSessionDuration");

        // Create the ReportRequest object.
        $request = reportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics([$avgSessionDuration]);
        $request->setPageSize(10000);

        $body = getReportsRequest();
        $body->setReportRequests([ $request]);
        $reports = $analytics->reports->batchGet($body);

        $gareports = $this->printGaReports($reports, $type=null);

        $monthly = [];
        $monthly['ad_revenue'] = $orderlist[0]['totalsales'] / $datediff;
        $monthly['ad_orders'] = $orderlist[0]['ordercount'] / $datediff;
        $monthly['ad_sos'] = $adsos_list[0]['avgsos'];
        $monthly['upt'] = $productcount['total'] / $orderlist[0]['ordercount'];
        $monthly['aisp_saree'] = $producttotal['ES'] / $productcount['ES'];
        $monthly['ad_saree'] = $productcount['ES'] / $datediff;
        $monthly['ad_customers'] = $confirmed_customers / $datediff;
        $monthly['ad_session'] = $gareports['0']['avgSessionDuration'];
        $monthly['ad_revenue_first'] = $firsttimelist[0]['totalsales'] / $datediff;
        $monthly['ad_orders_first'] = $firsttimelist[0]['ordercount'] / $datediff;
        $monthly['ad_revenue_repeat'] = $repeatlist[0]['totalsales'] / $datediff;
        $monthly['ad_orders_repeat'] = $repeatlist[0]['ordercount'] / $datediff;
        $monthly['product_returns'] = '';
        $monthly['total_orders'] = $orderlist[0]['ordercount'];
        $monthly['total_revenue'] = $orderlist[0]['totalsales'];
        $monthly['total_india'] = $indiasales_list[0]['totalrevenue'];
        $monthly['total_row'] = $rowsales_list[0]['totalrevenue'];
        $monthly['total_custom_orders'] = $productcount['custom'];
        $monthly['total_custom_order_sales'] = $producttotal['custom'];
        $monthly['from_date'] = $start;
        $monthly['to_date'] = $end;

        $monthlyfinal = [];
        $monthlyfinal[] = ['label' => 'label', 'value' => 'value'];
        foreach ($monthly as $monthlykey => $monthlyvalue) {
            $monthlyind = [];
            $monthlyind['label'] = $monthlykey;
            $monthlyind['value'] = $monthlyvalue;
            $monthlyfinal[] = $monthlyind;
        }

        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "monthly.csv";
        $mage_csv->saveData($file_path, $monthlyfinal);
        $filename = "monthly.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);
        die;
    }
}
