<?php
/**
 * AZ Kejora — Feed source configuration
 * ---------------------------------------------------------------
 * Keys are read from environment variables first (Render dashboard),
 * then fall back to the literal below. Never commit real keys to git.
 *
 * NOTE: variable is $FEED_KEYS (not $user/$pass) to avoid colliding
 * with the DB credentials supplied by inc_db.php.
 */

$FEED_KEYS = [
    // https://newsapi.org/register  — 100 req/day, dev only
    'newsapi'    => getenv('NEWSAPI_KEY')    ?: '',
    // https://gnews.io              — 100 req/day, 10 articles, 12h delay
    'gnews'      => getenv('GNEWS_KEY')      ?: '',
    // https://newsdata.io/register  — free credits/day
    'newsdata'   => getenv('NEWSDATA_KEY')   ?: '',
    // https://www.themoviedb.org/settings/api  — free v3 key
    'tmdb'       => getenv('TMDB_KEY')       ?: '',
    // https://rawg.io/apidocs       — free key
    'rawg'       => getenv('RAWG_KEY')       ?: '',
    // TheSportsDB free public key is "123"
    'thesportsdb'=> getenv('SPORTSDB_KEY')   ?: '123',
];

// Cache lifetime per category (minutes)
$FEED_TTL = [
    'news'          => 20,
    'entertainment' => 90,
    'sports'        => 10,
    'games'         => 60,
];

$FEED_HTTP_TIMEOUT = 12;   // seconds per request
$FEED_MAX_PER_SOURCE = 12; // items kept per source
$FEED_RETENTION_DAYS = 21; // prune older cached items

/**
 * SOURCE REGISTRY
 * kind: rss | newsapi | gnews | newsdata | tmdb | sportsdb | steam | rawg
 * Set 'enabled' => false to silence a source without deleting it.
 * Run rss.php?action=health to see which URLs actually respond.
 */
$FEED_SOURCES = [

'news' => [
    // ---- Malaysian ----
    ['key'=>'bernama_en', 'name'=>'Bernama',        'kind'=>'rss', 'lang'=>'en', 'tag'=>'Malaysia',
     'url'=>'https://www.bernama.com/en/rssfeed.php'],
    ['key'=>'bernama_ms', 'name'=>'Bernama BM',     'kind'=>'rss', 'lang'=>'ms', 'tag'=>'Malaysia',
     'url'=>'https://www.bernama.com/bm/rssfeed.php'],
    ['key'=>'star_nation','name'=>'The Star',       'kind'=>'rss', 'lang'=>'en', 'tag'=>'Malaysia',
     'url'=>'https://www.thestar.com.my/rss/News/Nation'],
    ['key'=>'star_tech',  'name'=>'The Star Tech',  'kind'=>'rss', 'lang'=>'en', 'tag'=>'Tech',
     'url'=>'https://www.thestar.com.my/rss/Tech/TechNews'],
    ['key'=>'malaymail',  'name'=>'Malay Mail',     'kind'=>'rss', 'lang'=>'en', 'tag'=>'Malaysia',
     'url'=>'https://www.malaymail.com/feed/rss/malaysia'],

    // ---- World ----
    ['key'=>'bbc_world',  'name'=>'BBC World',      'kind'=>'rss', 'lang'=>'en', 'tag'=>'World',
     'url'=>'https://feeds.bbci.co.uk/news/world/rss.xml'],
    ['key'=>'bbc_tech',   'name'=>'BBC Technology', 'kind'=>'rss', 'lang'=>'en', 'tag'=>'Tech',
     'url'=>'https://feeds.bbci.co.uk/news/technology/rss.xml'],
    ['key'=>'bbc_biz',    'name'=>'BBC Business',   'kind'=>'rss', 'lang'=>'en', 'tag'=>'Business',
     'url'=>'https://feeds.bbci.co.uk/news/business/rss.xml'],

    // ---- Reuters workaround (official feeds died June 2020) ----
    ['key'=>'reuters_gn', 'name'=>'Reuters (via Google News)', 'kind'=>'rss', 'lang'=>'en', 'tag'=>'World',
     'url'=>'https://news.google.com/rss/search?q=site:reuters.com+when:2d&hl=en-US&gl=US&ceid=US:en'],

    // ---- Optional paid/quota APIs (auto-skipped when key empty) ----
    ['key'=>'newsapi_my', 'name'=>'NewsAPI · Malaysia', 'kind'=>'newsapi', 'lang'=>'en', 'tag'=>'Malaysia',
     'params'=>['country'=>'my','pageSize'=>20], 'daily_limit'=>90],
    ['key'=>'gnews_tech', 'name'=>'GNews · Technology', 'kind'=>'gnews',   'lang'=>'en', 'tag'=>'Tech',
     'params'=>['category'=>'technology','lang'=>'en','max'=>10], 'daily_limit'=>90],
    ['key'=>'newsdata_my','name'=>'NewsData · Malaysia','kind'=>'newsdata','lang'=>'en', 'tag'=>'Malaysia',
     'params'=>['country'=>'my','language'=>'en'], 'daily_limit'=>90],
],

'entertainment' => [
    ['key'=>'tmdb_movie', 'name'=>'TMDB Trending Movies', 'kind'=>'tmdb', 'lang'=>'en', 'tag'=>'Movies',
     'params'=>['media'=>'movie','window'=>'week']],
    ['key'=>'tmdb_tv',    'name'=>'TMDB Trending TV',     'kind'=>'tmdb', 'lang'=>'en', 'tag'=>'TV',
     'params'=>['media'=>'tv','window'=>'week']],
    ['key'=>'bbc_arts',   'name'=>'BBC Entertainment',    'kind'=>'rss',  'lang'=>'en', 'tag'=>'Culture',
     'url'=>'https://feeds.bbci.co.uk/news/entertainment_and_arts/rss.xml'],
    ['key'=>'star_life',  'name'=>'The Star Lifestyle',   'kind'=>'rss',  'lang'=>'en', 'tag'=>'Lifestyle',
     'url'=>'https://www.thestar.com.my/rss/Lifestyle'],
],

'sports' => [
    ['key'=>'sdb_epl',    'name'=>'Premier League',   'kind'=>'sportsdb', 'lang'=>'en', 'tag'=>'Football',
     'params'=>['league'=>4328]],
    ['key'=>'sdb_laliga', 'name'=>'La Liga',          'kind'=>'sportsdb', 'lang'=>'en', 'tag'=>'Football',
     'params'=>['league'=>4335]],
    ['key'=>'sdb_nba',    'name'=>'NBA',              'kind'=>'sportsdb', 'lang'=>'en', 'tag'=>'Basketball',
     'params'=>['league'=>4387]],
    ['key'=>'sdb_f1',     'name'=>'Formula 1',        'kind'=>'sportsdb', 'lang'=>'en', 'tag'=>'Motorsport',
     'params'=>['league'=>4370]],
    ['key'=>'bbc_sport',  'name'=>'BBC Sport',        'kind'=>'rss',      'lang'=>'en', 'tag'=>'Sport',
     'url'=>'https://feeds.bbci.co.uk/sport/rss.xml'],
    ['key'=>'star_sport', 'name'=>'The Star Sport',   'kind'=>'rss',      'lang'=>'en', 'tag'=>'Sport',
     'url'=>'https://www.thestar.com.my/rss/Sport'],
],

'games' => [
    // Steam news — no API key required
    ['key'=>'steam_570',    'name'=>'Dota 2',            'kind'=>'steam', 'lang'=>'en', 'tag'=>'Steam',
     'params'=>['appid'=>570]],
    ['key'=>'steam_730',    'name'=>'Counter-Strike 2',  'kind'=>'steam', 'lang'=>'en', 'tag'=>'Steam',
     'params'=>['appid'=>730]],
    ['key'=>'steam_578080', 'name'=>'PUBG',              'kind'=>'steam', 'lang'=>'en', 'tag'=>'Steam',
     'params'=>['appid'=>578080]],
    ['key'=>'steam_1245620','name'=>'Elden Ring',        'kind'=>'steam', 'lang'=>'en', 'tag'=>'Steam',
     'params'=>['appid'=>1245620]],
    ['key'=>'rawg_new',     'name'=>'RAWG New Releases', 'kind'=>'rawg',  'lang'=>'en', 'tag'=>'Releases',
     'params'=>['ordering'=>'-released','page_size'=>12]],
    ['key'=>'pcgamer',      'name'=>'PC Gamer',          'kind'=>'rss',   'lang'=>'en', 'tag'=>'Gaming',
     'url'=>'https://www.pcgamer.com/rss/'],
    ['key'=>'star_games',   'name'=>'The Star Games',    'kind'=>'rss',   'lang'=>'en', 'tag'=>'Gaming',
     'url'=>'https://www.thestar.com.my/rss/Tech/Games'],
],
];
