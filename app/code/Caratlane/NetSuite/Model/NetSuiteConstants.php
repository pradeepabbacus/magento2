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

namespace Caratlane\NetSuite\Model;

class NetSuiteConstants
{
    const NETSUITE_TRACK_SHIPMENT_LOG_FILENAME = 'track_shipment';
    const NETSUITE_TRACK_ORDER_LOG_FILENAME = 'track_order';
    const NETSUITE_ORDER_UPODATE_LOG_FILENAME = 'order_update';
    const NETSUITE_CREATE_SHIPMENT_LOG_FILENAME = 'create_shipment';
    const NETSUITE_CREATE_UPDATEPDF_LOG_FILENAME = 'update_invoicepdf';
    const NETSUITE_LOG_DIR = '/netsuite/';
    const NETSUITE_DEFAULT_ERROR_CODE = 'false';
    const NETSUITE_DEFAULT_SUCCESS_CODE = 'true';
    const NETSUITE_DEFAULT_STATUS = 'pending';
    const NETSUITE_EMPTY_MESSAGE = 'The request data is empty or invalid.';
    const NETSUITE_SUCCESS_MESSAGE = 'Data saved successfully. Import process will be start soon.';
    const NETSUITE_MIGRATION_STATUS = 'success';
    const NETSUITE_MIGRATION_FAILED_STATUS = 'failed';
    const NETSUITE_MIGRATION_SUCCESS_MESSAGE = 'Shipment data inserted successfully.';
    const NETSUITE_MIGRATION_ORDER_INDERTED_SUCCESS_MESSAGE = 'Order update data inserted successfully.';
    const NETSUITE_MIGRATION_SHIPMENT_SUCCESS_MESSAGE = 'Shipment created successfully.';
    const NETSUITE_MIGRATION_SHIPMENT_UPDATE_SUCCESS_MESSAGE = 'Shipment status update successfully.';
    const NETSUITE_MIGRATION_ORDER_SUCCESS_MESSAGE = 'Order status update successfully.';
    const NETSUITE_MIGRATE_CREATE_SHIPMENT_LOG_FILENAME = 'create_shipment_process';
    const NETSUITE_MIGRATE_TRACK_SHIPMENT_LOG_FILENAME = 'track_shipment_process';
    const NETSUITE_MIGRATE_TRACK_ORDER_LOG_FILENAME = 'order_update_process';
    const NETSUITE_TRACKING_CODE = 'custom';
    const NETSUITE_DISABLE_MESSAGE = 'This feature has been disable. Please contact to service provider.';
    const CAN_NOT_CANCEL_ORDER = 'Can not canceled order due invoice or shipment has been created';
    const CAN_NOT_HOLDED_ORDER = 'Can not hold order due invoice or shipment has been created';
    const NETSUITE_CANCEL_MESSAGE = 'Order has been canceled successfully';
    const NETSUITE_ORDER_NOT_FOUND = 'Order not found';
    const STATUS_ARRAY_LIST = ['canceled','holded','shipped','delivered'];
}
