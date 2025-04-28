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

// Fetch average rating for the driver
$avgRating = 0;
$ratingCount = 0;
$sqlRating = "SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM driver_ratings WHERE driver_username = ?";
$stmtRating = mysqli_prepare($conn, $sqlRating);
mysqli_stmt_bind_param($stmtRating, "s", $username);
mysqli_stmt_execute($stmtRating);
mysqli_stmt_bind_result($stmtRating, $avgRating, $ratingCount);
mysqli_stmt_fetch($stmtRating);
mysqli_stmt_close($stmtRating);

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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      crossorigin=""/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
      #map {
        height: 400px;
        width: 100%;
        margin-bottom: 20px;
      }
      /* Ensure modal is visible and on top */
      #ratingModal {
        display: none;
        position: fixed;
        z-index: 2000 !important;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.5);
        padding-top: 60px;
      }
      #ratingModal .modal-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 600px;
        color: #333;
        position: relative;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
      }
  </style>
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
      <li><a href="members.php"><span class="icon"><i class="fa-solid fa-money-bill-wave"></i></span><span class="text">DATODA MEMBERS</span></a></li>
      <li><a href="#"><span class="icon"><i class="fa-solid fa-table-list"></i></span><span class="text">FARE MATRIX</span></a></li>
      <li><a href="#"><span class="icon"><i class="fa-solid fa-box-open"></i></span><span class="text">FUNDS TRACKING</span></a></li>
      <li><a href="login.php?action=logout"><span class="icon"><i class="fa-solid fa-right-from-bracket"></i></span><span class="text">LOG OUT</span></a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content" style="padding: 30px; padding-top: 120px;">

    <!-- 👤 Driver Profile Picture in Top Right -->
    <div class="driver-profile-section">
      <img src="<?php echo htmlspecialchars($userPicturePath); ?>" alt="Driver Profile Picture" id="driverPhoto" style="cursor: pointer;">
      <p>Welcome, <?php echo htmlspecialchars($firstName . ' ' . $lastName); ?>!</p>
    </div>

    <!-- 📢 Announcements Section -->
    <div class="announcement-section">
      <div class="section-title">Announcements</div>
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

    <div id="mapContainer" style="width: 100%; height: 400px; margin-bottom: 20px;">
      <div id="map" style="width: 100%; height: 100%;"></div>
    </div>

    <!-- Removed test open modal button as per user request -->

    <!-- Modal for Ratings and Comments -->
    <div id="ratingModal" class="modal">
      <div class="modal-content" style="position: relative;">
        <button class="close" onclick="closeModal()" style="position: absolute; top: 10px; right: 10px; font-size: 24px; background: none; border: none; cursor: pointer;">&times;</button>
        <h2>Ratings and Comments</h2>
        <div id="ratings">
          <p><strong>Average Rating:</strong> <?php echo $ratingCount > 0 ? number_format($avgRating, 2) : 'No ratings yet'; ?>/5 (<?php echo $ratingCount; ?> reviews)</p>
          <!-- Dynamic ratings will go here -->
        </div>
        <div id="comments">
          <h3>Messages for You:</h3>
          <ul>
            <?php
            /*
            // Fetch messages for the driver
            $sqlMessages = "SELECT passenger_username, message, created_at FROM messages WHERE driver_username = ? ORDER BY created_at DESC LIMIT 5";
            $stmtMessages = mysqli_prepare($conn, $sqlMessages);
            mysqli_stmt_bind_param($stmtMessages, "s", $username);
            mysqli_stmt_execute($stmtMessages);
            $resultMessages = mysqli_stmt_get_result($stmtMessages);
            if ($resultMessages && mysqli_num_rows($resultMessages) > 0) {
                while ($row = mysqli_fetch_assoc($resultMessages)) {
                    echo "<li><strong>" . htmlspecialchars($row['passenger_username']) . ":</strong> " . htmlspecialchars($row['message']) . " <em>(" . htmlspecialchars($row['created_at']) . ")</em></li>";
                }
            } else {
                echo "<li>No messages available.</li>";
            }
            mysqli_stmt_close($stmtMessages);
            */
            ?>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    crossorigin=""></script>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      console.log("DOM fully loaded and parsed");
      console.log("Initializing map...");
      var map = L.map('map').setView([14.48967, 121.1849], 18);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map);
      L.marker([14.48967, 121.1849]).addTo(map)
          .bindPopup('Main Terminal')
          .openPopup();

      // Add click event listener to driver photo to open modal
      var driverPhoto = document.getElementById("driverPhoto");
      if (driverPhoto) {
        driverPhoto.addEventListener("click", function() {
          openModal();
        });
      } else {
        console.error("Driver photo element not found.");
      }
    });

    // Function to open the modal
    function openModal() {
      const modal = document.getElementById("ratingModal");
      if (modal) {
        modal.style.display = "block";
        // Prevent background scrolling when modal is open
        document.body.style.overflow = 'hidden';
        console.log("Modal opened");
      } else {
        console.error("Modal element with id 'ratingModal' not found.");
      }
    }

    // Function to close the modal
    function closeModal() {
      const modal = document.getElementById("ratingModal");
      if (modal) {
        modal.style.display = "none";
        // Restore background scrolling when modal is closed
        document.body.style.overflow = '';
        console.log("Modal closed");
      } else {
        console.error("Modal element with id 'ratingModal' not found.");
      }
    }

    // Close modal if clicked outside of modal-content
    window.onclick = function(event) {
      const modal = document.getElementById("ratingModal");
      if (modal && event.target == modal) {
        closeModal();
      }
    }
  </script>
</body>
</html>
