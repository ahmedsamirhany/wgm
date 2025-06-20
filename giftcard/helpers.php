<?php
use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

if (!function_exists('giftcard_helper_config')) {
    function giftcard_helper_config() {
        return [
            'name' => 'Professional Gift Cards',
            'description' => 'An advanced module for selling and managing gift cards in WHMCS.',
            'version' => '8.0.0',
            'author' => 'Gemini (Final Package)',
            'fields' => [
                'enableSales' => [
                    'FriendlyName' => 'Enable Client Purchases',
                    'Type' => 'yesno',
                    'Description' => 'Allow clients to purchase gift cards as a product.',
                ],
                'giftCardProductId' => [
                    'FriendlyName' => 'Gift Card Product ID',
                    'Type' => 'text',
                    'Size' => '10',
                    'Description' => 'Enter the ID of the product you created for gift card sales.',
                ],
                'giftCardEmailTemplate' => [
                    'FriendlyName' => 'Delivery Email Template Name',
                    'Type' => 'text',
                    'Size' => '50',
                    'Description' => 'Enter the EXACT unique name of the email template to use for sending the gift card code (e.g., "Gift Card Delivery").',
                ],
                'allowMultipleUsers' => ['FriendlyName' => 'Allow Use By Multiple Clients', 'Type' => 'yesno', 'Default' => 'no'],
                'oneTimeUse' => ['FriendlyName' => 'One-Time Use Cards', 'Type' => 'yesno', 'Default' => 'no'],
                'preserveData' => ['FriendlyName' => 'Preserve Data on Deactivation', 'Type' => 'yesno', 'Default' => 'yes'],
            ]
        ];
    }
}

if (!function_exists('giftcard_helper_activate')) {
    function giftcard_helper_activate() {
        try {
            if (!Capsule::schema()->hasTable('mod_giftcards')) {
                Capsule::schema()->create('mod_giftcards', function ($table) {
                    $table->increments('id'); $table->string('code', 32)->unique(); $table->decimal('initial_value', 16, 2); $table->decimal('current_balance', 16, 2); $table->integer('currency_id'); $table->integer('created_by_admin_id')->default(0); $table->integer('purchased_by_client_id')->default(0); $table->integer('assigned_client_id')->default(0)->index(); $table->enum('status', ['Active', 'Redeemed', 'Expired', 'Cancelled'])->default('Active'); $table->text('admin_notes')->nullable(); $table->date('expiry_date')->nullable(); $table->timestamps();
                });
            }
            if (!Capsule::schema()->hasTable('mod_giftcard_logs')) {
                Capsule::schema()->create('mod_giftcard_logs', function ($table) {
                    $table->increments('id'); $table->integer('card_id'); $table->integer('admin_id')->default(0); $table->string('action', 255); $table->decimal('amount', 16, 2)->default(0.00); $table->text('notes'); $table->timestamp('created_at');
                });
            }
            return ['status' => 'success', 'description' => 'Gift Card module activated and database tables verified.'];
        } catch (\Exception $e) { return ['status' => 'error', 'description' => 'Unable to create/update tables: ' . $e->getMessage()]; }
    }
}

if (!function_exists('giftcard_helper_deactivate')) {
    function giftcard_helper_deactivate() {
        $preserveData = Capsule::table('tbladdonmodules')->where('module', 'giftcard')->where('setting', 'preserveData')->value('value');
        if ($preserveData !== 'on') { try { Capsule::schema()->dropIfExists('mod_giftcards'); Capsule::schema()->dropIfExists('mod_giftcard_logs'); return ['status' => 'success', 'description' => 'Module deactivated and data tables removed.']; } catch (\Exception $e) { return ['status' => "error", 'description' => "Unable to drop tables: {$e->getMessage()}"]; } }
        return ['status' => 'success', 'description' => 'Module deactivated. Data has been preserved.'];
    }
}

if (!function_exists('giftcard_helper_get_admin_user')) {
    function giftcard_helper_get_admin_user() {
        $admin = \WHMCS\User\Admin::where('disabled', 0)->whereHas('role', function ($query) { $query->where('isSuperAdmin', true); })->first();
        if ($admin) { return $admin->username; }
        $admin = \WHMCS\User\Admin::where('disabled', 0)->first();
        if ($admin) { return $admin->username; }
        throw new \Exception('Could not find a valid admin user to perform the API action.');
    }
}

if (!function_exists('giftcard_helper_log_transaction')) {
    function giftcard_helper_log_transaction($cardId, $action, $amount = 0.00, $notes = '') {
        try { Capsule::table('mod_giftcard_logs')->insert(['card_id' => $cardId, 'admin_id' => $_SESSION['adminid'] ?? 0, 'action' => $action, 'amount' => $amount, 'notes' => $notes, 'created_at' => date('Y-m-d H:i:s'),]);
        } catch (\Exception $e) { /* Log error */ }
    }
}

if (!function_exists('giftcard_helper_generate_segment')) {
    function giftcard_helper_generate_segment() {
        return strtoupper(substr(bin2hex(random_bytes(4)), 0, 4));
    }
}

if (!function_exists('giftcard_helper_format_date')) {
    function giftcard_helper_format_date($date) {
        return $date ? date('Y-m-d', strtotime($date)) : '';
    }
}

if (!function_exists('giftcard_helper_selected')) {
    function giftcard_helper_selected($value1, $value2) {
        return ($value1 == $value2) ? 'selected="selected"' : '';
    }
}