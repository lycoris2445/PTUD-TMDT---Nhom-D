<?php
declare(strict_types=1);

// Turn off all output buffering to prevent headers issues
while (ob_get_level()) {
    ob_end_clean();
}

session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || !isset($_SESSION['admin_role'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$allowed_roles = ['super_admin', 'operation_staff'];
if (!in_array($_SESSION['admin_role'], $allowed_roles)) {
    http_response_code(403);
    exit('Access denied');
}

try {
    $conn = require __DIR__ . '/../../config/db_connect.php';
    
    // Lấy tham số lọc (nếu có)
    $startDate = $_GET['start_date'] ?? date('Y-m-01'); // Đầu tháng
    $endDate = $_GET['end_date'] ?? date('Y-m-d'); // Hôm nay
    $reportType = $_GET['type'] ?? 'summary'; // summary, products, orders
    
    // Chuẩn bị tên file
    $filename = "sales_report_" . $reportType . "_" . date('Ymd_His') . ".csv";
    
    // Set headers để download CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Tạo output stream
    $output = fopen('php://output', 'w');
    
    // UTF-8 BOM để Excel đọc được tiếng Việt
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    if ($reportType === 'summary') {
        // ===== BÁO CÁO TỔNG QUAN =====
        fputcsv($output, ['SALES SUMMARY REPORT']);
        fputcsv($output, ['Period', "$startDate to $endDate"]);
        fputcsv($output, ['Generated', date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        
        // 1. Tổng doanh thu
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(final_amount) as total_revenue,
                AVG(final_amount) as avg_order_value
            FROM orders
            WHERE created_at BETWEEN ? AND ?
                AND status NOT IN ('cancelled', 'declined')
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        fputcsv($output, ['REVENUE SUMMARY']);
        fputcsv($output, ['Total Orders', number_format((int)$summary['total_orders'])]);
        fputcsv($output, ['Total Revenue', '$' . number_format((float)($summary['total_revenue'] ?? 0), 2)]);
        fputcsv($output, ['Average Order Value', '$' . number_format((float)($summary['avg_order_value'] ?? 0), 2)]);
        fputcsv($output, []);
        
        // 2. Đơn hàng theo trạng thái
        $stmt = $conn->prepare("
            SELECT 
                status,
                COUNT(*) as count,
                SUM(final_amount) as revenue
            FROM orders
            WHERE created_at BETWEEN ? AND ?
            GROUP BY status
            ORDER BY count DESC
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        fputcsv($output, ['ORDERS BY STATUS']);
        fputcsv($output, ['Status', 'Count', 'Revenue']);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['status'],
                (int)$row['count'],
                '$' . number_format((float)($row['revenue'] ?? 0), 2)
            ]);
        }
        fputcsv($output, []);
        
        // 3. Phương thức thanh toán
        $stmt = $conn->prepare("
            SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(final_amount) as revenue
            FROM orders
            WHERE created_at BETWEEN ? AND ?
                AND status NOT IN ('cancelled', 'declined')
            GROUP BY payment_method
            ORDER BY count DESC
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        fputcsv($output, ['PAYMENT METHODS']);
        fputcsv($output, ['Method', 'Count', 'Revenue']);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['payment_method'] ?? 'COD',
                (int)$row['count'],
                '$' . number_format((float)($row['revenue'] ?? 0), 2)
            ]);
        }
        fputcsv($output, []);
        
        // 4. Doanh thu theo ngày
        $stmt = $conn->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as orders,
                SUM(final_amount) as revenue
            FROM orders
            WHERE created_at BETWEEN ? AND ?
                AND status NOT IN ('cancelled', 'declined')
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        fputcsv($output, ['DAILY REVENUE']);
        fputcsv($output, ['Date', 'Orders', 'Revenue']);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['date'],
                (int)$row['orders'],
                '$' . number_format((float)($row['revenue'] ?? 0), 2)
            ]);
        }
        
    } elseif ($reportType === 'products') {
        // ===== BÁO CÁO SẢN PHẨM BÁN CHẠY =====
        fputcsv($output, ['TOP SELLING PRODUCTS REPORT']);
        fputcsv($output, ['Period', "$startDate to $endDate"]);
        fputcsv($output, ['Generated', date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        
        $stmt = $conn->prepare("
            SELECT 
                p.id,
                p.name as product_name,
                p.spu,
                COUNT(DISTINCT o.id) as order_count,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.quantity * oi.price_at_purchase) as total_revenue,
                AVG(oi.price_at_purchase) as avg_price
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN product_variants pv ON oi.product_variant_id = pv.id
            JOIN products p ON pv.product_id = p.id
            WHERE o.created_at BETWEEN ? AND ?
                AND o.status NOT IN ('cancelled', 'declined')
            GROUP BY p.id, p.name, p.spu
            ORDER BY total_quantity DESC
            LIMIT 50
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        fputcsv($output, ['Rank', 'Product ID', 'SPU', 'Product Name', 'Orders', 'Quantity Sold', 'Total Revenue', 'Avg Price']);
        $rank = 1;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $rank++,
                $row['id'],
                $row['spu'],
                $row['product_name'],
                (int)$row['order_count'],
                (int)$row['total_quantity'],
                '$' . number_format((float)($row['total_revenue'] ?? 0), 2),
                '$' . number_format((float)($row['avg_price'] ?? 0), 2)
            ]);
        }
        
    } elseif ($reportType === 'orders') {
        // ===== BÁO CÁO CHI TIẾT ĐỔN HÀNG =====
        fputcsv($output, ['DETAILED ORDERS REPORT']);
        fputcsv($output, ['Period', "$startDate to $endDate"]);
        fputcsv($output, ['Generated', date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        
        $stmt = $conn->prepare("
            SELECT 
                o.id,
                o.tracking_number,
                o.created_at,
                a.email as customer_email,
                a.full_name as customer_name,
                o.status,
                o.payment_method,
                o.total_amount,
                o.shipping_fee,
                o.final_amount,
                o.shipping_carrier,
                COUNT(oi.id) as item_count
            FROM orders o
            JOIN accounts a ON o.account_id = a.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.created_at BETWEEN ? AND ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        fputcsv($output, [
            'Order ID',
            'Tracking Number',
            'Date',
            'Customer Email',
            'Customer Name',
            'Status',
            'Payment Method',
            'Subtotal',
            'Shipping',
            'Total',
            'Carrier',
            'Items'
        ]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['tracking_number'],
                $row['created_at'],
                $row['customer_email'],
                $row['customer_name'],
                $row['status'],
                $row['payment_method'] ?? 'COD',
                '$' . number_format((float)($row['total_amount'] ?? 0), 2),
                '$' . number_format((float)($row['shipping_fee'] ?? 0), 2),
                '$' . number_format((float)($row['final_amount'] ?? 0), 2),
                $row['shipping_carrier'] ?? 'N/A',
                (int)$row['item_count']
            ]);
        }
    }
    
    fclose($output);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('[EXPORT REPORT ERROR] ' . $e->getMessage());
    exit('Error generating report: ' . $e->getMessage());
}
