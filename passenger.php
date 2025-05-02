<?php
session_start();
error_log("Session username: " . ($_SESSION['username'] ?? 'not set'));
error_log("Session user_type: " . ($_SESSION['user_type'] ?? 'not set'));
include 'db.php';

// Check if user is logged in and is a passenger
if (!isset($_SESSION['username']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'passenger') {
    session_write_close();
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Handle rating submission
$conn = $GLOBALS['conn'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $selectedDriver = $_POST['driver'] ?? '';
    $rating = intval($_POST['rating'] ?? 0);

    if ($selectedDriver && $rating >= 1 && $rating <= 5) {
        $sql = "INSERT INTO driver_ratings (driver_username, passenger_username, rating) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $selectedDriver, $username, $rating);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Rating submitted successfully.";

            // Log the rating action to activity_logs table in database
            $logAction = "Passenger '{$username}' rated Driver '{$selectedDriver}' with {$rating} star(s).";
            $logSql = "INSERT INTO activity_logs (username, action) VALUES (?, ?)";
            $logStmt = mysqli_prepare($conn, $logSql);
            mysqli_stmt_bind_param($logStmt, "ss", $username, $logAction);
            mysqli_stmt_execute($logStmt);
            mysqli_stmt_close($logStmt);

        } else {
            $message = "Failed to submit rating. Please try again.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "Please select a driver and a valid rating.";
    }
}

// Fetch user details
$sql = "SELECT first_name, last_name, user_picture_path FROM users WHERE username = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $firstName, $lastName, $userPicturePath);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$userPicturePath || empty($userPicturePath)) {
  $userPicturePath = "img/datodalogo.jpg";
} else {
  if (!file_exists($userPicturePath)) {
      $userPicturePath = "img/datodalogo.jpg";
  }
}

// Fetch list of drivers
$drivers = [];
$sql = "SELECT username, first_name, last_name FROM users WHERE user_type = 'driver' AND approved = 1";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $drivers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Passenger's Dashboard - Rangantodap</title>
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
  </style>
</head>
<body>

  <!-- Passenger Header -->
  <div class="driver-header">
    <img src="img/datodalogo.jpg" alt="Datoda Logo">
    <h1>RANGANTODAPP - Passenger Dashboard</h1>
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
      <li><a href="passenger.php"><span class="icon"><i class="fa-solid fa-user"></i></span><span class="text">HOME</span></a></li>
      <li><a href="profile.php"><span class="icon"><i class="fa-solid fa-user"></i></span><span class="text">PROFILE</span></a></li>
      <li><a href="#"><span class="icon"><i class="fa-solid fa-bell"></i></span><span class="text">ANNOUNCEMENT</span></a></li>
      <li><a href="#"><span class="icon"><i class="fa-solid fa-star"></i></span><span class="text">RATE DRIVERS</span></a></li>
      <li><a href="login.php?action=logout"><span class="icon"><i class="fa-solid fa-right-from-bracket"></i></span><span class="text">LOG OUT</span></a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">

    <!-- Passenger Profile Picture in Top Right -->
    <div class="driver-profile-section">
      <img src="<?php echo htmlspecialchars($userPicturePath); ?>" alt="Passenger Profile Picture" id="passengerPhoto">
      <p>Welcome, <?php echo htmlspecialchars($firstName . ' ' . $lastName); ?>!</p>
    </div>

    <!-- Announcements Section -->
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

    <!-- Inline Rating Form -->
    <div class="rating-module" style="margin-top: 20px;">
      <h2>Rate Your Driver</h2>
      <?php if ($message): ?>
        <p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
      <?php endif; ?>
      <form method="POST" action="passenger.php" id="ratingForm">
        <label for="driver">Select Driver:</label>
        <select id="driver" name="driver" required>
          <option value="">-- Select a driver --</option>
          <?php foreach ($drivers as $driver): ?>
            <option value="<?php echo htmlspecialchars($driver['username']); ?>">
              <?php echo htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <div class="stars" id="starRating">
          <i class="far fa-star" data-value="1"></i>
          <i class="far fa-star" data-value="2"></i>
          <i class="far fa-star" data-value="3"></i>
          <i class="far fa-star" data-value="4"></i>
          <i class="far fa-star" data-value="5"></i>
        </div>
        <input type="hidden" name="rating" id="ratingInput" value="0" />
        <p>Your rating: <span id="ratingValue">0</span> star(s)</p>

        <button type="submit" name="submit_rating">Submit Rating</button>
      </form>
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

    });
  </script>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const stars = document.querySelectorAll("#starRating i");
      const ratingInput = document.getElementById("ratingInput");
      const ratingValue = document.getElementById("ratingValue");

      stars.forEach(star => {
        star.addEventListener("click", function() {
          const selectedRating = parseInt(this.getAttribute("data-value"));
          ratingInput.value = selectedRating;
          ratingValue.textContent = selectedRating;

          stars.forEach(s => {
            if (parseInt(s.getAttribute("data-value")) <= selectedRating) {
              s.classList.remove("far");
              s.classList.add("fas");
            } else {
              s.classList.remove("fas");
              s.classList.add("far");
            }
          });
        });
      });
    });
  </script>
</body>
</html>
