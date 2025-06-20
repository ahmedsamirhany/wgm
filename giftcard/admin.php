 <?php
use WHMCS\Database\Capsule;
use WHMCS\Billing\Currency;

if (!defined("WHMCS")) { die("This file cannot be accessed directly"); }

require_once __DIR__ . '/helpers.php';

$action = $_REQUEST['action'] ?? 'manage';
$modulelink = 'addonmodules.php?module=giftcard';

if (isset($_GET['generatesuccess'])) { echo '<div class="alert alert-success">Gift cards generated successfully.</div>'; }
if (isset($_GET['savesuccess'])) { echo '<div class="alert alert-success">Gift card saved successfully.</div>'; }
if (isset($_GET['deletesuccess'])) { echo '<div class="alert alert-success">Gift card deleted successfully.</div>'; }

if ($action != 'edit') {
    echo '<h1>Professional Gift Cards - Admin</h1>';
    echo '<ul class="nav nav-tabs">
        <li class="nav-item"><a class="nav-link' . ($action == 'manage' ? ' active' : '') . '" href="' . $modulelink . '&action=manage">Manage Cards</a></li>
        <li class="nav-item"><a class="nav-link' . ($action == 'generate' ? ' active' : '') . '" href="' . $modulelink . '&action=generate">Generate New Cards</a></li>
        <li class="nav-item"><a class="nav-link' . ($action == 'logs' ? ' active' : '') . '" href="' . $modulelink . '&action=logs">View Logs</a></li>
    </ul>';
}

if ($action == 'manage') {
    echo '<div class="card card-body bg-light mt-3">';
    echo '<h3>Manage Existing Gift Cards</h3>';
    $cards = Capsule::table('mod_giftcards')->orderBy('id', 'desc')->get();
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>ID</th><th>Code</th><th>Initial Value</th><th>Current Balance</th><th>Status</th><th>Expiry Date</th><th>Created</th><th>Actions</th></tr></thead><tbody>';
    foreach ($cards as $card) {
        $currency = getCurrency(null, $card->currency_id);
        echo '<tr>
                <td>' . $card->id . '</td><td><strong>' . htmlspecialchars($card->code) . '</strong></td>
                <td>' . formatCurrency($card->initial_value, $currency['id']) . '</td>
                <td>' . formatCurrency($card->current_balance, $currency['id']) . '</td>
                <td>' . htmlspecialchars($card->status) . '</td>
                <td>' . ($card->expiry_date ? giftcard_helper_format_date($card->expiry_date) : 'Never') . '</td>
                <td>' . giftcard_helper_format_date($card->created_at) . '</td>
                <td>
                    <a href="' . $modulelink . '&action=edit&id=' . $card->id . '" class="btn btn-sm btn-info">Edit</a>
                    <a href="../modules/addons/giftcard/manage.php?action=delete&id=' . $card->id . '&token=' . generate_token('plain') . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>
                </td></tr>';
    }
    echo '</tbody></table></div>';
}

if ($action == 'generate') {
    echo '<div class="card card-body bg-light mt-3">';
    echo '<h3>Generate New Gift Cards</h3>';
    $currency_options = '';
    foreach (Currency::all() as $currency) {
        $selected = ($currency->default) ? 'selected' : '';
        $currency_options .= '<option value="' . $currency->id . '" ' . $selected . '>' . $currency->code . '</option>';
    }
    echo '<form action="../modules/addons/giftcard/manage.php" method="post">';
    echo '<input type="hidden" name="action" value="generate"><input type="hidden" name="token" value="' . generate_token('plain') . '">';
    echo '<table class="form" width="100%">';
    echo '<tr><td class="fieldlabel" width="20%">Quantity</td><td class="fieldarea"><input type="number" name="quantity" class="form-control input-100" value="10" min="1" max="500"></td></tr>';
    echo '<tr><td class="fieldlabel">Value</td><td class="fieldarea"><input type="text" name="value" class="form-control input-100" value="25.00"></td></tr>';
    echo '<tr><td class="fieldlabel">Currency</td><td class="fieldarea"><select name="currency" class="form-control select-200">' . $currency_options . '</select></td></tr>';
    echo '<tr><td class="fieldlabel">Expiry Date</td><td class="fieldarea"><input type="text" id="expiry_datepicker_gen" name="expiry_date" class="form-control input-200" placeholder="YYYY-MM-DD"> <small>Leave blank for no expiry.</small></td></tr>';
    echo '</table><div class="btn-container"><input type="submit" value="Generate Cards" class="btn btn-primary"></div></form>';
    echo '<script>jQuery(document).ready(function($){$("#expiry_datepicker_gen").datepicker({dateFormat:"yy-mm-dd"});});</script></div>';
}

if ($action == 'logs') {
    echo '<div class="card card-body bg-light mt-3">';
    echo '<h3>Gift Card Transaction Logs</h3>';
    $logs = Capsule::table('mod_giftcard_logs as l')->join('mod_giftcards as c', 'l.card_id', '=', 'c.id')->select('l.*', 'c.code')->orderBy('l.id', 'desc')->get();
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>Log ID</th><th>Card Code</th><th>Action</th><th>Amount</th><th>Notes</th><th>Date</th></tr></thead><tbody>';
    foreach ($logs as $log) {
        echo '<tr>
                <td>' . $log->id . '</td><td>' . htmlspecialchars($log->code) . '</td><td>' . htmlspecialchars($log->action) . '</td>
                <td>' . ($log->amount > 0 ? formatCurrency($log->amount) : '-') . '</td>
                <td>' . nl2br(htmlspecialchars($log->notes)) . '</td><td>' . date('Y-m-d H:i:s', strtotime($log->created_at)) . '</td>
              </tr>';
    }
    echo '</tbody></table></div>';
}

if ($action == 'edit') {
    $cardId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $cardToEdit = Capsule::table('mod_giftcards')->find($cardId);
    if ($cardToEdit) {
        echo '<h1>Edit Gift Card</h1><div class="card card-body bg-light mt-3">';
        $currency = getCurrency(null, $cardToEdit->currency_id);
        $statusOptions = '';
        foreach (['Active', 'Redeemed', 'Expired', 'Cancelled'] as $s) {
            $statusOptions .= '<option value="' . htmlspecialchars($s) . '" ' . giftcard_helper_selected($cardToEdit->status, $s) . '>' . htmlspecialchars($s) . '</option>';
        }
        $expiryDateValue = giftcard_helper_format_date($cardToEdit->expiry_date);
        echo '<script>jQuery(document).ready(function($){$("#expiry_datepicker_edit").datepicker({dateFormat:"yy-mm-dd"});});</script>';
        echo '<h3>Editing Card: ' . htmlspecialchars($cardToEdit->code) . '</h3>';
        echo '<form action="../modules/addons/giftcard/manage.php" method="post">';
        echo '<input type="hidden" name="action" value="save_card"><input type="hidden" name="token" value="' . generate_token('plain') . '"><input type="hidden" name="card_id" value="' . $cardToEdit->id . '">';
        echo '<table class="form" width="100%">';
        echo '<tr><td class="fieldlabel" width="20%">Code</td><td class="fieldarea"><strong>' . htmlspecialchars($cardToEdit->code) . '</strong></td></tr>';
        echo '<tr><td class="fieldlabel">Current Balance (' . htmlspecialchars($currency['code']) . ')</td><td class="fieldarea"><input type="text" name="current_balance" class="form-control input-200" value="' . htmlspecialchars($cardToEdit->current_balance) . '"></td></tr>';
        echo '<tr><td class="fieldlabel">Status</td><td class="fieldarea"><select name="status" class="form-control select-200">' . $statusOptions . '</select></td></tr>';
        echo '<tr><td class="fieldlabel">Expiry Date</td><td class="fieldarea"><input type="text" id="expiry_datepicker_edit" name="expiry_date" class="form-control input-200" value="' . htmlspecialchars($expiryDateValue) . '" placeholder="YYYY-MM-DD"> <small>Leave blank for no expiry.</small></td></tr>';
        echo '</table><div class="btn-container"><input type="submit" value="Save Changes" class="btn btn-primary"> <a href="' . $modulelink . '" class="btn btn-secondary">Cancel</a></div></form></div>';
    } else {
        echo '<div class="alert alert-danger">Gift card not found.</div>';
    }
}