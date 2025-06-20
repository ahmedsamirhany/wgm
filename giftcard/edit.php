<?php
/**
 * Edit Page for a Gift Card
 */

use WHMCS\Database\Capsule;

// Bootstrap WHMCS
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/helpers.php';

$cardId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$modulelink = 'addonmodules.php?module=giftcard';

// Start outputting the admin page using WHMCS's internal templating
$aInt = new WHMCS\Admin("My Addon"); 
$aInt->title = "Edit Gift Card";
$aInt->sidebar = 'addonmodules';
$aInt->icon = 'fas fa-gift';
$aInt->helplink = ''; 

// Start buffer to capture content
ob_start();

$cardToEdit = Capsule::table('mod_giftcards')->find($cardId);

if ($cardToEdit) {
    $currency = getCurrency(null, $cardToEdit->currency_id);
    $statusOptions = '';
    $statuses = ['Active', 'Redeemed', 'Expired', 'Cancelled'];
    foreach ($statuses as $s) {
        $statusOptions .= '<option value="' . htmlspecialchars($s) . '" ' . giftcard_helper_selected($cardToEdit->status, $s) . '>' . htmlspecialchars($s) . '</option>';
    }
    $expiryDateValue = giftcard_helper_format_date($cardToEdit->expiry_date);

    echo '<script>jQuery(document).ready(function($){$("#expiry_datepicker_edit").datepicker({dateFormat:"yy-mm-dd"});});</script>';
    echo '<h3>Edit Gift Card: ' . htmlspecialchars($cardToEdit->code) . '</h3>';
    
    echo '<form action="modules/addons/giftcard/manage.php" method="post">';
    echo '<input type="hidden" name="action" value="save_card">';
    echo '<input type="hidden" name="token" value="' . generate_token('plain') . '">'; // CSRF Token
    echo '<input type="hidden" name="card_id" value="' . $cardToEdit->id . '">';
    echo '<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">';
    echo '<tr><td class="fieldlabel" width="20%">Code</td><td class="fieldarea"><strong>' . htmlspecialchars($cardToEdit->code) . '</strong></td></tr>';
    echo '<tr><td class="fieldlabel">Current Balance (' . htmlspecialchars($currency['code']) . ')</td><td class="fieldarea"><input type="text" name="current_balance" class="form-control input-200" value="' . htmlspecialchars($cardToEdit->current_balance) . '"></td></tr>';
    echo '<tr><td class="fieldlabel">Status</td><td class="fieldarea"><select name="status" class="form-control select-200">' . $statusOptions . '</select></td></tr>';
    echo '<tr><td class="fieldlabel">Expiry Date</td><td class="fieldarea"><input type="text" id="expiry_datepicker_edit" name="expiry_date" class="form-control input-200" value="' . htmlspecialchars($expiryDateValue) . '" placeholder="YYYY-MM-DD"> <small>Leave blank for no expiry.</small></td></tr>';
    echo '</table>';
    echo '<div class="btn-container"><input type="submit" value="Save Changes" class="btn btn-primary"> <a href="' . $modulelink . '" class="btn btn-secondary">Cancel</a></div>';
    echo '</form>';
    
} else {
    echo "<div class=\"alert alert-danger\">Gift card not found.</div>";
}

$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->output();