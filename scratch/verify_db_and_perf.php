<?php
require_once __DIR__ . '/../vms/config/db.php';
require_once __DIR__ . '/../vms/includes/functions.php';

$pdo = getDBConnection();

echo "=== DATABASE INDEXES ===\n";
foreach (['bookings', 'children', 'hospitals', 'vaccines', 'hospital_vaccines'] as $table) {
    echo "--- Table: $table ---\n";
    $stmt = $pdo->query("SHOW INDEX FROM `$table`");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  " . $row['Key_name'] . " (" . $row['Seq_in_index'] . ") -> " . $row['Column_name'] . "\n";
    }
}

echo "\n=== SAFE LOAD TEST BENCHMARK ===\n";
$start = microtime(true);
$iterations = 100;

for ($i = 0; $i < $iterations; $i++) {
    // 1. Pending requests query (simulating admin dashboard load)
    $stmt = $pdo->query(
        'SELECT b.id, b.preferred_date, b.time_slot, c.name AS child_name, h.name AS hospital_name, v.name AS vaccine_name
         FROM bookings b
         JOIN children c ON b.child_id = c.id
         JOIN hospitals h ON b.hospital_id = h.id
         JOIN vaccines v ON b.vaccine_id = v.id
         WHERE b.admin_status = "pending"
         LIMIT 5'
    );
    $stmt->fetchAll();

    // 2. Child doses completed query (simulating parent dashboard load)
    $stmt = $pdo->query(
        'SELECT c.id, c.name, COUNT(b.id) as completed_doses
         FROM children c
         LEFT JOIN bookings b ON b.child_id = c.id AND b.vaccination_status = "vaccinated"
         GROUP BY c.id
         LIMIT 10'
    );
    $stmt->fetchAll();

    // 3. Hospital active vaccine stock check
    $stmt = $pdo->query(
        'SELECT hv.*, v.name as vaccine_name 
         FROM hospital_vaccines hv
         JOIN vaccines v ON hv.vaccine_id = v.id
         WHERE hv.status = "available"
         LIMIT 10'
    );
    $stmt->fetchAll();
}

$elapsed = microtime(true) - $start;
$avgPerQueryBatch = ($elapsed / $iterations) * 1000;
echo "Completed $iterations iterations of 3 complex multi-table queries (total " . ($iterations * 3) . " queries).\n";
echo "Total Time: " . number_format($elapsed, 4) . " seconds\n";
echo "Average latency per 3-query batch: " . number_format($avgPerQueryBatch, 3) . " ms\n";
echo "Queries per second: " . number_format(($iterations * 3) / $elapsed, 1) . " QPS\n";

