<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$sql = file_get_contents(__DIR__ . '/../sql/migrate_phase_c.sql');
if ($sql === false) {
    echo "Could not read migration file.\n";
    exit(1);
}

$pdo = db();
$pdo->exec($sql);
echo "Phase C migration applied.\n";
echo 'Notifications table: ' . (notifications_table_exists() ? 'OK' : 'MISSING') . "\n";
echo 'Password reset table: ' . (password_reset_table_exists() ? 'OK' : 'MISSING') . "\n";
