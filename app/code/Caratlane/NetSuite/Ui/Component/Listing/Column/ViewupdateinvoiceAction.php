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

class ViewupdateinvoiceAction extends \Magento\Ui\Component\Listing\Columns\Column
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
               
                if (isset($item['json_data'])) {
                    $id = $item['entity_id'];
                    $url = $this->urlBuilder->getUrl('caratlane/netsuite/updateinvoicepdfview/id', ['entity_id'=>$id]);
                    $link = '<a href="' . $url . '" target="_blank">' . __('View') . '</a>';
                    $item['json_data'] = $link;
                }
            }
        }
        return $dataSource;
    }
}
