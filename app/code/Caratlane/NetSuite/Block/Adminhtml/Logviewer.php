<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2020 18th DigiTech (https://www.18thdigitech.com)
 * @package Caratlane_NetSuite
 */
namespace Caratlane\NetSuite\Block\Adminhtml;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Caratlane\NetSuite\Helper\IntegrationConstants;
use Magento\Framework\Filesystem\Glob;

class Logviewer extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $driverFile;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Filesystem\Driver\File $driverFile
    ) {
        parent::__construct($context);

        $this->timezone = $timezone;
        $this->request = $request;
        $this->driverFile = $driverFile;
    }

    public function getDates()
    {
        $days = IntegrationConstants::CLEAR_LOG_DAYS;
        $count = 0;
        $dateArr = [];
        $dateBefore = date('d-m-Y', strtotime($this->timezone->date()->format('d-m-Y H:i:s')));

        do {
            $dateArr[] = [
                'date' => $dateBefore,
                'selected' => ($dateBefore == $this->request->getParam('date'))? true : false
            ];
            $dateBefore = date('d-m-Y', strtotime($dateBefore . ' -1 days'));

            $count++;
        } while ($count < $days);
        return $dateArr;
    }

    public function getLogs()
    {
        $logsArr = [];
        $logfile = '';
        $date = date('d-m-Y', strtotime($this->timezone->date()->format('d-m-Y H:i:s')));
        if ($this->request->getParam('date') != "") {
            $date = $this->request->getParam('date');
        }
        if ($this->request->getParam('logfile') != "") {
            $logfile = $this->request->getParam('logfile');
        }

        $logdir = DirectoryList::VAR_DIR.IntegrationConstants::NETSUITE_LOG_DIR;
        $files = Glob::glob(BP.'/'. $logdir.$date.'/'."*", GLOB_BRACE);
        foreach ($files as $file) {
            if ($this->driverFile->isFile($file)) {
                $logsArr[] = [
                    'log' => basename($file),
                    'selected' => ($logfile == basename($file))? true : false
                ];
            }
        }
        return $logsArr;
    }

    public function getDownloadLogsUrl()
    {
        $downloadUrl = '';
        $logfile = '';
        $date = date('d-m-Y', strtotime($this->timezone->date()->format('d-m-Y H:i:s')));
        if ($this->request->getParam('date') != "") {
            $date = $this->request->getParam('date');
        }
        if ($this->request->getParam('logfile') != "") {
            $logfile = $this->request->getParam('logfile');
        }

        $downloadUrl = $this->getUrl('caratlane/logviewer/downloadlogs', ['date' => $date, 'logfile' => $logfile]);

        return $downloadUrl;
    }

    public function getClearLogsUrl()
    {
        $downloadUrl = '';
        $logfile = '';
        $date = date('d-m-Y', strtotime($this->timezone->date()->format('d-m-Y H:i:s')));
        if ($this->request->getParam('date') != "") {
            $date = $this->request->getParam('date');
        }
        if ($this->request->getParam('logfile') != "") {
            $logfile = $this->request->getParam('logfile');
        }

        $downloadUrl = $this->getUrl('caratlane/logviewer/clearlogs', ['date' => $date, 'logfile' => $logfile]);

        return $downloadUrl;
    }

    public function getLogFileContent()
    {
        $contents = '';
        $logsArr = [];
        $logfile = '';
        $date = date('d-m-Y', strtotime($this->timezone->date()->format('d-m-Y H:i:s')));
        if ($this->request->getParam('date') != "") {
            $date = $this->request->getParam('date');
        }
        if ($this->request->getParam('logfile') != "") {
            $logfile = $this->request->getParam('logfile');
        }

        $file = BP . '/' . DirectoryList::VAR_DIR . IntegrationConstants::NETSUITE_LOG_DIR . $date .'/'.$logfile;
        if ($this->driverFile->isExists($file)) {
            try {
                $contents =  nl2br($this->driverFile->fileGetContents($file));
            } catch (FileSystemException $e) {
                $contents = 'Error: '. $e->getMessage();
            }
        }
        return $contents;
    }
}
