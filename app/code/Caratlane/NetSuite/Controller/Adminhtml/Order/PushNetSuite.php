<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2020 18th DigiTech (https://www.18thdigitech.com)
 * @package Caratlane_NetSuite
 */
namespace Caratlane\NetSuite\Controller\Adminhtml\Order;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Api\OrderManagementInterface;

class PushNetSuite extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction
{
    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param OrderManagementInterface $orderManagement
     * @param \Caratlane\NetSuite\Model\NetSuiteFactory $netsuitefactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        \Caratlane\NetSuite\Model\NetSuiteFactory $netsuitefactory
    ) {
        parent::__construct($context, $filter);
        $this->collectionFactory = $collectionFactory;
        $this->netsuitefactory = $netsuitefactory;
    }

    /**
     * Hold selected orders
     *
     * @param AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(AbstractCollection $collection)
    {
        $countOrder = 0;
        foreach ($collection->getItems() as $order) {
            if (!$order->getEntityId()) {
                continue;
            }
            $order->setNetsuiteMaxAttempt(0);
            $order->setPushToNetsuite(0);
            $order->setNetsuiteCreatedAt(null);
            $order->setNetsuiteId(null);
            $order->save();
            $countOrder++;
        }
        $countNonPushOrder = $collection->count() - $countOrder;

        if ($countNonPushOrder && $countOrder) {
            $this->messageManager->addError(__('%1 order(s) were not pushed to NetSuite.', $countNonPushOrder));
        } elseif ($countNonPushOrder) {
            $this->messageManager->addError(__('No order(s) were reset to NetSuite.'));
        }

        if ($countOrder) {
            $this->messageManager->addSuccess(__('Reset %1 order(s) for NetSuite orders.', $countOrder));
        }
        
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }
}
