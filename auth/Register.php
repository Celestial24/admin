<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include_once __DIR__ . '/../backend/sql/db.php'; // Your mysqli connection in $conn

require_once __DIR__ . '/../backend/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../backend/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../backend/PHPMailer/src/SMTP.php';

// Initialize variables
$name = $email = "";
$errors = [];

// Sanitize function
function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize inputs
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($name)) {
        $errors[] = "Full Name is required.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        // Check if email already exists
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Email is already registered.";
        } else {
            // Generate verification code (6 digits)
            $verification_code = random_int(100000, 999999);

            // Hash password securely
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $insert_stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password_hash, verification_code, is_verified) VALUES (?, ?, ?, ?, 0)");
            mysqli_stmt_bind_param($insert_stmt, "ssss", $name, $email, $password_hash, $verification_code);

            if (mysqli_stmt_execute($insert_stmt)) {
                // Send verification email using PHPMailer
                $mail = new PHPMailer(true);

                try {
                    // SMTP Configuration
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'linbilcelestre3@gmail.com'; // Your Gmail address
                    $mail->Password = 'senl eyxw cipm jwoz';    // ⚠️ App Password from Google
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Sender and recipient
                    $mail->setFrom('linbilcelestre3@gmail.com', 'Your Website Team');
                    $mail->addAddress($email, $name);

                    // Email content
                    $mail->isHTML(false);
                    $mail->Subject = "Verify your email address";
                    $mail->Body = "Hello $name,\n\nThank you for registering. Your verification code is: $verification_code\n\nPlease enter this code on the verification page to activate your account.\n\nRegards,\nYour Website Team";

                    $mail->send();

                    // Redirect to verification page
                    header("Location: verify.php?email=" . urlencode($email));
                    exit;

                } catch (Exception $e) {
                    $errors[] = "Failed to send verification email. Mailer Error: " . $mail->ErrorInfo;
                }

            } else {
                $errors[] = "Database error: Could not register user.";
            }

            mysqli_stmt_close($insert_stmt);
        }

        mysqli_stmt_close($stmt);
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Register</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

  <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
    <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Create Account</h2>

    <?php if (!empty($errors)): ?>
      <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
        <ul class="list-disc pl-5">
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="register.php" method="POST" class="space-y-5" novalidate>
      <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
        <input type="text" id="name" name="name" required
               value="<?= htmlspecialchars($name) ?>"
               class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>

      <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
        <input type="email" id="email" name="email" required
               value="<?= htmlspecialchars($email) ?>"
               class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
        <input type="password" id="password" name="password" required
               class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>

      <div>
        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required
               class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>

      <div>
        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md transition duration-200">
          Register
        </button>
      </div>

      <p class="text-center text-sm text-gray-600">
        Already have an account?
        <a href="../user/login.php" class="text-blue-600 hover:underline">Login</a>
      </p>
    </form>
  </div>

</body>
</html>
