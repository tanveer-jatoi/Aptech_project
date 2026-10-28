<?php
require_once __DIR__ . '/../vms/config/db.php';
$pdo = getDBConnection();
$rows = $pdo->query('SELECT id, name, email, role FROM users')->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);

