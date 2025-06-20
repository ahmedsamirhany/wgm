<?php
/**
 * WHMCS Professional Gift Card Module - Main Bootstrap File
 * @version     8.0.0 (Final Package)
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

if (!function_exists('giftcard_config')) {
    function giftcard_config() {
        require_once __DIR__ . '/helpers.php';
        return giftcard_helper_config();
    }
}

if (!function_exists('giftcard_activate')) {
    function giftcard_activate() {
        require_once __DIR__ . '/helpers.php';
        return giftcard_helper_activate();
    }
}

if (!function_exists('giftcard_deactivate')) {
    function giftcard_deactivate() {
        require_once __DIR__ . '/helpers.php';
        return giftcard_helper_deactivate();
    }
}

if (!function_exists('giftcard_output')) {
    function giftcard_output($vars) {
        require_once __DIR__ . '/admin.php';
    }
}