<?php
/**
 * Caratlane
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Caratlane.com license that is
 * available through the world-wide-web at this URL:
 * https://www.Caratlane.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to any file if you wish to upgrade this extension
 * version in the future.
 *
 * @category    Caratlane
 * @package     Caratlane_NetSuite
 * @copyright   Copyright (c) Caratlane (https://www.Caratlane.com/)
 * @license     https://www.Caratlane.com/LICENSE.txt
 */

namespace Caratlane\NetSuite\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem;
use Caratlane\NetSuite\Model\NetSuiteConstants;

class Logger extends AbstractHelper
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $_fileDriver;
    
    /**
     * @var Filesystem
     */
    protected $file;
    
    /**
     * @var WriteInterface
     */
    protected $newDirectory;
    
    protected $logger;

    public function __construct(
        Context $context,
        TimezoneInterface $timezone,
        File $fileDriver,
        Filesystem $file
    ) {
        $this->timezone = $timezone;
        $this->_fileDriver = $fileDriver;
        $this->newDirectory = $file->getDirectoryWrite(DirectoryList::VAR_DIR);
        parent::__construct($context);
    }

    public function writeLog($message, $type = 'info', $name = 'netsuite')
    {
        if (!$this->logger) {
            $this->initLog($name);
        }
        switch ($type) {
            case 'info':
                $this->logger->info($message);
                break;

            case 'warn':
                $this->logger->warn($message);
                break;

            case 'error':
                $this->logger->err($message);
                break;
            
            default:
                $this->logger->info($message);
                break;
        }
    }
    
    public function initLog($name = 'netsuite')
    {
        $today = $this->timezone->date()->format('d-m-Y');
        $logPath = BP . '/var'. NetSuiteConstants::NETSUITE_LOG_DIR . $today;
        if (!$this->isFileExist($logPath)) {
            $this->createDirectory($logPath);
        }

        $writer = new \Laminas\Log\Writer\Stream($logPath . '/' . $name . '.log');
        $this->logger = new \Laminas\Log\Logger();
        $this->logger->addWriter($writer);
        
        return $this->logger;
    }
    
    public function isFileExist($fileName)
    {
        return $this->_fileDriver->isExists($fileName);
    }
    
    /**
     * Create new folder
     *
     * @return bool
     * @throws LocalizedException
     */
    public function createDirectory($directoryName)
    {
        $newDirectory = false;
        try {
            $newDirectory = $this->newDirectory->create($directoryName);
        } catch (FileSystemException $e) {
            return false;
        }
 
        return $newDirectory;
    }
}
