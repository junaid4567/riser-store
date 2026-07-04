<?php
require_once __DIR__ . '/auth.php';
requireAdminLogin();

$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="riser-orders-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Order #', 'Customer', 'Phone', 'City', 'Province', 'Subtotal', 'Shipping', 'Total', 'Status', 'Date']);

foreach ($orders as $o) {
    fputcsv($out, [
        $o['order_number'], $o['customer_name'], $o['phone'], $o['city'], $o['province'],
        $o['subtotal'], $o['shipping_fee'], $o['total'], $o['status'], $o['created_at'],
    ]);
}
fclose($out);
