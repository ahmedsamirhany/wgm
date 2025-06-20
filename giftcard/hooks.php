<?php
use WHMCS\Database\Capsule;
use WHMCS\View\Menu\Item as MenuItem;

if (!defined("WHMCS")) { die("This file cannot be accessed directly"); }

// Include the main module file to get access to helper functions
require_once __DIR__ . '/giftcard.php';

add_hook('ClientAreaSecondarySidebar', 1, function(MenuItem $secondarySidebar) {
    if (!is_null($billing = $secondarySidebar->getChild('Billing'))) {
        $billing->addChild('My Gift Cards', ['uri' => 'mygiftcards.php', 'order' => 15, 'icon' => 'fas fa-gift']);
    }
});

// ... (other hooks) ...