<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2020 18th DigiTech (https://www.18thdigitech.com)
 * @package Caratlane_NetSuite
 */

namespace Caratlane\NetSuite\Cron;

use Caratlane\NetSuite\Helper\Data as Helper;
use Caratlane\NetSuite\Logger\Logger;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem\Glob;

class ClearLogs
{
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $fileDriver;

    /**
     * @var Helper
     */
    protected $helper;
    
    /**
     * @var Logger $logger
     */
    protected $logger;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var ResourceConnection $resourceConnection
     */
    protected $resourceConnection;
    
    /**
     * @param Helper $helper
     * @param Logger $logger
     */
    public function __construct(
        Helper $helper,
        Logger $logger,
        TimezoneInterface $timezone,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->timezone = $timezone;
        $this->fileFactory = $fileFactory;
        $this->resConn = $resourceConnection;
    }
    
    public function execute()
    {
        $days = \Caratlane\NetSuite\Helper\IntegrationConstants::CLEAR_LOG_DAYS;

        if ($days > 0) {
            
            $curdate = $this->timezone->date()->format('d-m-Y H:i:s');
            $dateBefore = date('d-m-Y', strtotime($curdate . ' -' . $days . 'days'));
            $currentdate = $this->timezone->date()->format('Y-m-d H:i:s');
            $dateTimeBefore = date('Y-m-d H:i:s', strtotime($currentdate . ' -' . $days . 'days'));

            $connection = $this->resConn->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
          
            $connection->delete(
                $connection->getTableName(\Caratlane\NetSuite\Helper\IntegrationConstants::TBL_MASTER_LOGS),
                [
                    'created_at <= ?' => $dateTimeBefore,
                    'status = ?' => '1'
                ]
            );

               $this->deleteMagentoLogs($days);
               $this->removeNetSuiteLogDir($dateBefore);
            
        }
    }

    private function removeNetSuiteLogDir($dateBefore)
    {
        $count = 0;
        do {
            $this->deleteDirectory(BP . '/' . DirectoryList::VAR_DIR . '/netsuite/' . $dateBefore);
            $this->deleteDirectory(BP . '/' . DirectoryList::VAR_DIR . '/netsuite/' . $dateBefore);
            $dateBefore = date('d-m-Y', strtotime($dateBefore . ' -1 days'));

            $count++;
        } while ($count < 10);
    }

    private function deleteMagentoLogs($days)
    {
        $files = Glob::glob(BP . '/' . DirectoryList::VAR_DIR . '/netsuite/'."*.{log,gz}", GLOB_BRACE);
        $now = time();

        foreach ($files as $file) {
            if ($this->fileFactory->isFile($file)) {
                if ($now - filemtime($file) >= 60 * 60 * 24 * $days) {
                    $this->fileFactory->deleteFile($file);
                }
            }
        }
    }

    private function deleteDirectory($dir)
    {
        if (!$this->fileFactory->isExists($dir)) {
            return true;
        }
    
        if (!$this->fileFactory->isDirectory($dir)) {
            return $this->fileFactory->deleteDirectory($dir);
        }
    
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
    
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
    
        return $this->fileFactory->deleteDirectory($dir);
    }
}
