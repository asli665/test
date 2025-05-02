<?php
require 'vendor/autoload.php';
require_once 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

$showOtpForm = false;
$message = '';

// Fetch OTP verification setting
$otpVerificationEnabled = '1'; // default enabled
$stmt = mysqli_prepare($conn, "SELECT setting_value FROM settings WHERE setting_key = 'otp_verification_enabled' LIMIT 1");
if ($stmt) {
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $settingValue);
    if (mysqli_stmt_fetch($stmt)) {
        $otpVerificationEnabled = $settingValue;
    }
    mysqli_stmt_close($stmt);
}

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

$conn = $GLOBALS['conn'];

if (!empty($_POST)) {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $resendOtp = $_POST['resend_otp'] ?? '';

        // Use session to persist username and password during OTP verification
        if ($resendOtp === '1') {
            $username = $_SESSION['login_username'] ?? '';
            $password = $_SESSION['login_password'] ?? '';

            if (!$username || !$password) {
                $message = "Session expired. Please login again.";
                $showOtpForm = false;
            } else {
                // Fetch user by username or email
                $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ss", $username, $username);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                if (!$user) {
                    $message = "User not found.";
                    $showOtpForm = false;
                } else {
                    if ($otpVerificationEnabled === '1') {
                        // Check for existing valid OTP within last 5 minutes
                        $sql = "SELECT otp_code, created_at FROM user_otps WHERE username = ? ORDER BY created_at DESC LIMIT 1";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "s", $user['username']);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_bind_result($stmt, $existingOtp, $createdAt);
                        $otpFound = mysqli_stmt_fetch($stmt);
                        mysqli_stmt_close($stmt);

                        $sendNewOtp = true;
                        if ($otpFound) {
                            $otpAge = time() - strtotime($createdAt);
                            if ($otpAge <= 300) { // 5 minutes = 300 seconds
                                $otp = $existingOtp;
                                $sendNewOtp = false;
                            }
                        }

                        if ($sendNewOtp) {
                            // Generate new OTP
                            $otp = '';
                            for ($i = 0; $i < 6; $i++) {
                                $otp .= rand(0, 9);
                            }

                            // Insert new OTP into database
                            $sql = "INSERT INTO user_otps (username, otp_code) VALUES (?, ?)";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "ss", $user['username'], $otp);
                            mysqli_stmt_execute($stmt);
                            mysqli_stmt_close($stmt);
                        }

                        if (sendOtpEmail($user['email'], $otp)) {
                            $message = "OTP resent to your email. Please verify.";
                            $showOtpForm = true;
                        } else {
                            $message = "Failed to resend OTP email. Please try again.";
                            $showOtpForm = true;
                        }
                    } else {
                        // OTP verification disabled, log user in directly
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_type'] = $user['user_type'];

                        // Log login action to activity_logs table
                        $logAction = "User '{$user['username']}' logged in.";
                        $logSql = "INSERT INTO activity_logs (username, action) VALUES (?, ?)";
                        $logStmt = mysqli_prepare($conn, $logSql);
                        mysqli_stmt_bind_param($logStmt, "ss", $user['username'], $logAction);
                        mysqli_stmt_execute($logStmt);
                        mysqli_stmt_close($logStmt);

                        // Clear temporary login session variables
                        unset($_SESSION['login_username']);
                        unset($_SESSION['login_password']);

                        if (strtoupper($user['username']) === 'ADMIN') {
                            header("Location: admin_approval.php");
                            exit();
                        } else {
                            if ($user['user_type'] === 'passenger') {
                                header("Location: passenger.php");
                                exit();
                            } elseif ($user['user_type'] === 'driver') {
                                header("Location: driver.php");
                                exit();
                            } else {
                                // Default fallback
                                header("Location: login.php");
                                exit();
                            }
                        }
                    }
                }
            }
        } elseif (empty($_POST['otp'])) {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!$username || !$password) {
                $message = "Username and password are required for login.";
            } else {
                // Fetch user by username or email
                $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ss", $username, $username);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                if (!$user) {
                    $message = "User not found.";
                } else {
                    if (!password_verify($password, $user['password'])) {
                        $message = "Incorrect password.";
                    } else {
                        if (!$user['approved']) {
                            $message = "Your account is awaiting admin approval.";
                        } else {
                            if ($otpVerificationEnabled === '1') {
                                // Store username and password in session for OTP verification
                                $_SESSION['login_username'] = $username;
                                $_SESSION['login_password'] = $password;

                                // Check for existing valid OTP within last 5 minutes
                                $sql = "SELECT otp_code, created_at FROM user_otps WHERE username = ? ORDER BY created_at DESC LIMIT 1";
                                $stmt = mysqli_prepare($conn, $sql);
                                mysqli_stmt_bind_param($stmt, "s", $user['username']);
                                mysqli_stmt_execute($stmt);
                                mysqli_stmt_bind_result($stmt, $existingOtp, $createdAt);
                                $otpFound = mysqli_stmt_fetch($stmt);
                                mysqli_stmt_close($stmt);

                                $sendNewOtp = true;
                                if ($otpFound) {
                                    $otpAge = time() - strtotime($createdAt);
                                    if ($otpAge <= 300) { // 5 minutes = 300 seconds
                                        $otp = $existingOtp;
                                        $sendNewOtp = false;
                                    }
                                }

                                if ($sendNewOtp) {
                                    // Generate new OTP
                                    $otp = '';
                                    for ($i = 0; $i < 6; $i++) {
                                        $otp .= rand(0, 9);
                                    }

                                    // Insert new OTP into database
                                    $sql = "INSERT INTO user_otps (username, otp_code) VALUES (?, ?)";
                                    $stmt = mysqli_prepare($conn, $sql);
                                    mysqli_stmt_bind_param($stmt, "ss", $user['username'], $otp);
                                    mysqli_stmt_execute($stmt);
                                    mysqli_stmt_close($stmt);
                                }

                                if (sendOtpEmail($user['email'], $otp)) {
                                    $showOtpForm = true;
                                    $message = "OTP sent to your email. Please verify.";
                                } else {
                                    $message = "Failed to send OTP email. Please try again.";
                                }
                            } else {
                                // OTP verification disabled, log user in directly
                                $_SESSION['username'] = $user['username'];
                                $_SESSION['user_type'] = $user['user_type'];

                                // Log login action to activity_logs table
                                $logAction = "User '{$user['username']}' logged in.";
                                $logSql = "INSERT INTO activity_logs (username, action) VALUES (?, ?)";
                                $logStmt = mysqli_prepare($conn, $logSql);
                                mysqli_stmt_bind_param($logStmt, "ss", $user['username'], $logAction);
                                mysqli_stmt_execute($logStmt);
                                mysqli_stmt_close($logStmt);

                                if (strtoupper($user['username']) === 'ADMIN') {
                                    header("Location: admin_approval.php");
                                    exit();
                                } else {
                                    if ($user['user_type'] === 'passenger') {
                                        header("Location: passenger.php");
                                        exit();
                                    } elseif ($user['user_type'] === 'driver') {
                                        header("Location: driver.php");
                                        exit();
                                    } else {
                                        // Default fallback
                                        header("Location: login.php");
                                        exit();
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            // OTP verification step
            $otp = $_POST['otp'] ?? '';
            $username = $_SESSION['login_username'] ?? '';
            $password = $_SESSION['login_password'] ?? '';

            if (!$username || !$password) {
                $message = "Session expired. Please login again.";
                $showOtpForm = false;
            } else {
                // Fetch user by username or email
                $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ss", $username, $username);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                if (!$user) {
                    $message = "User not found.";
                    $showOtpForm = false;
                } else {
                    if ($otpVerificationEnabled === '1') {
                        // Verify OTP
                        $sql = "SELECT otp_code FROM user_otps WHERE username = ? ORDER BY created_at DESC LIMIT 1";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "s", $user['username']);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_bind_result($stmt, $savedOtp);
                        $otpFound = mysqli_stmt_fetch($stmt);
                        mysqli_stmt_close($stmt);

                        if (!$otpFound) {
                            $message = "No OTP found. Please login again.";
                            $showOtpForm = false;
                        } else {
                            if ($otp === $savedOtp) {
                                // Delete OTP after successful login
                                $sql = "DELETE FROM user_otps WHERE username = ?";
                                $stmt = mysqli_prepare($conn, $sql);
                                mysqli_stmt_bind_param($stmt, "s", $user['username']);
                                mysqli_stmt_execute($stmt);
                                mysqli_stmt_close($stmt);

                                // Set session variables
                                $_SESSION['username'] = $user['username'];
                                $_SESSION['user_type'] = $user['user_type'];

                                // Log login action to activity_logs table
                                $logAction = "User '{$user['username']}' logged in.";
                                $logSql = "INSERT INTO activity_logs (username, action) VALUES (?, ?)";
                                $logStmt = mysqli_prepare($conn, $logSql);
                                mysqli_stmt_bind_param($logStmt, "ss", $user['username'], $logAction);
                                mysqli_stmt_execute($logStmt);
                                mysqli_stmt_close($logStmt);

                                // Clear temporary login session variables
                                unset($_SESSION['login_username']);
                                unset($_SESSION['login_password']);

                                if (strtoupper($user['username']) === 'ADMIN') {
                                    header("Location: admin_approval.php");
                                    exit();
                                } else {
                                    if ($user['user_type'] === 'passenger') {
                                        header("Location: passenger.php");
                                        exit();
                                    } elseif ($user['user_type'] === 'driver') {
                                        header("Location: driver.php");
                                        exit();
                                    } else {
                                        // Default fallback
                                        header("Location: login.php");
                                        exit();
                                    }
                                }
                            } else {
                                $message = "Invalid OTP. Please try again.";
                                $showOtpForm = true;
                            }
                        }
                    } else {
                        // OTP verification disabled, log user in directly
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_type'] = $user['user_type'];

                        // Log login action to activity_logs table
                        $logAction = "User '{$user['username']}' logged in.";
                        $logSql = "INSERT INTO activity_logs (username, action) VALUES (?, ?)";
                        $logStmt = mysqli_prepare($conn, $logSql);
                        mysqli_stmt_bind_param($logStmt, "ss", $user['username'], $logAction);
                        mysqli_stmt_execute($logStmt);
                        mysqli_stmt_close($logStmt);

                        // Clear temporary login session variables
                        unset($_SESSION['login_username']);
                        unset($_SESSION['login_password']);

                        if (strtoupper($user['username']) === 'ADMIN') {
                            header("Location: admin_approval.php");
                            exit();
                        } else {
                            if ($user['user_type'] === 'passenger') {
                                header("Location: passenger.php");
                                exit();
                            } elseif ($user['user_type'] === 'driver') {
                                header("Location: driver.php");
                                exit();
                            } else {
                                // Default fallback
                                header("Location: login.php");
                                exit();
                            }
                        }
                    }
                }
            }
        }
    } elseif ($action === 'logout') {
        session_destroy();
        $message = "Logged out successfully.";
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
<body class="login-page">
    <div class="container">
        <div class="logo-container">
            <img src="img/datodalogo.jpg" alt="DATODA Logo" />
            <h1>DATODA</h1>
        </div>
        <h2>Login</h2>
        <?php if ($message): ?>
            <p class="error-message"><?php echo htmlspecialchars($message); ?></p>
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
        <?php else: ?>
            <form method="POST" action="login.php">
                <input type="hidden" name="action" value="login" />
                <input type="hidden" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" />
                <input type="hidden" name="password" value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>" />
                <label for="otp">Enter OTP:</label>
                <input type="text" id="otp" name="otp" required />

                <button type="submit">Verify OTP</button>
                <button type="submit" name="resend_otp" value="1" style="margin-left: 10px;">Resend OTP</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
