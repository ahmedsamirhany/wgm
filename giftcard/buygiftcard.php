<?php
define('CLIENTAREA', true);
require __DIR__ . '/init.php';

use WHMCS\Database\Capsule;
use WHMCS\Authentication\CurrentUser;

// Initialize WHMCS client area environment
$ca = new WHMCS\ClientArea();
$ca->initPage();

// --- START: FIX FOR SMARTY TEMPLATE PATH - AGGRESSIVE VERSION ---
global $smarty; 

if (isset($smarty) && is_object($smarty) && method_exists($smarty, 'setTemplateDir')) {
    try {
        // Get the active client area template name from the database
        $activeTemplate = Capsule::table('tblconfiguration')
                                 ->where('setting', 'Template')
                                 ->value('value');

        if ($activeTemplate) {
            // Construct the absolute path to the active theme's root directory
            // ROOTDIR is a WHMCS constant for the main WHMCS installation directory.
            $templateRootPath = rtrim(ROOTDIR, '/') . '/templates/' . $activeTemplate;

            // IMPORTANT: setTemplateDir OVERRIDES all other paths.
            // This forces Smarty to ONLY look in this directory and its subdirectories.
            $smarty->setTemplateDir($templateRootPath);

            // You can optionally add other common WHMCS template directories if needed,
            // but for addon modules, they typically reside under the theme path.
            // Example: $smarty->addTemplateDir(rtrim(ROOTDIR, '/') . '/templates/');
        }

    } catch (Exception $e) {
        logActivity("GiftCard buygiftcard.php Error: Could not aggressively set Smarty template path. " . $e->getMessage());
        // For debugging, you might want to display the error
        // echo "Smarty Path Error: " . $e->getMessage();
    }
}
// --- END: FIX FOR SMARTY TEMPLATE PATH - AGGRESSIVE VERSION ---

$ca->assign('displayTitle', 'Buy a Gift Card'); // Title for the page
$ca->assign('templatefile', 'modules/addons/giftcard/buygiftcard'); // Path to your custom template file
// Note: With setTemplateDir, Smarty will now look directly in $templateRootPath
// for 'modules/addons/giftcard/buygiftcard.tpl'

// Get module config
$moduleConfig = [];
$configResult = Capsule::table('tbladdonmodules')->where('module', 'giftcard')->get()->keyBy('setting');
if ($configResult) {
    $moduleConfig = $configResult->map(function ($setting) {
        return $setting->value;
    })->all();
}

$isSalesEnabled = ($moduleConfig['enableSales'] ?? 'off') === 'on';
$isConfigured = !empty($moduleConfig['giftCardProductId']);
$currency = getCurrency(); // Get default currency

// Assign config and currency to the template
$ca->assign('isSalesEnabled', $isSalesEnabled);
$ca->assign('isConfigured', $isConfigured);
$ca->assign('currencyPrefix', $currency['prefix']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_token()) {
    $productId = $moduleConfig['giftCardProductId'] ?? null;
    if (!$productId) {
        $ca->assign('errorMessage', "This feature is not configured by the site administrator.");
    } else {
        $amount = (float) $_POST['amount'];
        $recipientName = strip_tags(trim($_POST['recipient_name']));
        $recipientEmail = filter_var(trim($_POST['recipient_email']), FILTER_VALIDATE_EMAIL);
        $message = strip_tags(trim($_POST['message']));

        $currentUser = new CurrentUser();
        $client = $currentUser->client();
        $senderName = $client ? $client->fullName : strip_tags(trim($_POST['sender_name']));
        $senderEmail = $client ? $client->email : filter_var(trim($_POST['sender_email']), FILTER_VALIDATE_EMAIL);

        if ($amount <= 0 || !$recipientEmail || !$senderName || !$senderEmail) {
            $ca->assign('errorMessage', "Invalid input. Please go back and fill out all required fields.");
        } else {
            $_SESSION['giftcard_purchase_details'] = [
                'sender_name' => $senderName,
                'sender_email' => $senderEmail,
                'recipient_name' => $recipientName,
                'recipient_email' => $recipientEmail,
                'message' => $message,
            ];

            $cartUrl = "cart.php?a=add&pid={$productId}&priceoverride={$amount}";
            header("Location: " . $cartUrl);
            exit;
        }
    }
}

// Check if user is logged in and assign details for pre-filling form
$currentUser = new CurrentUser();
if ($currentUser->client()) {
    $ca->assign('isLoggedIn', true);
    $ca->assign('clientName', $currentUser->client()->fullName);
    $ca->assign('clientEmail', $currentUser->client()->email);
} else {
    $ca->assign('isLoggedIn', false);
}

// Output the page using the WHMCS client area template
$ca->output();