<?php
require 'vendor/autoload.php';
require_once 'session_manager.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$showOtpForm = false;
$message = '';

define('USER_FILE', __DIR__ . DIRECTORY_SEPARATOR . 'users.txt');

$sessionManager = new SessionManager();

function sendOtpEmail($toEmail, $otp) {
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Set your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ashbahian1@gmail.com'; // SMTP username
        $mail->Password   = 'nbpkkqfiuowvtrvt';   // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('ashbahian1@gmail.com', 'Rangantodapp');
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "Your OTP code is: <b>$otp</b>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function readUsers() {
    $users = [];
    if (file_exists(USER_FILE)) {
        $lines = file(USER_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(',', $line);
            $username = $parts[0] ?? '';
            $email = $parts[1] ?? '';
            $phone = $parts[2] ?? '';
            $passwordHash = $parts[3] ?? '';
            $verified = $parts[4] ?? '0';
            $approved = $parts[5] ?? '0';
            $users[$username] = [
                'email' => $email,
                'phone' => $phone,
                'password' => $passwordHash,
                'verified' => $verified === '1',
                'approved' => $approved === '1'
            ];
        }
    }
    return $users;
}

function writeUsers($users) {
    $lines = [];
    foreach ($users as $username => $data) {
        $lines[] = implode(',', [
            $username,
            $data['email'],
            $data['phone'],
            $data['password'],
            $data['verified'] ? '1' : '0',
            $data['approved'] ? '1' : '0'
        ]);
    }
    file_put_contents(USER_FILE, implode(PHP_EOL, $lines));
}

$users = readUsers();

$sessionId = $_COOKIE['RangantodappSession'] ?? null;
$sessionData = null;
if ($sessionId) {
    $sessionData = $sessionManager->getSession($sessionId);
}

if (!empty($_POST)) {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            $message = "Username and password are required for login.";
        } elseif (!isset($users[$username])) {
            $message = "User not found.";
        } else {
            $user = $users[$username];
            if (!password_verify($password, $user['password'])) {
                $message = "Incorrect password.";
            } else {
                if (!$user['approved']) {
                    $message = "Your account is awaiting admin approval.";
                } else {
                    // Create session and set cookie
                    $sessionId = $sessionManager->createSession($username);
                    setcookie('RangantodappSession', $sessionId, time() + 3600, "/");
                    $sessionData = $sessionManager->getSession($sessionId);
                    if (strtoupper($username) === 'ADMIN') {
                        header("Location: admin_approval.php");
                        exit();
                    } else {
                        $message = "Login successful. Welcome, " . htmlspecialchars($username) . "!";
                    }
                }
            }
        }
    } elseif ($action === 'logout') {
        if ($sessionId) {
            $sessionManager->destroySession($sessionId);
            setcookie('RangantodappSession', '', time() - 3600, "/");
            $message = "Logged out successfully.";
            $sessionData = null;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - Rangantodapp</title>
    <link rel="stylesheet" href="rangantodapp.css" />
</head>
<body style="background: linear-gradient(135deg, #1e3c72, #2a5298); min-height: 100vh; display: flex; justify-content: center; align-items: center;">
    <div class="container">
        <div class="logo-container" style="text-align: center; margin-bottom: 20px;">
            <img src="img/datodalogo.jpg" alt="DATODA Logo" style="max-width: 150px; height: auto;" />
            <h1 style="margin: 10px 0 0; font-family: Arial, sans-serif; color: #333;">DATODA</h1>
        </div>
        <h2>Login</h2>
        <?php if ($message): ?>
            <p style="color: red; text-align: center;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if (!$showOtpForm): ?>
            <form method="POST" action="login.php">
                <input type="hidden" name="action" value="login" />
                <label for="username">Username or Email:</label>
                <input type="text" id="username" name="username" required />

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required />

                <button type="submit">Login</button>
            </form>
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
