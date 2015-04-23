<?php
/*
Plugin Name: Affiliates Manager WP eStore Integration
Plugin URI: https://wpaffiliatemanager.com/affiliates-manager-wp-estore-integration/
Description: Process an affiliate commission via Affiliates Manager after a WP eStore checkout.
Version: 1.0
Author: wp.insider, wpaffiliatemgr, affmngr
Author URI: https://wpaffiliatemanager.com
*/

function wpam_eStore_database_updated_after_payment($payment_data, $cart_items)
{
    $custom_data = $payment_data['custom'];
    WPAM_Logger::log_debug('WP eStore Integration - database updated hook fired. Custom field value: '.$custom_data);
    $custom_values = array();
    parse_str($custom_data, $custom_values);
    if(isset($custom_values['wpam_tracking']) && !empty($custom_values['wpam_tracking']))
    {
        $tracking_value = $custom_values['wpam_tracking'];
        WPAM_Logger::log_debug('WP eStore Integration - Tracking data present. Need to track affiliate commission. Tracking value: '.$tracking_value);
        $purchaseLogId = $payment_data['txn_id'];
        $purchaseAmount = $payment_data['mc_gross']; //TODO - later calculate sub-total only
        $strRefKey = $tracking_value;
        $requestTracker = new WPAM_Tracking_RequestTracker();
        $requestTracker->handleCheckoutWithRefKey( $purchaseLogId, $purchaseAmount, $strRefKey);
        WPAM_Logger::log_debug('WP eStore Integration - Commission tracked for transaction ID: '.$purchaseLogId.'. Purchase amt: '.$purchaseAmount);
    }
}
add_action("eStore_product_database_updated_after_payment", "wpam_eStore_database_updated_after_payment", 10, 2);

function wpam_eStore_add_custom_parameters($custom_field_val)
{
    if(isset($_COOKIE[WPAM_PluginConfig::$RefKey]))
    {
        $name = 'wpam_tracking';
        $value = $_COOKIE[WPAM_PluginConfig::$RefKey];
        $new_val = $name.'='.$value;
        $current_val = $custom_field_val;
        if(empty($current_val)){
            $custom_field_val = $new_val;
        }
        else{
            $custom_field_val = $current_val.'&'.$new_val;
        }
        WPAM_Logger::log_debug('WP eStore Integration - Adding custom field value. New value: '.$custom_field_val);
    }
    return $custom_field_val;
}

add_filter("eStore_custom_field_value_filter", "wpam_eStore_add_custom_parameters");