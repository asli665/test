<?php

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$showOtpForm = false;
$message = '';

define('USER_FILE', __DIR__ . DIRECTORY_SEPARATOR . 'users.txt');

function getOtpFilePath($username) {
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . "otp_{$username}.txt";
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

$users = readUsers();

if (!empty($_POST)) {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $otp = $_POST['otp'] ?? '';
        $userType = $_POST['user_type'] ?? '';

        // Common fields
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $birthday = $_POST['birthday'] ?? '';

        // Additional fields
        $lastName = trim($_POST['last_name'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $bodyNumber = trim($_POST['body_number'] ?? '');
        $numTricycles = trim($_POST['num_tricycles'] ?? '');
        $driversNames = trim($_POST['drivers_names'] ?? '');
        $operatorName = trim($_POST['operator_name'] ?? '');

        // File uploads
        $proofOfEmployment = $_FILES['proof_of_employment'] ?? null;
        $orcrPicture = $_FILES['orcr_picture'] ?? null;
        $todaIdPicture = $_FILES['toda_id_picture'] ?? null;
        $userPicture = $_FILES['user_picture'] ?? null;

        if (!$otp) {
            // Validate required fields based on user type
            $errors = [];

            if (!$userType) {
                $errors[] = "User type is required.";
            }
            if (!$birthday) {
                $errors[] = "Birthday is required.";
            }
            if ($userType === 'official') {
                if (!$proofOfEmployment || $proofOfEmployment['error'] === UPLOAD_ERR_NO_FILE) {
                    $errors[] = "Proof of employment is required for officials.";
                }
                if (!$lastName) {
                    $errors[] = "Last name is required for officials.";
                }
                if (!$firstName) {
                    $errors[] = "First name is required for officials.";
                }
                if (!$bodyNumber) {
                    $errors[] = "Body number of tricycle is required for officials.";
                }
                if (!$orcrPicture || $orcrPicture['error'] === UPLOAD_ERR_NO_FILE) {
                    $errors[] = "ORCR picture is required for officials.";
                }
                if (!$phone) {
                    $errors[] = "Phone number is required for officials.";
                }
            } elseif ($userType === 'operator') {
                if (!$numTricycles) {
                    $errors[] = "Number of tricycles owned is required for operators.";
                }
                if (!$driversNames) {
                    $errors[] = "Drivers names are required for operators.";
                }
                if (!$orcrPicture || $orcrPicture['error'] === UPLOAD_ERR_NO_FILE) {
                    $errors[] = "ORCR copy is required for operators.";
                }
                if (!$phone) {
                    $errors[] = "Phone number is required for operators.";
                }
            } elseif ($userType === 'driver') {
                if (!$userPicture || $userPicture['error'] === UPLOAD_ERR_NO_FILE) {
                    $errors[] = "Picture is required for drivers.";
                }
                if (!$lastName) {
                    $errors[] = "Last name is required for drivers.";
                }
                if (!$firstName) {
                    $errors[] = "First name is required for drivers.";
                }
                if (!$todaIdPicture || $todaIdPicture['error'] === UPLOAD_ERR_NO_FILE) {
                    $errors[] = "TODA ID picture is required for drivers.";
                }
                if (!$operatorName) {
                    $errors[] = "Operator name is required for drivers.";
                }
                if (!$orcrPicture || $orcrPicture['error'] === UPLOAD_ERR_NO_FILE) {
                    $errors[] = "ORCR copy is required for drivers.";
                }
                if (!$phone) {
                    $errors[] = "Phone number is required for drivers.";
                }
            } elseif ($userType === 'passenger') {
                if (!$lastName) {
                    $errors[] = "Last name is required for passengers.";
                }
                if (!$firstName) {
                    $errors[] = "First name is required for passengers.";
                }
                if (!$address) {
                    $errors[] = "Address is required for passengers.";
                }
                if (!$phone) {
                    $errors[] = "Phone number is required for passengers.";
                }
            }
            if (!$username || !$email || !$password) {
                $errors[] = "Username, email, and password are required.";
            }
            if (!empty($errors)) {
                $message = "Please correct the following errors:<br>" . implode("<br>", $errors);
            } elseif (isset($users[$username])) {
                $message = "Username already exists.";
            } else {
                // Handle file uploads
                $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $proofOfEmploymentPath = '';
                $orcrPicturePath = '';
                $todaIdPicturePath = '';
                $userPicturePath = '';

                if ($proofOfEmployment && $proofOfEmployment['error'] === UPLOAD_ERR_OK) {
                    $proofOfEmploymentPath = $uploadDir . DIRECTORY_SEPARATOR . basename($proofOfEmployment['name']);
                    move_uploaded_file($proofOfEmployment['tmp_name'], $proofOfEmploymentPath);
                }
                if ($orcrPicture && $orcrPicture['error'] === UPLOAD_ERR_OK) {
                    $orcrPicturePath = $uploadDir . DIRECTORY_SEPARATOR . basename($orcrPicture['name']);
                    move_uploaded_file($orcrPicture['tmp_name'], $orcrPicturePath);
                }
                if ($todaIdPicture && $todaIdPicture['error'] === UPLOAD_ERR_OK) {
                    $todaIdPicturePath = $uploadDir . DIRECTORY_SEPARATOR . basename($todaIdPicture['name']);
                    move_uploaded_file($todaIdPicture['tmp_name'], $todaIdPicturePath);
                }
                if ($userPicture && $userPicture['error'] === UPLOAD_ERR_OK) {
                    $userPicturePath = $uploadDir . DIRECTORY_SEPARATOR . basename($userPicture['name']);
                    move_uploaded_file($userPicture['tmp_name'], $userPicturePath);
                }

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
            }
            else {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $userType = $_POST['user_type'] ?? '';
            $lastName = trim($_POST['last_name'] ?? '');
            $firstName = trim($_POST['first_name'] ?? '');
            $middleName = trim($_POST['middle_name'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $bodyNumber = trim($_POST['body_number'] ?? '');
            $numTricycles = trim($_POST['num_tricycles'] ?? '');
            $driversNames = trim($_POST['drivers_names'] ?? '');
            $operatorName = trim($_POST['operator_name'] ?? '');
            $proofOfEmploymentPath = $_POST['proof_of_employment_path'] ?? '';
            $orcrPicturePath = $_POST['orcr_picture_path'] ?? '';
            $todaIdPicturePath = $_POST['toda_id_picture_path'] ?? '';
            $userPicturePath = $_POST['user_picture_path'] ?? '';

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
                        'approved' => false,
                        'user_type' => $userType,
                        'last_name' => $lastName,
                        'first_name' => $firstName,
                        'middle_name' => $middleName,
                        'address' => $address,
                        'birthday' => $birthday,
                        'body_number' => $bodyNumber,
                        'num_tricycles' => $numTricycles,
                        'drivers_names' => $driversNames,
                        'operator_name' => $operatorName,
                        'proof_of_employment_path' => $proofOfEmploymentPath,
                        'orcr_picture_path' => $orcrPicturePath,
                        'toda_id_picture_path' => $todaIdPicturePath,
                        'user_picture_path' => $userPicturePath
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
                <p style="font-size: 0.9em; color: #555; margin-top: 5px;">
                    Please select your user type and fill in the corresponding fields below before submitting the form.
                </p>

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

                <div id="official_fields" style="display:<?php echo (($_POST['user_type'] ?? '') === 'official') ? 'block' : 'none'; ?>;">
                    <label for="proof_of_employment">Proof of Employment (Picture):</label>
                    <input type="file" id="proof_of_employment" name="proof_of_employment" accept="image/*" />

                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required />

                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required />

                    <label for="middle_name">Middle Name (Optional):</label>
                    <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>" />

                    <label for="body_number">Body Number of Tricycle:</label>
                    <input type="text" id="body_number" name="body_number" value="<?php echo htmlspecialchars($_POST['body_number'] ?? ''); ?>" required />

                    <label for="orcr_picture">ORCR Picture:</label>
                    <input type="file" id="orcr_picture" name="orcr_picture" accept="image/*" />
                </div>

                <div id="operator_fields" style="display:<?php echo (($_POST['user_type'] ?? '') === 'operator') ? 'block' : 'none'; ?>;">
                    <label for="num_tricycles">Number of Tricycles Owned:</label>
                    <input type="number" id="num_tricycles" name="num_tricycles" value="<?php echo htmlspecialchars($_POST['num_tricycles'] ?? ''); ?>" />

                    <label for="drivers_names">Drivers Names:</label>
                    <input type="text" id="drivers_names" name="drivers_names" value="<?php echo htmlspecialchars($_POST['drivers_names'] ?? ''); ?>" />

                    <label for="orcr_picture">ORCR Copy:</label>
                    <input type="file" id="orcr_picture" name="orcr_picture" accept="image/*" />
                </div>

                <div id="driver_fields" style="display:<?php echo (($_POST['user_type'] ?? '') === 'driver') ? 'block' : 'none'; ?>;">
                    <label for="user_picture">Picture:</label>
                    <input type="file" id="user_picture" name="user_picture" accept="image/*" />

                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required />

                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required />

                    <label for="middle_name">Middle Name (Optional):</label>
                    <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>" />

                    <label for="toda_id_picture">TODA ID Picture:</label>
                    <input type="file" id="toda_id_picture" name="toda_id_picture" accept="image/*" />

                    <label for="operator_name">Operator Name:</label>
                    <input type="text" id="operator_name" name="operator_name" value="<?php echo htmlspecialchars($_POST['operator_name'] ?? ''); ?>" />

                    <label for="orcr_picture">ORCR Copy:</label>
                    <input type="file" id="orcr_picture" name="orcr_picture" accept="image/*" />
                </div>

                <div id="passenger_fields" style="display:<?php echo (($_POST['user_type'] ?? '') === 'passenger') ? 'block' : 'none'; ?>;">
                    <label for="user_picture">Picture (Optional):</label>
                    <input type="file" id="user_picture" name="user_picture" accept="image/*" />

                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required />

                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required />

                    <label for="middle_name">Middle Name (Optional):</label>
                    <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>" />

                    <label for="address">Address:</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" />

                    <label for="phone">Phone Number:</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" />
                </div>

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
                <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" />
                <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" />
                <input type="hidden" name="middle_name" value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>" />
                <input type="hidden" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" />
                <input type="hidden" name="body_number" value="<?php echo htmlspecialchars($_POST['body_number'] ?? ''); ?>" />
                <input type="hidden" name="num_tricycles" value="<?php echo htmlspecialchars($_POST['num_tricycles'] ?? ''); ?>" />
                <input type="hidden" name="drivers_names" value="<?php echo htmlspecialchars($_POST['drivers_names'] ?? ''); ?>" />
                <input type="hidden" name="operator_name" value="<?php echo htmlspecialchars($_POST['operator_name'] ?? ''); ?>" />
                <input type="hidden" name="proof_of_employment_path" value="<?php echo htmlspecialchars($_POST['proof_of_employment_path'] ?? ''); ?>" />
                <input type="hidden" name="orcr_picture_path" value="<?php echo htmlspecialchars($_POST['orcr_picture_path'] ?? ''); ?>" />
                <input type="hidden" name="toda_id_picture_path" value="<?php echo htmlspecialchars($_POST['toda_id_picture_path'] ?? ''); ?>" />
                <input type="hidden" name="user_picture_path" value="<?php echo htmlspecialchars($_POST['user_picture_path'] ?? ''); ?>" />
                <label for="otp">Enter OTP:</label>
                <input type="text" id="otp" name="otp" required />

                <button type="submit">Verify OTP</button>
            </form>
        <?php endif; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const userTypeSelect = document.getElementById('user_type');
            const userTypeFields = {
                official: document.getElementById('official_fields'),
                operator: document.getElementById('operator_fields'),
                driver: document.getElementById('driver_fields'),
                passenger: document.getElementById('passenger_fields')
            };

            function updateUserTypeFields() {
                const selectedType = userTypeSelect.value;
                for (const type in userTypeFields) {
                    if (type === selectedType) {
                        userTypeFields[type].style.display = 'block';
                    } else {
                        userTypeFields[type].style.display = 'none';
                    }
                }
            }

            userTypeSelect.addEventListener('change', updateUserTypeFields);

            // Initialize on page load
            updateUserTypeFields();
        });
    </script>
</body>
</html>
