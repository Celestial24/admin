<?php
session_start();

// Get user info before destroying session
$username = 'User';
$user_id = $_SESSION['user_id'] ?? null;

if ($user_id) {
    // Try to get username from admin_user table first
    require_once '../backend/sql/db.php';
    
    $stmt = $conn->prepare("SELECT username FROM admin_user WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        $username = $user['username'];
    } else {
        // Try users table
        $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            $username = $user['name'];
        }
    }
    $stmt->close();
}

// Destroy the session
session_destroy();

// Clear all session data
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - ATIERA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/image/logo2.png">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .fade-in { animation: fadeIn 0.6s ease-out; }
        .pulse-animation { animation: pulse 2s infinite; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-blue-900 flex items-center justify-center p-4">
    
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><defs><pattern id=\"grain\" width=\"100\" height=\"100\" patternUnits=\"userSpaceOnUse\"><circle cx=\"25\" cy=\"25\" r=\"1\" fill=\"white\"/><circle cx=\"75\" cy=\"75\" r=\"1\" fill=\"white\"/><circle cx=\"50\" cy=\"10\" r=\"0.5\" fill=\"white\"/><circle cx=\"10\" cy=\"60\" r=\"0.5\" fill=\"white\"/><circle cx=\"90\" cy=\"40\" r=\"0.5\" fill=\"white\"/></pattern></defs><rect width=\"100\" height=\"100\" fill=\"url(%23grain)\"/></svg>');"></div>
    </div>

    <div class="relative z-10 max-w-md w-full">
        <!-- Main Card -->
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl p-8 text-center fade-in">
            
            <!-- Logo -->
            <div class="mb-6">
                <img src="../assets/image/logo.png" alt="ATIERA Logo" class="w-20 h-20 mx-auto mb-4 pulse-animation">
                <h1 class="text-2xl font-bold text-gray-800">ATIERA</h1>
                <p class="text-sm text-gray-600">Hotel & Restaurant Management</p>
            </div>

            <!-- Logout Message -->
            <div class="mb-8">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Successfully Logged Out</h2>
                <p class="text-gray-600 mb-1">Goodbye, <span class="font-medium text-gray-800"><?= htmlspecialchars($username) ?></span>!</p>
                <p class="text-sm text-gray-500">Thank you for using ATIERA Management System</p>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3">
                <a href="login.php" 
                   class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 transform hover:scale-105">
                    Sign In Again
                </a>
                <a href="../index.php" 
                   class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-6 rounded-lg transition duration-200">
                    Go to Homepage
                </a>
            </div>

            <!-- Footer -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <p class="text-xs text-gray-500">
                    © 2025 ATIERA BSIT 4101 CLUSTER 1<br>
                    All rights reserved
                </p>
            </div>
        </div>

        <!-- Floating Elements -->
        <div class="absolute -top-4 -right-4 w-8 h-8 bg-yellow-400 rounded-full opacity-20"></div>
        <div class="absolute -bottom-4 -left-4 w-12 h-12 bg-blue-400 rounded-full opacity-20"></div>
        <div class="absolute top-1/2 -right-8 w-6 h-6 bg-green-400 rounded-full opacity-20"></div>
    </div>

    <!-- Auto-redirect Script -->
    <script>
        // Auto-redirect to login after 5 seconds
        let countdown = 5;
        const redirectTimer = setInterval(() => {
            countdown--;
            if (countdown <= 0) {
                clearInterval(redirectTimer);
                window.location.href = 'login.php';
            }
        }, 1000);

        // Add countdown display
        document.addEventListener('DOMContentLoaded', function() {
            const loginBtn = document.querySelector('a[href="login.php"]');
            if (loginBtn) {
                const originalText = loginBtn.textContent;
                loginBtn.textContent = `${originalText} (${countdown})`;
                
                const updateCountdown = setInterval(() => {
                    countdown--;
                    if (countdown > 0) {
                        loginBtn.textContent = `${originalText} (${countdown})`;
                    } else {
                        clearInterval(updateCountdown);
                    }
                }, 1000);
            }
        });
    </script>

</body>
</html>