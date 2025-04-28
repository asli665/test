<?php
require 'vendor/autoload.php';
require_once 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$showOtpForm = false;
$message = '';

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
        echo "<p style='color:red;'>Mailer Error: {$mail->ErrorInfo}</p>";
        return false;
    }
}

$conn = $GLOBALS['conn'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p>Form submitted</p>"; // Debug output

    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $otp = $_POST['otp'] ?? '';
        $userType = $_POST['user_type'] ?? '';

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $birthday = $_POST['birthday'] ?? '';
        $lastName = trim($_POST['last_name'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');

        $resendOtp = $_POST['resend_otp'] ?? '';

        if ($resendOtp === '1' && $username && $email) {
            // Resend OTP logic
            $sql = "SELECT otp_code, created_at FROM user_otps WHERE username = ? ORDER BY created_at DESC LIMIT 1";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $existingOtp, $createdAt);
            $otpFound = mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            $sendNewOtp = true;
            if ($otpFound) {
                $otpAge = time() - strtotime($createdAt);
                if ($otpAge <= 300) {
                    $otp = $existingOtp;
                    $sendNewOtp = false;
                }
            }

            if ($sendNewOtp) {
                $otp = '';
                for ($i = 0; $i < 6; $i++) {
                    $otp .= rand(0, 9);
                }
                $sql = "INSERT INTO user_otps (username, otp_code) VALUES (?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ss", $username, $otp);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            if (sendOtpEmail($email, $otp)) {
                $message = "OTP resent to your email. Please verify.";
                $showOtpForm = true;
            } else {
                $message = "Failed to resend OTP email. Please try again.";
                $showOtpForm = true;
            }
        } elseif (!$otp) {
            $errors = [];

            if (!$userType) {
                $errors[] = "User type is required.";
            }
            if (!$birthday) {
                $errors[] = "Birthday is required.";
            }
            if (!$username || !$email || !$password) {
                $errors[] = "Username, email, and password are required.";
            }
            if (!empty($errors)) {
                $message = "Please correct the following errors:<br>" . implode("<br>", $errors);
            } else {
                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = "Invalid email format. Please enter a valid email address.";
                } else {
                    // Handle file upload if provided
                    $userPicturePath = null;
                    if (isset($_FILES['user_picture']) && $_FILES['user_picture']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = 'img/uploads/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        $fileTmpPath = $_FILES['user_picture']['tmp_name'];
                        $fileName = basename($_FILES['user_picture']['name']);
                        $fileSize = $_FILES['user_picture']['size'];
                        $fileType = $_FILES['user_picture']['type'];
                        $fileNameCmps = explode(".", $fileName);
                        $fileExtension = strtolower(end($fileNameCmps));

                        $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                        if (in_array($fileExtension, $allowedfileExtensions)) {
                            $newFileName = $username . '_' . time() . '.' . $fileExtension;
                            $destPath = $uploadDir . $newFileName;

                            if (move_uploaded_file($fileTmpPath, $destPath)) {
                                $userPicturePath = $destPath;
                            } else {
                                $message = "There was an error moving the uploaded file.";
                            }
                        } else {
                            $message = "Upload failed. Allowed file types: " . implode(", ", $allowedfileExtensions);
                        }
                    }

                    $sql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "s", $username);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_bind_result($stmt, $usernameCount);
                    mysqli_stmt_fetch($stmt);
                    mysqli_stmt_close($stmt);

                    /* 
                    // Temporarily disable email uniqueness check to allow duplicate emails for testing
                    $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "s", $email);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_bind_result($stmt, $emailCount);
                    mysqli_stmt_fetch($stmt);
                    mysqli_stmt_close($stmt);
                    */

                    if ($usernameCount > 0) {
                        $message = "Username already exists.";
                    /*
                    } elseif ($emailCount > 0) {
                        $message = "Email already exists.";
                    */
                    } else {
                        $otp = '';
                        for ($i = 0; $i < 6; $i++) {
                            $otp .= rand(0, 9);
                        }

                        $sql = "SELECT otp_code, created_at FROM user_otps WHERE username = ? ORDER BY created_at DESC LIMIT 1";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "s", $username);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_bind_result($stmt, $existingOtp, $createdAt);
                        $otpFound = mysqli_stmt_fetch($stmt);
                        mysqli_stmt_close($stmt);

                        $sendNewOtp = true;
                        if ($otpFound) {
                            $otpAge = time() - strtotime($createdAt);
                            if ($otpAge <= 300) {
                                $otp = $existingOtp;
                                $sendNewOtp = false;
                            }
                        }

                        if ($sendNewOtp) {
                            $sql = "INSERT INTO user_otps (username, otp_code) VALUES (?, ?)";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "ss", $username, $otp);
                            mysqli_stmt_execute($stmt);
                            mysqli_stmt_close($stmt);
                        }

                        if (sendOtpEmail($email, $otp)) {
                            $showOtpForm = true;
                            $message = "OTP sent to your email. Please verify.";
                        } else {
                            $message = "Failed to send OTP email. Please try again.";
                        }
                    }
                }
            }
        } else {
            $sql = "SELECT otp_code FROM user_otps WHERE username = ? ORDER BY created_at DESC LIMIT 1";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $savedOtp);
            $otpFound = mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if (!$otpFound) {
                $message = "No OTP found. Please register again.";
                $showOtpForm = false;
            } else {
if ($otp === $savedOtp) {

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, phone, password, verified, approved, user_type, birthday, user_picture_path, last_name, first_name, middle_name) VALUES (?, ?, ?, ?, 1, 0, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    $userPicturePathParam = $userPicturePath ?? '';
    if (!$stmt) {
        error_log("MySQL prepare failed: " . mysqli_error($conn));
        echo "<p>Database error: failed to prepare statement.</p>";
    } else {
        mysqli_stmt_bind_param($stmt, "ssssssssss", $username, $email, $phone, $passwordHash, $userType, $birthday, $userPicturePathParam, $lastName, $firstName, $middleName);
        $execResult = mysqli_stmt_execute($stmt);
        if (!$execResult) {
            error_log("MySQL execute failed: " . mysqli_stmt_error($stmt));
            echo "<p>Database error: failed to execute statement.</p>";
        }
        mysqli_stmt_close($stmt);
    }

                    // Log registration action to activity_logs table
                    $logAction = "User '{$username}' registered an account.";
                    $logSql = "INSERT INTO activity_logs (username, action) VALUES (?, ?)";
                    $logStmt = mysqli_prepare($conn, $logSql);
                    mysqli_stmt_bind_param($logStmt, "ss", $username, $logAction);
                    mysqli_stmt_execute($logStmt);
                    mysqli_stmt_close($logStmt);

                    $sql = "DELETE FROM user_otps WHERE username = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "s", $username);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    $message = "Registration successful. You can now log in.";
                    $showOtpForm = false;

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
<body class="register-page">
    <div class="container">
        <div class="logo-container">
            <img src="img/datodalogo.jpg" alt="DATODA Logo" />
            <h1>DATODA</h1>
        </div>
        <h2>Register</h2>
        <?php if ($message): ?>
            <p class="error-message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if (!$showOtpForm): ?>
<form method="POST" action="register.php" enctype="multipart/form-data">
    <input type="hidden" name="action" value="register" />

    <label for="user_type">User Type:</label>
    <select id="user_type" name="user_type" required>
        <option value="">Select user type</option>
        <option value="official" <?php if (($_POST['user_type'] ?? '') === 'official') echo 'selected'; ?>>Official</option>
        <option value="operator" <?php if (($_POST['user_type'] ?? '') === 'operator') echo 'selected'; ?>>Operator</option>
        <option value="driver" <?php if (($_POST['user_type'] ?? '') === 'driver') echo 'selected'; ?>>Driver</option>
        <option value="passenger" <?php if (($_POST['user_type'] ?? '') === 'passenger') echo 'selected'; ?>>Passenger</option>
    </select>

    <label for="last_name">Last Name:</label>
    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required />

    <label for="first_name">First Name:</label>
    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required />

    <label for="middle_name">Middle Name:</label>
    <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>" />

    <label for="username">Username:</label>
    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required />

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required />

    <label for="phone">Phone Number:</label>
    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required />

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required />

    <label for="birthday">Birthday:</label>
    <input type="date" id="birthday" name="birthday" value="<?php echo htmlspecialchars($_POST['birthday'] ?? ''); ?>" required />

    <label for="user_picture">Profile Picture:</label>
    <input type="file" id="user_picture" name="user_picture" accept="image/*" />

    <button type="submit">Register</button>
</form>
            <p>Already have an account? <a href="login.php">Login here</a></p>
        <?php else: ?>
            <form method="POST" action="register.php">
                <input type="hidden" name="action" value="register" />
                <input type="hidden" name="user_type" value="<?php echo htmlspecialchars($_POST['user_type'] ?? ''); ?>" />
                <input type="hidden" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" />
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" />
                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" />
                <input type="hidden" name="password" value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>" />
                <input type="hidden" name="birthday" value="<?php echo htmlspecialchars($_POST['birthday'] ?? ''); ?>" />
                <label for="otp">Enter OTP:</label>
                <input type="text" id="otp" name="otp" required />

                <button type="submit">Verify OTP</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
