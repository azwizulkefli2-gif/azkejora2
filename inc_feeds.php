<?php
/**
 * AZ Kejora — Live Feed Engine
 * Fetch → normalise → cache in PostgreSQL → serve.
 * Requires: $pdo (PDO pgsql), inc_feed_config.php
 */
require_once __DIR__ . '/inc_feed_config.php';

/* ============================================================
 * HTTP
 * ========================================================== */
function feed_http_get(string $url, array $headers = []): ?array {
    global $FEED_HTTP_TIMEOUT;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 4,
        CURLOPT_TIMEOUT        => $FEED_HTTP_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_ENCODING       => '',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'AZKejoraFeedBot/1.0 (+https://azkejora2.onrender.com)',
        CURLOPT_HTTPHEADER     => array_merge(['Accept: */*'], $headers),
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($body === false || $code >= 400) {
        return ['ok'=>false, 'status'=>$code, 'body'=>null, 'error'=>$err ?: "HTTP $code"];
    }
    return ['ok'=>true, 'status'=>$code, 'body'=>$body, 'error'=>null];
}

function feed_http_json(string $url, array $headers = []): ?array {
    $r = feed_http_get($url, $headers);
    if (!$r || !$r['ok']) return null;
    $j = json_decode($r['body'], true);
    return is_array($j) ? $j : null;
}

/* ============================================================
 * TEXT HELPERS
 * ========================================================== */
function feed_clean(?string $html): string {
    if (!$html) return '';
    $t = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $t = preg_replace('/\s+/u', ' ', $t);
    return trim($t);
}

function feed_excerpt(?string $text, int $max = 220): string {
    $t = feed_clean($text);
    if (mb_strlen($t) <= $max) return $t;
    $cut = mb_substr($t, 0, $max);
    $sp  = mb_strrpos($cut, ' ');
    if ($sp !== false && $sp > $max * 0.6) $cut = mb_substr($cut, 0, $sp);
    return rtrim($cut, " ,.;:") . '…';
}

/** Only allow safe tags in stored article HTML */
function feed_sanitize_html(?string $html): string {
    if (!$html) return '';
    $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html);
    $html = strip_tags($html, '<p><br><b><strong><i><em><u><ul><ol><li><a><img><h2><h3><h4><blockquote><figure><figcaption>');
    $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/i', '$1="#"', $html);
    return trim($html);
}

/** Steam returns BBCode-ish content — convert the common tags */
function feed_steam_bbcode(?string $t): string {
    if (!$t) return '';
    $t = preg_replace('/\{STEAM_CLAN_IMAGE\}/i', 'https://clan.cloudflare.steamstatic.com/images', $t);
    $map = [
        '/\[h1\](.*?)\[\/h1\]/is' => '<h3>$1</h3>',
        '/\[h2\](.*?)\[\/h2\]/is' => '<h3>$1</h3>',
        '/\[h3\](.*?)\[\/h3\]/is' => '<h4>$1</h4>',
        '/\[b\](.*?)\[\/b\]/is'   => '<strong>$1</strong>',
        '/\[i\](.*?)\[\/i\]/is'   => '<em>$1</em>',
        '/\[u\](.*?)\[\/u\]/is'   => '<u>$1</u>',
        '/\[list\]/i'             => '<ul>',
        '/\[\/list\]/i'           => '</ul>',
        '/\[\*\](.*?)(?=\[\*\]|\[\/list\]|$)/is' => '<li>$1</li>',
        '/\[url=([^\]]+)\](.*?)\[\/url\]/is' => '<a href="$1" target="_blank" rel="noopener">$2</a>',
        '/\[img\](.*?)\[\/img\]/is' => '<img src="$1" alt="">',
        '/\[previewicon\](.*?)\[\/previewicon\]/is' => '',
    ];
    foreach ($map as $re => $rep) $t = preg_replace($re, $rep, $t);
    $t = nl2br($t);
    return feed_sanitize_html($t);
}

/** Real relative timestamps — replaces the old fake "2h ago" strings */
function feed_time_ago(?string $ts, string $lang = 'ms'): string {
    if (!$ts) return $lang === 'ms' ? 'Baru dikemas kini' : 'Just updated';
    $t = strtotime($ts);
    if (!$t) return '';
    $d = time() - $t;
    if ($d < 0)      return $lang === 'ms' ? 'Akan datang' : 'Upcoming';
    if ($d < 60)     return $lang === 'ms' ? 'Baru sahaja' : 'Just now';
    if ($d < 3600)   { $n = floor($d/60);   return $lang==='ms' ? "$n min lalu" : "$n min ago"; }
    if ($d < 86400)  { $n = floor($d/3600); return $lang==='ms' ? "$n jam lalu" : "$n hr ago"; }
    if ($d < 604800) { $n = floor($d/86400);return $lang==='ms' ? "$n hari lalu": "$n d ago"; }
    return date('d M Y', $t);
}

/* ============================================================
 * RSS / ATOM PARSER
 * ========================================================== */
function feed_parse_rss(string $xml, array $src): array {
    $prev = libxml_use_internal_errors(true);
    $s = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    if (!$s) return [];

    $out = [];
    // RSS 2.0 vs Atom
    $items = isset($s->channel->item) ? $s->channel->item : (isset($s->entry) ? $s->entry : []);

    foreach ($items as $it) {
        $ns   = $it->children('content', true);
        $dc   = $it->children('dc', true);
        $med  = $it->children('media', true);

        $title = (string)($it->title ?? '');
        $link  = (string)($it->link ?? '');
        if (!$link && isset($it->link['href'])) $link = (string)$it->link['href'];

        $desc    = (string)($it->description ?? $it->summary ?? '');
        $content = (string)($ns->encoded ?? '');
        $date    = (string)($it->pubDate ?? $it->published ?? $it->updated ?? $dc->date ?? '');

        // Image discovery: media:content → media:thumbnail → enclosure → first <img>
        $img = '';
        if (isset($med->content['url']))   $img = (string)$med->content['url'];
        if (!$img && isset($med->thumbnail['url'])) $img = (string)$med->thumbnail['url'];
        if (!$img && isset($it->enclosure['url'])) {
            $type = (string)$it->enclosure['type'];
            if (strpos($type, 'image') === 0) $img = (string)$it->enclosure['url'];
        }
        if (!$img && preg_match('/<img[^>]+src=["\']([^"\']+)/i', $content . $desc, $m)) $img = $m[1];

        if (!$title || !$link) continue;

        $out[] = [
            'external_id'  => md5($src['key'] . '|' . $link),
            'title'        => feed_clean($title),
            'summary'      => feed_excerpt($desc ?: $content, 240),
            'content_html' => feed_sanitize_html($content ?: $desc),
            'feed_url'     => $link,
            'image_url'    => $img,
            'tag'          => $src['tag'] ?? 'News',
            'lang'         => $src['lang'] ?? 'en',
            'detail_kind'  => 'rss',
            'published_at' => $date ? (date('c', strtotime($date) ?: time())) : date('c'),
        ];
    }
    return $out;
}

/* ============================================================
 * SOURCE FETCHERS  (all return the same normalised shape)
 * ========================================================== */
function feed_fetch_rss(array $src): array {
    $r = feed_http_get($src['url']);
    if (!$r['ok']) throw new RuntimeException($r['error']);
    return feed_parse_rss($r['body'], $src);
}

function feed_fetch_newsapi(array $src, string $key): array {
    $q = array_merge(['apiKey'=>$key], $src['params'] ?? []);
    $j = feed_http_json('https://newsapi.org/v2/top-headlines?' . http_build_query($q));
    if (!$j || ($j['status'] ?? '') !== 'ok') throw new RuntimeException($j['message'] ?? 'NewsAPI error');
    $out = [];
    foreach (($j['articles'] ?? []) as $a) {
        if (empty($a['url']) || empty($a['title'])) continue;
        $out[] = [
            'external_id'  => md5($src['key'].'|'.$a['url']),
            'title'        => feed_clean($a['title']),
            'summary'      => feed_excerpt($a['description'] ?? '', 240),
            'content_html' => feed_sanitize_html('<p>'.htmlspecialchars($a['content'] ?? $a['description'] ?? '').'</p>'),
            'feed_url'     => $a['url'],
            'image_url'    => $a['urlToImage'] ?? '',
            'tag'          => $src['tag'] ?? 'News',
            'lang'         => $src['lang'] ?? 'en',
            'detail_kind'  => 'article',
            'published_at' => $a['publishedAt'] ?? date('c'),
        ];
    }
    return $out;
}

function feed_fetch_gnews(array $src, string $key): array {
    $q = array_merge(['token'=>$key], $src['params'] ?? []);
    $j = feed_http_json('https://gnews.io/api/v4/top-headlines?' . http_build_query($q));
    if (!$j || isset($j['errors'])) throw new RuntimeException(json_encode($j['errors'] ?? 'GNews error'));
    $out = [];
    foreach (($j['articles'] ?? []) as $a) {
        $out[] = [
            'external_id'  => md5($src['key'].'|'.$a['url']),
            'title'        => feed_clean($a['title']),
            'summary'      => feed_excerpt($a['description'] ?? '', 240),
            'content_html' => feed_sanitize_html('<p>'.htmlspecialchars($a['content'] ?? '').'</p>'),
            'feed_url'     => $a['url'],
            'image_url'    => $a['image'] ?? '',
            'tag'          => $src['tag'] ?? 'News',
            'lang'         => $src['lang'] ?? 'en',
            'detail_kind'  => 'article',
            'published_at' => $a['publishedAt'] ?? date('c'),
        ];
    }
    return $out;
}

function feed_fetch_newsdata(array $src, string $key): array {
    $q = array_merge(['apikey'=>$key], $src['params'] ?? []);
    $j = feed_http_json('https://newsdata.io/api/1/latest?' . http_build_query($q));
    if (!$j || ($j['status'] ?? '') !== 'success') throw new RuntimeException($j['message'] ?? 'NewsData error');
    $out = [];
    foreach (($j['results'] ?? []) as $a) {
        if (empty($a['link'])) continue;
        $out[] = [
            'external_id'  => md5($src['key'].'|'.$a['link']),
            'title'        => feed_clean($a['title'] ?? ''),
            'summary'      => feed_excerpt($a['description'] ?? '', 240),
            'content_html' => feed_sanitize_html('<p>'.htmlspecialchars($a['content'] ?? $a['description'] ?? '').'</p>'),
            'feed_url'     => $a['link'],
            'image_url'    => $a['image_url'] ?? '',
            'tag'          => $src['tag'] ?? 'News',
            'lang'         => $a['language'] ?? 'en',
            'detail_kind'  => 'article',
            'published_at' => !empty($a['pubDate']) ? date('c', strtotime($a['pubDate'])) : date('c'),
        ];
    }
    return $out;
}

function feed_fetch_tmdb(array $src, string $key): array {
    $media  = $src['params']['media']  ?? 'movie';
    $window = $src['params']['window'] ?? 'week';
    $url = "https://api.themoviedb.org/3/trending/{$media}/{$window}?" . http_build_query(['api_key'=>$key,'language'=>'en-US']);
    $j = feed_http_json($url);
    if (!$j || empty($j['results'])) throw new RuntimeException('TMDB empty/error');
    $out = [];
    foreach ($j['results'] as $m) {
        $title = $m['title'] ?? $m['name'] ?? '';
        $date  = $m['release_date'] ?? $m['first_air_date'] ?? '';
        $out[] = [
            'external_id'  => 'tmdb_'.$media.'_'.$m['id'],
            'title'        => feed_clean($title),
            'summary'      => feed_excerpt($m['overview'] ?? '', 240),
            'content_html' => '',
            'feed_url'     => "https://www.themoviedb.org/{$media}/{$m['id']}",
            'image_url'    => !empty($m['poster_path']) ? 'https://image.tmdb.org/t/p/w500'.$m['poster_path'] : '',
            'tag'          => $media === 'tv' ? 'TV Series' : 'Movie',
            'lang'         => 'en',
            'detail_kind'  => 'tmdb',
            'published_at' => $date ? date('c', strtotime($date)) : date('c'),
        ];
    }
    return $out;
}

function feed_fetch_sportsdb(array $src, string $key): array {
    $league = (int)($src['params']['league'] ?? 4328);
    $base   = "https://www.thesportsdb.com/api/v1/json/{$key}";
    // Next fixtures + last results = a real "scores & fixtures" feed
    $next = feed_http_json("$base/eventsnextleague.php?id=$league")['events'] ?? [];
    $past = feed_http_json("$base/eventspastleague.php?id=$league")['events'] ?? [];
    $rows = array_merge(array_slice($past, 0, 6), array_slice($next, 0, 6));
    if (!$rows) throw new RuntimeException('TheSportsDB empty');
    $out = [];
    foreach ($rows as $e) {
        $hs = $e['intHomeScore']; $as = $e['intAwayScore'];
        $played = ($hs !== null && $hs !== '' && $as !== null && $as !== '');
        $title  = $e['strHomeTeam'].' vs '.$e['strAwayTeam'];
        $sum    = $played
            ? "Full time: {$e['strHomeTeam']} {$hs} – {$as} {$e['strAwayTeam']}. {$e['strLeague']} · {$e['strVenue']}"
            : "Upcoming {$e['strLeague']} fixture at {$e['strVenue']}. Kick-off {$e['strTime']} UTC.";
        $out[] = [
            'external_id'  => 'sdb_'.$e['idEvent'],
            'title'        => feed_clean($title),
            'summary'      => feed_excerpt($sum, 240),
            'content_html' => '',
            'feed_url'     => $e['strVideo'] ?? "https://www.thesportsdb.com/event/{$e['idEvent']}",
            'image_url'    => $e['strThumb'] ?? ($e['strFanart'] ?? ''),
            'tag'          => $e['strSport'] ?? ($src['tag'] ?? 'Sport'),
            'lang'         => 'en',
            'detail_kind'  => 'sportsdb',
            'published_at' => !empty($e['strTimestamp']) ? date('c', strtotime($e['strTimestamp'])) : date('c'),
        ];
    }
    return $out;
}

function feed_fetch_steam(array $src): array {
    $appid = (int)$src['params']['appid'];
    $url = 'https://api.steampowered.com/ISteamNews/GetNewsForApp/v0002/?' . http_build_query([
        'appid'=>$appid, 'count'=>10, 'maxlength'=>0, 'format'=>'json'
    ]);
    $j = feed_http_json($url);
    $items = $j['appnews']['newsitems'] ?? [];
    if (!$items) throw new RuntimeException('Steam empty');
    $out = [];
    foreach ($items as $n) {
        $out[] = [
            'external_id'  => 'steam_'.$n['gid'],
            'title'        => feed_clean($n['title']),
            'summary'      => feed_excerpt($n['contents'], 240),
            'content_html' => feed_steam_bbcode($n['contents']),
            'feed_url'     => $n['url'],
            'image_url'    => '',
            'tag'          => $src['name'],
            'lang'         => 'en',
            'detail_kind'  => 'steam',
            'published_at' => date('c', (int)$n['date']),
        ];
    }
    return $out;
}

function feed_fetch_rawg(array $src, string $key): array {
    $today = date('Y-m-d');
    $from  = date('Y-m-d', strtotime('-60 days'));
    $q = array_merge([
        'key'=>$key, 'dates'=>"$from,$today",
    ], $src['params'] ?? []);
    $j = feed_http_json('https://api.rawg.io/api/games?' . http_build_query($q));
    if (empty($j['results'])) throw new RuntimeException('RAWG empty/error');
    $out = [];
    foreach ($j['results'] as $g) {
        $plats = implode(', ', array_map(fn($p)=>$p['platform']['name'], array_slice($g['platforms'] ?? [], 0, 4)));
        $genres= implode(', ', array_map(fn($x)=>$x['name'], array_slice($g['genres'] ?? [], 0, 3)));
        $out[] = [
            'external_id'  => 'rawg_'.$g['id'],
            'title'        => feed_clean($g['name']),
            'summary'      => feed_excerpt("Released {$g['released']}. Genres: {$genres}. Platforms: {$plats}. Metacritic: ".($g['metacritic'] ?? 'N/A').".", 240),
            'content_html' => '',
            'feed_url'     => "https://rawg.io/games/{$g['slug']}",
            'image_url'    => $g['background_image'] ?? '',
            'tag'          => 'Releases',
            'lang'         => 'en',
            'detail_kind'  => 'rawg',
            'published_at' => !empty($g['released']) ? date('c', strtotime($g['released'])) : date('c'),
        ];
    }
    return $out;
}

/* ============================================================
 * QUOTA GUARD
 * ========================================================== */
function feed_quota_used(PDO $pdo, string $key): int {
    $s = $pdo->prepare("SELECT COUNT(*) FROM feed_fetch_log
                        WHERE source_key = ? AND fetched_at >= date_trunc('day', now())");
    $s->execute([$key]);
    return (int)$s->fetchColumn();
}

function feed_log(PDO $pdo, string $key, bool $ok, int $status, int $count, string $msg = ''): void {
    $s = $pdo->prepare("INSERT INTO feed_fetch_log (source_key, ok, http_status, items_count, message)
                        VALUES (?,?,?,?,?)");
    $s->execute([$key, $ok ? 'true' : 'false', $status, $count, mb_substr($msg, 0, 500)]);
}

/* ============================================================
 * UPSERT + PRUNE
 * ========================================================== */
function feed_upsert(PDO $pdo, string $type, array $src, array $items): int {
    global $FEED_MAX_PER_SOURCE;
    $sql = "INSERT INTO feed_cache
        (feed_type, source_key, source_name, external_id, title, summary, content_html,
         feed_url, image_url, tag, lang, detail_kind, published_at, fetched_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,now())
        ON CONFLICT (feed_type, external_id) DO UPDATE SET
            title        = EXCLUDED.title,
            summary      = EXCLUDED.summary,
            content_html = CASE WHEN EXCLUDED.content_html <> '' THEN EXCLUDED.content_html
                                ELSE feed_cache.content_html END,
            image_url    = CASE WHEN EXCLUDED.image_url <> '' THEN EXCLUDED.image_url
                                ELSE feed_cache.image_url END,
            tag          = EXCLUDED.tag,
            published_at = EXCLUDED.published_at,
            fetched_at   = now()";
    $st = $pdo->prepare($sql);
    $n = 0;
    foreach (array_slice($items, 0, $FEED_MAX_PER_SOURCE) as $i) {
        if (empty($i['title'])) continue;
        $st->execute([
            $type, $src['key'], $src['name'], $i['external_id'], $i['title'],
            $i['summary'] ?? '', $i['content_html'] ?? '', $i['feed_url'] ?? '',
            $i['image_url'] ?? '', $i['tag'] ?? '', $i['lang'] ?? 'en',
            $i['detail_kind'] ?? 'rss', $i['published_at'] ?? date('c'),
        ]);
        $n++;
    }
    return $n;
}

function feed_prune(PDO $pdo): void {
    global $FEED_RETENTION_DAYS;
    $pdo->exec("DELETE FROM feed_cache
                WHERE fetched_at < now() - interval '{$FEED_RETENTION_DAYS} days'
                  AND id NOT IN (SELECT feed_cache_id FROM saved_feeds WHERE feed_cache_id IS NOT NULL)");
}

/* ============================================================
 * REFRESH ORCHESTRATOR
 * ========================================================== */
function feed_refresh(PDO $pdo, string $type, bool $force = false): array {
    global $FEED_SOURCES, $FEED_TTL, $FEED_KEYS;
    if (!isset($FEED_SOURCES[$type])) return ['ok'=>false, 'error'=>'Unknown feed type'];

    // Throttle: skip if cache is still fresh
    if (!$force) {
        $s = $pdo->prepare("SELECT MAX(fetched_at) FROM feed_cache WHERE feed_type = ?");
        $s->execute([$type]);
        $last = $s->fetchColumn();
        $ttl  = ($FEED_TTL[$type] ?? 30) * 60;
        if ($last && (time() - strtotime($last)) < $ttl) {
            return ['ok'=>true, 'skipped'=>true, 'last'=>$last];
        }
    }

    $report = ['ok'=>true, 'sources'=>[], 'total'=>0];

    foreach ($FEED_SOURCES[$type] as $src) {
        if (($src['enabled'] ?? true) === false) continue;
        $kind = $src['kind'];

        // Skip quota APIs with no key configured
        $needsKey = in_array($kind, ['newsapi','gnews','newsdata','tmdb','rawg'], true);
        $keyMap   = ['newsapi'=>'newsapi','gnews'=>'gnews','newsdata'=>'newsdata','tmdb'=>'tmdb','rawg'=>'rawg'];
        $key      = $needsKey ? ($FEED_KEYS[$keyMap[$kind]] ?? '') : ($FEED_KEYS['thesportsdb'] ?? '123');
        if ($needsKey && $key === '') {
            $report['sources'][$src['key']] = 'skipped: no API key';
            continue;
        }

        // Daily quota guard
        if (!empty($src['daily_limit'])) {
            $used = feed_quota_used($pdo, $src['key']);
            if ($used >= $src['daily_limit']) {
                $report['sources'][$src['key']] = "skipped: daily quota ($used)";
                continue;
            }
        }

        try {
            switch ($kind) {
                case 'rss':      $items = feed_fetch_rss($src); break;
                case 'newsapi':  $items = feed_fetch_newsapi($src, $key); break;
                case 'gnews':    $items = feed_fetch_gnews($src, $key); break;
                case 'newsdata': $items = feed_fetch_newsdata($src, $key); break;
                case 'tmdb':     $items = feed_fetch_tmdb($src, $key); break;
                case 'sportsdb': $items = feed_fetch_sportsdb($src, $key); break;
                case 'steam':    $items = feed_fetch_steam($src); break;
                case 'rawg':     $items = feed_fetch_rawg($src, $key); break;
                default:         $items = [];
            }
            $n = feed_upsert($pdo, $type, $src, $items);
            feed_log($pdo, $src['key'], true, 200, $n);
            $report['sources'][$src['key']] = "$n items";
            $report['total'] += $n;
        } catch (Throwable $e) {
            feed_log($pdo, $src['key'], false, 0, 0, $e->getMessage());
            $report['sources'][$src['key']] = 'error: '.$e->getMessage();
        }
    }

    feed_prune($pdo);
    return $report;
}

/* ============================================================
 * READ API
 * ========================================================== */
function feed_get(PDO $pdo, string $type, int $limit = 24, string $lang = 'ms'): array {
    $s = $pdo->prepare("SELECT * FROM feed_cache
                        WHERE feed_type = ?
                        ORDER BY published_at DESC NULLS LAST, id DESC
                        LIMIT ?");
    $s->execute([$type, $limit]);
    $rows = $s->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['time_ago'] = feed_time_ago($r['published_at'], $lang);
        $r['summary']  = $r['summary'] ?: feed_excerpt($r['content_html'], 220);
    }
    return $rows;
}

/** Mixed feed for the Home dashboard */
function feed_get_mixed(PDO $pdo, int $perType = 3, string $lang = 'ms'): array {
    $all = [];
    foreach (['news','entertainment','sports','games'] as $t) {
        foreach (feed_get($pdo, $t, $perType, $lang) as $r) { $r['feed_type'] = $t; $all[] = $r; }
    }
    usort($all, fn($a,$b) => strtotime($b['published_at'] ?? 'now') <=> strtotime($a['published_at'] ?? 'now'));
    return $all;
}

function feed_find(PDO $pdo, int $id): ?array {
    $s = $pdo->prepare("SELECT * FROM feed_cache WHERE id = ?");
    $s->execute([$id]);
    return $s->fetch(PDO::FETCH_ASSOC) ?: null;
}

function feed_last_updated(PDO $pdo, string $type): ?string {
    $s = $pdo->prepare("SELECT MAX(fetched_at) FROM feed_cache WHERE feed_type = ?");
    $s->execute([$type]);
    return $s->fetchColumn() ?: null;
}

/* ============================================================
 * DETAIL ENRICHERS — called when a user opens a card
 * ========================================================== */
function feed_enrich(PDO $pdo, array $item): array {
    global $FEED_KEYS;
    $kind = $item['detail_kind'];

    // Already have full HTML? Nothing to do.
    if (!empty($item['content_html']) && mb_strlen($item['content_html']) > 400) return $item;

    try {
        if ($kind === 'tmdb') {
            [$p, $media, $id] = explode('_', $item['external_id']);
            $j = feed_http_json("https://api.themoviedb.org/3/{$media}/{$id}?" .
                 http_build_query(['api_key'=>$FEED_KEYS['tmdb'],'language'=>'en-US']));
            if ($j) {
                $genres = implode(', ', array_map(fn($g)=>$g['name'], $j['genres'] ?? []));
                $html  = '<p>'.htmlspecialchars($j['overview'] ?? '').'</p><ul>';
                $html .= '<li><strong>Genres:</strong> '.htmlspecialchars($genres).'</li>';
                $html .= '<li><strong>Rating:</strong> '.number_format($j['vote_average'] ?? 0,1).' / 10 ('.($j['vote_count'] ?? 0).' votes)</li>';
                if (!empty($j['runtime']))  $html .= '<li><strong>Runtime:</strong> '.$j['runtime'].' min</li>';
                if (!empty($j['number_of_seasons'])) $html .= '<li><strong>Seasons:</strong> '.$j['number_of_seasons'].'</li>';
                if (!empty($j['homepage'])) $html .= '<li><a href="'.htmlspecialchars($j['homepage']).'" target="_blank" rel="noopener">Official site</a></li>';
                $html .= '</ul>';
                $item['content_html'] = feed_sanitize_html($html);
                if (!empty($j['backdrop_path'])) $item['image_url'] = 'https://image.tmdb.org/t/p/w780'.$j['backdrop_path'];
            }
        }
        elseif ($kind === 'rawg') {
            $id = str_replace('rawg_', '', $item['external_id']);
            $j  = feed_http_json("https://api.rawg.io/api/games/{$id}?key=".$FEED_KEYS['rawg']);
            if ($j) {
                $item['content_html'] = feed_sanitize_html($j['description'] ?? '');
                if (!empty($j['background_image'])) $item['image_url'] = $j['background_image'];
            }
        }
        elseif ($kind === 'sportsdb') {
            $id = str_replace('sdb_', '', $item['external_id']);
            $j  = feed_http_json("https://www.thesportsdb.com/api/v1/json/{$FEED_KEYS['thesportsdb']}/lookupevent.php?id={$id}");
            $e  = $j['events'][0] ?? null;
            if ($e) {
                $html = '<ul>'
                      . '<li><strong>League:</strong> '.htmlspecialchars($e['strLeague'] ?? '').'</li>'
                      . '<li><strong>Venue:</strong> '.htmlspecialchars($e['strVenue'] ?? '').'</li>'
                      . '<li><strong>Score:</strong> '.htmlspecialchars(($e['intHomeScore'] ?? '-').' – '.($e['intAwayScore'] ?? '-')).'</li>'
                      . '<li><strong>Status:</strong> '.htmlspecialchars($e['strStatus'] ?? 'Scheduled').'</li></ul>';
                if (!empty($e['strDescriptionEN'])) $html .= '<p>'.htmlspecialchars($e['strDescriptionEN']).'</p>';
                $item['content_html'] = feed_sanitize_html($html);
                if (!empty($e['strThumb'])) $item['image_url'] = $e['strThumb'];
            }
        }
    } catch (Throwable $e) { /* keep whatever we already cached */ }

    // Persist enrichment so the next visit is instant
    if (!empty($item['content_html'])) {
        $s = $pdo->prepare("UPDATE feed_cache SET content_html = ?, image_url = ? WHERE id = ?");
        $s->execute([$item['content_html'], $item['image_url'], $item['id']]);
    }
    return $item;
}

/* ============================================================
 * BOOKMARKS
 * ========================================================== */
function feed_is_saved(PDO $pdo, string $userId, int $feedId): bool {
    $s = $pdo->prepare("SELECT 1 FROM saved_feeds WHERE user_id = ? AND feed_cache_id = ?");
    $s->execute([$userId, $feedId]);
    return (bool)$s->fetchColumn();
}

function feed_saved_ids(PDO $pdo, string $userId): array {
    $s = $pdo->prepare("SELECT feed_cache_id FROM saved_feeds WHERE user_id = ? AND feed_cache_id IS NOT NULL");
    $s->execute([$userId]);
    return array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));
}
