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
    <title>Admin Dashboard - Rangantodapp</title>
    <link rel="stylesheet" href="rangantodapp.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        html, body {
            height: auto !important;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start !important;
            margin: 0;
            padding: 0;
        }
        body.admin-dashboard {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start !important;
        }
    </style>
</head>
<body class="admin-dashboard">
    <header class="admin-header">
        <h1 style="text-align: center; font-family: Arial, sans-serif; font-weight: bold; font-size: 2.5rem; margin: 20px 0;">RANGANTODAP</h1>
        <img src="img/datodalogo.jpg" alt="DATODA Logo" />
        <h1>DATODA Admin Dashboard</h1>
    </header>
    <p class="logged-in">Logged in as: <?php echo htmlspecialchars($username); ?> | <a class="logout-link" href="login.php?action=logout">Logout</a></p>

    <div style="display: flex; flex-direction: column; gap: 30px; max-width: 900px; margin: 20px auto 40px auto; align-items: stretch; justify-content: flex-start;">

        <section class="activity-log-section">
            <h2>Activity Log</h2>
            <div class="activity-log" style="max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; background: #f9f9f9;">
                <?php
                $logFile = __DIR__ . DIRECTORY_SEPARATOR . 'activity_log.txt';
                if (file_exists($logFile)) {
                    $logs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    if (empty($logs)) {
                        echo "<p>No activity logs available.</p>";
                    } else {
                        echo "<ul>";
                        foreach (array_reverse($logs) as $log) {
                            echo "<li>" . htmlspecialchars($log) . "</li>";
                        }
                        echo "</ul>";
                    }
                } else {
                    // Since user confirmed file exists, this else block is unlikely to be reached
                    echo "<p>Activity log file not found or inaccessible.</p>";
                }
                ?>
            </div>
        </section>

        <section class="user-info-section">
            <h2>User Information</h2>
            <?php if (empty($users)): ?>
                <p>No users found.</p>
            <?php else: ?>
                <table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Verified</th>
                            <th>Approved</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $uname => $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($uname); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td><?php echo $user['verified'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $user['approved'] ? 'Yes' : 'No'; ?></td>
                                <td>
                                    <?php if (!$user['approved']): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($uname); ?>" />
                                            <button type="submit" name="action" value="approve">Approve</button>
                                        </form>
                                        <form method="POST" style="display:inline; margin-left: 10px;">
                                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($uname); ?>" />
                                            <button type="submit" name="action" value="reject" onclick="return confirm('Are you sure you want to reject this user?');">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span>Approved</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section class="announcement-section" style="margin-top: 20px;">
            <h2>Add Announcement</h2>
            <?php
            $announcementFile = __DIR__ . DIRECTORY_SEPARATOR . 'announcements.txt';
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement'])) {
                $announcement = trim($_POST['announcement']);
                if ($announcement !== '') {
                    file_put_contents($announcementFile, date('Y-m-d H:i:s') . " - " . $announcement . PHP_EOL, FILE_APPEND);
                    header("Location: admin_approval.php");
                    exit();
                }
            }
            ?>
            <form method="POST" action="admin_approval.php">
                <textarea name="announcement" rows="3" cols="50" placeholder="Enter announcement here..." required></textarea><br />
                <button type="submit">Add Announcement</button>
            </form>
            <h3>Announcements</h3>
            <div class="announcements" style="max-height: 150px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; background: #f9f9f9;">
                <?php
                if (file_exists($announcementFile)) {
                    $announcements = file($announcementFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    if (empty($announcements)) {
                        echo "<p>No announcements available.</p>";
                    } else {
                        echo "<ul>";
                        foreach (array_reverse($announcements) as $ann) {
                            echo "<li>" . htmlspecialchars($ann) . "</li>";
                        }
                        echo "</ul>";
                    }
                } else {
                    echo "<p>No announcements found.</p>";
                }
                ?>
            </div>
        </section>

        <section class="user-stats-section" style="margin-top: 20px;">
            <h2>User Statistics</h2>
            <canvas id="userStatsChart" width="400" height="200"></canvas>
            <script>
                const ctx = document.getElementById('userStatsChart').getContext('2d');
                const userStatsChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Total Users', 'Verified Users', 'Approved Users', 'Pending Approval'],
                        datasets: [{
                            label: 'User Counts',
                            data: [
                                <?php echo count($users); ?>,
                                <?php echo count(array_filter($users, function($u) { return $u['verified']; })); ?>,
                                <?php echo count(array_filter($users, function($u) { return $u['approved']; })); ?>,
                                <?php echo count(array_filter($users, function($u) { return $u['verified'] && !$u['approved']; })); ?>
                            ],
                            backgroundColor: [
                                'rgba(54, 162, 235, 0.7)',
                                'rgba(75, 192, 192, 0.7)',
                                'rgba(153, 102, 255, 0.7)',
                                'rgba(255, 159, 64, 0.7)'
                            ],
                            borderColor: [
                                'rgba(54, 162, 235, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                precision: 0
                            }
                        }
                    }
                });
            </script>
        </section>
    </div>
</body>
</html>
