<?php
require "../database/database.php";
$pdo = Database::connect();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['fname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($fname) || empty($lname) || empty($email) || empty($mobile) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM iss_persons WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $error = 'This email is already registered.';
        } else {
            // Generate salt and hashed password
            $salt = bin2hex(random_bytes(16));
            $hashed_password = md5($password . $salt);

            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO iss_persons (fname, lname, mobile, email, pwd_hash, pwd_salt, admin) 
                                   VALUES (:fname, :lname, :mobile, :email, :pwd_hash, :pwd_salt, '0')");
            $stmt->execute([
                ':fname' => $fname,
                ':lname' => $lname,
                ':mobile' => $mobile,
                ':email' => $email,
                ':pwd_hash' => $hashed_password,
                ':pwd_salt' => $salt
            ]);

            $success = "Registration successful! You can now <a href='login.php'>login</a>.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Department Status Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eef;
            padding: 50px;
        }

        .register-container {
            max-width: 450px;
            margin: auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        input[type="text"], input[type="email"], input[type="password"] {
            width: 90%;
            padding: 10px;
            margin: 8px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #0275d8;
            border: none;
            color: #fff;
            font-size: 16px;
            border-radius: 5px;
        }

        .error {
            color: red;
            text-align: center;
        }

        .success {
            color: green;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="register-container">
    <h2>Register - Department Status Report</h2>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($success): ?>
        <p class="success"><?= $success ?></p>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <input type="text" name="fname" placeholder="First Name" required><br>
        <input type="text" name="lname" placeholder="Last Name" required><br>
        <input type="text" name="mobile" placeholder="Mobile" required><br>
        <input type="email" name="email" placeholder="Email Address" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required><br>
        <input type="submit" value="Register">
    </form>

    <p style="text-align: center; margin-top: 10px;">
        Already have an account? <a href="login.php">Login here</a>
    </p>
</div>

</body>
</html>
