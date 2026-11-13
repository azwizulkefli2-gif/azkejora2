<?php
session_start();

// Protect page: redirect to login if not signed in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$user = $_SESSION['user'];

// Handle logout request
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 400px; text-align: center; }
        img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; border: 2px solid #4CAF50; }
        h2 { margin-bottom: 5px; color: #333; }
        p { color: #666; font-size: 14px; margin: 8px 0; }
        .badge { display: inline-block; padding: 4px 8px; font-size: 12px; background: #e0f2fe; color: #0369a1; border-radius: 4px; margin-bottom: 15px; font-weight: bold; }
        .logout-btn { display: inline-block; margin-top: 20px; background: #d9534f; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .logout-btn:hover { background: #c23321; }
    </style>
</head>
<body>

<div class="card">
    <?php if(!empty($user['photo'])): ?>
        <img src="<?= htmlspecialchars($user['photo']) ?>" alt="User Photo">
    <?php else: ?>
        <div style="font-size: 50px; margin-bottom: 15px;">👤</div>
    <?php endif; ?>

    <h2><?= htmlspecialchars($user['name'] ?: 'User') ?></h2>
    <p><strong>Email ID:</strong> <?= htmlspecialchars($user['userid']) ?></p>
    <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?: 'Not provided') ?></p>
    <div class="badge">Signed in via: <?= strtoupper(htmlspecialchars($user['source'])) ?></div>
    
    <br>
    <a href="home.php?logout=true" class="logout-btn">Sign Out</a>
</div>

</body>
</html>
