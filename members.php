<?php
session_start();
require_once 'db.php';

$conn = $GLOBALS['conn'];

// Check if user is logged in and is authorized to view members
if (!isset($_SESSION['username']) || !isset($_SESSION['user_type'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$userType = $_SESSION['user_type'];

// For simplicity, allow all logged in users to view members
// You can add role-based access control here if needed

// Fetch all approved users
$sql = "SELECT username, first_name, middle_name, last_name, phone, address, user_picture_path FROM users WHERE approved = 1";
$result = mysqli_query($conn, $sql);
$members = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $members[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Members - Rangantodapp</title>
    <link rel="stylesheet" href="driver.css" />
</head>
<body>
    <h1>Members</h1>
    <div class="gallery">
        <?php if (empty($members)): ?>
            <p>No members found.</p>
        <?php else: ?>
            <?php foreach ($members as $member): ?>
                <div class="member-card">
                    <?php if (!empty($member['user_picture_path']) && file_exists($member['user_picture_path'])): ?>
                        <img src="<?php echo htmlspecialchars($member['user_picture_path']); ?>" alt="Profile Picture" />
                    <?php else: ?>
                        <img src="img/datodalogo.jpg" alt="No Picture" />
                    <?php endif; ?>
                    <div class="member-info">
                        <div class="member-name">
                            <?php
                                $nameParts = array_filter([
                                    $member['first_name'],
                                    $member['middle_name'],
                                    $member['last_name']
                                ]);
                                echo htmlspecialchars(implode(' ', $nameParts));
                            ?>
                        </div>
                        <div class="member-phone"><?php echo htmlspecialchars($member['phone']); ?></div>
                        <div class="member-address"><?php echo htmlspecialchars($member['address']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
