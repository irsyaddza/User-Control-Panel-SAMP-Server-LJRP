<?php
require_once 'config.php';
require_once 'hash_helper.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

$error = "";
$success = "";
$showSecretForm = false;
$showVerificationForm = false;
$showPasswordForm = false;
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['find_account'])) {
        $email = trim($_POST['email']);

        $stmt = $conn->prepare("SELECT ID, Email FROM accounts WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            $_SESSION['reset_user_id'] = $user['ID'];
            $_SESSION['reset_email'] = $email;
            $showSecretForm = true;
        } else {
            $error = "Email tidak ditemukan";
        }
        $stmt->close();
    } elseif (isset($_POST['verify_secret'])) {
        $secret_word = trim($_POST['secret_word']);
        $user_id = $_SESSION['reset_user_id'];

        $stmt = $conn->prepare("SELECT ID FROM accounts WHERE ID = ? AND SecretWord = ?");
        $stmt->bind_param("is", $user_id, $secret_word);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $verificationCode = sprintf("%06d", mt_rand(100000, 999999));
            $expiryTime = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $updateStmt = $conn->prepare("UPDATE accounts SET ResetCode = ?, ResetCodeExpiry = ? WHERE ID = ?");
            $updateStmt->bind_param("ssi", $verificationCode, $expiryTime, $user_id);
            $updateStmt->execute();

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'your@gmail.com';
                $mail->Password = '';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('irsyadza04@gmail.com', 'Jawa System');
                $mail->addAddress($_SESSION['reset_email']);

                $mail->isHTML(true);
                $mail->Subject = 'Kode Verifikasi Reset Password';
                $mail->Body = "Kode verifikasi Anda adalah: <b>" . $verificationCode . "</b><br>Kode ini akan kadaluarsa dalam 1 jam.";
                $mail->AltBody = "Kode verifikasi Anda adalah: " . $verificationCode . "\nKode ini akan kadaluarsa dalam 1 jam.";

                $mail->send();
                $success = "Kode verifikasi telah dikirim ke email Anda.";
                $showVerificationForm = true;
                $showSecretForm = false;
            } catch (Exception $e) {
                $error = "Gagal mengirim kode verifikasi. Error: {$mail->ErrorInfo}";
                $showSecretForm = true;
            }
        } else {
            $error = "Kata rahasia tidak valid";
            $showSecretForm = true;
        }
        $stmt->close();

    } elseif (isset($_POST['verify_code'])) {
        if (!isset($_SESSION['reset_user_id'])) {
            $error = "Sesi telah berakhir. Silakan mulai dari awal.";
            $showVerificationForm = false;
            return;
        }

        $verification_code = trim($_POST['verification_code']);
        $user_id = $_SESSION['reset_user_id'];

        // Query sederhana dan langsung
        $stmt = $conn->prepare("SELECT ID, ResetCode, ResetCodeExpiry FROM accounts WHERE ID = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Periksa hasil query
        if ($result->num_rows === 1) {
            // Periksa kode dan waktu kadaluarsa
            if ($user['ResetCode'] === $verification_code) {
                if (strtotime($user['ResetCodeExpiry']) > time()) {
                    $showPasswordForm = true;
                    $showVerificationForm = false;
                } else {
                    $error = "Kode verifikasi sudah kadaluarsa";
                    $showVerificationForm = true;
                }
            } else {
                $error = "Kode verifikasi tidak valid";
                $showVerificationForm = true;
            }
        } else {
            $error = "User tidak ditemukan";
            $showVerificationForm = false;
        }
        $stmt->close();
    } elseif (isset($_POST['reset_password'])) {
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);
        $user_id = $_SESSION['reset_user_id'];

        if ($new_password === $confirm_password) {
            $password = hash('whirlpool', $new_password);
            $password = strtoupper($password);

            $stmt = $conn->prepare("UPDATE accounts SET Password = ?, ResetCode = NULL, ResetCodeExpiry = NULL WHERE ID = ?");
            $stmt->bind_param("si", $password, $user_id);

            if ($stmt->execute()) {
                $success = "Password berhasil direset. Silakan login.";
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_email']);
            } else {
                $error = "Gagal mereset password";
                $showPasswordForm = true;
            }
            $stmt->close();
        } else {
            $error = "Password tidak cocok";
            $showPasswordForm = true;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Jawa System</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <img src="assets/logo.png" alt="Logo Jawa System" class="logo">
        <h2>Reset Password</h2>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!$showSecretForm && !$showVerificationForm && !$showPasswordForm && empty($success)): ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="email">Masukkan alamat email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" name="find_account" class="btn">Cari Akun</button>
            </form>
        <?php endif; ?>

        <?php if ($showSecretForm): ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="secret_word">Masukkan kata rahasia</label>
                    <input type="text" id="secret_word" name="secret_word" required>
                </div>
                <button type="submit" name="verify_secret" class="btn">Verifikasi Kata Rahasia</button>
            </form>
        <?php endif; ?>

        <?php if ($showVerificationForm): ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="verification_code">Masukkan kode verifikasi</label>
                    <input type="text" id="verification_code" name="verification_code" required>
                    <small>Periksa email Anda untuk kode verifikasi</small>
                </div>
                <button type="submit" name="verify_code" class="btn">Verifikasi Kode</button>
            </form>
        <?php endif; ?>

        <?php if ($showPasswordForm): ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="new_password">Password Baru</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password Baru</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" name="reset_password" class="btn">Reset Password</button>
            </form>
        <?php endif; ?>

        <div class="login-link">
            <a href="login.php">Kembali ke Login</a>
        </div>
    </div>
</body>

</html>