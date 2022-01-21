<?php
namespace Eighteentech\DisableStockReservation\Plugin;

use Magento\Inventory\Model\SourceItem\Command\Handler\SourceItemsSaveHandler;
use Magento\Framework\App\ResourceConnection;

class AfterSourceItemsSaveHandler
{
    const TABLE_NAME_INVENTORY_RESERVATION = 'inventory_reservation';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        ResourceConnection $resourceConnection
    ) {	
        $this->resourceConnection = $resourceConnection;	
    }

    public function afterExecute(
        SourceItemsSaveHandler $subject,
        $result,
        array $sourceItems
    ) {
        if (!count($sourceItems)) {
            return $result;
        }
       
        if(!empty($sourceItems)){
            $deleteSkuArr = [];
            foreach ($sourceItems as $sourceItem) {
                $sku = $sourceItem->getSku();
                $source_code = $sourceItem->getSourceCode();
                if(($source_code == 'default') || ($source_code == 'default')){
                    if(!in_array($sku, $deleteSkuArr)){
                        $deleteSkuArr[] = $sku;
                    }
                }
            }
            if(!empty($deleteSkuArr)){
                $connection = $this->resourceConnection->getConnection();
                $tableName = $this->resourceConnection->getTableName(self::TABLE_NAME_INVENTORY_RESERVATION);
                $connection->delete($tableName, ['sku IN (?)' => $deleteSkuArr]);
            }   
        }
        return $result;
    }
}