<?php
// filepath: c:\xampp\htdocs\admin\auth\login_api.php
session_start();
header('Content-Type: application/json');

// Database connection
require_once '../backend/sql/db.php';

// Helper: check admin_user table
function checkAdmin($conn, $usernameOrEmail, $password) {
    $stmt = $conn->prepare("SELECT * FROM admin_user WHERE username = ? OR email = ? LIMIT 1");
    $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();

    if (!$admin) return false;

    // Password check: hashed or plain
    if (isset($admin['password_hash']) && !empty($admin['password_hash'])) {
        if (!password_verify($password, $admin['password_hash'])) return false;
    } elseif (isset($admin['password'])) {
        if ($admin['password'] !== $password) return false;
    } else {
        return false;
    }
    return $admin;
}

// Helper: check users table
function checkUser($conn, $usernameOrEmail, $password) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR name = ? LIMIT 1");
    $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) return false;
    if (isset($user['is_verified']) && !$user['is_verified']) return 'not_verified';

    // Password check: hashed or plain
    if (isset($user['password_hash']) && !empty($user['password_hash'])) {
        if (!password_verify($password, $user['password_hash'])) return false;
    } elseif (isset($user['password'])) {
        if ($user['password'] !== $password) return false;
    } else {
        return false;
    }
    return $user;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Please enter username/email and password.']);
    exit;
}

// Try admin first (including Super Admin role)
$admin = checkAdmin($conn, $username, $password);
if ($admin) {
    $adminRoleRaw = strtolower(trim($admin['role'] ?? 'admin'));
    // Normalize possible variants for Super Admin
    $superAliases = ['super_admin','superadmin','super admin','super'];
    $isSuper = in_array($adminRoleRaw, $superAliases, true);

    $_SESSION['role'] = $isSuper ? 'super_admin' : 'admin';
    $_SESSION['user_type'] = $_SESSION['role'];
    $_SESSION['user_id'] = $admin['id'];
    $_SESSION['name'] = $admin['name'] ?? $admin['username'] ?? 'Admin';
    $_SESSION['email'] = $admin['email'] ?? '';

    echo json_encode([
        'success' => true,
        'role' => $_SESSION['role'],
        'greeting' => $isSuper ? 'Super Admin' : 'Admin',
        'name' => $_SESSION['name'],
        'redirectUrl' => $isSuper ? '../Main/super_Dashboard.php' : '../Main/Dashboard.php'
    ]);
    exit;
}

// Try user table
$user = checkUser($conn, $username, $password);
if ($user === 'not_verified') {
    echo json_encode(['success' => false, 'message' => 'Your account is not verified.']);
    exit;
}
if ($user) {
    $_SESSION['role'] = 'user';
    $_SESSION['user_type'] = 'user';
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'] ?? '';
    $_SESSION['email'] = $user['email'] ?? '';
    echo json_encode([
        'success' => true,
        'role' => 'user',
        'greeting' => 'User',
        'name' => $_SESSION['name'],
        'redirectUrl' => '../user/dashboard.php'
    ]);
    exit;
}

// If both fail
echo json_encode([
    'success' => false,
    'message' => 'Invalid username/email or password.'
]);
exit;