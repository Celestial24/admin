<?php
session_start();
include_once __DIR__ . '/../backend/sql/db.php'; // Your database connection ($conn)

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, name, password_hash, is_verified FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            $errors[] = "No account found with that email.";
        } elseif (!$user['is_verified']) {
            $errors[] = "Your account is not verified. Please verify your email first.";
        } elseif (!password_verify($password, $user['password_hash'])) {
            $errors[] = "Incorrect password.";
        } else {
            // Login successful - create session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            
            // Redirect to dashboard or homepage
            header("Location: dashboard.php");
            exit;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
    <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Login</h2>

    <?php if (!empty($errors)): ?>
        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-5" novalidate>
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" id="email" name="email" required
                   class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                   placeholder="Enter your email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" id="password" name="password" required
                   class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                   placeholder="Enter your password" />
        </div>

        <div>
            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md transition duration-200">
                Login
            </button>
        </div>
    </form>

    <p class="mt-6 text-center text-gray-600 text-sm">
        Don't have an account? <a href="../auth/Register.php" class="text-blue-600 hover:underline">Register here</a>.
    </p>
</div>

</body>
</html>
