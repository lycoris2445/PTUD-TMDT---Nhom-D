<?php
header('Content-Type: text/html; charset=utf-8');

// Sample customer data
$customers = [
    ["name"=>"testsite","username"=>"testsite","last_active"=>"2025-11-15","registered"=>"2025-08-18","email"=>"dev-email@wpengine.local","orders"=>3,"total_spent"=>38552400,"aov"=>12850800,"country"=>"VN","city"=>"Ho Chi Minh City","region"=>"HCM"],
    ["name"=>"Alice","username"=>"alice01","last_active"=>"2025-11-10","registered"=>"2025-06-12","email"=>"alice@gmail.com","orders"=>1,"total_spent"=>5200000,"aov"=>5200000,"country"=>"VN","city"=>"Da Nang","region"=>"DN"],
    ["name"=>"Bob","username"=>"bob88","last_active"=>"2025-10-22","registered"=>"2025-05-01","email"=>"bob@yahoo.com","orders"=>5,"total_spent"=>21000000,"aov"=>4200000,"country"=>"US","city"=>"New York","region"=>"NY"],
    ["name"=>"Charlie Brown","username"=>"charlieb","last_active"=>"2025-11-20","registered"=>"2025-07-05","email"=>"charlie@example.com","orders"=>2,"total_spent"=>15000000,"aov"=>7500000,"country"=>"UK","city"=>"London","region"=>"LDN"],
    ["name"=>"Diana Prince","username"=>"diana","last_active"=>"2025-11-18","registered"=>"2025-09-15","email"=>"diana@example.com","orders"=>7,"total_spent"=>45000000,"aov"=>6428571,"country"=>"CA","city"=>"Toronto","region"=>"ON"],
];

// Get search query
$q = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';

// Debug: Log the search query
error_log("Search query: " . $q);

// Format currency function
function vnd($n) {
    return number_format($n, 0, ',', '.') . " ₫";
}

// Filter customers
$results = [];
if (empty($q)) {
    // If empty search, return empty
    echo '<tr><td colspan="11" class="text-center text-muted py-4">Type something to search...</td></tr>';
    exit;
} else {
    foreach ($customers as $customer) {
        // Search in multiple fields
        if (stripos($customer['name'], $q) !== false ||
            stripos($customer['username'], $q) !== false ||
            stripos($customer['email'], $q) !== false ||
            stripos($customer['country'], $q) !== false ||
            stripos($customer['city'], $q) !== false ||
            stripos($customer['region'], $q) !== false) {
            $results[] = $customer;
        }
    }
}

// Output results
if (empty($results)) {
    echo '<tr><td colspan="11" class="text-center text-muted py-4">No customers found for "' . htmlspecialchars($q) . '"</td></tr>';
} else {
    foreach ($results as $c) {
        echo "
        <tr>
            <td>{$c['name']}</td>
            <td>{$c['username']}</td>
            <td>{$c['last_active']}</td>
            <td>{$c['registered']}</td>
            <td><a href='mailto:{$c['email']}'>{$c['email']}</a></td>
            <td>{$c['orders']}</td>
            <td>" . vnd($c['total_spent']) . "</td>
            <td>" . vnd($c['aov']) . "</td>
            <td>{$c['country']}</td>
            <td>{$c['city']}</td>
            <td>{$c['region']}</td>
        </tr>";
    }
}
?>