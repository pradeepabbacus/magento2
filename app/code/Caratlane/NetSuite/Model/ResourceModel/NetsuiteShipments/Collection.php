<?php
/**
 * Caratlane
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Caratlane.com license that is
 * available through the world-wide-web at this URL:
 * https://www.Caratlane.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to any file if you wish to upgrade this extension
 * version in the future.
 *
 * @category    Caratlane
 * @package     Caratlane_NetSuite
 * @copyright   Copyright (c) Caratlane (https://www.Caratlane.com/)
 * @license     https://www.Caratlane.com/LICENSE.txt
 */

namespace Caratlane\NetSuite\Model\ResourceModel\NetsuiteShipments;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
  
class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            \Caratlane\NetSuite\Model\NetsuiteShipments::class,
            \Caratlane\NetSuite\Model\ResourceModel\NetsuiteShipments::class
        );
    }
}
