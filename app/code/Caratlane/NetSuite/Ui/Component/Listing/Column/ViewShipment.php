<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2020 18th DigiTech (https://www.18thdigitech.com)
 * @package Caratlane_NetSuite
 */
namespace Caratlane\NetSuite\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ViewShipment extends \Magento\Ui\Component\Listing\Columns\Column
{

   /**
    * @var UrlInterface
    */
    protected $urlBuilder;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->orderInterface = $orderInterface;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function getOrderId(string $incrementOrderId)
    {
        $orderId = 0;
        try {
            $orderInfo = $this->orderInterface->loadByIncrementId($incrementOrderId);
            $orderId = $orderInfo->getId();
            return $orderId;
        } catch (Exception $e) {
            return $orderId;
        }
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                
                if (isset($item['magento_order_id'])) {
                    if (!empty($item['magento_order_id'])) {
                        $orderId = $this->getOrderId($item['magento_order_id']);
                        if ($orderId>0) {
                            $url = $this->urlBuilder->getUrl('sales/order/view/order_id/'.$orderId);
                            $link = '<a href="' . $url . '" target="_blank">' . $item['magento_order_id'] . '</a>';
                            $item['magento_order_id'] = $link;
                        }
                    }
                }
                if (isset($item['shipment_id'])) {
                    if ($item['shipment_id']>0) {
                        $url = $this->urlBuilder->getUrl('sales/shipment/view/shipment_id/'.$item['shipment_id']);
                        $link = '<a href="' . $url . '" target="_blank">' . $item['shipment_id'] . '</a>';
                        $item['shipment_id'] = $link;
                    }
                }
            }
        }
        return $dataSource;
    }
}
