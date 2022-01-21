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
 * Class ClearShipmentHistory from controller
 */
class ClearShipmentHistory extends \Magento\Backend\App\Action
{
    
    const MENU_ID = 'Caratlane_NetSuite::createshipment';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Caratlane\NetSuite\Model\NetsuiteShipmentsFactory
     */
    protected $netsuiteShipments;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Caratlane\NetSuite\Model\NetsuiteShipmentsFactory $netsuiteShipments
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->netsuiteShipments = $netsuiteShipments;

        parent::__construct($context);
    }

    public function execute()
    {
        $model = $this->netsuiteShipments->create();
        if (!count($model->getCollection()->getData())) {
              $this->messageManager->addErrorMessage(__('There is no records to delete.'));
              $this->_redirect('caratlane/netsuite/createshipments');
              return;
        }
       
        if (count($model->getCollection()->getData())) {
            $connection = $model->getCollection()->getConnection();
            $tableName = $model->getCollection()->getMainTable();
            $connection->truncateTable($tableName);
        }

        $this->messageManager->addSuccessMessage(__('History has been removed successfully.'));
        $this->_redirect('caratlane/netsuite/createshipments');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(static::MENU_ID);
    }
}
