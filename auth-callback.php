<?php
session_start();

$clientId     = getenv('GOOGLE_CLIENT_ID');
$clientSecret = getenv('GOOGLE_CLIENT_SECRET');
$redirectUri  = 'https://azkejora2.onrender.com/auth-callback.php';

if (!isset($_GET['code'])) {
    header("Location: test-login.php");
    exit;
}

$code = $_GET['code'];

// 1. Exchange authorization code for access token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$postData = [
    'code'          => $code,
    'client_id'     => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri'  => $redirectUri,
    'grant_type'    => 'authorization_code'
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
$response = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode($response, true);

if (!isset($tokenData['access_token'])) {
    die("Google Authentication Failed: Unable to get access token.");
}

// 2. Fetch user profile information from Google
$userInfoUrl = 'https://www.googleapis.com/oauth2/v3/userinfo?access_token=' . $tokenData['access_token'];
$userInfo = json_decode(file_get_contents($userInfoUrl), true);

if (!isset($userInfo['email'])) {
    die("Google Authentication Failed: Unable to retrieve email.");
}

$email = $userInfo['email'];
$name  = $userInfo['name'] ?? '';
$photo = $userInfo['picture'] ?? '';

// 3. Connect to Database
$host = getenv('DB_HOST');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$port = getenv('DB_PORT') ?: '5432';

$pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// 4. Check if user already exists in database
$stmt = $pdo->prepare("SELECT * FROM \"User\" WHERE userid = ?");
$stmt->execute([$email]);
$userData = $stmt->fetch();

if (!$userData) {
    // Insert new Google user automatically
    $stmt = $pdo->prepare("INSERT INTO \"User\" (userid, name, photo, source) VALUES (?, ?, ?, 'google')");
    $stmt->execute([$email, $name, $photo]);

    // Fetch newly created record
    $stmt = $pdo->prepare("SELECT * FROM \"User\" WHERE userid = ?");
    $stmt->execute([$email]);
    $userData = $stmt->fetch();
} else {
    // Update photo or name if changed
    $stmt = $pdo->prepare("UPDATE \"User\" SET name = ?, photo = ?, updated_at = CURRENT_TIMESTAMP WHERE userid = ?");
    $stmt->execute([$name, $photo, $email]);
}

// 5. Start user session and redirect to home page
$_SESSION['user'] = $userData;
header("Location: test-home.php");
exit;
?>
