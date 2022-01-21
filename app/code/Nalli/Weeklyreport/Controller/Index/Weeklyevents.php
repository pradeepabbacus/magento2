<?php
namespace Nalli\Weeklyreport\Controller\Index;

class Weeklyevents extends \Magento\Framework\App\Action\Action
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

    protected function fetchWeeklyGaReports($analytics, $type, $start, $end)
    {

        $VIEW_ID = "115854344";

        // Create the DateRange object.
        $dateRange = daterange();
        $dateRange->setStartDate($start);
        $dateRange->setEndDate($end);

        // Create the Metrics object.
        $sessions = metric();
        $sessions->setExpression("ga:sessions");
        $sessions->setAlias("sessions");

        $sessionDuration = metric();
        $sessionDuration->setExpression("ga:sessionDuration");
        $sessionDuration->setAlias("sessionDuration");

        $totalEvents = metric();
        $totalEvents->setExpression("ga:totalEvents");
        $totalEvents->setAlias("totalEvents");

        $uniqueEvents = metric();
        $uniqueEvents->setExpression("ga:uniqueEvents");
        $uniqueEvents->setAlias("uniqueEvents");

        $transactions = metric();
        $transactions->setExpression("ga:transactions");
        $transactions->setAlias("transactions");

        $transactionRevenue = metric();
        $transactionRevenue->setExpression("ga:transactionRevenue");
        $transactionRevenue->setAlias("transactionRevenue");

        $avgSessionDuration = metric();
        $avgSessionDuration->setExpression("ga:avgSessionDuration");
        $avgSessionDuration->setAlias("avgSessionDuration");

        // Dimensions
        $eventCategory = dimension();
        $eventCategory->setName("ga:eventCategory");

        $eventAction = dimension();
        $eventAction->setName("ga:eventAction");

        $eventLabel = dimension();
        $eventLabel->setName("ga:eventLabel");

        switch ($type) {
            case "device":
                $deviceCategory = dimension();
                $deviceCategory->setName("ga:deviceCategory");

            case 'all':
                break;

            default:
                # code...
                break;
        }
        
        // Remove Client ID
        
        $dimensionfilterclause = dimensionfilterclause();
        $dimensionfilterclause->setOperator("AND");

        $dimensionfilter = dimensionFilter();
        $dimensionfilter->setDimensionName("ga:eventCategory");
        $dimensionfilter->setNot(true);
        $dimensionfilter->getOperator("EXACT");
        $dimensionfilter->setExpressions(["Client ID"]);

        $dimensionfilterclause->setFilters([$dimensionfilter]);


        // Create the ReportRequest object.
        $request = reportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics([$sessions, $sessionDuration, $totalEvents, $uniqueEvents, $transactions, $transactionRevenue, $avgSessionDuration]);
        $request->setDimensionFilterClauses([$dimensionfilterclause]);
        
        if ($type == "all") {
            $request->setDimensions([$eventCategory, $eventAction, $eventLabel]);
        } elseif ($type == "device") {
            $request->setDimensions([$eventCategory, $eventAction, $eventLabel, $deviceCategory]);
        }

        $request->setPageSize(100000);

        $body = getReportsRequest();
        $body->setReportRequests([ $request]);
        // print_r($body); die;
        $reports = $analytics->reports->batchGet($body);

        return $reports;
    }

    protected function printWeeklyReports($reports, $type)
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
                $reportitem['ga:segment'] = '';
                $reportitem['ga:eventCategory'] = '';
                $reportitem['ga:eventAction'] = '';
                $reportitem['ga:eventLabel'] = '';
                $reportitem['sessions'] = '';
                $reportitem['sessionDuration'] = '';
                $reportitem['totalEvents'] = '';
                $reportitem['uniqueEvents'] = '';
                $reportitem['transactions'] = '';
                $reportitem['transactionRevenue'] = '';
                $reportitem['avgSessionDuration'] = '';
                $reportitem['device'] = $type;

                $row = $rows[ $rowIndex ];
                $dimensions = $row->getDimensions();
                $metrics = $row->getMetrics();
                for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
                    $dheader = $dimensionHeaders[$i];
                    $deviceparams = ["ga:deviceCategory"];
                    if (in_array($dheader, $deviceparams)) {
                        $reportitem['device'] = $dimensions[$i];
                    } else {
                        $reportitem[$dheader] = $dimensions[$i];
                    }
                }

                for ($j = 0; $j < count($metrics); $j++) {
                    $values = $metrics[$j]->getValues();
                    for ($k = 0; $k < count($values); $k++) {
                        $entry = $metricHeaders[$k];
                        // print($entry->getName() . ": " . $values[$k] . "<br>");
                        $reportitem[$entry->getName()] = $values[$k];
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

        $analytics = initializeAnalytics();

        $alltypes = ["all", "device"];

        $final = [];

        foreach ($alltypes as $type) {
            $reports = $this->fetchWeeklyGaReports($analytics, $type, $start, $end);
            $final = array_merge($final, $this->printWeeklyReports($reports, $type));
        }

        $keys = array_keys($final['0']);

        array_unshift($final, $keys);

        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "widgets.csv";
        $mage_csv->saveData($file_path, $final);
        $filename = "widgets.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);

        die;
    }
}
