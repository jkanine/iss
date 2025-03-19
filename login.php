<?php
session_start();

require "../database/database.php";
$pdo = Database::connect();

$error = '';

// Check if the login form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the user input
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Input validation (you can expand this as needed)
    if (empty($email) || empty($password)) {
        $error = 'Email and Password are required.';
    } else {
        // Prepare a SQL query to fetch the user data
        $sql = "SELECT * FROM iss_persons WHERE email = :email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        // Check if the user exists
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Get the salt from the user record
            $salt = $user['pwd_salt'];

            // Hash the entered password with the stored salt
            $hashed_password = md5($password . $salt);

            // Compare the hashed password with the stored password
            if ($hashed_password === $user['pwd_hash']) {
                // Store user session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];

                // Redirect to the issues list page
                header('Location: issues_list.php');
                exit();
            } else {
                $error = 'Incorrect email or password.';
            }
        } else {
            $error = 'User not found.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Department Status Report</title>
    <style>
        /* Basic styling for the login page */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 50px;
        }

        .login-container {
            max-width: 400px;
            margin: auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
        }

        input[type="email"], input[type="password"] {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #5cb85c;
            border: none;
            color: #fff;
            font-size: 16px;
            border-radius: 5px;
        }

        .error {
            color: red;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Login - Department Status Report</h2>

    <?php if (isset($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <input type="email" name="email" placeholder="Enter your email" required><br>
        <input type="password" name="password" placeholder="Enter your password" required><br>
        <input type="submit" value="Login">
    </form>
</div>

</body>
</html>
