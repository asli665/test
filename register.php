<?php

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$showOtpForm = false;
$message = '';

define('USER_FILE', __DIR__ . DIRECTORY_SEPARATOR . 'users.txt');

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
    $result = file_put_contents(USER_FILE, implode(PHP_EOL, $lines));
    if ($result === false) {
        error_log("Failed to write users to " . USER_FILE);
    }
}

function getOtpFilePath($username) {
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . "otp_{$username}.txt";
}

$users = readUsers();

if (!empty($_POST)) {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $otp = $_POST['otp'] ?? '';

        if (!$otp) {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!$username || !$email || !$phone || !$password) {
                $message = "All fields are required.";
            } elseif (isset($users[$username])) {
                $message = "Username already exists.";
            } else {
                // Generate OTP and send email
                $otp = '';
                for ($i = 0; $i < 6; $i++) {
                    $otp .= rand(0, 9);
                }
                $otpFile = getOtpFilePath($username);
                file_put_contents($otpFile, $otp);

                if (sendOtpEmail($email, $otp)) {
                    $showOtpForm = true;
                    $message = "OTP sent to your email. Please verify.";
                } else {
                    $message = "Failed to send OTP email. Please try again.";
                }
            }
        } else {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';

            $otpFile = getOtpFilePath($username);
            if (!file_exists($otpFile)) {
                $message = "No OTP found. Please register again.";
                $showOtpForm = false;
            } else {
                $savedOtp = file_get_contents($otpFile);
                if ($otp === $savedOtp) {
                    // Save user
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $users[$username] = [
                        'email' => $email,
                        'phone' => $phone,
                        'password' => $passwordHash,
                        'verified' => true,
                        'approved' => false
                    ];
                    writeUsers($users);
                    unlink($otpFile);
                    $message = "Registration successful. You can now log in.";
                    $showOtpForm = false;
                    // Redirect to login page after successful registration
                    header("Location: login.php");
                    exit();
                } else {
                    $message = "Invalid OTP. Please try again.";
                    $showOtpForm = true;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Register - Rangantodapp</title>
    <link rel="stylesheet" href="rangantodapp.css" />
</head>
<body style="background: linear-gradient(135deg, #1e3c72, #2a5298); min-height: 100vh; display: flex; justify-content: center; align-items: center;">
    <div class="container">
        <div class="logo-container" style="text-align: center; margin-bottom: 20px;">
            <img src="img/datodalogo.jpg" alt="DATODA Logo" style="max-width: 150px; height: auto;" />
            <h1 style="margin: 10px 0 0; font-family: Arial, sans-serif; color: #333;">DATODA</h1>
        </div>
        <h2>Register</h2>
        <?php if ($message): ?>
            <p style="color: red; text-align: center;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if (!$showOtpForm): ?>
            <form method="POST" action="register.php">
                <input type="hidden" name="action" value="register" />
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required />

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required />

                <label for="phone">Phone Number:</label>
                <input type="text" id="phone" name="phone" required />

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required />

                <button type="submit">Register</button>
            </form>
            <p>Already have an account? <a href="login.php">Login here</a></p>
        <?php else: ?>
            <form method="POST" action="register.php">
                <input type="hidden" name="action" value="register" />
                <input type="hidden" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" />
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" />
                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" />
                <input type="hidden" name="password" value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>" />
                <label for="otp">Enter OTP:</label>
                <input type="text" id="otp" name="otp" required />

                <button type="submit">Verify OTP</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
