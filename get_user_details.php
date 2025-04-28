<?php
require_once 'session_manager.php';

require_once 'db.php';

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

$conn = $GLOBALS['conn'];

$sql = "SELECT username, email, phone, verified, approved, user_type, last_name, first_name, middle_name, address, birthday, body_number, num_tricycles, drivers_names, operator_name, proof_of_employment_path, orcr_picture_path, toda_id_picture_path, user_picture_path FROM users WHERE username = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $requestedUsername);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    echo "User not found.";
    exit();
}

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