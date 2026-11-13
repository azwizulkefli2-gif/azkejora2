<?php
/**
 * rss.php — Live feed JSON API
 *   ?type=news            → cached items (auto-refresh if stale)
 *   ?type=news&refresh=1  → force refresh
 *   ?action=health        → test every source URL (admin only)
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'Not authenticated']); exit;
}

require_once 'inc_db.php';
require_once 'inc_feeds.php';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'DB connection failed']); exit;
}

$lang   = $_SESSION['lang'] ?? 'ms';
$action = $_GET['action'] ?? 'list';

/* ---------- HEALTH CHECK: which sources actually work? ---------- */
if ($action === 'health') {
    $out = [];
    foreach ($FEED_SOURCES as $type => $list) {
        foreach ($list as $src) {
            if ($src['kind'] !== 'rss') { $out[$type][$src['key']] = 'api source (not URL-tested)'; continue; }
            $r = feed_http_get($src['url']);
            $n = $r['ok'] ? count(feed_parse_rss($r['body'], $src)) : 0;
            $out[$type][$src['key']] = $r['ok'] ? "OK · $n items" : "FAIL · ".$r['error'];
        }
    }
    echo json_encode(['ok'=>true,'health'=>$out], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES); exit;
}

/* ---------- LIST ---------- */
$type = $_GET['type'] ?? 'news';
if (!in_array($type, ['news','entertainment','sports','games','mixed'], true)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Invalid type']); exit;
}

$force = !empty($_GET['refresh']);

// Manual refresh throttle: max once per 60s per session
if ($force) {
    $k = 'feed_force_'.$type;
    if (isset($_SESSION[$k]) && time() - $_SESSION[$k] < 60) $force = false;
    else $_SESSION[$k] = time();
}

if ($type === 'mixed') {
    foreach (['news','entertainment','sports','games'] as $t) feed_refresh($pdo, $t, $force);
    $items = feed_get_mixed($pdo, 3, $lang);
    $last  = feed_last_updated($pdo, 'news');
} else {
    feed_refresh($pdo, $type, $force);
    $items = feed_get($pdo, $type, 30, $lang);
    $last  = feed_last_updated($pdo, $type);
}

$savedIds = feed_saved_ids($pdo, $_SESSION['user']['userid'] ?? '');
foreach ($items as &$i) {
    $i['saved']       = in_array((int)$i['id'], $savedIds, true);
    $i['detail_link'] = 'feed_detail.php?id='.(int)$i['id'];
    unset($i['content_html']); // keep payload light
}

echo json_encode([
    'ok'           => true,
    'type'         => $type,
    'count'        => count($items),
    'last_updated' => $last,
    'updated_ago'  => feed_time_ago($last, $lang),
    'items'        => $items,
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
