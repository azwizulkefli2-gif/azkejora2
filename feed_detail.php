<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: index.php"); exit; }

require_once 'inc_db.php';
require_once 'inc_feeds.php';

$currentUser = $_SESSION['user'];
$userId = $currentUser['userid'] ?? '';
$lang   = $_SESSION['lang'] ?? 'ms';
$id     = (int)($_GET['id'] ?? 0);

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) { die('DB connection failed'); }

$item = feed_find($pdo, $id);
if (!$item) { header("Location: home.php?page=news"); exit; }

$item    = feed_enrich($pdo, $item);
$isSaved = feed_is_saved($pdo, $userId, $id);
$back    = 'home.php?page='.$item['feed_type'];

$T = fn($ms,$en) => $lang === 'ms' ? $ms : $en;
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($item['title']) ?> · AZ Kejora</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/fontsource/fonts/inter@latest/latin-400-normal.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/fontsource/fonts/inter@latest/latin-600-normal.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/fontsource/fonts/inter@latest/latin-700-normal.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/fontsource/fonts/inter@latest/latin-800-normal.css">
<style>
:root{
  --bg:#05081a;--surface:rgba(255,255,255,.04);--surface-2:rgba(255,255,255,.06);--surface-3:rgba(255,255,255,.09);
  --border:rgba(255,255,255,.08);--border-2:rgba(255,255,255,.14);
  --text:#e6ecff;--dim:#c5cfee;--muted:#9aa6c7;--faint:#6b7699;
  --brand:#3b82f6;--brand-2:#60a5fa;--emerald:#10b981;--rose:#f43f5e;
  --grad:linear-gradient(135deg,#3b82f6,#8b5cf6 50%,#22d3ee);
  color-scheme:dark;
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);line-height:1.7;-webkit-font-smoothing:antialiased}
a{color:inherit;text-decoration:none}
button{font:inherit;cursor:pointer;border:none;background:none;color:inherit}
.orb{position:fixed;border-radius:50%;filter:blur(100px);opacity:.45;pointer-events:none;z-index:0}
.orb.a{top:-10%;left:-5%;width:480px;height:480px;background:radial-gradient(circle,#3b82f6,transparent 70%)}
.orb.b{bottom:-15%;right:-8%;width:520px;height:520px;background:radial-gradient(circle,#8b5cf6,transparent 70%)}
.wrap{max-width:860px;margin:0 auto;padding:28px 20px 80px;position:relative;z-index:1}
.bar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:24px;flex-wrap:wrap}
.back{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;background:var(--surface-2);border:1px solid var(--border-2);border-radius:12px;font-size:13px;font-weight:700;transition:.2s}
.back:hover{background:var(--surface-3)}
.meta-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px}
.tag{font-size:10px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;padding:5px 12px;border-radius:999px;background:rgba(59,130,246,.15);color:var(--brand-2);border:1px solid rgba(59,130,246,.3)}
.src{font-size:12px;font-weight:700;color:var(--muted)}
.time{font-size:12px;color:var(--faint)}
h1{font-size:clamp(24px,4vw,38px);font-weight:800;letter-spacing:-.02em;line-height:1.2}
.hero-img{width:100%;max-height:420px;object-fit:cover;border-radius:18px;border:1px solid var(--border);margin:22px 0}
.card{background:linear-gradient(180deg,rgba(15,23,64,.6),rgba(10,15,46,.75));border:1px solid var(--border);border-radius:20px;padding:26px;backdrop-filter:blur(10px)}
.summary{background:linear-gradient(135deg,rgba(59,130,246,.12),rgba(139,92,246,.12));border:1px solid rgba(59,130,246,.25);border-radius:16px;padding:18px 20px;margin-bottom:22px}
.summary b{display:block;font-size:11px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--brand-2);margin-bottom:8px}
.summary p{font-size:15px;color:var(--dim)}
.body{font-size:15.5px;color:var(--dim)}
.body p{margin-bottom:14px}
.body h3,.body h4{color:var(--text);margin:20px 0 10px;font-size:18px}
.body ul,.body ol{margin:0 0 14px 22px}
.body li{margin-bottom:6px}
.body img{max-width:100%;border-radius:12px;margin:14px 0}
.body a{color:var(--brand-2);text-decoration:underline}
.body blockquote{border-left:3px solid var(--brand);padding-left:14px;color:var(--muted);margin:14px 0}
.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:26px;padding-top:22px;border-top:1px solid var(--border)}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 20px;border-radius:12px;font-size:13px;font-weight:700;transition:.2s}
.btn.primary{background:var(--grad);color:#fff;box-shadow:0 8px 22px -8px rgba(59,130,246,.6)}
.btn.primary:hover{transform:translateY(-1px)}
.btn.ghost{background:var(--surface-2);border:1px solid var(--border-2)}
.btn.ghost:hover{background:var(--surface-3)}
.btn.saved{background:rgba(16,185,129,.15);border:1px solid rgba(16,185,129,.35);color:var(--emerald)}
.note{margin-top:18px;font-size:12px;color:var(--faint);text-align:center}
.toast{position:fixed;bottom:22px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,#10b981,#14b8a6);color:#fff;padding:12px 22px;border-radius:12px;font-size:13px;font-weight:700;z-index:99;box-shadow:0 18px 40px -12px rgba(16,185,129,.5)}
@media(max-width:600px){.wrap{padding:20px 14px 60px}.card{padding:20px}.actions .btn{flex:1}}
</style>
</head>
<body>
<div class="orb a"></div><div class="orb b"></div>

<div class="wrap">
  <div class="bar">
    <a class="back" href="<?= $back ?>">← <?= $T('Kembali ke suapan','Back to feed') ?></a>
    <a class="back" href="home.php">🏠 <?= $T('Papan Pemuka','Dashboard') ?></a>
  </div>

  <div class="meta-row">
    <span class="tag"><?= htmlspecialchars($item['tag'] ?: ucfirst($item['feed_type'])) ?></span>
    <span class="src"><?= htmlspecialchars($item['source_name']) ?></span>
    <span class="time">🕐 <?= feed_time_ago($item['published_at'], $lang) ?></span>
  </div>

  <h1><?= htmlspecialchars($item['title']) ?></h1>

  <?php if (!empty($item['image_url'])): ?>
    <img class="hero-img" src="<?= htmlspecialchars($item['image_url']) ?>" alt="" loading="lazy"
         onerror="this.style.display='none'">
  <?php endif; ?>

  <div class="card" style="margin-top:22px">
    <?php if (!empty($item['summary'])): ?>
    <div class="summary">
      <b><?= $T('Ringkasan','Summary') ?></b>
      <p><?= htmlspecialchars($item['summary']) ?></p>
    </div>
    <?php endif; ?>

    <div class="body">
      <?php if (!empty($item['content_html'])): ?>
        <?= $item['content_html'] /* already sanitised by feed_sanitize_html() */ ?>
      <?php else: ?>
        <p style="color:var(--muted)">
          <?= $T('Pratonton penuh tidak tersedia daripada sumber ini. Buka artikel asal untuk membaca sepenuhnya.',
                 'Full preview is not available from this source. Open the original article to read it in full.') ?>
        </p>
      <?php endif; ?>
    </div>

    <div class="actions">
      <?php if (!empty($item['feed_url'])): ?>
      <a class="btn primary" href="<?= htmlspecialchars($item['feed_url']) ?>" target="_blank" rel="noopener noreferrer">
        🔗 <?= $T('Baca sumber asal','Read original source') ?>
      </a>
      <?php endif; ?>
      <button class="btn <?= $isSaved ? 'saved' : 'ghost' ?>" id="saveBtn" onclick="toggleSave(<?= (int)$item['id'] ?>)">
        <?= $isSaved ? '✓ '.$T('Disimpan','Saved') : '🔖 '.$T('Simpan','Save') ?>
      </button>
      <button class="btn ghost" onclick="shareItem()">📤 <?= $T('Kongsi','Share') ?></button>
    </div>

    <p class="note">
      <?= $T('Kandungan hak milik','Content belongs to') ?> <?= htmlspecialchars($item['source_name']) ?>.
      <?= $T('Dipaparkan melalui suapan awam rasmi.','Displayed via their official public feed.') ?>
    </p>
  </div>
</div>

<script>
const L = {
  saved:   <?= json_encode($T('Disimpan','Saved')) ?>,
  save:    <?= json_encode($T('Simpan','Save')) ?>,
  err:     <?= json_encode($T('Gagal. Cuba lagi.','Failed. Try again.')) ?>,
  copied:  <?= json_encode($T('Pautan disalin!','Link copied!')) ?>
};
function toast(msg, ok=true){
  const t=document.createElement('div');
  t.className='toast'; t.textContent=msg;
  if(!ok) t.style.background='linear-gradient(135deg,#f43f5e,#ec4899)';
  document.body.appendChild(t); setTimeout(()=>t.remove(),2200);
}
async function toggleSave(id){
  const b=document.getElementById('saveBtn');
  b.disabled=true;
  try{
    const r=await fetch('feed_save.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id});
    const j=await r.json();
    if(j.ok){
      b.className='btn '+(j.saved?'saved':'ghost');
      b.textContent=(j.saved?'✓ ':'🔖 ')+(j.saved?L.saved:L.save);
      toast(j.saved?L.saved:L.save);
    } else toast(L.err,false);
  }catch(e){ toast(L.err,false); }
  b.disabled=false;
}
function shareItem(){
  const url=location.href;
  if(navigator.share){ navigator.share({title:document.title,url}).catch(()=>{}); }
  else { navigator.clipboard.writeText(url).then(()=>toast(L.copied)); }
}
</script>
</body>
</html>
