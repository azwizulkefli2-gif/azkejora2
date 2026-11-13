<?php
session_start();

// Connect to PostgreSQL database using Render environment variables
$host = getenv('DB_HOST') ?: 'dpg-dbr5q0t6e07s73ak86pg-a';
$db   = getenv('DB_NAME') ?: 'azkejora2';
$user = getenv('DB_USER') ?: 'azkejora2_user';
$pass = getenv('DB_PASS') ?: 'iaWV8wGhAnXWEY1JycGWvVDrOeRjfRAt';
$port = getenv('DB_PORT') ?: '5432';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

$message = "";

// Handle Email Form Submission (Sign Up or Sign In)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email  = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $name   = trim($_POST['name'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {
        if ($action === 'signup') {
            // Register new email user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO \"User\" (userid, name, source, password_hash) VALUES (?, ?, 'email', ?)");
                $stmt->execute([$email, $name, $hash]);
                $message = "Account created successfully! You can now sign in.";
            } catch (\PDOException $e) {
                $message = "Error: Email is already registered.";
            }
        } elseif ($action === 'signin') {
            // Sign in existing email user
            $stmt = $pdo->prepare("SELECT * FROM \"User\" WHERE userid = ? AND source = 'email'");
            $stmt->execute([$email]);
            $userData = $stmt->fetch();

            if ($userData && password_verify($password, $userData['password_hash'])) {
                $_SESSION['user'] = $userData;
                header("Location: test-home.php");
                exit;
            } else {
                $message = "Invalid email or password.";
            }
        }
    }
}

// Google OAuth Login URL configuration
$googleClientId = getenv('GOOGLE_CLIENT_ID') ?: '879355190369-hi2086n76ivjn67uic0bfjfgv1baj8ca.apps.googleusercontent.com';
$redirectUri = 'https://azkejora2.onrender.com/auth-callback.php';
$googleAuthUrl = "https://accounts.google.com/o/oauth2/v2/auth?client_id=" . urlencode($googleClientId) . "&redirect_uri=" . urlencode($redirectUri) . "&response_type=code&scope=email%20profile";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login / Sign Up</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 350px; }
        h2 { text-align: center; margin-bottom: 20px; }
        input { width: 100%; padding: 10px; margin: 8px 0 16px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        button:hover { background: #45a049; }
        .google-btn { background: #db4437; margin-top: 10px; text-decoration: none; display: block; text-align: center; color: white; padding: 10px; border-radius: 4px; font-weight: bold; }
        .google-btn:hover { background: #c23321; }
        .msg { color: #d9534f; font-size: 14px; text-align: center; margin-bottom: 10px; }
        .toggle { text-align: center; margin-top: 15px; font-size: 14px; cursor: pointer; color: #007BFF; }
    </style>
</head>
<body>

<div class="container">
    <h2 id="form-title">Sign In</h2>
    <?php if($message): ?><div class="msg"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <form method="POST" id="auth-form">
        <input type="hidden" name="action" id="form-action" value="signin">
        
        <div id="name-field" style="display:none;">
            <label>Full Name:</label>
            <input type="text" name="name" id="name-input">
        </div>

        <label>Email (User ID):</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <button type="submit" id="submit-btn">Sign In</button>
    </form>

    <a href="<?= $googleAuthUrl ?>" class="google-btn">Sign in with Google</a>

    <div class="toggle" onclick="toggleMode()">Don't have an account? Sign Up</div>
</div>

<script>
let isSignUp = false;
function toggleMode() {
    isSignUp = !isSignUp;
    document.getElementById('form-title').innerText = isSignUp ? 'Sign Up' : 'Sign In';
    document.getElementById('form-action').value = isSignUp ? 'signup' : 'signin';
    document.getElementById('submit-btn').innerText = isSignUp ? 'Register Account' : 'Sign In';
    document.getElementById('name-field').style.display = isSignUp ? 'block' : 'none';
    document.getElementById('name-input').required = isSignUp;
    document.querySelector('.toggle').innerText = isSignUp ? 'Already have an account? Sign In' : "Don't have an account? Sign Up";
}
</script>

</body>
</html>
