<?php
require_once __DIR__ . '/../vms/config/db.php';
$pdo = getDBConnection();
$cols = $pdo->query('DESCRIBE users')->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);

