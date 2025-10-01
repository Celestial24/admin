<?php
session_start();
// Isama ang iyong database connection file
include '../backend/sql/db.php';

// Debug: Check if we can connect to database
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Suriin kung ang form ay na-submit
if (isset($_POST['username']) && isset($_POST['password'])) {

    // Function para i-validate ang input
    function validate($data){
       $data = trim($data);
       $data = stripslashes($data);
       $data = htmlspecialchars($data);
       return $data;
    }

    $username = validate($_POST['username']);
    $password = validate($_POST['password']);

    // Suriin kung may laman ang mga field
    if (empty($username)) {
        header("Location: login.php?error=Username is required");
        exit();
    } else if (empty($password)) {
        header("Location: login.php?error=Password is required");
        exit();
    } else {
        // PAALALA TUNGKOL SA SEGURIDAD: Ang iyong database ay kasalukuyang nag-iimbak ng mga password bilang plain text.
        // Ito ay hindi secure. Mas mainam na gamitin ang password_hash() kapag nag-iimbak at password_verify() para sa pagsusuri.
        // Ang code sa ibaba ay gumagamit ng "prepared statements" para maiwasan ang SQL injection
        // habang pinapanatili ang kasalukuyang paraan ng pag-verify ng password.

        // Maghanda ng statement para maiwasan ang SQL injection
        $sql = "SELECT * FROM admin_user WHERE username=? OR email=?";
        $stmt = mysqli_prepare($conn, $sql);
        
        // Debug: Check if statement preparation was successful
        if (!$stmt) {
            die("Statement preparation failed: " . mysqli_error($conn));
        }

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $username, $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) === 1) {
                $row = mysqli_fetch_assoc($result);

                // I-verify ang password (plain text comparison for admin_user table)
                if ($password === $row['password']) {
                    // Set default role to admin since admin_user table doesn't have role column
                    $row['role'] = 'admin';
                    
                    // Itakda ang mga session variable
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['name'] = $row['name'];
                    $_SESSION['email'] = $row['email'] ?? '';
                    $_SESSION['role'] = $row['role'];
                    $_SESSION['user_type'] = $row['role'];

                    // Mag-redirect batay sa role ng user
                    mysqli_stmt_close($stmt);
                    if ($_SESSION['role'] == 'admin') {
                        header("Location: ../Main/Dashboard.php");
                        exit();
                    } elseif ($_SESSION['role'] == 'superadmin') {
                        header("Location: ../Main/super_Dashboard.php");
                        exit();
                    } else {
                        // Pangasiwaan ang ibang mga role o default case
                        header("Location: login.php?error=Unknown user role");
                        exit();
                    }
                } else {
                    // Kung mali ang password
                    mysqli_stmt_close($stmt);
                    header("Location: login.php?error=Incorrect Username or Password");
                    exit();
                }
            } else {
                // Kung hindi nahanap ang user
                mysqli_stmt_close($stmt);
                header("Location: login.php?error=Incorrect Username or Password");
                exit();
            }
        } else {
            // Kung may error sa SQL
            header("Location: login.php?error=An unexpected database error occurred");
            exit();
        }
    }
} else {
    // Kung hindi na-submit ang form, i-redirect pabalik sa login page
    header("Location: login.php");
    exit();
}
?>
