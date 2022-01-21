<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2020 18th DigiTech (https://www.18thdigitech.com)
 * @package Caratlane_NetSuite
 */
namespace Caratlane\NetSuite\Controller\Adminhtml\NetSuite;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class ClearHistory from controller
 */
class ClearHistory extends \Magento\Backend\App\Action
{
    const MENU_ID = 'Caratlane_NetSuite::log';

    protected $resultPageFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Caratlane\NetSuite\Model\NetSuiteFactory $masterlogsFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->masterlogsFactory = $masterlogsFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $model = $this->masterlogsFactory->create();
        if (!count($model->getCollection()->getData())) {
              $this->messageManager->addErrorMessage(__('There is no records to delete.'));
              $this->_redirect('caratlane/netsuite/index');
              return;
        }
       
        if (count($model->getCollection()->getData())) {
            $connection = $model->getCollection()->getConnection();
            $tableName = $model->getCollection()->getMainTable();
            $connection->truncateTable($tableName);
        }

        $this->messageManager->addSuccessMessage(__('History has been removed successfully.'));
        $this->_redirect('caratlane/netsuite/index');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(static::MENU_ID);
    }
}
