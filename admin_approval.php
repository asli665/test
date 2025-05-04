<?php
session_start();
require_once 'db.php';

$conn = $GLOBALS['conn'];

// Handle logout action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Handle OTP verification setting update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp_verification_enabled'])) {
    $otpEnabled = $_POST['otp_verification_enabled'] === '1' ? '1' : '0';
    $stmt = mysqli_prepare($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('otp_verification_enabled', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    mysqli_stmt_bind_param($stmt, "s", $otpEnabled);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: admin_approval.php");
    exit();
}

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

// Check if user is logged in and is admin
if (!isset($_SESSION['username']) || !isset($_SESSION['user_type'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$userType = $_SESSION['user_type'];

if ($userType !== 'official') {
    echo "Access denied. You are not an admin.";
    exit();
}

// Fetch all users from database
$sql = "SELECT * FROM users";
$result = mysqli_query($conn, $sql);
$users = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[$row['username']] = $row;
    }
}


if (!empty($_POST)) {
    $action = $_POST['action'] ?? '';
    $targetUser = $_POST['username'] ?? '';

        if ($action && $targetUser && isset($users[$targetUser])) {
        if ($action === 'approve') {
            $sql = "UPDATE users SET approved = 1 WHERE username = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $targetUser);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Log approval action
            $logAction = "Admin '{$username}' approved user '{$targetUser}'.";
            $logSql = "INSERT INTO activity_logs (username, action) VALUES (?, ?)";
            $logStmt = mysqli_prepare($conn, $logSql);
            mysqli_stmt_bind_param($logStmt, "ss", $username, $logAction);
            mysqli_stmt_execute($logStmt);
            mysqli_stmt_close($logStmt);

        } elseif ($action === 'reject') {
            $sql = "DELETE FROM users WHERE username = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $targetUser);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Log rejection action
            $logAction = "Admin '{$username}' rejected user '{$targetUser}'.";
            $logSql = "INSERT INTO activity_logs (username, action) VALUES (?, ?)";
            $logStmt = mysqli_prepare($conn, $logSql);
            mysqli_stmt_bind_param($logStmt, "ss", $username, $logAction);
            mysqli_stmt_execute($logStmt);
            mysqli_stmt_close($logStmt);

        } elseif ($action === 'delete') {
            $sql = "DELETE FROM users WHERE username = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $targetUser);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Log delete action
            $logAction = "Admin '{$username}' deleted user '{$targetUser}'.";
            $logSql = "INSERT INTO activity_logs (username, action) VALUES (?, ?)";
            $logStmt = mysqli_prepare($conn, $logSql);
            mysqli_stmt_bind_param($logStmt, "ss", $username, $logAction);
            mysqli_stmt_execute($logStmt);
            mysqli_stmt_close($logStmt);
        }
        header("Location: admin_approval.php");
        exit();
        }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['announcement'])) {
        // Handle announcement image upload
        $announcement = trim($_POST['announcement']);
        $imagePath = null;

        if (!empty($_FILES['announcement_image']['name'])) {
            $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'announcements' . DIRECTORY_SEPARATOR;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $imageName = basename($_FILES['announcement_image']['name']);
            $targetFile = $uploadDir . $imageName;
            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($imageFileType, $allowedTypes)) {
                if (move_uploaded_file($_FILES['announcement_image']['tmp_name'], $targetFile)) {
                    $imagePath = 'uploads/announcements/' . $imageName;
                }
            }
        }

        if ($announcement !== '') {
            $stmt = mysqli_prepare($conn, "INSERT INTO announcements (announcement_text, image_path) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ss", $announcement, $imagePath);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Log the announcement addition
            $logAction = "Admin '{$username}' added an announcement.";
            $logSql = "INSERT INTO activity_logs (username, action) VALUES (?, ?)";
            $logStmt = mysqli_prepare($conn, $logSql);
            mysqli_stmt_bind_param($logStmt, "ss", $username, $logAction);
            mysqli_stmt_execute($logStmt);
            mysqli_stmt_close($logStmt);

            header("Location: admin_approval.php");
            exit();
        }
    } elseif (isset($_POST['clear_announcements'])) {
        // Clear all announcements
        $deleteSql = "DELETE FROM announcements";
        mysqli_query($conn, $deleteSql);

        // Log the announcement clearing
        $logAction = "Admin '{$username}' cleared all announcements.";
        $logSql = "INSERT INTO activity_logs (username, action) VALUES (?, ?)";
        $logStmt = mysqli_prepare($conn, $logSql);
        mysqli_stmt_bind_param($logStmt, "ss", $username, $logAction);
        mysqli_stmt_execute($logStmt);
        mysqli_stmt_close($logStmt);

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
    <link rel="stylesheet" href="admin_approval.css" />
    <link rel="stylesheet" href="rangantodapp.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-approval">
    <div class="slidebar">
        <ul>
            <li>
                <a href="#" class="logo">
                    <span class="icon"><img src="img/datodalogo.jpg" alt="Datoda Logo" style="width: 25px; height: 25px; object-fit: contain;"></span>
                    <span class="text">RANGANTODAP</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <span class="icon"><i class="fa-solid fa-user"></i></span>
                    <span class="text">PROFILE</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <span class="icon"><i class="fa-solid fa-star"></i></span>
                    <span class="text">RATINGS</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <span class="icon"><i class="fa-solid fa-money-bill-wave"></i></span>
                    <span class="text">FUNDS</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <span class="icon"><i class="fa-solid fa-table-list"></i></span>
                    <span class="text">FARE MATRIX</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <span class="icon"><i class="fa-solid fa-box-open"></i></span>
                    <span class="text">LOST AND FOUND</span>
                </a>
            </li>
            <li>
<form method="POST" action="admin_approval.php" style="padding: 10px; color: white;">
    <input type="hidden" name="otp_verification_enabled" value="0" />
    <label for="otp_verification_enabled" style="display: flex; align-items: center; cursor: pointer;">
        <input type="checkbox" id="otp_verification_enabled" name="otp_verification_enabled" value="1" <?php echo ($otpVerificationEnabled === '1') ? 'checked' : ''; ?> onchange="this.form.submit()" style="margin-right: 8px;" />
        Enable OTP Verification
    </label>
</form>
            </li>
            <li>
                <a href="login.php?action=logout">
                    <span class="icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                    <span class="text">LOG OUT</span>
                </a>
            </li>
        </ul>
    </div>
    <header class="admin-header">
        <h1>RANGANTODAP</h1>
        <img src="img/datodalogo.jpg" alt="DATODA Logo" />
        <h1>DATODA Admin Dashboard</h1>
    </header>
    <!-- Removed logged-in info as per user request -->

    <div class="main-content">

<section class="activity-log-section">
            <h2>Activity Log</h2>
            <form method="GET" action="export_activity_log.php" target="_blank" style="margin-bottom: 10px;">
                <button type="submit" style="padding: 8px 12px; background-color: #007bff; color: white; border: none; cursor: pointer;">Print Report</button>
            </form>
            <div class="activity-log" style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">
                <?php
                // Fetch activity logs from database instead of text file
                $sql = "SELECT username, action, created_at FROM activity_logs ORDER BY created_at DESC";
                $result = mysqli_query($conn, $sql);
                if ($result && mysqli_num_rows($result) > 0) {
                    echo "<ul>";
                    while ($row = mysqli_fetch_assoc($result)) {
                        $logEntry = "[" . htmlspecialchars($row['created_at']) . "] " . htmlspecialchars($row['username']) . ": " . htmlspecialchars($row['action']);
                        echo "<li>" . $logEntry . "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>No activity logs available.</p>";
                }
                ?>
            </div>
        </section>

        <section class="user-info-section">
            <h2>User Information</h2>
            <?php if (empty($users)): ?>
                <p>No users found.</p>
            <?php else: ?>
            <div class="table-responsive">
        <table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>Picture</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>User Type</th>
                            <th>Name</th>
                            <th>Body Number</th>
                            <th>Number of Tricycles</th>
                            <th>Driver's Names</th>
                            <th>Operator Name</th>
                            <th>ORCR Picture</th>
                            <th>TODA ID Picture</th>
                            <th>Proof of Employment</th>
                            <th>Verified</th>
                            <th>Approved</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $uname => $user): ?>
                            <tr>
                                <td>
                    <?php if (!empty($user['user_picture_path']) && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $user['user_picture_path'])): ?>
                        <img src="<?php echo htmlspecialchars($user['user_picture_path']); ?>" alt="User Picture" class="clickable-image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;" />
                    <?php else: ?>
                        <img src="img/datodalogo.jpg" alt="No Picture" class="clickable-image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;" />
                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($uname); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($user['user_type'] ?? ''); ?></td>
                                <td>
                                    <?php 
                                        $name = [];
                                        if (!empty($user['first_name'])) $name[] = htmlspecialchars($user['first_name']);
                                        if (!empty($user['middle_name'])) $name[] = htmlspecialchars($user['middle_name']);
                                        if (!empty($user['last_name'])) $name[] = htmlspecialchars($user['last_name']);
                                        echo implode(' ', $name);
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['body_number'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($user['num_tricycles'] ?? ''); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($user['drivers_names'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($user['operator_name'] ?? ''); ?></td>
                                <td>
                    <?php if (!empty($user['orcr_picture_path']) && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $user['orcr_picture_path'])): ?>
                        <img src="<?php echo htmlspecialchars($user['orcr_picture_path']); ?>" alt="ORCR Picture" class="clickable-image" style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;" />
                    <?php else: ?>
                        <span>No ORCR Picture</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($user['toda_id_picture_path']) && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $user['toda_id_picture_path'])): ?>
                        <img src="<?php echo htmlspecialchars($user['toda_id_picture_path']); ?>" alt="TODA ID Picture" class="clickable-image" style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;" />
                    <?php else: ?>
                        <span>No TODA ID Picture</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($user['proof_of_employment_path']) && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $user['proof_of_employment_path'])): ?>
                        <img src="<?php echo htmlspecialchars($user['proof_of_employment_path']); ?>" alt="Proof of Employment" class="clickable-image" style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;" />
                    <?php else: ?>
                        <span>No Proof of Employment</span>
                    <?php endif; ?>
                </td>
                <td><?php echo ($user['verified'] ?? false) ? 'Yes' : 'No'; ?></td>
                <td><?php echo ($user['approved'] ?? false) ? 'Yes' : 'No'; ?></td>
                <td>
                    <?php if (!($user['approved'] ?? false)): ?>
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
                    <form method="POST" style="display:inline; margin-left: 10px;">
                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($uname); ?>" />
                        <button type="submit" name="action" value="delete" onclick="return confirm('Are you sure you want to delete this user account?');">Delete Account</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
</tbody>
</table>

<div id="imageModal" style="display:none; position:fixed; z-index:10000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.8);">
    <span id="closeModal" style="position:absolute; top:20px; right:35px; color:#fff; font-size:40px; font-weight:bold; cursor:pointer;">&times;</span>
    <img id="modalImage" style="margin:auto; display:block; max-width:90%; max-height:90%; position:relative; top:50%; transform:translateY(-50%);" />
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const closeModal = document.getElementById('closeModal');

    document.querySelectorAll('.clickable-image').forEach(img => {
        img.addEventListener('click', function() {
            modal.style.display = 'block';
            modalImage.src = this.src;
        });
    });

    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
        modalImage.src = '';
    });

    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
            modalImage.src = '';
        }
    });
});
</script>
            <?php endif; ?>
        </section>

        <section class="announcement-section" style="margin-top: 20px;">
            <h2>Add Announcement</h2>
            <form method="POST" action="admin_approval.php" enctype="multipart/form-data" style="margin-bottom: 10px;">
                <textarea name="announcement" rows="3" cols="50" placeholder="Enter announcement here..." required></textarea><br />
                <label for="announcement_image">Upload Image (optional):</label>
                <input type="file" name="announcement_image" id="announcement_image" accept="image/*" /><br />
                <button type="submit">Add Announcement</button>
            </form>
            <form method="POST" action="admin_approval.php" onsubmit="return confirm('Are you sure you want to clear all announcements? This action cannot be undone.');">
                <input type="hidden" name="clear_announcements" value="1" />
                <button type="submit" style="background-color: #d9534f; color: white; border: none; padding: 8px 12px; cursor: pointer;">Clear Announcements</button>
            </form>
            <h3>Announcements</h3>
            <div class="announcements" style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; background: #f9f9f9;">
            <?php
                $stmt = mysqli_prepare($conn, "SELECT announcement_text, image_path, created_at FROM announcements ORDER BY created_at DESC");
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $announcementText, $imagePath, $createdAt);
                $announcements = [];
                while (mysqli_stmt_fetch($stmt)) {
                    $announcements[] = ['text' => $announcementText, 'image' => $imagePath, 'created_at' => $createdAt];
                }
                mysqli_stmt_close($stmt);

                if (empty($announcements)) {
                    echo "<p>No announcements available.</p>";
                } else {
                    foreach ($announcements as $ann) {
                        echo "<div style='border-bottom: 1px solid #ddd; margin-bottom: 10px; padding-bottom: 10px;'>";
                        echo "<p>" . nl2br(htmlspecialchars($ann['text'])) . "</p>";
                        if (!empty($ann['image'])) {
                            echo "<img src='" . htmlspecialchars($ann['image']) . "' alt='Announcement Image' style='max-width: 100%; height: auto; margin-top: 5px;' />";
                        }
                        echo "<small style='color: #666; font-size: 0.8em;'>Posted on " . htmlspecialchars($ann['created_at']) . "</small>";
                        echo "</div>";
                    }
                }
            ?>
            </div>

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
    <div id="userDetailsModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div class="modal-content" style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 800px;">
        <span class="close" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        <h2>User Details</h2>
        <div id="userDetailsContent"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add View Details button to each user row
    const userRows = document.querySelectorAll('table tbody tr');
    userRows.forEach(row => {
        const actionsCell = row.querySelector('td:last-child');
        const username = row.querySelector('td:first-child').textContent;
        
        const viewDetailsBtn = document.createElement('button');
        viewDetailsBtn.textContent = 'View Details';
        viewDetailsBtn.style.marginLeft = '10px';
        viewDetailsBtn.onclick = function() {
            showUserDetails(username);
            return false;
        };
        
        actionsCell.appendChild(viewDetailsBtn);
    });
    
    // Modal functionality
    const modal = document.getElementById('userDetailsModal');
    const closeBtn = document.querySelector('.close');
    
    closeBtn.onclick = function() {
        modal.style.display = 'none';
    };
    
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };
    
    function showUserDetails(username) {
        // AJAX request to get user details
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `get_user_details.php?username=${encodeURIComponent(username)}`, true);
        xhr.onload = function() {
            if (this.status === 200) {
                document.getElementById('userDetailsContent').innerHTML = this.responseText;
                modal.style.display = 'block';
            }
        };
        xhr.send();
    }
});
</script>
</body>
</html>