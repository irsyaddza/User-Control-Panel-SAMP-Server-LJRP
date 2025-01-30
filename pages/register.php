<?php
require_once '../includes/config.php';
require_once '../includes/hash_helper.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $email = trim($_POST['email']);
    $secret_word = trim($_POST['secret_word']);
    $quiz = 1; // Default value for Quiz column

    if (empty($username) || empty($password) || empty($email) || empty($secret_word)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT ID FROM accounts WHERE Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if($stmt->num_rows > 0) {
            $error = "Username already exists";
            $stmt->close();
        } else {
            $stmt->close();
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT ID FROM accounts WHERE Email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if($stmt->num_rows > 0) {
                $error = "Email already registered";
                $stmt->close();
            } else {
                $stmt->close();
                
                // Hash password dengan whirlpool dan uppercase
                $password = hash('whirlpool', $password);
                $password = strtoupper($password);

                // Insert with SecretWord only
                $stmt = $conn->prepare("INSERT INTO accounts (Username, Password, Email, SecretWord, Quiz) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $username, $password, $email, $secret_word, $quiz);

                if ($stmt->execute()) {
                    $success = "Registration successful! You can now login.";
                } else {
                    $error = "Error: " . $stmt->error;
                }

                $stmt->close();
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Jawa System</title>
    <link rel="stylesheet" href="../assets/css/style.css">

</head>
<body>
    <div class="container">
    <img src="../assets/images/logo.png" alt="Jawa System Logo" class="logo">
        <h2>Create Account</h2>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
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

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="secret_word">Secret Word</label>
                <input type="text" id="secret_word" name="secret_word" required>
                <small class="form-text">Set this word carefully. It will be used to verify your identity if you forget your password.</small>
            </div>

            <button type="submit" class="btn">Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="../index.php">Login here</a>
        </div>
    </div>
</body>
</html>