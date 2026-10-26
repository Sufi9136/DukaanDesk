<?php
// downloads a csv file (opens in excel). ?type=sales&from=..&to=..  or  ?type=stock
require_once __DIR__ . '/inc/auth.php';
guard('reports');

$type = $_GET['type'] ?? 'sales';

header('Content-Type: text/csv; charset=utf-8');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");   // so excel reads the file as utf-8

if ($type === 'stock') {
    header('Content-Disposition: attachment; filename="stock-' . date('Y-m-d') . '.csv"');
    fputcsv($out, array('Product', 'Category', 'Supplier', 'Cost Price', 'Selling Price', 'Stock', 'Alert Level'));

    $rows = $db->query("SELECT p.name, c.name AS category, s.name AS supplier, p.cost_price, p.price, p.stock, p.min_stock
                        FROM products p
                        LEFT JOIN categories c ON c.id = p.category_id
                        LEFT JOIN suppliers s ON s.id = p.supplier_id
                        ORDER BY p.name");
    foreach ($rows as $r) {
        fputcsv($out, array(csv_safe($r['name']), csv_safe($r['category']), csv_safe($r['supplier']),
                            $r['cost_price'], $r['price'], $r['stock'], $r['min_stock']));
    }
} else {
    $from = clean_date($_GET['from'] ?? '', date('Y-m-01'));
    $to   = clean_date($_GET['to'] ?? '', date('Y-m-d'));

    header('Content-Disposition: attachment; filename="sales-' . $from . '-to-' . $to . '.csv"');
    fputcsv($out, array('Bill No', 'Date', 'Customer', 'Payment', 'Subtotal', 'Discount', 'GST', 'Total'));

    $st = $db->prepare("SELECT b.bill_no, b.created_at, c.name AS customer, b.pay_mode, b.subtotal, b.discount, b.gst_amount, b.total
                        FROM bills b LEFT JOIN customers c ON c.id = b.customer_id
                        WHERE b.created_at >= ? AND b.created_at < DATE_ADD(?, INTERVAL 1 DAY)
                        ORDER BY b.id");
    $st->execute(array($from, $to));
    foreach ($st->fetchAll() as $r) {
        fputcsv($out, array($r['bill_no'], $r['created_at'], csv_safe($r['customer'] ? $r['customer'] : 'Walk-in'),
                            $r['pay_mode'], $r['subtotal'], $r['discount'], $r['gst_amount'], $r['total']));
    }
}
fclose($out);
