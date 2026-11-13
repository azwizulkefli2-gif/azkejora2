<?php
session_start();

// Protect page: redirect to login if not signed in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// Handle logout request
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

require_once 'inc_db.php';

// ✅ FIX: Renamed to $currentUser to prevent overwriting the DB $user variable from inc_db.php
$currentUser = $_SESSION['user'];
$userId = $currentUser['userid'] ?? $currentUser['id'] ?? '';
$userName = $currentUser['name'] ?? 'User';
$userEmail = $currentUser['userid'] ?? '';
$userPhone = $currentUser['phone'] ?? '';
$userAddress = $currentUser['address'] ?? '';
$userPhoto = $currentUser['photo'] ?? '';
$userSource = $currentUser['source'] ?? 'email';

$message = '';
$messageType = '';

// ---------- LANGUAGE ----------
$lang = $_SESSION['lang'] ?? 'ms';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ms','en'])) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
}

// ---------- DB HELPERS ----------
try {
    // ✅ Now $user correctly refers to the database username string from inc_db.php
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    $pdo = null;
}

// Ensure required tables exist
if ($pdo) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS budget_goals (
                id SERIAL PRIMARY KEY,
                user_id VARCHAR(255) NOT NULL,
                monthly_income NUMERIC(12,2) DEFAULT 0,
                savings_goal NUMERIC(12,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS transactions (
                id SERIAL PRIMARY KEY,
                user_id VARCHAR(255) NOT NULL,
                type VARCHAR(10) NOT NULL,
                category VARCHAR(50) NOT NULL,
                amount NUMERIC(12,2) NOT NULL,
                description VARCHAR(255),
                tx_date DATE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS saved_feeds (
                id SERIAL PRIMARY KEY,
                user_id VARCHAR(255) NOT NULL,
                feed_type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                url VARCHAR(500),
                saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");
    } catch (PDOException $e) { /* tables exist */ }

    // Handle Transaction Add
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'add_tx' && isset($_POST['tx_type'], $_POST['tx_category'], $_POST['tx_amount'], $_POST['tx_date'])) {
            try {
                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, category, amount, description, tx_date) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $userId,
                    $_POST['tx_type'],
                    $_POST['tx_category'],
                    (float)$_POST['tx_amount'],
                    $_POST['tx_description'] ?? '',
                    $_POST['tx_date']
                ]);
                header("Location: home.php?page=budget&msg=" . urlencode($lang === 'ms' ? 'Transaksi ditambah' : 'Transaction added'));
                exit;
            } catch (PDOException $e) {
                $message = $lang === 'ms' ? 'Gagal menambah transaksi' : 'Failed to add transaction';
                $messageType = 'error';
            }
        }
        elseif ($_POST['action'] === 'delete_tx' && isset($_POST['tx_id'])) {
            try {
                $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
                $stmt->execute([$_POST['tx_id'], $userId]);
                header("Location: home.php?page=budget&msg=" . urlencode($lang === 'ms' ? 'Transaksi dipadam' : 'Transaction deleted'));
                exit;
            } catch (PDOException $e) {
                $message = 'Delete failed'; $messageType = 'error';
            }
        }
        elseif ($_POST['action'] === 'update_profile') {
            try {
                $stmt = $pdo->prepare('UPDATE "User" SET name = ?, phone = ?, address = ?, photo = ? WHERE userid = ?');
                $stmt->execute([$_POST['name'], $_POST['phone'], $_POST['address'], $_POST['photo'], $userId]);
                $_SESSION['user']['name'] = $_POST['name'];
                $_SESSION['user']['phone'] = $_POST['phone'];
                $_SESSION['user']['address'] = $_POST['address'];
                $_SESSION['user']['photo'] = $_POST['photo'];
                header("Location: home.php?page=profile&msg=" . urlencode($lang === 'ms' ? 'Profil dikemas kini' : 'Profile updated'));
                exit;
            } catch (PDOException $e) {
                $message = 'Update failed'; $messageType = 'error';
            }
        }
        elseif ($_POST['action'] === 'change_password' && $userSource !== 'google') {
            if ($_POST['new_password'] === $_POST['confirm_password'] && strlen($_POST['new_password']) >= 6) {
                try {
                    $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE "User" SET password_hash = ? WHERE userid = ?');
                    $stmt->execute([$hash, $userId]);
                    header("Location: home.php?page=profile&msg=" . urlencode($lang === 'ms' ? 'Kata laluan ditukar' : 'Password changed'));
                    exit;
                } catch (PDOException $e) {
                    $message = 'Password change failed'; $messageType = 'error';
                }
            } else {
                $message = $lang === 'ms' ? 'Kata laluan tidak sepadan atau terlalu pendek' : 'Passwords do not match or too short';
                $messageType = 'error';
            }
        }
        elseif ($_POST['action'] === 'update_goal') {
            try {
                $exists = $pdo->prepare("SELECT id FROM budget_goals WHERE user_id = ?");
                $exists->execute([$userId]);
                if ($exists->fetch()) {
                    $stmt = $pdo->prepare("UPDATE budget_goals SET monthly_income = ?, savings_goal = ? WHERE user_id = ?");
                    $stmt->execute([$_POST['income'], $_POST['goal'], $userId]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO budget_goals (user_id, monthly_income, savings_goal) VALUES (?, ?, ?)");
                    $stmt->execute([$userId, $_POST['income'], $_POST['goal']]);
                }
                header("Location: home.php?page=budget&msg=" . urlencode($lang === 'ms' ? 'Matlamat dikemas kini' : 'Goals updated'));
                exit;
            } catch (PDOException $e) {
                $message = 'Goal update failed'; $messageType = 'error';
            }
        }
    }
}

// ---------- DATA FETCH ----------
$transactions = [];
$goalData = ['monthly_income' => 0, 'savings_goal' => 0];
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY tx_date DESC, created_at DESC LIMIT 200");
        $stmt->execute([$userId]);
        $transactions = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT * FROM budget_goals WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$userId]);
        $goalData = $stmt->fetch() ?: $goalData;
    } catch (PDOException $e) {}
}

// ---------- BUDGET CALCULATIONS ----------
$monthlyIncome = (float)($goalData['monthly_income'] ?? 0);
$savingsGoal = (float)($goalData['savings_goal'] ?? 0);

$currentMonth = date('Y-m');
$monthIncome = 0; $monthSpent = 0;
$categoryTotals = [];
$todaySpent = 0;
$daysInMonth = (int)date('t');
$daysPassed = (int)date('j');

foreach ($transactions as $tx) {
    if (substr($tx['tx_date'], 0, 7) === $currentMonth) {
        if ($tx['type'] === 'income') {
            $monthIncome += (float)$tx['amount'];
        } else {
            $monthSpent += (float)$tx['amount'];
            $cat = $tx['category'];
            $categoryTotals[$cat] = ($categoryTotals[$cat] ?? 0) + (float)$tx['amount'];
            if ($tx['tx_date'] === date('Y-m-d')) {
                $todaySpent += (float)$tx['amount'];
            }
        }
    }
}

$totalBudget = $monthlyIncome > 0 ? $monthlyIncome : $monthIncome;
$remaining = $totalBudget - $monthSpent;
$dailyAvg = $daysPassed > 0 ? $monthSpent / $daysPassed : 0;
$daysLeft = $daysInMonth - $daysPassed;
$projectedSpend = $monthSpent + ($dailyAvg * $daysLeft);
$projectedBalance = $totalBudget - $projectedSpend;
$savingsProgress = $savingsGoal > 0 ? min(100, ($monthIncome - $monthSpent) / $savingsGoal * 100) : 0;

// AI Insight
if ($daysLeft > 0 && $dailyAvg > 0) {
    if ($projectedBalance < 0) {
        $insightType = 'danger';
        $insightText = $lang === 'ms'
            ? "⚠ Amaran: Pada kadar semasa, anda akan defisit RM" . number_format(abs($projectedBalance), 2) . " menjelang hujung bulan. Kurangkan perbelanjaan!"
            : "⚠ Warning: At current pace, you'll be over budget by RM" . number_format(abs($projectedBalance), 2) . " by month-end. Cut back on spending!";
    } elseif ($projectedBalance < $totalBudget * 0.2) {
        $insightType = 'warn';
        $insightText = $lang === 'ms'
            ? "💡 Petua: Anda dijangka ada RM" . number_format($projectedBalance, 2) . " berbaki. Cuba kurangkan kategori tertinggi untuk capai matlamat simpanan."
            : "💡 Tip: You're projected to have RM" . number_format($projectedBalance, 2) . " left. Try reducing your top category to hit savings goals.";
    } else {
        $insightType = 'good';
        $insightText = $lang === 'ms'
            ? "🎉 Hebat! Anda dijangka ada RM" . number_format($projectedBalance, 2) . " berbaki. Teruskan tabiat baik ini!"
            : "🎉 Great! You're projected to have RM" . number_format($projectedBalance, 2) . " left. Keep up the good habits!";
    }
} else {
    $insightType = 'info';
    $insightText = $lang === 'ms' ? "Mulakan dengan menambah pendapatan dan perbelanjaan anda." : "Start by logging your income and expenses.";
}

// Top spending category
$topCategory = ''; $topAmount = 0;
foreach ($categoryTotals as $c => $a) { if ($a > $topAmount) { $topCategory = $c; $topAmount = $a; } }

// ---------- VIEW ----------
$page = $_GET['page'] ?? 'home';
$validPages = ['home','profile','budget','news','entertainment','sports','games','subscription','download'];
if (!in_array($page, $validPages)) $page = 'home';

// Feed mock data
$feeds = [
    'news' => [
        ['title' => 'AI Revolution Reshapes Asian Tech Startups', 'tldr_en' => 'Generative AI adoption surges across SEA startups, with Malaysia seeing 40% YoY growth in AI-first products.', 'tldr_ms' => 'Penggunaan AI generatif melonjak dalam startup SEA, dengan Malaysia mencatat pertumbuhan 40% YoY.', 'time' => '2h ago', 'tag' => 'Tech'],
        ['title' => 'Digital Ringgit Pilot Launches Q2 2026', 'tldr_en' => 'Bank Negara begins CBDC trials with major Malaysian banks. Could change how you transact daily.', 'tldr_ms' => 'Bank Negara mulakan ujian CBDC dengan bank-bank utama. Boleh ubah cara anda berurus niaga.', 'time' => '5h ago', 'tag' => 'Finance'],
        ['title' => 'Youth Climate Summit in KL Draws 10k', 'tldr_en' => 'Young activists push for faster renewable energy transition across ASEAN.', 'tldr_ms' => 'Aktivis muda desak peralihan tenaga boleh diperbaharui lebih pantas di ASEAN.', 'time' => '8h ago', 'tag' => 'World'],
        ['title' => 'Malaysia\'s Digital Nomad Visa Gets Upgrade', 'tldr_en' => 'New perks include tax breaks and co-working partnerships for remote workers.', 'tldr_ms' => 'Faedah baharu termasuk potongan cukai untuk pekerja jarak jauh.', 'time' => '1d ago', 'tag' => 'Lifestyle'],
    ],
    'entertainment' => [
        ['title' => 'Top 10 Netflix Picks This Week', 'tldr_en' => 'A mix of K-drama thrillers and local Malaysian productions trending globally.', 'tldr_ms' => 'Gabungan thriller K-drama dan produksi Malaysia trending global.', 'time' => '1h ago', 'tag' => 'Streaming'],
        ['title' => 'New Taylor Swift Album Breaks Records', 'tldr_en' => 'First-day streams surpass 300M worldwide. Fans call it her most personal work yet.', 'tldr_ms' => 'Strim hari pertama lepasi 300M seluruh dunia.', 'time' => '3h ago', 'tag' => 'Music'],
        ['title' => 'Marvel Phase 7 Roadmap Revealed', 'tldr_en' => 'New heroes and multiverse twists planned through 2029.', 'tldr_ms' => 'Wira baharu dan plot multiverse dirancang hingga 2029.', 'time' => '6h ago', 'tag' => 'Movies'],
    ],
    'sports' => [
        ['title' => 'Harimau Malaya Win AFF Cup Qualifier 3-1', 'tldr_en' => 'Dominant second-half performance secures spot in final tournament.', 'tldr_ms' => 'Persembahan separuh masa kedua yang dominan mengesahkan tempat dalam kejohanan akhir.', 'time' => '4h ago', 'tag' => 'Football'],
        ['title' => 'Lee Zii Jia Advances to World Tour Finals', 'tldr_en' => 'Malaysian shuttler beats top seed in straight games.', 'tldr_ms' => 'Pemain badminton Malaysia tewaskan pilihan utama.', 'time' => '7h ago', 'tag' => 'Badminton'],
        ['title' => 'F1 2026: New Hybrid Engine Rules Confirmed', 'tldr_en' => 'Sustainable fuel and increased electric power set to reshape the grid.', 'tldr_ms' => 'Bahan api lestari dan kuasa elektrik meningkat akan ubah grid F1.', 'time' => '1d ago', 'tag' => 'Motorsport'],
    ],
    'games' => [
        ['title' => 'GTA VI Official Release Date Confirmed', 'tldr_en' => 'Rockstar announces fall 2026 launch with Vice City reboot. Pre-orders break records.', 'tldr_ms' => 'Rockstar umum pelancaran musim luruh 2026 dengan reboot Vice City.', 'time' => '2h ago', 'tag' => 'Gaming'],
        ['title' => 'Mobile Legends M6 World Championship', 'tldr_en' => 'Malaysian team makes top 4 in Jakarta finals.', 'tldr_ms' => 'Pasukan Malaysia masuk 4 teratas dalam final Jakarta.', 'time' => '5h ago', 'tag' => 'Esports'],
        ['title' => 'Steam Winter Sale: Top 50 Deals', 'tldr_en' => 'Best indie and AAA discounts of the year curated for students.', 'tldr_ms' => 'Diskaun indie dan AAA terbaik tahun ini untuk pelajar.', 'time' => '12h ago', 'tag' => 'Deals'],
    ],
];

$greeting = function() use ($lang) {
    $h = (int)date('G');
    if ($h < 12) return $lang === 'ms' ? 'Selamat Pagi' : 'Good Morning';
    if ($h < 18) return $lang === 'ms' ? 'Selamat Petang' : 'Good Afternoon';
    return $lang === 'ms' ? 'Selamat Malam' : 'Good Evening';
};

$categoryLabels = [
    'food' => $lang === 'ms' ? 'Makanan & Snek' : 'Food & Snacks',
    'gaming' => $lang === 'ms' ? 'Permainan & App' : 'Gaming & Apps',
    'shopping' => $lang === 'ms' ? 'Membeli-belah' : 'Shopping',
    'transport' => $lang === 'ms' ? 'Pengangkutan' : 'Transport',
    'entertainment' => $lang === 'ms' ? 'Hiburan' : 'Entertainment',
    'savings' => $lang === 'ms' ? 'Simpanan' : 'Savings',
    'other' => $lang === 'ms' ? 'Lain-lain' : 'Other',
    'salary' => $lang === 'ms' ? 'Gaji' : 'Salary',
    'allowance' => $lang === 'ms' ? 'Elaun' : 'Allowance',
    'freelance' => 'Freelance',
    'gift' => $lang === 'ms' ? 'Hadiah' : 'Gift',
];

$categoryColors = [
    'food' => '#f59e0b', 'gaming' => '#8b5cf6', 'shopping' => '#ec4899',
    'transport' => '#22d3ee', 'entertainment' => '#f43f5e', 'savings' => '#10b981',
    'other' => '#64748b', 'salary' => '#10b981', 'allowance' => '#3b82f6',
    'freelance' => '#22d3ee', 'gift' => '#f59e0b'
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AZ Kejora | Dashboard</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/fontsource/fonts/inter@latest/latin-400-normal.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/fontsource/fonts/inter@latest/latin-600-normal.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/fontsource/fonts/inter@latest/latin-700-normal.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/fontsource/fonts/inter@latest/latin-800-normal.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
:root{
  --bg:#05081a;--bg-2:#0a0f2e;--bg-3:#0f1740;--bg-4:#141e4a;
  --surface:rgba(255,255,255,0.04);--surface-2:rgba(255,255,255,0.06);--surface-3:rgba(255,255,255,0.08);--surface-4:rgba(255,255,255,0.12);
  --border:rgba(255,255,255,0.08);--border-2:rgba(255,255,255,0.14);--border-3:rgba(255,255,255,0.2);
  --text:#e6ecff;--text-dim:#c5cfee;--muted:#9aa6c7;--faint:#6b7699;
  --brand:#3b82f6;--brand-2:#60a5fa;--cyan:#22d3ee;--violet:#8b5cf6;--emerald:#10b981;--rose:#f43f5e;--amber:#f59e0b;
  --grad:linear-gradient(135deg,#3b82f6 0%,#8b5cf6 50%,#22d3ee 100%);
  --sidebar-w:260px;--sidebar-collapsed:72px;
}
*{margin:0;padding:0;box-sizing:border-box}
html,body{height:100%}
body{
  font-family:'Inter',system-ui,-apple-system,sans-serif;
  background:var(--bg);color:var(--text);
  -webkit-font-smoothing:antialiased;
  overflow-x:hidden;line-height:1.6;
}
a{color:inherit;text-decoration:none}
button{font:inherit;cursor:pointer;border:none;background:none;color:inherit}
input,select,textarea{font-family:inherit}
img{max-width:100%;display:block}

/* Background orbs */
.bg-orb{position:fixed;border-radius:50%;filter:blur(100px);pointer-events:none;z-index:0;opacity:0.5}
.bg-orb.o1{top:-10%;left:-5%;width:500px;height:500px;background:radial-gradient(circle,#3b82f6,transparent 70%)}
.bg-orb.o2{top:40%;right:-10%;width:600px;height:600px;background:radial-gradient(circle,#8b5cf6,transparent 70%)}
.bg-orb.o3{bottom:-20%;left:30%;width:500px;height:500px;background:radial-gradient(circle,#22d3ee,transparent 70%)}

/* Layout */
.app{display:flex;min-height:100vh;position:relative;z-index:1}

/* Sidebar */
.sidebar{
  position:fixed;top:0;left:0;bottom:0;
  width:var(--sidebar-w);
  background:linear-gradient(180deg,rgba(10,15,46,0.85),rgba(5,8,26,0.95));
  backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);
  border-right:1px solid var(--border);
  display:flex;flex-direction:column;
  padding:20px 14px;
  z-index:30;transition:width .3s ease,transform .3s ease;
  overflow-y:auto;overflow-x:hidden;
}
.sidebar.collapsed{width:var(--sidebar-collapsed)}
.sidebar.collapsed .hide-collapsed{display:none !important}
.sidebar.collapsed .brand-text{display:none}
.sidebar.collapsed .nav-label{display:none}
.sidebar.collapsed .nav-item{justify-content:center;padding:10px}
.sidebar.collapsed .sidebar-user{justify-content:center}
.sidebar.collapsed .sidebar-user .avatar{margin:0}
.sidebar.collapsed .sidebar-footer{align-items:center}

.brand{display:flex;align-items:center;gap:10px;padding:6px 6px 16px;white-space:nowrap}
.brand-logo{
  width:36px;height:36px;border-radius:10px;flex-shrink:0;
  background:var(--grad);display:grid;place-items:center;
  box-shadow:0 8px 20px -4px rgba(59,130,246,0.6);
}
.brand-logo svg{width:20px;height:20px}
.brand-text{font-weight:800;font-size:16px;letter-spacing:-0.02em}
.brand-text em{font-style:normal;background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}

.sidebar-user{
  display:flex;align-items:center;gap:10px;
  padding:12px;background:var(--surface-2);border:1px solid var(--border);
  border-radius:14px;margin-bottom:16px;
}
.avatar{
  width:40px;height:40px;border-radius:50%;flex-shrink:0;
  background:var(--grad);display:grid;place-items:center;
  font-weight:800;color:#fff;font-size:15px;overflow:hidden;
  border:2px solid var(--border-2);
}
.avatar img{width:100%;height:100%;object-fit:cover}
.sidebar-user .user-info{min-width:0;flex:1}
.sidebar-user b{display:block;font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sidebar-user small{font-size:11px;color:var(--muted);display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

.quick-log{
  display:flex;align-items:center;justify-content:center;gap:8px;
  width:100%;padding:11px;background:var(--grad);color:#fff;
  border-radius:12px;font-weight:700;font-size:13px;
  box-shadow:0 10px 24px -8px rgba(59,130,246,0.5);
  transition:transform .2s;margin-bottom:20px;
}
.quick-log:hover{transform:translateY(-1px)}
.sidebar.collapsed .quick-log{padding:11px 0;font-size:0}
.sidebar.collapsed .quick-log::before{content:'+';font-size:20px}

.nav-label{font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--faint);padding:12px 10px 6px}

.nav-group{margin-bottom:4px}
.nav-item{
  display:flex;align-items:center;gap:11px;
  padding:10px 12px;border-radius:10px;
  color:var(--muted);font-size:14px;font-weight:600;
  transition:all .15s;cursor:pointer;white-space:nowrap;
}
.nav-item:hover{background:var(--surface-2);color:var(--text)}
.nav-item.active{background:linear-gradient(135deg,rgba(59,130,246,0.2),rgba(139,92,246,0.15));color:var(--brand-2)}
.nav-item.active::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:18px;background:var(--grad);border-radius:2px}
.nav-item{position:relative}
.nav-item .nav-icon{width:18px;height:18px;flex-shrink:0;display:grid;place-items:center}
.nav-item .nav-icon svg{width:18px;height:18px}
.nav-group-items{margin-left:14px;padding-left:14px;border-left:1px solid var(--border);margin-top:4px}
.nav-group-items .nav-item{font-size:13px;padding:8px 12px}
.nav-group-toggle{display:flex;align-items:center;justify-content:space-between;width:100%}
.nav-group-toggle .arrow{transition:transform .2s;color:var(--faint);font-size:10px}
.nav-group.open .arrow{transform:rotate(180deg)}

.sidebar-footer{
  margin-top:auto;padding-top:16px;border-top:1px solid var(--border);
  display:flex;flex-direction:column;gap:8px;
}
.lang-switch-side{
  display:flex;gap:4px;background:var(--surface-2);border:1px solid var(--border);
  border-radius:10px;padding:4px;
}
.lang-switch-side button{
  flex:1;padding:6px;border-radius:7px;font-size:11px;font-weight:700;
  color:var(--muted);transition:.2s;
}
.lang-switch-side button.active{background:var(--grad);color:#fff}
.signout{
  display:flex;align-items:center;justify-content:center;gap:8px;
  padding:10px;background:var(--surface-2);border:1px solid var(--border);
  border-radius:10px;font-size:13px;font-weight:600;color:var(--rose);
  transition:.2s;
}
.signout:hover{background:rgba(244,63,94,0.1);border-color:rgba(244,63,94,0.3)}

/* Main content */
.main{
  flex:1;margin-left:var(--sidebar-w);
  padding:28px 32px 60px;
  transition:margin-left .3s ease;
  min-height:100vh;
}
.sidebar.collapsed ~ .main{margin-left:var(--sidebar-collapsed)}

/* Top bar */
.topbar{
  display:flex;align-items:center;gap:12px;justify-content:space-between;
  margin-bottom:24px;flex-wrap:wrap;
}
.topbar-left{display:flex;align-items:center;gap:12px;min-width:0;flex:1}
.toggle-btn{
  width:38px;height:38px;border-radius:10px;
  background:var(--surface-2);border:1px solid var(--border);
  display:grid;place-items:center;transition:.2s;flex-shrink:0;
}
.toggle-btn:hover{background:var(--surface-3)}
.page-title{font-size:22px;font-weight:800;letter-spacing:-0.02em}
.page-title small{display:block;font-size:12px;font-weight:600;color:var(--muted);letter-spacing:.04em;text-transform:uppercase;margin-top:2px}

/* Cards */
.card{
  background:linear-gradient(180deg,rgba(15,23,64,0.6),rgba(10,15,46,0.7));
  border:1px solid var(--border);border-radius:18px;
  padding:22px;backdrop-filter:blur(10px);
  transition:all .2s;
}
.card:hover{border-color:var(--border-2)}
.card-title{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px}
.card-title h3{font-size:16px;font-weight:700;letter-spacing:-0.01em}
.card-title .badge{font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:4px 10px;border-radius:999px;background:rgba(59,130,246,0.15);color:var(--brand-2);border:1px solid rgba(59,130,246,0.3)}

/* Grids */
.grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px}
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px}
.grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:20px}
.grid-2-1{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:20px}

/* Stat cards */
.stat{
  position:relative;overflow:hidden;
  background:linear-gradient(180deg,rgba(15,23,64,0.7),rgba(10,15,46,0.8));
  border:1px solid var(--border);border-radius:18px;
  padding:20px;
}
.stat::before{
  content:'';position:absolute;top:0;right:0;width:120px;height:120px;
  background:radial-gradient(circle,var(--grad-color,rgba(59,130,246,0.3)),transparent 70%);
  opacity:0.4;transform:translate(30%,-30%);pointer-events:none;
}
.stat .icon{width:40px;height:40px;border-radius:11px;background:var(--surface-3);display:grid;place-items:center;margin-bottom:12px}
.stat .icon svg{width:20px;height:20px;color:var(--stat-color,var(--brand-2))}
.stat b{display:block;font-size:26px;font-weight:800;letter-spacing:-0.02em}
.stat p{margin-top:4px;font-size:12px;color:var(--muted);font-weight:600;letter-spacing:.04em;text-transform:uppercase}
.stat .delta{display:inline-block;margin-top:8px;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px}
.delta.up{background:rgba(16,185,129,0.15);color:var(--emerald)}
.delta.down{background:rgba(244,63,94,0.15);color:var(--rose)}

/* AI Briefing */
.briefing{
  background:linear-gradient(135deg,rgba(59,130,246,0.15),rgba(139,92,246,0.15),rgba(34,211,238,0.1));
  border:1px solid rgba(59,130,246,0.25);
  border-radius:20px;padding:24px;
  position:relative;overflow:hidden;margin-bottom:20px;
}
.briefing::before{content:'';position:absolute;top:-50%;right:-20%;width:400px;height:400px;background:radial-gradient(circle,rgba(139,92,246,0.3),transparent 70%);pointer-events:none}
.briefing > *{position:relative;z-index:1}
.briefing-head{display:flex;align-items:center;gap:10px;margin-bottom:14px}
.ai-chip{
  display:inline-flex;align-items:center;gap:6px;
  background:var(--grad);color:#fff;border-radius:999px;
  padding:5px 12px;font-size:11px;font-weight:800;letter-spacing:.08em;
  box-shadow:0 8px 20px -6px rgba(59,130,246,0.5);
}
.briefing h2{font-size:18px;font-weight:800;letter-spacing:-0.01em}
.briefing ul{list-style:none;display:grid;gap:10px;margin-top:14px}
.briefing li{display:flex;gap:10px;font-size:14px;line-height:1.6;color:var(--text-dim)}
.briefing li::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--brand-2);margin-top:8px;flex-shrink:0}

/* Feed cards */
.feed-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
.feed-card{
  background:linear-gradient(180deg,rgba(15,23,64,0.6),rgba(10,15,46,0.7));
  border:1px solid var(--border);border-radius:16px;padding:20px;
  transition:all .25s;position:relative;overflow:hidden;
  display:flex;flex-direction:column;gap:10px;
}
.feed-card:hover{transform:translateY(-3px);border-color:var(--border-2);box-shadow:0 20px 40px -20px rgba(59,130,246,0.3)}
.feed-card .tag{display:inline-block;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:3px 9px;border-radius:999px;background:rgba(59,130,246,0.15);color:var(--brand-2);border:1px solid rgba(59,130,246,0.3);align-self:flex-start}
.feed-card h4{font-size:15px;font-weight:700;line-height:1.4;letter-spacing:-0.01em}
.feed-card .tldr{font-size:13px;color:var(--muted);line-height:1.6;flex:1}
.feed-card .meta{display:flex;align-items:center;justify-content:space-between;font-size:11px;color:var(--faint);margin-top:6px}
.save-btn{padding:6px 12px;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;font-size:11px;font-weight:700;color:var(--brand-2);transition:.2s}
.save-btn:hover{background:rgba(59,130,246,0.15);border-color:rgba(59,130,246,0.4)}

/* Transactions table */
.tx-table{width:100%;border-collapse:separate;border-spacing:0}
.tx-table th{text-align:left;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--faint);padding:10px 12px;border-bottom:1px solid var(--border)}
.tx-table td{padding:12px;font-size:13px;border-bottom:1px solid var(--border)}
.tx-table tr:last-child td{border-bottom:none}
.tx-table tr:hover td{background:var(--surface)}
.cat-badge{display:inline-block;padding:3px 9px;border-radius:999px;font-size:10px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
.amt{font-weight:700;font-variant-numeric:tabular-nums}
.amt.income{color:var(--emerald)}
.amt.expense{color:var(--rose)}
.del-btn{width:28px;height:28px;border-radius:8px;background:var(--surface-2);display:grid;place-items:center;color:var(--faint);transition:.2s}
.del-btn:hover{background:rgba(244,63,94,0.15);color:var(--rose)}

/* Forms */
.form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}
.field{display:flex;flex-direction:column;gap:6px}
.field.full{grid-column:1 / -1}
.field label{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}

.field input,.field select,.field textarea{
  width:100%;background:var(--surface-2);
  border:1px solid var(--border-2);border-radius:11px;
  padding:11px 14px;font-size:14px;color:var(--text);outline:none;transition:.2s;
  color-scheme:dark; /* makes native popups (dropdown, date picker) render dark */
}

/* Dark theme for the dropdown list itself */
.field select option,
.field select optgroup{
  background:#0f1740;      /* dark navy panel */
  color:#e6ecff;           /* readable light text */
  font-weight:600;
  padding:8px 12px;
}

/* Group headers (Expenses / Income) */
.field select optgroup{
  background:#0a0f2e;
  color:#60a5fa;
  font-style:normal;
  font-size:12px;
  letter-spacing:.06em;
  text-transform:uppercase;
}

/* Highlight the selected / hovered item */
.field select option:checked,
.field select option:hover{
  background:linear-gradient(135deg,#3b82f6,#8b5cf6);
  color:#fff;
}
    
.field input:focus,.field select:focus,.field textarea:focus{
  border-color:var(--brand);box-shadow:0 0 0 3px rgba(59,130,246,0.15);background:var(--surface-3);
}
.field textarea{resize:vertical;min-height:90px}

/* Buttons */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:11px;padding:11px 18px;font-size:13px;font-weight:700;transition:all .2s;white-space:nowrap}
.btn.primary{background:var(--grad);color:#fff;box-shadow:0 8px 20px -6px rgba(59,130,246,0.5)}
.btn.primary:hover{transform:translateY(-1px);box-shadow:0 12px 24px -6px rgba(59,130,246,0.6)}
.btn.ghost{background:var(--surface-2);border:1px solid var(--border-2);color:var(--text)}
.btn.ghost:hover{background:var(--surface-3);border-color:rgba(59,130,246,0.4)}
.btn.danger{background:rgba(244,63,94,0.15);border:1px solid rgba(244,63,94,0.3);color:var(--rose)}
.btn.danger:hover{background:rgba(244,63,94,0.25)}
.btn.full{width:100%}

/* Insights */
.insight{
  padding:16px 18px;border-radius:14px;
  display:flex;gap:12px;align-items:flex-start;
  margin-bottom:16px;font-size:14px;line-height:1.6;
}
.insight .insight-icon{width:34px;height:34px;border-radius:10px;display:grid;place-items:center;flex-shrink:0;font-size:16px}
.insight.good{background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.25);color:var(--emerald)}
.insight.warn{background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.25);color:var(--amber)}
.insight.danger{background:rgba(244,63,94,0.12);border:1px solid rgba(244,63,94,0.25);color:var(--rose)}
.insight.info{background:rgba(59,130,246,0.12);border:1px solid rgba(59,130,246,0.25);color:var(--brand-2)}

/* Forecast slider */
.forecast-box{background:var(--surface-2);border:1px solid var(--border);border-radius:14px;padding:18px;margin-top:16px}
.forecast-box label{font-size:12px;font-weight:700;color:var(--muted);display:block;margin-bottom:8px}
.forecast-box input[type=range]{width:100%;accent-color:var(--brand);margin:8px 0}
.forecast-val{display:flex;justify-content:space-between;align-items:center;margin-top:8px}
.forecast-val b{font-size:22px;font-weight:800}
.forecast-val small{color:var(--muted);font-size:12px}

/* Progress bar */
.progress{height:10px;background:var(--surface-2);border-radius:999px;overflow:hidden;margin-top:8px;border:1px solid var(--border)}
.progress-fill{height:100%;background:var(--grad);border-radius:999px;transition:width .6s}

/* Tabs */
.tabs{display:flex;gap:6px;margin-bottom:16px;background:var(--surface-2);border:1px solid var(--border);padding:4px;border-radius:12px;width:fit-content;flex-wrap:wrap}
.tab{padding:7px 14px;border-radius:9px;font-size:12px;font-weight:700;color:var(--muted);transition:.15s;cursor:pointer}
.tab:hover{color:var(--text)}
.tab.active{background:var(--grad);color:#fff}

/* Empty state */
.empty{text-align:center;padding:40px 20px;color:var(--faint);font-size:14px}
.empty .emoji{font-size:48px;display:block;margin-bottom:10px;opacity:0.6}

/* Toast */
.toast{
  position:fixed;bottom:24px;right:24px;
  background:linear-gradient(135deg,#10b981,#14b8a6);color:#fff;
  padding:14px 20px;border-radius:12px;font-size:13px;font-weight:700;
  box-shadow:0 20px 40px -10px rgba(16,185,129,0.4);
  z-index:100;animation:slideInToast .3s ease-out;
}
.toast.error{background:linear-gradient(135deg,#f43f5e,#ec4899);box-shadow:0 20px 40px -10px rgba(244,63,94,0.4)}
@keyframes slideInToast{from{transform:translateY(100%);opacity:0}to{transform:translateY(0);opacity:1}}

/* Mobile drawer overlay */
.mobile-overlay{position:fixed;inset:0;background:rgba(5,8,26,0.7);backdrop-filter:blur(4px);z-index:25;display:none}
.mobile-overlay.open{display:block}

/* Responsive */
@media(max-width:1020px){
  .grid-4{grid-template-columns:repeat(2,1fr)}
  .grid-2-1{grid-template-columns:1fr}
  .form-grid{grid-template-columns:1fr}
}
@media(max-width:860px){
  .sidebar{transform:translateX(-100%);width:280px}
  .sidebar.mobile-open{transform:translateX(0)}
  .main{margin-left:0;padding:20px 16px 60px}
  .sidebar.collapsed ~ .main{margin-left:0}
  .grid-4{grid-template-columns:repeat(2,1fr)}
  .grid-3{grid-template-columns:1fr}
  .grid-2{grid-template-columns:1fr}
  .page-title{font-size:18px}
}
@media(max-width:480px){
  .grid-4{grid-template-columns:1fr}
  .stat b{font-size:22px}
  .feed-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<div class="bg-orb o1"></div>
<div class="bg-orb o2"></div>
<div class="bg-orb o3"></div>

<div class="app">

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="brand">
    <div class="brand-logo">
      <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    </div>
    <div class="brand-text hide-collapsed">AZ Kejora <em>SaaS</em></div>
  </div>

  <div class="sidebar-user">
    <div class="avatar">
      <?php if (!empty($userPhoto)): ?><img src="<?= htmlspecialchars($userPhoto) ?>" alt=""><?php else: ?><?= strtoupper(substr($userName, 0, 1)) ?><?php endif; ?>
    </div>
    <div class="user-info hide-collapsed">
      <b><?= htmlspecialchars(explode(' ', $userName)[0]) ?></b>
      <small><?= htmlspecialchars($userEmail) ?></small>
    </div>
  </div>

  <button class="quick-log" onclick="openQuickLog()" title="<?= $lang === 'ms' ? 'Log Perbelanjaan Pantas' : 'Quick Log Expense' ?>">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    <span class="hide-collapsed"><?= $lang === 'ms' ? 'Log Perbelanjaan' : 'Log Expense' ?></span>
  </button>

  <div class="nav-label hide-collapsed"><?= $lang === 'ms' ? 'Menu' : 'Menu' ?></div>

  <a href="?page=home" class="nav-item <?= $page === 'home' ? 'active' : '' ?>">
    <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 12l9-9 9 9M5 10v10h14V10"/></svg></span>
    <span class="hide-collapsed"><?= $lang === 'ms' ? 'Utama' : 'Home' ?></span>
  </a>

  <a href="?page=profile" class="nav-item <?= $page === 'profile' ? 'active' : '' ?>">
    <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0112 0v1"/></svg></span>
    <span class="hide-collapsed"><?= $lang === 'ms' ? 'Profil Saya' : 'My Profile' ?></span>
  </a>

  <div class="nav-group <?= in_array($page, ['budget','news','entertainment','sports','games']) ? 'open' : '' ?>" id="solutionGroup">
    <div class="nav-item nav-group-toggle hide-collapsed" onclick="document.getElementById('solutionGroup').classList.toggle('open')">
      <span style="display:flex;align-items:center;gap:11px">
        <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>
        <?= $lang === 'ms' ? 'Penyelesaian' : 'Solution' ?>
      </span>
      <span class="arrow">▼</span>
    </div>
    <a href="?page=budget" class="nav-item" style="display:none"></a>
  </div>
  <div class="nav-group-items">
    <a href="?page=budget" class="nav-item <?= $page === 'budget' ? 'active' : '' ?>">
      <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></span>
      <span><?= $lang === 'ms' ? 'Bajet Peribadi' : 'Personal Budget' ?></span>
    </a>
    <a href="?page=news" class="nav-item <?= $page === 'news' ? 'active' : '' ?>">
      <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 22h16a2 2 0 002-2V4a2 2 0 00-2-2H8a2 2 0 00-2 2v16a2 2 0 01-2 2zm0 0a2 2 0 01-2-2v-9c0-1.1.9-2 2-2h2"/></svg></span>
      <span><?= $lang === 'ms' ? 'Suapan: Berita' : 'Feed: News' ?></span>
    </a>
    <a href="?page=entertainment" class="nav-item <?= $page === 'entertainment' ? 'active' : '' ?>">
      <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v18M5.5 7.5l13 9M5.5 16.5l13-9"/></svg></span>
      <span><?= $lang === 'ms' ? 'Suapan: Hiburan' : 'Feed: Entertainment' ?></span>
    </a>
    <a href="?page=sports" class="nav-item <?= $page === 'sports' ? 'active' : '' ?>">
      <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a15 15 0 010 20M12 2a15 15 0 000 20M2 12h20"/></svg></span>
      <span><?= $lang === 'ms' ? 'Suapan: Sukan' : 'Feed: Sports' ?></span>
    </a>
    <a href="?page=games" class="nav-item <?= $page === 'games' ? 'active' : '' ?>">
      <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="6" width="20" height="12" rx="3"/><path d="M6 12h4M8 10v4M15 11h.01M18 13h.01"/></svg></span>
      <span><?= $lang === 'ms' ? 'Suapan: Permainan' : 'Feed: Games' ?></span>
    </a>
  </div>

  <a href="?page=subscription" class="nav-item <?= $page === 'subscription' ? 'active' : '' ?>" style="margin-top:4px">
    <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span>
    <span class="hide-collapsed"><?= $lang === 'ms' ? 'Langganan' : 'Subscription' ?></span>
  </a>

  <a href="?page=download" class="nav-item <?= $page === 'download' ? 'active' : '' ?>">
    <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></span>
    <span class="hide-collapsed"><?= $lang === 'ms' ? 'Muat Turun' : 'Download' ?></span>
  </a>

  <div class="sidebar-footer">
    <div class="lang-switch-side hide-collapsed">
      <button class="<?= $lang === 'ms' ? 'active' : '' ?>" onclick="location.href='?lang=ms&page=<?= $page ?>'">🇲🇾 BM</button>
      <button class="<?= $lang === 'en' ? 'active' : '' ?>" onclick="location.href='?lang=en&page=<?= $page ?>'">🇬🇧 EN</button>
    </div>
    <a href="?logout=1" class="signout">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      <span class="hide-collapsed"><?= $lang === 'ms' ? 'Log Keluar' : 'Sign Out' ?></span>
    </a>
  </div>
</aside>

<div class="mobile-overlay" id="mobileOverlay" onclick="closeSidebar()"></div>

<!-- MAIN -->
<main class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="topbar-left">
      <button class="toggle-btn" onclick="toggleSidebar()" id="toggleBtn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div>
        <div class="page-title" id="pageTitle">
          <?php
          $titles = [
            'home' => $lang === 'ms' ? 'Utama' : 'Home',
            'profile' => $lang === 'ms' ? 'Profil Saya' : 'My Profile',
            'budget' => $lang === 'ms' ? 'Bajet Peribadi' : 'Personal Budget',
            'news' => $lang === 'ms' ? 'Suapan Berita' : 'News Feed',
            'entertainment' => $lang === 'ms' ? 'Suapan Hiburan' : 'Entertainment Feed',
            'sports' => $lang === 'ms' ? 'Suapan Sukan' : 'Sports Feed',
            'games' => $lang === 'ms' ? 'Suapan Permainan' : 'Games Feed',
            'subscription' => $lang === 'ms' ? 'Langganan' : 'Subscription',
            'download' => $lang === 'ms' ? 'Muat Turun Laporan' : 'Download Reports',
          ];
          echo $titles[$page] ?? 'Dashboard';
          ?>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
      <div style="display:flex;align-items:center;gap:6px;padding:6px 12px;background:var(--surface-2);border:1px solid var(--border);border-radius:999px;font-size:12px;font-weight:600;color:var(--muted)">
        <span style="width:7px;height:7px;border-radius:50%;background:var(--emerald);box-shadow:0 0 8px var(--emerald)"></span>
        <?= $lang === 'ms' ? 'Dalam talian' : 'Online' ?>
      </div>
    </div>
  </div>

  <?php if (!empty($_GET['msg'])): ?>
  <div class="toast" id="toast"><?= htmlspecialchars($_GET['msg']) ?></div>
  <script>setTimeout(()=>document.getElementById('toast').remove(),3000);</script>
  <?php endif; ?>

  <?php if ($message): ?>
  <div class="toast error" id="toast"><?= htmlspecialchars($message) ?></div>
  <script>setTimeout(()=>document.getElementById('toast').remove(),4000);</script>
  <?php endif; ?>

  <!-- =========== HOME PAGE =========== -->
  <?php if ($page === 'home'): ?>

    <div class="briefing">
      <div class="briefing-head">
        <span class="ai-chip">✨ <?= $lang === 'ms' ? 'Taklimat AI Pagi' : 'AI Morning Briefing' ?></span>
        <span style="margin-left:auto;font-size:11px;color:var(--faint);font-weight:600"><?= date('d M Y') ?></span>
      </div>
      <h2><?= $greeting() ?>, <?= htmlspecialchars(explode(' ', $userName)[0]) ?>! 👋</h2>
      <ul>
        <li><strong><?= $lang === 'ms' ? 'Bajet' : 'Budget' ?>:</strong>
          <?= $lang === 'ms'
              ? "Anda telah berbelanja RM" . number_format($monthSpent, 2) . " daripada RM" . number_format($totalBudget, 2) . " bulan ini. Baki RM" . number_format($remaining, 2) . "."
              : "You've spent RM" . number_format($monthSpent, 2) . " of RM" . number_format($totalBudget, 2) . " this month. RM" . number_format($remaining, 2) . " remaining." ?>
        </li>
        <li><strong><?= $lang === 'ms' ? 'Trend' : 'Trend' ?>:</strong>
          <?= $topCategory
              ? ($lang === 'ms' ? "Kategori teratas anda: " . ($categoryLabels[$topCategory] ?? $topCategory) . " (RM" . number_format($topAmount, 2) . ")" : "Your top category: " . ($categoryLabels[$topCategory] ?? $topCategory) . " (RM" . number_format($topAmount, 2) . ")")
              : ($lang === 'ms' ? "Tiada perbelanjaan dicatatkan bulan ini." : "No spending logged this month.") ?>
        </li>
        <li><strong><?= $lang === 'ms' ? 'Trending' : 'Trending' ?>:</strong>
          <?= $lang === 'ms' ? "GTA VI tarikh pelancaran disahkan · Harimau Malaya menang 3-1 · AI lonjak startup Asia" : "GTA VI release date confirmed · Harimau Malaya wins 3-1 · AI boosts Asian startups" ?>
        </li>
      </ul>
    </div>

    <div class="grid-4">
      <div class="stat" style="--grad-color:rgba(16,185,129,0.4);--stat-color:#10b981">
        <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
        <p><?= $lang === 'ms' ? 'Pendapatan Bulan Ini' : 'Monthly Income' ?></p>
        <b>RM<?= number_format($monthIncome, 2) ?></b>
      </div>
      <div class="stat" style="--grad-color:rgba(244,63,94,0.4);--stat-color:#f43f5e">
        <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
        <p><?= $lang === 'ms' ? 'Perbelanjaan Bulan Ini' : 'Monthly Spent' ?></p>
        <b>RM<?= number_format($monthSpent, 2) ?></b>
      </div>
      <div class="stat" style="--grad-color:rgba(59,130,246,0.4);--stat-color:#60a5fa">
        <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path d="M12 8v4l3 3"/></svg></div>
        <p><?= $lang === 'ms' ? 'Baki Selamat' : 'Safe Balance' ?></p>
        <b>RM<?= number_format(max(0, $remaining), 2) ?></b>
        <span class="delta <?= $remaining >= 0 ? 'up' : 'down' ?>"><?= $remaining >= 0 ? '✓ OK' : '⚠ Over' ?></span>
      </div>
      <div class="stat" style="--grad-color:rgba(139,92,246,0.4);--stat-color:#8b5cf6">
        <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z"/></svg></div>
        <p><?= $lang === 'ms' ? 'Matlamat Simpanan' : 'Savings Goal' ?></p>
        <b><?= number_format($savingsProgress, 0) ?>%</b>
        <div class="progress" style="margin-top:10px"><div class="progress-fill" style="width:<?= $savingsProgress ?>%"></div></div>
      </div>
    </div>

    <div class="grid-2-1">
      <div class="card">
        <div class="card-title">
          <h3>📰 <?= $lang === 'ms' ? 'Suapan Trending' : 'Trending Feed' ?></h3>
          <span class="badge"><?= $lang === 'ms' ? 'Dicadangkan AI' : 'AI-Picked' ?></span>
        </div>
        <div class="feed-grid">
          <?php
          $mixed = [];
          foreach ($feeds as $type => $items) { foreach ($items as $it) { $it['type'] = $type; $mixed[] = $it; } }
          shuffle($mixed);
          foreach (array_slice($mixed, 0, 4) as $item):
          ?>
          <div class="feed-card">
            <span class="tag"><?= htmlspecialchars($item['tag']) ?></span>
            <h4><?= htmlspecialchars($item['title']) ?></h4>
            <div class="tldr"><?= htmlspecialchars($lang === 'ms' ? $item['tldr_ms'] : $item['tldr_en']) ?></div>
            <div class="meta">
              <span><?= htmlspecialchars($item['time']) ?></span>
              <button class="save-btn">🔖 <?= $lang === 'ms' ? 'Simpan' : 'Save' ?></button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-title">
          <h3>⚡ <?= $lang === 'ms' ? 'Tindakan Pantas' : 'Quick Actions' ?></h3>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px">
          <button class="btn primary full" onclick="openQuickLog()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <?= $lang === 'ms' ? 'Log Perbelanjaan' : 'Log Expense' ?>
          </button>
          <a href="?page=budget" class="btn ghost full">📊 <?= $lang === 'ms' ? 'Lihat Bajet' : 'View Budget' ?></a>
          <a href="?page=profile" class="btn ghost full">👤 <?= $lang === 'ms' ? 'Edit Profil' : 'Edit Profile' ?></a>
          <a href="?page=download" class="btn ghost full">📥 <?= $lang === 'ms' ? 'Muat Turun Laporan' : 'Download Report' ?></a>
        </div>

        <div style="margin-top:20px;padding:14px;background:var(--surface-2);border-radius:12px;border:1px solid var(--border)">
          <div style="font-size:11px;font-weight:700;color:var(--faint);letter-spacing:.08em;text-transform:uppercase;margin-bottom:6px"><?= $lang === 'ms' ? 'Perbelanjaan Hari Ini' : 'Today\'s Spending' ?></div>
          <div style="font-size:24px;font-weight:800">RM<?= number_format($todaySpent, 2) ?></div>
          <div style="font-size:11px;color:var(--muted);margin-top:4px"><?= $lang === 'ms' ? 'Purata harian' : 'Daily average' ?>: RM<?= number_format($dailyAvg, 2) ?></div>
        </div>
      </div>
    </div>

  <?php endif; ?>

  <!-- =========== BUDGET PAGE =========== -->
  <?php if ($page === 'budget'): ?>

    <div class="insight <?= $insightType ?>">
      <div class="insight-icon">💡</div>
      <div>
        <strong><?= $lang === 'ms' ? 'Enjin Ramalan Pintar' : 'Smart Forecast Engine' ?></strong><br>
        <?= $insightText ?>
      </div>
    </div>

    <div class="grid-4">
      <div class="stat" style="--grad-color:rgba(16,185,129,0.4);--stat-color:#10b981">
        <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20"/></svg></div>
        <p><?= $lang === 'ms' ? 'Elaun/Pendapatan' : 'Allowance/Income' ?></p>
        <b>RM<?= number_format($totalBudget, 2) ?></b>
      </div>
      <div class="stat" style="--grad-color:rgba(244,63,94,0.4);--stat-color:#f43f5e">
        <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg></div>
        <p><?= $lang === 'ms' ? 'Jumlah Perbelanjaan' : 'Total Spent' ?></p>
        <b>RM<?= number_format($monthSpent, 2) ?></b>
      </div>
      <div class="stat" style="--grad-color:rgba(59,130,246,0.4);--stat-color:#60a5fa">
        <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg></div>
        <p><?= $lang === 'ms' ? 'Baki Selamat' : 'Safe to Spend' ?></p>
        <b>RM<?= number_format(max(0, $remaining), 2) ?></b>
      </div>
      <div class="stat" style="--grad-color:rgba(139,92,246,0.4);--stat-color:#8b5cf6">
        <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z"/></svg></div>
        <p><?= $lang === 'ms' ? 'Matlamat Simpanan' : 'Savings Goal' ?></p>
        <b><?= number_format($savingsProgress, 0) ?>%</b>
        <div class="progress"><div class="progress-fill" style="width:<?= $savingsProgress ?>%"></div></div>
      </div>
    </div>

    <div class="grid-2">
      <div class="card">
        <div class="card-title">
          <h3>📊 <?= $lang === 'ms' ? 'Pecahan Perbelanjaan' : 'Spending Breakdown' ?></h3>
        </div>
        <div style="position:relative;height:280px">
          <canvas id="spendingChart"></canvas>
        </div>
      </div>

      <div class="card">
        <div class="card-title">
          <h3>🔮 <?= $lang === 'ms' ? 'Simulasi "Apa Jika"' : 'What-If Simulator' ?></h3>
        </div>
        <div class="forecast-box">
          <label><?= $lang === 'ms' ? 'Jika saya simpan setiap minggu:' : 'If I save per week:' ?></label>
          <input type="range" id="saveSlider" min="0" max="500" step="10" value="50">
          <div class="forecast-val">
            <b>RM<span id="saveAmt">50</span> / <?= $lang === 'ms' ? 'minggu' : 'week' ?></b>
            <small><?= $lang === 'ms' ? 'Baki dijangka hujung bulan' : 'Projected end-of-month' ?></small>
          </div>
          <div style="text-align:center;margin-top:14px;font-size:28px;font-weight:800" id="projectedEnd">
            RM<?= number_format($projectedBalance, 2) ?>
          </div>
        </div>

        <div style="margin-top:16px">
          <button class="btn ghost full" onclick="document.getElementById('goalForm').style.display='block'">
            ⚙ <?= $lang === 'ms' ? 'Tetapkan Matlamat' : 'Set Goals' ?>
          </button>
          <form method="POST" id="goalForm" style="display:none;margin-top:12px">
            <input type="hidden" name="action" value="update_goal">
            <div class="field"><label><?= $lang === 'ms' ? 'Pendapatan Bulanan (RM)' : 'Monthly Income (RM)' ?></label>
              <input type="number" name="income" step="0.01" value="<?= $monthlyIncome ?>"></div>
            <div class="field" style="margin-top:10px"><label><?= $lang === 'ms' ? 'Matlamat Simpanan (RM)' : 'Savings Goal (RM)' ?></label>
              <input type="number" name="goal" step="0.01" value="<?= $savingsGoal ?>"></div>
            <button type="submit" class="btn primary full" style="margin-top:10px">💾 <?= $lang === 'ms' ? 'Simpan Matlamat' : 'Save Goals' ?></button>
          </form>
        </div>
      </div>
    </div>

    <div class="grid-2-1">
      <div class="card">
        <div class="card-title">
          <h3>📜 <?= $lang === 'ms' ? 'Transaksi Terkini' : 'Recent Transactions' ?></h3>
          <span class="badge"><?= count($transactions) ?> <?= $lang === 'ms' ? 'jumlah' : 'total' ?></span>
        </div>
        <div style="overflow-x:auto">
          <table class="tx-table">
            <thead>
              <tr>
                <th><?= $lang === 'ms' ? 'Tarikh' : 'Date' ?></th>
                <th><?= $lang === 'ms' ? 'Kategori' : 'Category' ?></th>
                <th><?= $lang === 'ms' ? 'Keterangan' : 'Description' ?></th>
                <th style="text-align:right"><?= $lang === 'ms' ? 'Jumlah' : 'Amount' ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($transactions)): ?>
              <tr><td colspan="5"><div class="empty"><span class="emoji">📭</span><?= $lang === 'ms' ? 'Tiada transaksi lagi. Tambah yang pertama!' : 'No transactions yet. Add your first!' ?></div></td></tr>
              <?php else: ?>
                <?php foreach (array_slice($transactions, 0, 15) as $tx): ?>
                <tr>
                  <td style="color:var(--muted);font-size:12px"><?= date('d M', strtotime($tx['tx_date'])) ?></td>
                  <td>
                    <span class="cat-badge" style="background:<?= $categoryColors[$tx['category']] ?? '#64748b' ?>22;color:<?= $categoryColors[$tx['category']] ?? '#64748b' ?>">
                      <?= htmlspecialchars($categoryLabels[$tx['category']] ?? $tx['category']) ?>
                    </span>
                  </td>
                  <td style="color:var(--text-dim)"><?= htmlspecialchars($tx['description'] ?: '-') ?></td>
                  <td style="text-align:right"><span class="amt <?= $tx['type'] ?>"><?= $tx['type'] === 'income' ? '+' : '-' ?>RM<?= number_format($tx['amount'], 2) ?></span></td>
                  <td>
                    <form method="POST" onsubmit="return confirm('<?= $lang === 'ms' ? 'Padam transaksi ini?' : 'Delete this transaction?' ?>')">
                      <input type="hidden" name="action" value="delete_tx">
                      <input type="hidden" name="tx_id" value="<?= $tx['id'] ?>">
                      <button type="submit" class="del-btn">✕</button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-title">
          <h3>➕ <?= $lang === 'ms' ? 'Log Pantas' : 'Quick Log' ?></h3>
        </div>
        <form method="POST" id="txForm">
          <input type="hidden" name="action" value="add_tx">
          <div class="field">
            <label><?= $lang === 'ms' ? 'Jenis' : 'Type' ?></label>
            <select name="tx_type" required>
              <option value="expense"><?= $lang === 'ms' ? 'Perbelanjaan' : 'Expense' ?></option>
              <option value="income"><?= $lang === 'ms' ? 'Pendapatan' : 'Income' ?></option>
            </select>
          </div>
          <div class="field" style="margin-top:10px">
            <label><?= $lang === 'ms' ? 'Kategori' : 'Category' ?></label>
            <select name="tx_category" required id="catSelect">
              <optgroup label="<?= $lang === 'ms' ? 'Perbelanjaan' : 'Expenses' ?>">
                <option value="food"><?= $categoryLabels['food'] ?></option>
                <option value="gaming"><?= $categoryLabels['gaming'] ?></option>
                <option value="shopping"><?= $categoryLabels['shopping'] ?></option>
                <option value="transport"><?= $categoryLabels['transport'] ?></option>
                <option value="entertainment"><?= $categoryLabels['entertainment'] ?></option>
                <option value="savings"><?= $categoryLabels['savings'] ?></option>
                <option value="other"><?= $categoryLabels['other'] ?></option>
              </optgroup>
              <optgroup label="<?= $lang === 'ms' ? 'Pendapatan' : 'Income' ?>">
                <option value="salary"><?= $categoryLabels['salary'] ?></option>
                <option value="allowance"><?= $categoryLabels['allowance'] ?></option>
                <option value="freelance"><?= $categoryLabels['freelance'] ?></option>
                <option value="gift"><?= $categoryLabels['gift'] ?></option>
              </optgroup>
            </select>
          </div>
          <div class="field" style="margin-top:10px">
            <label><?= $lang === 'ms' ? 'Jumlah (RM)' : 'Amount (RM)' ?></label>
            <input type="number" name="tx_amount" step="0.01" min="0.01" required>
          </div>
          <div class="field" style="margin-top:10px">
            <label><?= $lang === 'ms' ? 'Keterangan' : 'Description' ?></label>
            <input type="text" name="tx_description" placeholder="<?= $lang === 'ms' ? 'cth: Kopi dengan kawan' : 'e.g. Coffee with friends' ?>">
          </div>
          <div class="field" style="margin-top:10px">
            <label><?= $lang === 'ms' ? 'Tarikh' : 'Date' ?></label>
            <input type="date" name="tx_date" value="<?= date('Y-m-d') ?>" required>
          </div>
          <button type="submit" class="btn primary full" style="margin-top:14px">✓ <?= $lang === 'ms' ? 'Tambah Transaksi' : 'Add Transaction' ?></button>
        </form>
      </div>
    </div>

  <?php endif; ?>

  <!-- =========== PROFILE PAGE =========== -->
  <?php if ($page === 'profile'): ?>
    <div class="grid-2">
      <div class="card">
        <div class="card-title">
          <h3>👤 <?= $lang === 'ms' ? 'Maklumat Peribadi' : 'Personal Information' ?></h3>
        </div>
        <form method="POST">
          <input type="hidden" name="action" value="update_profile">
          <div class="form-grid">
            <div class="field">
              <label><?= $lang === 'ms' ? 'Nama Penuh' : 'Full Name' ?></label>
              <input type="text" name="name" value="<?= htmlspecialchars($userName) ?>" required>
            </div>
            <div class="field">
              <label><?= $lang === 'ms' ? 'E-mel' : 'Email' ?></label>
              <input type="email" value="<?= htmlspecialchars($userEmail) ?>" disabled>
            </div>
            <div class="field">
              <label><?= $lang === 'ms' ? 'Telefon' : 'Phone' ?></label>
              <input type="tel" name="phone" value="<?= htmlspecialchars($userPhone) ?>" placeholder="+60 12-345 6789">
            </div>
            <div class="field">
              <label><?= $lang === 'ms' ? 'URL Gambar Profil' : 'Profile Photo URL' ?></label>
              <input type="url" name="photo" value="<?= htmlspecialchars($userPhoto) ?>" placeholder="https://...">
            </div>
            <div class="field full">
              <label><?= $lang === 'ms' ? 'Alamat' : 'Address' ?></label>
              <textarea name="address" placeholder="<?= $lang === 'ms' ? 'Alamat penuh anda' : 'Your full address' ?>"><?= htmlspecialchars($userAddress) ?></textarea>
            </div>
          </div>
          <button type="submit" class="btn primary" style="margin-top:16px">💾 <?= $lang === 'ms' ? 'Simpan Perubahan' : 'Save Changes' ?></button>
        </form>
      </div>

      <div>
        <div class="card" style="margin-bottom:16px">
          <div class="card-title"><h3>📊 <?= $lang === 'ms' ? 'Statistik Akaun' : 'Account Stats' ?></h3></div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div style="padding:12px;background:var(--surface-2);border-radius:12px">
              <div style="font-size:11px;color:var(--faint);font-weight:700;letter-spacing:.08em;text-transform:uppercase"><?= $lang === 'ms' ? 'Jumlah Transaksi' : 'Total Tx' ?></div>
              <div style="font-size:22px;font-weight:800;margin-top:4px"><?= count($transactions) ?></div>
            </div>
            <div style="padding:12px;background:var(--surface-2);border-radius:12px">
              <div style="font-size:11px;color:var(--faint);font-weight:700;letter-spacing:.08em;text-transform:uppercase"><?= $lang === 'ms' ? 'Sumber Log Masuk' : 'Login Source' ?></div>
              <div style="font-size:22px;font-weight:800;margin-top:4px"><?= ucfirst(htmlspecialchars($userSource)) ?></div>
            </div>
            <div style="padding:12px;background:var(--surface-2);border-radius:12px">
              <div style="font-size:11px;color:var(--faint);font-weight:700;letter-spacing:.08em;text-transform:uppercase"><?= $lang === 'ms' ? 'Pelan Semasa' : 'Current Plan' ?></div>
              <div style="font-size:22px;font-weight:800;margin-top:4px"><?= $lang === 'ms' ? 'Percuma' : 'Free' ?></div>
            </div>
            <div style="padding:12px;background:var(--surface-2);border-radius:12px">
              <div style="font-size:11px;color:var(--faint);font-weight:700;letter-spacing:.08em;text-transform:uppercase"><?= $lang === 'ms' ? 'Ahli Sejak' : 'Member Since' ?></div>
              <div style="font-size:16px;font-weight:800;margin-top:4px"><?= date('M Y') ?></div>
            </div>
          </div>
        </div>

        <?php if ($userSource !== 'google'): ?>
        <div class="card">
          <div class="card-title"><h3>🔒 <?= $lang === 'ms' ? 'Tukar Kata Laluan' : 'Change Password' ?></h3></div>
          <form method="POST">
            <input type="hidden" name="action" value="change_password">
            <div class="field">
              <label><?= $lang === 'ms' ? 'Kata Laluan Baharu' : 'New Password' ?></label>
              <input type="password" name="new_password" minlength="6" required>
            </div>
            <div class="field" style="margin-top:10px">
              <label><?= $lang === 'ms' ? 'Sahkan Kata Laluan' : 'Confirm Password' ?></label>
              <input type="password" name="confirm_password" minlength="6" required>
            </div>
            <button type="submit" class="btn primary" style="margin-top:14px">🔐 <?= $lang === 'ms' ? 'Tukar Kata Laluan' : 'Change Password' ?></button>
          </form>
        </div>
        <?php else: ?>
        <div class="card" style="background:linear-gradient(135deg,rgba(59,130,246,0.1),rgba(139,92,246,0.1));border-color:rgba(59,130,246,0.25)">
          <div style="display:flex;align-items:center;gap:10px;font-size:14px;color:var(--brand-2)">
            <span style="font-size:20px">🔐</span>
            <?= $lang === 'ms' ? 'Log masuk melalui Google. Kata laluan diurus oleh Google.' : 'Signed in via Google. Password managed by Google.' ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- =========== FEED PAGES =========== -->
  <?php if (in_array($page, ['news','entertainment','sports','games'])): ?>
    <div class="tabs">
      <a class="tab <?= $page === 'news' ? 'active' : '' ?>" href="?page=news">📰 <?= $lang === 'ms' ? 'Berita' : 'News' ?></a>
      <a class="tab <?= $page === 'entertainment' ? 'active' : '' ?>" href="?page=entertainment">🎬 <?= $lang === 'ms' ? 'Hiburan' : 'Entertainment' ?></a>
      <a class="tab <?= $page === 'sports' ? 'active' : '' ?>" href="?page=sports">⚽ <?= $lang === 'ms' ? 'Sukan' : 'Sports' ?></a>
      <a class="tab <?= $page === 'games' ? 'active' : '' ?>" href="?page=games">🎮 <?= $lang === 'ms' ? 'Permainan' : 'Games' ?></a>
    </div>

    <div class="briefing">
      <div class="briefing-head">
        <span class="ai-chip">✨ <?= $lang === 'ms' ? 'Dikurasi AI' : 'AI-Curated' ?></span>
      </div>
      <h2><?= $lang === 'ms' ? 'Pilihan hari ini untuk anda' : 'Today\'s picks for you' ?></h2>
    </div>

    <div class="feed-grid">
      <?php foreach ($feeds[$page] as $item): ?>
      <div class="feed-card">
        <span class="tag"><?= htmlspecialchars($item['tag']) ?></span>
        <h4><?= htmlspecialchars($item['title']) ?></h4>
        <div class="tldr"><strong><?= $lang === 'ms' ? 'Mengapa penting:' : 'Why it matters:' ?></strong> <?= htmlspecialchars($lang === 'ms' ? $item['tldr_ms'] : $item['tldr_en']) ?></div>
        <div class="meta">
          <span>🕐 <?= htmlspecialchars($item['time']) ?></span>
          <button class="save-btn">🔖 <?= $lang === 'ms' ? 'Simpan' : 'Save' ?></button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- =========== SUBSCRIPTION PAGE =========== -->
  <?php if ($page === 'subscription'): ?>
    <div class="briefing">
      <div class="briefing-head">
        <span class="ai-chip">💎 <?= $lang === 'ms' ? 'Pelan Anda' : 'Your Plan' ?></span>
      </div>
      <h2><?= $lang === 'ms' ? 'Anda berada di pelan Percuma' : 'You are on the Free plan' ?></h2>
      <p style="color:var(--text-dim);margin-top:8px"><?= $lang === 'ms' ? 'Naik taraf ke Pro Youth untuk ciri tanpa had dan sokongan keutamaan.' : 'Upgrade to Pro Youth for unlimited features and priority support.' ?></p>
    </div>

    <div class="grid-3">
      <div class="card" style="border-color:var(--border-2)">
        <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--faint)">Free</div>
        <div style="font-size:36px;font-weight:800;margin-top:10px">RM0<span style="font-size:14px;color:var(--muted);font-weight:600">/<?= $lang === 'ms' ? 'bulan' : 'mo' ?></span></div>
        <ul style="list-style:none;margin-top:16px;display:grid;gap:8px;font-size:13px;color:var(--text-dim)">
          <li>✓ <?= $lang === 'ms' ? '50 transaksi sebulan' : '50 transactions/month' ?></li>
          <li>✓ <?= $lang === 'ms' ? 'Bajet asas' : 'Basic budgeting' ?></li>
          <li>✓ <?= $lang === 'ms' ? 'Suapan berita' : 'News feeds' ?></li>
        </ul>
        <button class="btn ghost full" style="margin-top:18px"><?= $lang === 'ms' ? 'Pelan Semasa' : 'Current Plan' ?></button>
      </div>

      <div class="card" style="border-color:rgba(59,130,246,0.4);box-shadow:0 0 0 2px rgba(59,130,246,0.2)">
        <div style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:var(--grad);color:#fff;padding:3px 12px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.08em">⭐ POPULAR</div>
        <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--brand-2)">Pro Youth</div>
        <div style="font-size:36px;font-weight:800;margin-top:10px">RM19<span style="font-size:14px;color:var(--muted);font-weight:600">/<?= $lang === 'ms' ? 'bulan' : 'mo' ?></span></div>
        <ul style="list-style:none;margin-top:16px;display:grid;gap:8px;font-size:13px;color:var(--text-dim)">
          <li>✓ <?= $lang === 'ms' ? 'Transaksi tanpa had' : 'Unlimited transactions' ?></li>
          <li>✓ <?= $lang === 'ms' ? 'Ramalan AI lanjutan' : 'Advanced AI forecasting' ?></li>
          <li>✓ <?= $lang === 'ms' ? 'Semua suapan premium' : 'All premium feeds' ?></li>
          <li>✓ <?= $lang === 'ms' ? 'Eksport tanpa had' : 'Unlimited exports' ?></li>
          <li>✓ <?= $lang === 'ms' ? 'Sokongan keutamaan' : 'Priority support' ?></li>
        </ul>
        <button class="btn primary full" style="margin-top:18px">🚀 <?= $lang === 'ms' ? 'Naik Taraf' : 'Upgrade' ?></button>
      </div>

      <div class="card" style="border-color:var(--border-2)">
        <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--violet)">Scale</div>
        <div style="font-size:36px;font-weight:800;margin-top:10px">RM49<span style="font-size:14px;color:var(--muted);font-weight:600">/<?= $lang === 'ms' ? 'bulan' : 'mo' ?></span></div>
        <ul style="list-style:none;margin-top:16px;display:grid;gap:8px;font-size:13px;color:var(--text-dim)">
          <li>✓ <?= $lang === 'ms' ? 'Semua ciri Pro' : 'All Pro features' ?></li>
          <li>✓ <?= $lang === 'ms' ? 'API & webhooks' : 'API & webhooks' ?></li>
          <li>✓ <?= $lang === 'ms' ? 'Pengurus akaun' : 'Account manager' ?></li>
          <li>✓ <?= $lang === 'ms' ? 'Log audit lanjutan' : 'Advanced audit logs' ?></li>
        </ul>
        <button class="btn ghost full" style="margin-top:18px"><?= $lang === 'ms' ? 'Hubungi Jualan' : 'Contact Sales' ?></button>
      </div>
    </div>

    <div class="card" style="margin-top:20px">
      <div class="card-title">
        <h3>📄 <?= $lang === 'ms' ? 'Sejarah Bil' : 'Billing History' ?></h3>
      </div>
      <div class="empty"><span class="emoji">📭</span><?= $lang === 'ms' ? 'Tiada sejarah bil. Naik taraf untuk melihat invois di sini.' : 'No billing history. Upgrade to see invoices here.' ?></div>
    </div>
  <?php endif; ?>

  <!-- =========== DOWNLOAD PAGE =========== -->
  <?php if ($page === 'download'): ?>
    <div class="card" style="max-width:640px">
      <div class="card-title">
        <h3>📥 <?= $lang === 'ms' ? 'Eksport Laporan Bajet' : 'Export Budget Report' ?></h3>
      </div>
      <p style="color:var(--muted);margin-bottom:20px"><?= $lang === 'ms' ? 'Pilih julat tarikh dan muat turun log perbelanjaan anda dalam format CSV.' : 'Select a date range and download your expense logs in CSV format.' ?></p>

      <form method="POST" action="download.php">
        <div class="form-grid">
          <div class="field">
            <label><?= $lang === 'ms' ? 'Tarikh Mula' : 'Start Date' ?></label>
            <input type="date" name="date_from" value="<?= date('Y-m-01') ?>" required>
          </div>
          <div class="field">
            <label><?= $lang === 'ms' ? 'Tarikh Akhir' : 'End Date' ?></label>
            <input type="date" name="date_to" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="field full">
            <label><?= $lang === 'ms' ? 'Format' : 'Format' ?></label>
            <select name="format">
              <option value="csv">CSV (Excel-compatible)</option>
              <option value="csv-utf8">CSV (UTF-8 BOM)</option>
            </select>
          </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:18px">
          <button type="submit" class="btn primary">📊 <?= $lang === 'ms' ? 'Jana & Muat Turun' : 'Generate & Download' ?></button>
          <button type="button" class="btn ghost" onclick="location.href='?page=download&preview=1'">👁 <?= $lang === 'ms' ? 'Pratonton' : 'Preview' ?></button>
        </div>
      </form>
    </div>

    <div class="card" style="margin-top:20px">
      <div class="card-title">
        <h3>📊 <?= $lang === 'ms' ? 'Statistik Akaun Anda' : 'Your Account Stats' ?></h3>
      </div>
      <div class="grid-3">
        <div style="padding:16px;background:var(--surface-2);border-radius:12px;text-align:center">
          <div style="font-size:28px;font-weight:800"><?= count($transactions) ?></div>
          <div style="font-size:11px;color:var(--muted);font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-top:4px"><?= $lang === 'ms' ? 'Jumlah Transaksi' : 'Total Transactions' ?></div>
        </div>
        <div style="padding:16px;background:var(--surface-2);border-radius:12px;text-align:center">
          <div style="font-size:28px;font-weight:800">RM<?= number_format($monthSpent, 0) ?></div>
          <div style="font-size:11px;color:var(--muted);font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-top:4px"><?= $lang === 'ms' ? 'Jumlah Perbelanjaan' : 'Total Spent' ?></div>
        </div>
        <div style="padding:16px;background:var(--surface-2);border-radius:12px;text-align:center">
          <div style="font-size:28px;font-weight:800"><?= count($categoryTotals) ?></div>
          <div style="font-size:11px;color:var(--muted);font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-top:4px"><?= $lang === 'ms' ? 'Kategori Digunakan' : 'Categories Used' ?></div>
        </div>
      </div>
    </div>
  <?php endif; ?>

</main>
</div>

<script>
// Sidebar toggle
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('mobileOverlay');
function toggleSidebar() {
  if (window.innerWidth <= 860) {
    sidebar.classList.toggle('mobile-open');
    overlay.classList.toggle('open');
  } else {
    sidebar.classList.toggle('collapsed');
  }
}
function closeSidebar() {
  sidebar.classList.remove('mobile-open');
  overlay.classList.remove('open');
}

// Quick log - navigate to budget
function openQuickLog() {
  location.href = '?page=budget';
}

// Spending Chart (Budget page)
<?php if ($page === 'budget' && !empty($categoryTotals)): ?>
(function(){
  const ctx = document.getElementById('spendingChart');
  if (!ctx) return;
  const labels = <?= json_encode(array_map(function($c) use ($categoryLabels){ return $categoryLabels[$c] ?? $c; }, array_keys($categoryTotals))) ?>;
  const data = <?= json_encode(array_values($categoryTotals)) ?>;
  const colors = <?= json_encode(array_map(function($c) use ($categoryColors){ return $categoryColors[$c] ?? '#64748b'; }, array_keys($categoryTotals))) ?>;
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{
        data: data,
        backgroundColor: colors,
        borderColor: 'rgba(5,8,26,0.5)',
        borderWidth: 2,
        hoverOffset: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '65%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: '#c5cfee', padding: 14, font: { size: 11, family: 'Inter', weight: '600' }, usePointStyle: true, pointStyle: 'circle' }
        },
        tooltip: {
          backgroundColor: 'rgba(15,23,64,0.95)',
          titleColor: '#e6ecff',
          bodyColor: '#c5cfee',
          borderColor: 'rgba(59,130,246,0.3)',
          borderWidth: 1,
          padding: 12,
          callbacks: {
            label: function(c){ return ' RM' + c.parsed.toFixed(2) + ' (' + ((c.parsed / data.reduce((a,b)=>a+b,0)) * 100).toFixed(1) + '%)'; }
          }
        }
      }
    }
  });
})();
<?php endif; ?>

// Forecast simulator
(function(){
  const slider = document.getElementById('saveSlider');
  const amt = document.getElementById('saveAmt');
  const end = document.getElementById('projectedEnd');
  if (!slider) return;
  const base = <?= (float)$projectedBalance ?>;
  const weeksLeft = Math.max(0, Math.ceil((<?= $daysLeft ?>) / 7));
  slider.addEventListener('input', function(){
    const v = parseInt(this.value);
    amt.textContent = v;
    const projected = base + (v * weeksLeft);
    end.textContent = 'RM' + projected.toFixed(2);
    end.style.color = projected >= 0 ? 'var(--emerald)' : 'var(--rose)';
  });
})();

// Auto-close sidebar on nav click (mobile)
document.querySelectorAll('.sidebar a').forEach(a => {
  a.addEventListener('click', () => { if (window.innerWidth <= 860) closeSidebar(); });
});
</script>

</body>
</html>
