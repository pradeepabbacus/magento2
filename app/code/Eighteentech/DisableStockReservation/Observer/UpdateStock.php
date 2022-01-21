<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Eighteentech\DisableStockReservation\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Model\ProductRepository;

class UpdateStock implements ObserverInterface
{
    protected $productRepository;
    protected $stockRegistry;
    public function __construct(ProductRepository $productRepository,
    StockRegistryInterface $stockRegistry)
    {
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

     $orderData = $observer->getEvent()->getOrder();

            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/orderData.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info(print_r($orderData->getId(), true));

            $orderItems = $orderData->getAllVisibleItems();
               foreach($orderItems as $item) {
                
                $productId = $item->getProductId();
                $product = $this->productRepository->getById($productId);
                $sku = $product->getSku();
                $stockItem = $this->stockRegistry->getStockItemBySku($sku);
                $qty = $stockItem->getQty() - $item->getQty();
                $stockItem->setQty($qty);
                $stockItem->setIsInStock((bool)$qty);
                $this->stockRegistry->updateStockItemBySku($sku, $stockItem);

        }
    }
}