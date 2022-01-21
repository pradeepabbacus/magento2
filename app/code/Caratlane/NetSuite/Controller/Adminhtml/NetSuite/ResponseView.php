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
 * Class ViewJson
 */
class ResponseView extends \Magento\Backend\App\Action
{
     const MENU_ID = 'Caratlane_NetSuite::log';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory = false;

    /**
     * Index constructor.
     *
     * @param  \Magento\Backend\App\Action\Context $context
     * @param PageFactory $resultPageFactory
     */
    /*public function __construct(
        \Magento\Backend\App\Action\Context $context
    ) {
        parent::__construct($context);
    }*/

    /**
     * Load the page to view response in json format
     *
     * @return Page
     */
    public function execute()
    {
        $entity_id = $this->getRequest()->getParam('entity_id');
        if ($entity_id > 0) {
             $model = $this->_objectManager->create(\Caratlane\NetSuite\Model\NetSuite::class)->load($entity_id);
            if ($model) {
                $resJson = $model->getData('response_info');
                header('Content-Type: application/json');
                http_response_code(200);
                exit($resJson);
            }
        }
        $this->messageManager->addErrorMessage(__('No record found!'));
         $this->_redirect('caratlane/netsuite/index');
    }
    
     /**
      * Allow modules
      * @return Int
      */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Caratlane_NetSuite::log');
    }
}
