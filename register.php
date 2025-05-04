 <?php
session_start();
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
    ob_start(); // Start output buffering to prevent headers sent errors

    // Comment out or remove debug echo to prevent output before header()
    // echo "<p>Form submitted</p>"; // Debug output

    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        // Debug log raw POST data keys and values
        error_log("Debug: Raw POST data: " . print_r($_POST, true));

        $otp = $_POST['otp'] ?? '';
        $userType = $_POST['user_type'] ?? '';

        // On OTP verification POST, update session file path variables from hidden inputs to persist them
        if (!empty($otp)) {
            $_SESSION['user_picture_path'] = $_POST['user_picture_path'] ?? $_SESSION['user_picture_path'] ?? null;
            $_SESSION['orcr_picture_path'] = $_POST['orcr_picture_path'] ?? $_SESSION['orcr_picture_path'] ?? null;
            $_SESSION['toda_id_picture_path'] = $_POST['toda_id_picture_path'] ?? $_SESSION['toda_id_picture_path'] ?? null;
            $_SESSION['proof_of_employment_path'] = $_POST['proof_of_employment_path'] ?? $_SESSION['proof_of_employment_path'] ?? null;
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $birthday = $_POST['birthday'] ?? '';
        $lastName = trim($_POST['last_name'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');

        // Initialize variables to avoid undefined warnings
        $bodyNumber = trim($_POST['body_number'] ?? '');
        $numTricycles = intval($_POST['num_tricycles'] ?? 0);
        $driversNamesArray = $_POST['drivers_names'] ?? [];
        if (is_array($driversNamesArray)) {
            $driversNames = implode("\n", array_map('trim', $driversNamesArray));
        } else {
            $driversNames = trim($driversNamesArray);
        }
        $operatorName = isset($_POST['operator_name']) ? trim($_POST['operator_name']) : '';

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
                $orcrPicturePath = null;
                $todaIdPicturePath = null;
                $proofOfEmploymentPath = null;

                // Store all relevant form input fields in session
                $_SESSION['username'] = trim($_POST['username'] ?? '');
                $_SESSION['email'] = trim($_POST['email'] ?? '');
                $_SESSION['phone'] = trim($_POST['phone'] ?? '');
                $_SESSION['password'] = $_POST['password'] ?? '';
                $_SESSION['birthday'] = $_POST['birthday'] ?? '';
                $_SESSION['last_name'] = trim($_POST['last_name'] ?? '');
                $_SESSION['first_name'] = trim($_POST['first_name'] ?? '');
                $_SESSION['middle_name'] = trim($_POST['middle_name'] ?? '');
                $_SESSION['user_type'] = $_POST['user_type'] ?? '';

                // User-type specific fields
                $_SESSION['body_number'] = trim($_POST['body_number'] ?? '');
                $_SESSION['num_tricycles'] = intval($_POST['num_tricycles'] ?? 0);
                if (isset($_POST['drivers_names']) && is_array($_POST['drivers_names'])) {
                    $_SESSION['drivers_names'] = implode("\n", array_map('trim', $_POST['drivers_names']));
                } else {
                    $_SESSION['drivers_names'] = trim($_POST['drivers_names'] ?? '');
                }

                $bodyNumber = $_SESSION['body_number'];
                $numTricycles = $_SESSION['num_tricycles'];
                $driversNames = $_SESSION['drivers_names'];
                $operatorName = $_SESSION['operator_name'] ?? '';

                $username = $_SESSION['username'];
                $email = $_SESSION['email'];
                $phone = $_SESSION['phone'];
                $password = $_SESSION['password'];
                $birthday = $_SESSION['birthday'];
                $lastName = $_SESSION['last_name'];
                $firstName = $_SESSION['first_name'];
                $middleName = $_SESSION['middle_name'];
                $userType = $_SESSION['user_type'];

                // Handle file uploads after session variables are set
                error_log("Debug: \$_FILES contents: " . print_r($_FILES, true));
                $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif', 'jfif'];
                $uploadDir = __DIR__ . '/img/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                if (isset($_FILES['user_picture'])) {
                    error_log("Debug: user_picture error code: " . $_FILES['user_picture']['error'] . ", size: " . $_FILES['user_picture']['size']);
                }
                if (isset($_FILES['orcr_picture'])) {
                    error_log("Debug: orcr_picture error code: " . $_FILES['orcr_picture']['error'] . ", size: " . $_FILES['orcr_picture']['size']);
                }
                if (isset($_FILES['toda_id_picture'])) {
                    error_log("Debug: toda_id_picture error code: " . $_FILES['toda_id_picture']['error'] . ", size: " . $_FILES['toda_id_picture']['size']);
                }
                if (isset($_FILES['proof_of_employment'])) {
                    error_log("Debug: proof_of_employment error code: " . $_FILES['proof_of_employment']['error'] . ", size: " . $_FILES['proof_of_employment']['size']);
                }

                if (isset($_FILES['user_picture']) && $_FILES['user_picture']['error'] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES['user_picture']['tmp_name'];
                    $fileName = basename($_FILES['user_picture']['name']);
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    if (in_array($fileExtension, $allowedfileExtensions)) {
                        $newFileName = $username . '_' . uniqid() . '.' . $fileExtension;
                        $destPath = $uploadDir . $newFileName;

                        if (move_uploaded_file($fileTmpPath, $destPath)) {
                            $userPicturePath = 'img/uploads/' . $newFileName;
                            $_SESSION['user_picture_path'] = $userPicturePath;
                            error_log("Debug: userPicturePath set to '$userPicturePath'");
                        } else {
                            $message = "There was an error moving the uploaded user picture file.";
                        }
                    } else {
                        $message = "Upload failed for user picture. Allowed file types: " . implode(", ", $allowedfileExtensions);
                    }
                }

                if (isset($_FILES['orcr_picture']) && $_FILES['orcr_picture']['error'] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES['orcr_picture']['tmp_name'];
                    $fileName = basename($_FILES['orcr_picture']['name']);
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    if (in_array($fileExtension, $allowedfileExtensions)) {
                        $newFileName = $username . '_orcr_' . uniqid() . '.' . $fileExtension;
                        $destPath = $uploadDir . $newFileName;
                        if (move_uploaded_file($fileTmpPath, $destPath)) {
                            $orcrPicturePath = 'img/uploads/' . $newFileName;
                            $_SESSION['orcr_picture_path'] = $orcrPicturePath;
                        } else {
                            $message = "There was an error moving the ORCR picture file.";
                        }
                    } else {
                        $message = "Upload failed for ORCR picture. Allowed file types: " . implode(", ", $allowedfileExtensions);
                    }
                }

                if (isset($_FILES['toda_id_picture']) && $_FILES['toda_id_picture']['error'] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES['toda_id_picture']['tmp_name'];
                    $fileName = basename($_FILES['toda_id_picture']['name']);
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    if (in_array($fileExtension, $allowedfileExtensions)) {
                        $newFileName = $username . '_toda_id_' . uniqid() . '.' . $fileExtension;
                        $destPath = $uploadDir . $newFileName;
                        if (move_uploaded_file($fileTmpPath, $destPath)) {
                            $todaIdPicturePath = 'img/uploads/' . $newFileName;
                            $_SESSION['toda_id_picture_path'] = $todaIdPicturePath;
                        } else {
                            $message = "There was an error moving the TODA ID picture file.";
                        }
                    } else {
                        $message = "Upload failed for TODA ID picture. Allowed file types: " . implode(", ", $allowedfileExtensions);
                    }
                }

                if (isset($_FILES['proof_of_employment']) && $_FILES['proof_of_employment']['error'] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES['proof_of_employment']['tmp_name'];
                    $fileName = basename($_FILES['proof_of_employment']['name']);
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    if (in_array($fileExtension, $allowedfileExtensions)) {
                        $newFileName = $username . '_proof_employment_' . uniqid() . '.' . $fileExtension;
                        $destPath = $uploadDir . $newFileName;
                        if (move_uploaded_file($fileTmpPath, $destPath)) {
                            $proofOfEmploymentPath = 'img/uploads/' . $newFileName;
                        } else {
                            $message = "There was an error moving the proof of employment file.";
                        }
                    } else {
                        $message = "Upload failed for proof of employment. Allowed file types: " . implode(", ", $allowedfileExtensions);
                    }
                }

                // Store uploaded file paths in session after upload handling
                error_log("Debug: Storing user_picture_path in session: " . var_export($userPicturePath ?? null, true));
                $_SESSION['user_picture_path'] = $userPicturePath ?? null;
                $_SESSION['orcr_picture_path'] = $orcrPicturePath ?? null;
                $_SESSION['toda_id_picture_path'] = $todaIdPicturePath ?? null;
                $_SESSION['proof_of_employment_path'] = $proofOfEmploymentPath ?? null;

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

                    // Retrieve all relevant fields from session
                    $username = $_SESSION['username'] ?? '';
                    $email = $_SESSION['email'] ?? '';
                    $phone = $_SESSION['phone'] ?? '';
                    $password = $_SESSION['password'] ?? '';
                    $birthday = $_SESSION['birthday'] ?? '';
                    $lastName = $_SESSION['last_name'] ?? '';
                    $firstName = $_SESSION['first_name'] ?? '';
                    $middleName = $_SESSION['middle_name'] ?? '';
                    $userType = $_SESSION['user_type'] ?? '';
                    $userPicturePath = $_SESSION['user_picture_path'] ?? null;
                    $orcrPicturePath = $_SESSION['orcr_picture_path'] ?? null;
                    $todaIdPicturePath = $_SESSION['toda_id_picture_path'] ?? null;
                    $proofOfEmploymentPath = $_SESSION['proof_of_employment_path'] ?? null;

                    $bodyNumber = $_SESSION['body_number'] ?? '';
                    $numTricycles = $_SESSION['num_tricycles'] ?? 0;
                    $driversNames = $_SESSION['drivers_names'] ?? '';
                    $operatorName = $_SESSION['operator_name'] ?? '';

                    $sql = "INSERT INTO users (username, email, phone, password, verified, approved, user_type, birthday, body_number, num_tricycles, drivers_names, orcr_picture_path, toda_id_picture_path, proof_of_employment_path, user_picture_path, last_name, first_name, middle_name) VALUES (?, ?, ?, ?, 1, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $container = $conn->prepare($sql);
                    $userPicturePathParam = $userPicturePath ?? '';
                    $orcrPicturePathParam = $orcrPicturePath ?? '';
                    $todaIdPicturePathParam = $todaIdPicturePath ?? '';
                    $proofOfEmploymentPathParam = $proofOfEmploymentPath ?? '';
                    if (!$container) {
                        die("Prepare failed: " . $conn->error);
                    }

                    // Debugging statements to log values before insertion
                    error_log("Debug: lastName='$lastName', firstName='$firstName', middleName='$middleName', userPicturePathParam='$userPicturePathParam'");

                    // Additional debug logs for all relevant variables
                    error_log("Debug: bodyNumber='$bodyNumber', numTricycles='$numTricycles', driversNames='$driversNames'");
    error_log("Debug: orcrPicturePathParam='$orcrPicturePathParam', todaIdPicturePathParam='$todaIdPicturePathParam', proofOfEmploymentPathParam='$proofOfEmploymentPathParam'");

    // Convert empty strings to null for file paths
    $userPicturePathParam = !empty($userPicturePathParam) ? $userPicturePathParam : null;
    $orcrPicturePathParam = !empty($orcrPicturePathParam) ? $orcrPicturePathParam : null;
    $todaIdPicturePathParam = !empty($todaIdPicturePathParam) ? $todaIdPicturePathParam : null;
    $proofOfEmploymentPathParam = !empty($proofOfEmploymentPathParam) ? $proofOfEmploymentPathParam : null;

    // Dynamically build insert fields, placeholders, types, and values based on user type
    $fields = [
        'username' => $username,
        'email' => $email,
        'phone' => $phone,
        'password' => $passwordHash,
        'verified' => 1,
        'approved' => 0,
        'user_type' => $userType,
        'birthday' => $birthday,
        'last_name' => $lastName,
        'first_name' => $firstName,
        'middle_name' => $middleName,
        'user_picture_path' => $userPicturePathParam
    ];

    // Add user-type specific fields
    if ($userType === 'driver') {
        $fields['body_number'] = $bodyNumber;
        $fields['orcr_picture_path'] = $orcrPicturePathParam;
        $fields['toda_id_picture_path'] = $todaIdPicturePathParam;
        $fields['proof_of_employment_path'] = $proofOfEmploymentPathParam;
    } elseif ($userType === 'operator') {
        $fields['num_tricycles'] = $numTricycles;
        $fields['drivers_names'] = $driversNames;
        $fields['body_number'] = $bodyNumber;
        $fields['orcr_picture_path'] = $orcrPicturePathParam;
        $fields['toda_id_picture_path'] = $todaIdPicturePathParam;
        $fields['proof_of_employment_path'] = $proofOfEmploymentPathParam;
    }

    // Debug log the final fields array before insert
    error_log("Debug: Final fields array for insert: " . print_r($fields, true));

    // Build SQL parts
    $columns = array_keys($fields);
    $placeholders = implode(',', array_fill(0, count($columns), '?'));
    $sql = "INSERT INTO users (" . implode(',', $columns) . ") VALUES ($placeholders)";
    $container = $conn->prepare($sql);
    if (!$container) {
        die("Prepare failed: " . $conn->error);
    }

    // Build type string and values array
    $types = '';
    $values = [];
    foreach ($fields as $key => $value) {
        // Determine type: 'i' for integer, 's' for string
        if (in_array($key, ['num_tricycles'])) {
            $types .= 'i';
            $values[] = (int)$value;
        } elseif (in_array($key, ['verified', 'approved'])) {
            $types .= 'i';
            $values[] = (int)$value;
        } else {
            $types .= 's';
            $values[] = $value ?? '';
        }
    }

    // Bind parameters dynamically using call_user_func_array
    $bind_names[] = $types;
    for ($i=0; $i<count($values); $i++) {
        $bind_name = 'bind' . $i;
        $$bind_name = $values[$i];
        $bind_names[] = &$$bind_name;
    }

    $bindResult = call_user_func_array([$container, 'bind_param'], $bind_names);
    if (!$bindResult) {
        die("Bind param failed: " . $container->error);
    }
    $execResult = $container->execute();
    if (!$execResult) {
        die("Execute failed: " . $container->error);
    }
                    $container->close();

                    // Log registration action to activity_logs table
                    $logAction = "User '{$username}' registered an account.";
                    $logSql = "INSERT INTO activity_logs (username, action) VALUES (?, ?)";
                    $logStmt = mysqli_prepare($conn, $logSql);
                    mysqli_stmt_bind_param($logStmt, "ss", $username, $logAction);
                    mysqli_stmt_execute($logStmt);
                    mysqli_stmt_close($logStmt);

                    // Clear operator-specific session variables after successful registration
                    if ($userType === 'operator') {
                        unset($_SESSION['num_tricycles'], $_SESSION['drivers_names']);
                    }

                    // Clear all session variables used for registration
                    unset(
                        $_SESSION['username'], $_SESSION['email'], $_SESSION['phone'], $_SESSION['password'], $_SESSION['birthday'],
                        $_SESSION['last_name'], $_SESSION['first_name'], $_SESSION['middle_name'], $_SESSION['user_type'], $_SESSION['user_picture_path'],
                        $_SESSION['body_number'], $_SESSION['num_tricycles'], $_SESSION['drivers_names']
                    );

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
    <select id="user_type" name="user_type" required onchange="toggleAdditionalFields()">
        <option value="">Select user type</option>
        <option value="official" <?php if (($_POST['user_type'] ?? '') === 'official') echo 'selected'; ?>>Official</option>
        <option value="operator" <?php if (($_POST['user_type'] ?? '') === 'operator') echo 'selected'; ?>>Operator</option>
        <option value="driver" <?php if (($_POST['user_type'] ?? '') === 'driver') echo 'selected'; ?>>Driver</option>
        <option value="passenger" <?php if (($_POST['user_type'] ?? '') === 'passenger') echo 'selected'; ?>>Passenger</option>
    </select>

    <div id="driverFields" style="display:none; margin-top: 10px; display: flex; flex-direction: column; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <label for="body_number" style="width: 150px;">Body Number:</label>
            <input type="text" id="body_number" name="body_number" value="<?php echo htmlspecialchars($_POST['body_number'] ?? ''); ?>" style="flex: 1;" />
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <label for="orcr_picture" style="width: 150px;">ORCR Picture:</label>
            <input type="file" id="orcr_picture" name="orcr_picture" accept="image/*" style="flex: 1;" />
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <label for="toda_id_picture" style="width: 150px;">TODA ID Picture:</label>
            <input type="file" id="toda_id_picture" name="toda_id_picture" accept="image/*" style="flex: 1;" />
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <label for="proof_of_employment" style="width: 150px;">Proof of Employment:</label>
            <input type="file" id="proof_of_employment" name="proof_of_employment" accept="image/*" style="flex: 1;" />
        </div>
    </div>

    <div id="operatorFields" style="display:none; margin-top: 10px; display: flex; flex-direction: column; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <label for="num_tricycles" style="width: 150px;">Number of Tricycles:</label>
            <input type="number" id="num_tricycles" name="num_tricycles" min="0" value="<?php echo htmlspecialchars($_POST['num_tricycles'] ?? ''); ?>" style="flex: 1;" />
        </div>

        <div id="drivers_names_container" style="display: flex; flex-direction: column; gap: 5px;">
            <label>Driver's Name(s):</label>
            <?php
            $driversNamesArray = [];
            if (!empty($_POST['drivers_names'])) {
                $driversNamesArray = explode("\n", trim($_POST['drivers_names']));
            }
            if (empty($driversNamesArray)) {
                $driversNamesArray = [''];
            }
            foreach ($driversNamesArray as $index => $driverName) {
                echo '<input type="text" name="drivers_names[]" value="' . htmlspecialchars(trim($driverName)) . '" placeholder="Driver\'s Name ' . ($index + 1) . '" style="margin-bottom: 5px;" />';
            }
            ?>
        </div>
    </div>

    <script>
        function toggleAdditionalFields() {
            var userType = document.getElementById('user_type').value;
            document.getElementById('driverFields').style.display = (userType === 'driver' || userType === 'operator') ? 'block' : 'none';
            document.getElementById('operatorFields').style.display = (userType === 'operator') ? 'flex' : 'none';
        }

        function updateDriverNameFields() {
            var numTricyclesInput = document.getElementById('num_tricycles');
            var container = document.getElementById('drivers_names_container');
            var currentCount = container.querySelectorAll('input[name="drivers_names[]"]').length;
            var desiredCount = parseInt(numTricyclesInput.value) || 0;

            if (desiredCount < 0) desiredCount = 0;

            if (desiredCount > currentCount) {
                for (var i = currentCount; i < desiredCount; i++) {
                    var input = document.createElement('input');
                    input.type = 'text';
                    input.name = 'drivers_names[]';
                    input.placeholder = "Driver's Name " + (i + 1);
                    input.style.marginBottom = '5px';
                    container.appendChild(input);
                }
            } else if (desiredCount < currentCount) {
                for (var i = currentCount; i > desiredCount; i--) {
                    container.removeChild(container.lastElementChild);
                }
            }
        }

        document.getElementById('num_tricycles').addEventListener('input', updateDriverNameFields);

        window.onload = function() {
            toggleAdditionalFields();
            updateDriverNameFields();
        };
    </script>

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
                <input type="hidden" name="user_type" value="<?php echo htmlspecialchars($_SESSION['user_type'] ?? ''); ?>" />
                <input type="hidden" name="username" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" />
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" />
                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>" />
                <input type="hidden" name="password" value="<?php echo htmlspecialchars($_SESSION['password'] ?? ''); ?>" />
                <input type="hidden" name="birthday" value="<?php echo htmlspecialchars($_SESSION['birthday'] ?? ''); ?>" />
                <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($_SESSION['last_name'] ?? ''); ?>" />
                <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?>" />
                <input type="hidden" name="middle_name" value="<?php echo htmlspecialchars($_SESSION['middle_name'] ?? ''); ?>" />
                <input type="hidden" name="user_picture_path" value="<?php echo htmlspecialchars($_SESSION['user_picture_path'] ?? ''); ?>" />
                <input type="hidden" name="orcr_picture_path" value="<?php echo htmlspecialchars($_SESSION['orcr_picture_path'] ?? ''); ?>" />
                <input type="hidden" name="toda_id_picture_path" value="<?php echo htmlspecialchars($_SESSION['toda_id_picture_path'] ?? ''); ?>" />
                <input type="hidden" name="proof_of_employment_path" value="<?php echo htmlspecialchars($_SESSION['proof_of_employment_path'] ?? ''); ?>" />
                <label for="otp">Enter OTP:</label>
                <input type="text" id="otp" name="otp" required />

                <button type="submit">Verify OTP</button>
                <button type="submit" name="resend_otp" value="1" style="margin-left: 10px;">Resend OTP</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
