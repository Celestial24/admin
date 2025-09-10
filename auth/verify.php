<?php
include_once __DIR__ . '/../backend/sql/db.php'; // DB connection ($conn)

$errors = [];
$success = false;
$email = htmlspecialchars($_GET['email'] ?? '');

function sanitize($data) {
    return htmlspecialchars(trim(stripslashes($data)));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = sanitize($_POST['email'] ?? '');
    $code = sanitize($_POST['verification_code'] ?? '');

    // Basic validations
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($code)) {
        $errors[] = "Verification code is required.";
    } elseif (!preg_match('/^\d{6}$/', $code)) {
        $errors[] = "Verification code must be a 6-digit number.";
    }

    // Process verification
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, is_verified FROM users WHERE email = ? AND verification_code = ?");
        $stmt->bind_param("ss", $email, $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            $errors[] = "Invalid email or verification code.";
        } elseif ((int)$user['is_verified'] === 1) {
            $errors[] = "This account is already verified.";
        } else {
            $update_stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_code = NULL WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            if ($update_stmt->execute()) {
                $success = true;
            } else {
                $errors[] = "Something went wrong during verification.";
            }
            $update_stmt->close();
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Email Verification</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        input[type="text"] {
            -moz-appearance: textfield;
        }
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
    <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Email Verification</h2>

    <?php if ($success): ?>
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded text-center">
            ✅ Your email has been successfully verified!<br />
            <a href="../user/login.php" class="mt-2 inline-block text-blue-600 hover:underline">Go to Login</a>
        </div>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                <ul class="list-disc pl-5">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5" novalidate>
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>" />

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Verification Code</label>
                <div class="flex space-x-2 justify-center">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <input
                            type="text"
                            maxlength="1"
                            pattern="\d"
                            inputmode="numeric"
                            class="w-10 h-10 text-center text-lg border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            id="code<?= $i ?>"
                            autocomplete="off"
                            />
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="verification_code" id="verification_code" />
            </div>

            <div>
                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md transition duration-200">
                    Verify
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
    const inputs = document.querySelectorAll('input[id^="code"]');
    inputs[0].focus();

    inputs.forEach((input, idx) => {
        input.addEventListener('input', () => {
            // Only allow digits
            input.value = input.value.replace(/[^0-9]/g, '');

            if (input.value.length === 1 && idx < inputs.length - 1) {
                inputs[idx + 1].focus();
            }
            updateHiddenInput();
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === "Backspace" && !input.value && idx > 0) {
                inputs[idx - 1].focus();
            }
        });
    });

    function updateHiddenInput() {
        let code = '';
        inputs.forEach(i => code += i.value);
        document.getElementById('verification_code').value = code;
    }
</script>

</body>
</html>
