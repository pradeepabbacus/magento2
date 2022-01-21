<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2020 18th DigiTech (https://www.18thdigitech.com)
 * @package Caratlane_NetSuite
 */
namespace Caratlane\NetSuite\Controller\Adminhtml\NetSuite;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Caratlane\NetSuite\Model\NetsuiteUpdateinvoicepdf;

/**
 * Class CreateShippmentView
 */
class Updateinvoicepdfview extends \Magento\Backend\App\Action
{
     const MENU_ID = 'Caratlane_NetSuite::updateinvoicepdf';

    /**
     * @var Caratlane\NetSuite\Model\NetsuiteUpdateinvoicepdf
     */
    protected $netsuiteUpdateinvoicepdf;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory = false;

    /**
     * Index constructor.
     *
     * @param  \Magento\Backend\App\Action\Context $context
     * @param NetsuiteUpdateinvoicepdf $netsuiteUpdateinvoicepdf
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        NetsuiteUpdateinvoicepdf $netsuiteUpdateinvoicepdf,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->netsuiteUpdateinvoicepdf = $netsuiteUpdateinvoicepdf;
        parent::__construct($context);
    }

    /**
     * Load the page defined in view/adminhtml/layout/netsuit_logviewer_index.xml
     *
     * @return Page
     */
    public function execute()
    {
        $entity_id = $this->getRequest()->getParam('entity_id');
        if ($entity_id > 0) {
            $model = $this->netsuiteUpdateinvoicepdf->load($entity_id);
            if ($model) {
                $resJson = $model->getData('json_data');
                header('Content-Type: application/json');
                http_response_code(200);
                exit($resJson);
            }
        }
        $this->messageManager->addErrorMessage(__('No record found!'));
        $this->_redirect('caratlane/netsuite/updateinvoicepdf');
    }
    
     /**
      * Allow modules
      * @return Int
      */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Caratlane_NetSuite::updateinvoicepdf');
    }
}
