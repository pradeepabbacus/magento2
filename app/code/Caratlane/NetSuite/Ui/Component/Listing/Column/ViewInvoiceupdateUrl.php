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

class ViewInvoiceupdateUrl extends \Magento\Ui\Component\Listing\Columns\Column
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
                
                if (isset($item['invoice_url'])) {
                    if (!empty($item['invoice_url'])) {
                        $url = $item['invoice_url'];
                        $link = '<a href="' . $url . '" target="_blank">' . __('View') . '</a>';
                        $item['invoice_url'] = $link;
                    }
                }

                if (isset($item['ns_invoice_url'])) {
                    if (!empty($item['ns_invoice_url'])) {
                        $url = $item['ns_invoice_url'];
                        $link = '<a href="' . $url . '" target="_blank">' . __('View') . '</a>';
                        $item['ns_invoice_url'] = $link;
                    }
                }
            }
        }
        return $dataSource;
    }
}
