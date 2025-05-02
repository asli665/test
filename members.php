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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-o9N1j7kGkQY0h0v+v+3k5b5Q5b5Q5b5Q5b5Q5b5Q5b5Q="
      crossorigin=""/>
    <style>
      #map {
        height: 400px;
        width: 100%;
        margin-bottom: 20px;
      }
    </style>
</head>
<body>

  <?php include 'header.php'; ?>
  <?php include 'sidebar.php'; ?>

  <!-- Admin Header (For Members Dashboard) -->
  <div class="driver-header">
    <img src="img/datodalogo.jpg" alt="Datoda Logo">
    <h1>RANGANTODAPP - Members Dashboard</h1>
    <div style="margin-left:auto; color: white; font-weight: bold; font-size: 1.2rem; align-self: center;">
      Welcome, <?php echo htmlspecialchars($username); ?>
    </div>
  </div>

  <!-- Sidebar -->
  <div class="slidebar">
    <ul>
      <li>
        <a href="#" class="logo">
          <span class="icon">
            <img src="img/datodalogo.jpg" alt="Datoda Logo" style="width: 25px; height: 25px; object-fit: contain;">
          </span>
          <span class="text">RANGANTODAP</span>
        </a>
      </li>
      <li><a href="driver.php"><span class="icon"><i class="fa-solid fa-user"></i></span><span class="text">HOME</span></a></li>
      <li><a href="profile.php"><span class="icon"><i class="fa-solid fa-star"></i></span><span class="text">PROFILE</span></a></li>
      <li><a href="members.php"><span class="icon"><i class="fa-solid fa-money-bill-wave"></i></span><span class="text">DATODA MEMBERS</span></a></li>
      <li><a href="fare_matrix.php"><span class="icon"><i class="fa-solid fa-table-list"></i></span><span class="text">FARE MATRIX</span></a></li>
      <li><a href="#"><span class="icon"><i class="fa-solid fa-box-open"></i></span><span class="text">FUNDS TRACKING</span></a></li>
      <li><a href="login.php?action=logout"><span class="icon"><i class="fa-solid fa-right-from-bracket"></i></span><span class="text">LOG OUT</span></a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content" style="padding: 30px; padding-top: 120px;">
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
              <div class="member-phone"><?php echo htmlspecialchars($member['phone'] ?? ''); ?></div>
              <div class="member-address"><?php echo htmlspecialchars($member['address'] ?? ''); ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
