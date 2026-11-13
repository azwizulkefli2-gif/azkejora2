<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: index.php"); exit; }
require_once 'inc_db.php';
$user = $_SESSION['user'];
$userId = $user['userid'] ?? $user['id'];

$from = $_POST['date_from'] ?? date('Y-m-01');
$to = $_POST['date_to'] ?? date('Y-m-d');

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? AND tx_date BETWEEN ? AND ? ORDER BY tx_date DESC");
    $stmt->execute([$userId, $from, $to]);
    $rows = $stmt->fetchAll();
} catch (Exception $e) { die("DB error"); }

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="azkejora_report_'.$from.'_to_'.$to.'.csv"');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
fputcsv($out, ['Date','Type','Category','Amount','Description']);
foreach ($rows as $r) {
    fputcsv($out, [$r['tx_date'], $r['type'], $r['category'], $r['amount'], $r['description']]);
}
fclose($out);
exit;
