<?php
$host = 'localhost'; $db = 'Darling_cosmetics'; $user = 'root'; $pass = ''; $charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $q = $_GET['q'] ?? '';
    if (empty($q)) exit;

    $sql = "SELECT a.full_name, a.email, a.created_at, a.last_login_at,
                   ad.detail_address, COUNT(o.id) as orders, 
                   IFNULL(SUM(o.final_amount), 0) as total_spent
            FROM ACCOUNTS a
            INNER JOIN ACCOUNT_ROLES ar ON a.id = ar.account_id
            INNER JOIN ROLES r ON ar.role_id = r.id
            LEFT JOIN ADDRESSES ad ON a.id = ad.account_id AND ad.is_default = 1
            LEFT JOIN ORDERS o ON a.id = o.account_id
            WHERE r.name = 'user' 
              AND (a.full_name LIKE :s1 OR a.email LIKE :s2 OR ad.detail_address LIKE :s3)
            GROUP BY a.id, a.full_name, a.email, a.created_at, a.last_login_at, ad.detail_address";

    $stmt = $pdo->prepare($sql);
    $val = "%$q%";
    $stmt->execute(['s1' => $val, 's2' => $val, 's3' => $val]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($results) {
        foreach ($results as $c) {
            echo "<tr>
                    <td class='fw-bold'>".htmlspecialchars($c['full_name'])."</td>
                    <td>".htmlspecialchars($c['email'])."</td>
                    <td>".date('d/m/Y', strtotime($c['created_at']))."</td>
                    <td>".($c['last_login_at'] ? date('d/m/Y H:i', strtotime($c['last_login_at'])) : 'Never')."</td>
                    <td class='text-center'>".$c['orders']."</td>
                    <td class='text-success fw-bold'>$".number_format($c['total_spent'], 2)."</td>
                    <td class='small text-muted'>".htmlspecialchars($c['detail_address'] ?? 'No address')."</td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='7' class='text-center py-4'>Không tìm thấy khách hàng nào.</td></tr>";
    }
} catch (Exception $e) { echo "Lỗi hệ thống."; }