<?php
session_start();
include 'db.php';

// Check if user is logged in and is a driver
if (!isset($_SESSION['username']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'driver') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Fetch user details from database
$conn = $GLOBALS['conn'];
$sql = "SELECT first_name, last_name, user_picture_path FROM users WHERE username = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $firstName, $lastName, $userPicturePath);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

error_log("Raw userPicturePath from DB: " . var_export($userPicturePath, true));
error_log("Server document root: " . $_SERVER['DOCUMENT_ROOT']);
error_log("Current script directory: " . dirname(__FILE__));



if (!$userPicturePath || empty($userPicturePath)) {
  $userPicturePath = "img/datodalogo.jpg";
} else {
  // Make sure the path is valid and exists
  if (!file_exists($userPicturePath)) {
      error_log("Profile picture not found at: " . $userPicturePath);
      $userPicturePath = "img/datodalogo.jpg";
  }
}

// Debug output for image path and file existence (remove in production)
error_log("Final driver profile picture path: " . $userPicturePath);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Driver's Dashboard - Rangantodap</title>
    <link rel="stylesheet" href="driver.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

  <!-- Admin Header (For Driver's Dashboard) -->
  <div class="driver-header">
    <img src="img/datodalogo.jpg" alt="Datoda Logo">
    <h1>RANGANTODAPP - Driver Dashboard</h1>
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
      <li><a href="#"><span class="icon"><i class="fa-solid fa-user"></i></span><span class="text">ANNOUNCEMENT</span></a></li>
      <li><a href="#"><span class="icon"><i class="fa-solid fa-star"></i></span><span class="text">PROFILE</span></a></li>
      <li><a href="#"><span class="icon"><i class="fa-solid fa-money-bill-wave"></i></span><span class="text">DATODA MEMBERS</span></a></li>
      <li><a href="#"><span class="icon"><i class="fa-solid fa-table-list"></i></span><span class="text">FARE MATRIX</span></a></li>
      <li><a href="#"><span class="icon"><i class="fa-solid fa-box-open"></i></span><span class="text">FUNDS TRACKING</span></a></li>
      <li><a href="login.php?action=logout"><span class="icon"><i class="fa-solid fa-right-from-bracket"></i></span><span class="text">LOG OUT</span></a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">

    <!-- 👤 Driver Profile Picture in Top Right -->
    <div class="driver-profile-section">
      <img src="<?php echo htmlspecialchars($userPicturePath); ?>" alt="Driver Profile Picture" id="driverPhoto" onclick="openModal()">
      <p>Welcome, <?php echo htmlspecialchars($firstName . ' ' . $lastName); ?>!</p>
    </div>

    <!-- 📢 Announcements Section -->
    <div class="announcement-section">
      <div class="section-title">Announcements</div>
      <p>This is where admin announcements will appear. You can display multiple announcements or bullet points here.</p>
    </div>

    <!-- Modal for Ratings and Comments -->
    <div id="ratingModal" class="modal">
      <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Ratings and Comments</h2>
        <div id="ratings">
          <p><strong>Average Rating:</strong> 4.5/5</p>
          <!-- Dynamic ratings will go here -->
        </div>
        <div id="comments">
          <h3>Top Comments:</h3>
          <ul>
            <li>"Great service!"</li>
            <li>"Friendly driver!"</li>
            <!-- Top comments will be displayed here -->
          </ul>
        </div>
      </div>
    </div>

  </div>

  <script>
    // Function to open the modal
    function openModal() {
      document.getElementById("ratingModal").style.display = "block";
    }

    // Function to close the modal
    function closeModal() {
      document.getElementById("ratingModal").style.display = "none";
    }

    // Close modal if clicked outside of modal-content
    window.onclick = function(event) {
      if (event.target == document.getElementById("ratingModal")) {
        closeModal();
      }
    }
  </script>
</body>
</html>
