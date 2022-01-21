<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2020 18th DigiTech (https://www.18thdigitech.com)
 * @package Caratlane_NetSuite
 */
namespace Caratlane\NetSuite\Controller\Adminhtml\Logviewer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Caratlane\NetSuite\Helper\IntegrationConstants;

class ClearLogs extends \Magento\Backend\App\Action
{
    const MENU_ID = 'Caratlane_NetSuite::log';

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $fileDriver;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->fileFactory = $fileFactory;
        $this->fileDriver = $fileDriver;
        $this->fileSystem = $fileSystem;
        $this->timezone = $timezone;

        parent::__construct($context);
    }
 
    public function execute()
    {
        $contents = '';
        $today = $this->timezone->date()->format('d-m-Y');
        $date = $this->getRequest()->getParam('date');
        $logfile = $this->getRequest()->getParam('logfile');

        if ($date == "") {
            $date = $today;
        }

        if ($logfile == '') {
            $this->messageManager->addErrorMessage(__('Please select log first and try again!'));
            $this->_redirect('caratlane/logviewer/index');
            return;
        }

        $filePath = IntegrationConstants::NETSUITE_LOG_DIR.$date.'/'.$logfile;
        $varDir = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR);
        $fileAbsolutePath = $varDir->getAbsolutePath($filePath);

        if ($this->fileDriver->isExists($fileAbsolutePath)) {
            $f = $this->fileDriver->fileOpen($fileAbsolutePath, "r+");
            if ($f !== false) {
                ftruncate($f, 0);
                $this->fileDriver->fileClose($f);
            }
            $this->messageManager->addSuccessMessage(__('Logfile cleaned successfully.'));
            $this->_redirect('caratlane/logviewer/index');
        } else {
            $this->messageManager->addErrorMessage(__('Log file not exist!'));
            $this->_redirect('caratlane/logviewer/index');
            return;
        }
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(static::MENU_ID);
    }
}
