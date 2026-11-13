<?php
// 1. Connect to PostgreSQL using environment variables from Render
$host = getenv('DB_HOST') ?: 'dpg-dbr5q0t6e07s73ak86pg-a';
$db   = getenv('DB_NAME') ?: 'azkejora2';
$user = getenv('DB_USER') ?: 'azkejora2_user';
$pass = getenv('DB_PASS') ?: 'iaWV8wGhAnXWEY1JycGWvVDrOeRjfRAt';
$port = getenv('DB_PORT') ?: '5432';

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// 2. Automatically create the 'azadmin' table with AI-recommended migration & logging columns if it doesn't exist
$pdo->exec("
    CREATE TABLE IF NOT EXISTS azadmin (
        id SERIAL PRIMARY KEY,
        userid VARCHAR(100) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        role VARCHAR(50) DEFAULT 'admin',
        -- AI-recommended columns for data migration, versioning, and logs --
        version INT DEFAULT 1,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
    );
");

$message = "";
$editData = null;

// 3. Handle Form Submission (Save or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userid = $_POST['userid'] ?? '';
    $email  = $_POST['email'] ?? '';
    $phone  = $_POST['phone'] ?? '';
    $role   = $_POST['role'] ?? '';
    $id     = $_POST['id'] ?? '';

    if (!empty($id)) {
        // Update existing record
        $stmt = $pdo->prepare("UPDATE azadmin SET userid = ?, email = ?, phone = ?, role = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$userid, $email, $phone, $role, $id]);
        $message = "Record updated successfully! ✅";
    } else {
        // Insert new record
        try {
            $stmt = $pdo->prepare("INSERT INTO azadmin (userid, email, phone, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userid, $email, $phone, $role]);
            $message = "New admin registered successfully! 🎉";
        } catch (\PDOException $e) {
            $message = "Error: User ID already exists or invalid data.";
        }
    }
}

// 4. Handle Edit Request
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM azadmin WHERE id = ?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch();
}

// 5. Fetch all records to list
$stmt = $pdo->query("SELECT * FROM azadmin ORDER BY id DESC");
$admins = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Management - test.php</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background: #f4f7f6; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        input, select { width: 100%; padding: 10px; margin: 8px 0 16px 0; display: inline-block; border: 1px solid #ccc; box-sizing: border-box; border-radius: 4px; }
        button { background-color: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #45a049; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .msg { color: green; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="card">
    <h2><?= $editData ? 'Edit Admin' : 'Register New Admin' ?></h2>
    <?php if($message): ?>
        <p class="msg"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">
        
        <label>User ID:</label>
        <input type="text" name="userid" value="<?= htmlspecialchars($editData['userid'] ?? '') ?>" required>
        
        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($editData['email'] ?? '') ?>" required>
        
        <label>Phone:</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($editData['phone'] ?? '') ?>">
        
        <label>Role:</label>
        <select name="role">
            <option value="admin" <?= ($editData['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="superadmin" <?= ($editData['role'] ?? '') === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
        </select>
        
        <button type="submit"><?= $editData ? 'Update Record' : 'Register & Save' ?></button>
        <?php if($editData): ?>
            <a href="test.php" style="margin-left: 10px; text-decoration: none; color: #555;">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <h2>Registered Admins List</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>User ID</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Created At</th>
            <th>Action</th>
        </tr>
        <?php if(count($admins) > 0): ?>
            <?php foreach($admins as $admin): ?>
            <tr>
                <td><?= $admin['id'] ?></td>
                <td><?= htmlspecialchars($admin['userid']) ?></td>
                <td><?= htmlspecialchars($admin['email']) ?></td>
                <td><?= htmlspecialchars($admin['phone']) ?></td>
                <td><?= htmlspecialchars($admin['role']) ?></td>
                <td><?= $admin['created_at'] ?></td>
                <td><a href="test.php?edit=<?= $admin['id'] ?>">Edit</a></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align: center;">No records found yet. Register one above!</td></tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>
