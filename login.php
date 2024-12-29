<?php
require_once 'config.php';
require_once 'hash_helper.php';
session_start();

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        // Hash password dengan whirlpool dan uppercase
        $password = hash('whirlpool', $password);
        $password = strtoupper($password);

        // Prepare SQL statement
        $stmt = $conn->prepare("SELECT ID, Username FROM accounts WHERE Username = ? AND Password = ?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['ID'];
            $_SESSION['username'] = $user['Username'];
            header("Location: dashboard.php"); 
            exit();
        } else {
            $error = "Invalid username or password";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Jawa System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <img src="assets/logo.png" alt="Jawa System Logo" class="logo">
        <h2>Login</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

        <div class="login-link">
            <a href="forgot-password.php">Forgot Password?</a>
            <br>
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</body>
</html>