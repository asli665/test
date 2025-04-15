<?php
require_once 'session_manager.php';

define('USER_FILE', __DIR__ . DIRECTORY_SEPARATOR . 'users.txt');

$sessionManager = new SessionManager();

function readUsers() {
    $users = [];
    if (file_exists(USER_FILE)) {
        $lines = file(USER_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            list($username, $email, $phone, $passwordHash, $verified, $approved) = explode(',', $line);
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

// Check if user is logged in and is admin
$sessionId = $_COOKIE['RangantodappSession'] ?? null;
if (!$sessionId) {
    header("Location: login.php");
    exit();
}
$sessionData = $sessionManager->getSession($sessionId);
if (!$sessionData) {
    header("Location: login.php");
    exit();
}
$username = $sessionData['username'];

$adminUsers = ['ADMIN'];
if (!in_array($username, $adminUsers)) {
    echo "Access denied. You are not an admin.";
    exit();
}

$users = readUsers();

if (!empty($_POST)) {
    $action = $_POST['action'] ?? '';
    $targetUser = $_POST['username'] ?? '';

    if ($action && $targetUser && isset($users[$targetUser])) {
        if ($action === 'approve') {
            $users[$targetUser]['approved'] = true;
        } elseif ($action === 'reject') {
            unset($users[$targetUser]);
        }
        writeUsers($users);
        header("Location: admin_approval.php");
        exit();
    }
}

// Filter users pending approval
$pendingUsers = array_filter($users, function($user) {
    return $user['verified'] && !$user['approved'];
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Approval - Rangantodapp</title>
    <link rel="stylesheet" href="rangantodapp.css" />
</head>
<body class="admin-approval">
    <header class="admin-header">
        <img src="img/datodalogo.jpg" alt="DATODA Logo" />
        <h1>DATODA Admin Approval</h1>
    </header>
    <p class="logged-in">Logged in as: <?php echo htmlspecialchars($username); ?> | <a class="logout-link" href="login.php?action=logout">Logout</a></p>

    <?php if (empty($pendingUsers)): ?>
        <p>No users pending approval.</p>
    <?php else: ?>
        <table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingUsers as $uname => $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($uname); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($uname); ?>" />
                                <button type="submit" name="action" value="approve">Approve</button>
                            </form>
                            <form method="POST" style="display:inline; margin-left: 10px;">
                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($uname); ?>" />
                                <button type="submit" name="action" value="reject" onclick="return confirm('Are you sure you want to reject this user?');">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
