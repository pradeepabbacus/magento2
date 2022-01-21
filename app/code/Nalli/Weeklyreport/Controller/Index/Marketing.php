<?php
namespace Nalli\Weeklyreport\Controller\Index;

class Marketing extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;

    protected $formKeyValidator;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Customer\Model\Session $session,
        \Nalli\Weeklyreport\Helper\Data $helperdata,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_pageFactory = $pageFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->_dir = $dir;
        $this->_customerSession = $session;
        $this->helperdata = $helperdata;
        $this->messageManager = $messageManager;
        return parent::__construct($context);
    }

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

    protected function fetchMarketingGaReports($analytics, $type, $start, $end)
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

        $avgSessionDuration = metric();
        $avgSessionDuration->setExpression("ga:avgSessionDuration");
        $avgSessionDuration->setAlias("avgSessionDuration");

        $bounceRate = metric();
        $bounceRate->setExpression("ga:bounceRate");
        $bounceRate->setAlias("bounceRate");

        $users = metric();
        $users->setExpression("ga:users");
        $users->setAlias("users");

        $transactions = metric();
        $transactions->setExpression("ga:transactions");
        $transactions->setAlias("transactions");

        $transactionRevenue = metric();
        $transactionRevenue->setExpression("ga:transactionRevenue");
        $transactionRevenue->setAlias("transactionRevenue");

        $transactionsPerSession = metric();
        $transactionsPerSession->setExpression("ga:transactionsPerSession");
        $transactionsPerSession->setAlias("transactionsPerSession");

        // Dimensions
        $countryIsoCode = dimension();
        $countryIsoCode->setName("ga:countryIsoCode");

        switch ($type) {
            case 'device':
                $deviceCategory = dimension();
                $deviceCategory->setName("ga:deviceCategory");
                break;

            case 'os':
                $operatingSystem = dimension();
                $operatingSystem->setName("ga:operatingSystem");
                break;

            case 'email':
                $dimensionfilterclause = dimensionfilterclause();
                $dimensionfilterclause->setOperator("AND");

                $dimensionfilter = dimensionFilter();
                $dimensionfilter->setDimensionName("ga:channelGrouping");
                $dimensionfilter->setNot(false);
                $dimensionfilter->getOperator("EXACT");
                $dimensionfilter->setExpressions(["Email"]);
                $dimensionfilter->setCaseSensitive(false);

                $dimensionfilterclause->setFilters([$dimensionfilter]);
                break;

            default:
                # code...
                break;
        }
        // Create the ReportRequest object.
        $request = reportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics(
            [
                $sessions,
                $avgSessionDuration,
                $bounceRate,
                $users,
                $transactions,
                $transactionRevenue,
                $transactionsPerSession
            ]
        );
        if ($type == "email") {
            $request->setDimensions([$countryIsoCode]);
            $request->setDimensionFilterClauses([$dimensionfilterclause]);
        } elseif ($type == "device") {
            $request->setDimensions([$countryIsoCode, $deviceCategory]);
        } elseif ($type == "os") {
            $request->setDimensions([$countryIsoCode, $operatingSystem]);
        } elseif ($type == "channel") {
            $channelGrouping = new Google_Service_AnalyticsReporting_Dimension();
            $channelGrouping->setName("ga:channelGrouping");
            $request->setDimensions([$countryIsoCode, $channelGrouping]);
        }

        $request->setPageSize(10000);

        $body = getReportsRequest();
        $body->setReportRequests([ $request]);
        // print_r($body); die;
        $reports = $analytics->reports->batchGet($body);

        return $reports;
    }

    protected function printMarketingReports($reports, $type)
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
                $reportitem['ga:segment'] = '';
                $reportitem['ga:countryIsoCode'] = '';
                $reportitem['sessions'] = '';
                $reportitem['avgSessionDuration'] = '';
                $reportitem['bounceRate'] = '';
                $reportitem['users'] = '';
                $reportitem['transactions'] = '';
                $reportitem['transactionRevenue'] = '';
                $reportitem['transactionsPerSession'] = '';
                $reportitem['device'] = $type;

                $row = $rows[ $rowIndex ];
                $dimensions = $row->getDimensions();
                $metrics = $row->getMetrics();
                $dimensionHeaderscnt = count($dimensionHeaders);
                $dimensionscnt = count($dimensions);
                for ($i = 0; $i < $dimensionHeaderscnt && $i < $dimensionscnt; $i++) {
                    // print($dimensionHeaders[$i] . ": " . $dimensions[$i] . "\n");
                    $dheader = $dimensionHeaders[$i];
                    $deviceparams = ["ga:deviceCategory", "ga:operatingSystem", "ga:channelGrouping"];
                    if (in_array($dheader, $deviceparams)) {
                        $reportitem['device'] = $dimensions[$i];
                    } else {
                        $reportitem[$dheader] = $dimensions[$i];
                    }
                    
                }
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
            $message = __("You do not have access to this report");
            $this->messageManager->addErrorMessage($message);
            return $this->_redirect('*/*');
        }

        $data = $this->getRequest()->getParams();
        if (!$data['from'] || !$data['to']) {
            $message = __("Please enter a from and to date");
            $this->messageManager->addErrorMessage($message);
            return $this->_redirect('*/*');
        }
        $start = $data['from'];
        $end = $data['to'];

        $analytics = initializeAnalytics();
        // $alltypes = array("desktop", "tablet", "mobile", "ios", "android", 'email');

        $alltypes = ["device", "os", "email"];

        $final = [];

        foreach ($alltypes as $type) {
            $reports = $this->fetchMarketingGaReports($analytics, $type, $start, $end);
            $final = array_merge($final, $this->printMarketingReports($reports, $type));
        }

        $keys = array_keys($final['0']);
        array_unshift($final, $keys);
        $mage_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());
        $file_path = "marketing.csv";
        $mage_csv->saveData($file_path, $final);
        $filename = "marketing.csv";
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Type: application/csv');
        header('Pragma: no-cache');
        readfile($file_path);
    }
}
