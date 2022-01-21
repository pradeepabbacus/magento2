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

class ViewAction extends \Magento\Ui\Component\Listing\Columns\Column
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

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['order_id'])) {
                    $order = $this->orderInterface->loadByIncrementId($item['order_id']);
                    $url = $this->urlBuilder->getUrl('sales/order/view', ['order_id' => $order->getId()]);
                    $link = '<a href="' . $url . '" target="_blank">' . $item['order_id'] . '</a>';
                    $item['order_id'] = $link;
                }
                if (isset($item['customer_id'])) {
                    $url = $this->urlBuilder->getUrl('customer/index/edit/id', ['customer_id' => $item['customer_id']]);
                    $link = '<a href="' . $url . '" target="_blank">' . $item['customer_id'] . '</a>';
                    $item['customer_id'] = $link;
                }
                if (isset($item['request_info'])) {
                    $url = $this->urlBuilder->getUrl('caratlane/netsuite/view/id', ['entity_id' => $item['entity_id']]);
                    $link = '<a href="' . $url . '" target="_blank">' . __('View') . '</a>';
                    $item['request_info'] = $link;
                }
                
                if (isset($item['json_data'])) {
                    $iid = $item['entity_id'];
                    $url = $this->urlBuilder->getUrl('caratlane/netsuite/createshippmentview/id', ['entity_id'=>$iid]);
                    $link = '<a href="' . $url . '" target="_blank">' . __('View') . '</a>';
                    $item['json_data'] = $link;
                }
                
                if (isset($item['response_info'])) {
                    $itemid = $item['entity_id'];
                    $url = $this->urlBuilder->getUrl('caratlane/netsuite/responseview/id', ['entity_id' => $itemid]);
                    $link = '<a href="' . $url . '" target="_blank">' . __('View') . '</a>';
                    $item['response_info'] = $link;
                }

            }
        }
        return $dataSource;
    }
}
