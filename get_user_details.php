<?php
require_once 'session_manager.php';

define('USER_FILE', __DIR__ . DIRECTORY_SEPARATOR . 'users.txt');

$sessionManager = new SessionManager();

// Check if user is logged in and is admin
$sessionId = $_COOKIE['RangantodappSession'] ?? null;
if (!$sessionId) {
    echo "Access denied.";
    exit();
}
$sessionData = $sessionManager->getSession($sessionId);
if (!$sessionData) {
    echo "Access denied.";
    exit();
}
$username = $sessionData['username'];

$adminUsers = ['ADMIN'];
if (!in_array($username, $adminUsers)) {
    echo "Access denied. You are not an admin.";
    exit();
}

// Get requested username
$requestedUsername = $_GET['username'] ?? '';
if (!$requestedUsername) {
    echo "No username specified.";
    exit();
}

// Read user data
function readUsers() {
    $users = [];
    if (file_exists(USER_FILE)) {
        $lines = file(USER_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(',', $line);
            $username = $parts[0] ?? '';
            
            // Create user entry with all possible fields
            $users[$username] = [
                'email' => $parts[1] ?? '',
                'phone' => $parts[2] ?? '',
                'password' => $parts[3] ?? '',
                'verified' => ($parts[4] ?? '0') === '1',
                'approved' => ($parts[5] ?? '0') === '1',
                'user_type' => $parts[6] ?? '',
                'last_name' => $parts[7] ?? '',
                'first_name' => $parts[8] ?? '',
                'middle_name' => $parts[9] ?? '',
                'address' => $parts[10] ?? '',
                'birthday' => $parts[11] ?? '',
                'body_number' => $parts[12] ?? '',
                'num_tricycles' => $parts[13] ?? '',
                'drivers_names' => $parts[14] ?? '',
                'operator_name' => $parts[15] ?? '',
                'proof_of_employment_path' => $parts[16] ?? '',
                'orcr_picture_path' => $parts[17] ?? '',
                'toda_id_picture_path' => $parts[18] ?? '',
                'user_picture_path' => $parts[19] ?? ''
            ];
        }
    }
    return $users;
}

$users = readUsers();

// Check if user exists
if (!isset($users[$requestedUsername])) {
    echo "User not found.";
    exit();
}

$user = $users[$requestedUsername];

// Display user details
?>
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 10px;">
    <div><strong>Username:</strong></div>
    <div><?php echo htmlspecialchars($requestedUsername); ?></div>
    
    <div><strong>Email:</strong></div>
    <div><?php echo htmlspecialchars($user['email']); ?></div>
    
    <div><strong>Phone:</strong></div>
    <div><?php echo htmlspecialchars($user['phone']); ?></div>
    
    <div><strong>User Type:</strong></div>
    <div><?php echo htmlspecialchars($user['user_type']); ?></div>
    
    <div><strong>Name:</strong></div>
    <div>
        <?php 
            $name = [];
            if (!empty($user['first_name'])) $name[] = htmlspecialchars($user['first_name']);
            if (!empty($user['middle_name'])) $name[] = htmlspecialchars($user['middle_name']);
            if (!empty($user['last_name'])) $name[] = htmlspecialchars($user['last_name']);
            echo implode(' ', $name);
        ?>
    </div>
    
    <div><strong>Address:</strong></div>
    <div><?php echo htmlspecialchars($user['address']); ?></div>
    
    <div><strong>Birthday:</strong></div>
    <div><?php echo htmlspecialchars($user['birthday']); ?></div>
    
    <?php if (!empty($user['body_number'])): ?>
        <div><strong>Body Number:</strong></div>
        <div><?php echo htmlspecialchars($user['body_number']); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($user['num_tricycles'])): ?>
        <div><strong>Number of Tricycles:</strong></div>
        <div><?php echo htmlspecialchars($user['num_tricycles']); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($user['drivers_names'])): ?>
        <div><strong>Drivers Names:</strong></div>
        <div><?php echo htmlspecialchars($user['drivers_names']); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($user['operator_name'])): ?>
        <div><strong>Operator Name:</strong></div>
        <div><?php echo htmlspecialchars($user['operator_name']); ?></div>
    <?php endif; ?>
</div>

<h3>Uploaded Documents</h3>
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <?php if (!empty($user['proof_of_employment_path'])): ?>
        <div>
            <strong>Proof of Employment:</strong><br>
            <img src="<?php echo htmlspecialchars($user['proof_of_employment_path']); ?>" style="max-width: 100%; max-height: 200px;">
        </div>
    <?php endif; ?>
    
    <?php if (!empty($user['orcr_picture_path'])): ?>
        <div>
            <strong>ORCR Picture:</strong><br>
            <img src="<?php echo htmlspecialchars($user['orcr_picture_path']); ?>" style="max-width: 100%; max-height: 200px;">
        </div>
    <?php endif; ?>
    
    <?php if (!empty($user['toda_id_picture_path'])): ?>
        <div>
            <strong>TODA ID Picture:</strong><br>
            <img src="<?php echo htmlspecialchars($user['toda_id_picture_path']); ?>" style="max-width: 100%; max-height: 200px;">
        </div>
    <?php endif; ?>
    
    <?php if (!empty($user['user_picture_path'])): ?>
        <div>
            <strong>User Picture:</strong><br>
            <img src="<?php echo htmlspecialchars($user['user_picture_path']); ?>" style="max-width: 100%; max-height: 200px;">
        </div>
    <?php endif; ?>
</div>