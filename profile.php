<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['user_type'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$user_type = $_SESSION['user_type'];

// Fetch user details from the database
$sql = "SELECT first_name, last_name, birthday, phone, email, user_picture_path
        FROM users
        WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($firstName, $lastName, $birthday, $phoneNumber, $email, $userPicturePath);
$stmt->fetch();
$stmt->close();

// If no profile picture, use default
if (empty($userPicturePath) || !file_exists($userPicturePath)) {
    $userPicturePath = "img/datodalogo.jpg"; // Make sure this default file exists
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profile - Rangantodapp</title>
  <link rel="stylesheet" href="driver.css" />
</head>
<body>

<?php include 'header.php'; ?>
<?php
if ($user_type === 'passenger') {
    include 'passenger_sidebar.php';
} else {
    include 'sidebar.php';
}
?>

<div class="main-content" style="padding: 30px; padding-top: 120px;">
  <h1><?php echo $user_type === 'passenger' ? 'Passenger Profile' : 'Driver Profile'; ?></h1>

  <div class="profile-container" style="display: flex; align-items: flex-start; background: #2a5298; padding: 30px; border-radius: 12px; color: white; gap: 30px;">
    
    <div class="profile-left" style="flex: 0 0 150px; text-align: center;">
      <img src="<?php echo htmlspecialchars($userPicturePath); ?>" alt="Profile Picture" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 3px solid #fff;">
    </div>

    <div class="profile-right" style="flex: 1;">
      <h2><?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></h2>
      <p><strong>Birthday:</strong> <?php echo htmlspecialchars($birthday); ?></p>
      <p><strong>Phone:</strong> <?php echo htmlspecialchars($phoneNumber); ?></p>
      <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
    </div>

  </div>

</div>

</body>
</html>
