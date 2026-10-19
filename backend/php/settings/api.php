<?php
/* Settings API (Admin for GET/POST, Cashier for GET). ?action=get | save */
require_once __DIR__ . '/../config/helpers.php';
$user = require_login();
$pdo = db();
$action = $_GET['action'] ?? 'get';

if ($action === 'get') {
    $st = $pdo->query('SELECT setting_key, setting_value FROM settings');
    $rows = $st->fetchAll();
    $settings = [];
    foreach ($rows as $r) {
        $settings[$r['setting_key']] = $r['setting_value'];
    }
    json_out(['success' => true, 'settings' => $settings]);
}

if ($action === 'save') {
    only_post();
    require_role('admin');
    $b = body();

    $taxEnabled = !empty($b['tax_enabled']) ? '1' : '0';
    $taxRate = filter_var($b['tax_rate'] ?? '0', FILTER_VALIDATE_FLOAT);
    $header = clean($b['receipt_header'] ?? 'QUICK BUY RETAIL');
    $footer = clean($b['receipt_footer'] ?? 'Thank you for shopping!');

    if ($taxRate === false || $taxRate < 0 || $taxRate > 100) fail('Tax rate must be between 0 and 100.');

    $st = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $st->execute(['tax_enabled', $taxEnabled]);
    $st->execute(['tax_rate', (string)$taxRate]);
    $st->execute(['receipt_header', $header]);
    $st->execute(['receipt_footer', $footer]);

    log_activity('settings_update', "Updated store settings (Tax: {$taxEnabled}, Rate: {$taxRate}%)");
    json_out(['success' => true, 'message' => 'Settings saved successfully.']);
}

fail('Unknown action.');
