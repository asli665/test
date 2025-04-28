<?php
require_once 'db.php';

$conn = $GLOBALS['conn'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        
        echo "<title>Submitted Registration Data</title>";
        echo "<link rel='stylesheet' href='rangantodapp.css' />";
        echo "</head>";
        echo "<body class='register-page'>";
        echo "<div class='container'>";
        echo "<div class='logo-container'>";
        echo "<img src='img/datodalogo.jpg' alt='DATODA Logo' />";
        echo "<h1>DATODA</h1>";
        echo "</div>";
        echo "<h2>Submitted Registration Data</h2>";
        echo "<ul>";
        echo "<li>User Type: " . htmlspecialchars($_POST['user_type'] ?? '') . "</li>";
        echo "<li>Last Name: " . htmlspecialchars($_POST['last_name'] ?? '') . "</li>";
        echo "<li>First Name: " . htmlspecialchars($_POST['first_name'] ?? '') . "</li>";
        echo "<li>Middle Name: " . htmlspecialchars($_POST['middle_name'] ?? '') . "</li>";
        echo "<li>Username: " . htmlspecialchars($_POST['username'] ?? '') . "</li>";
        echo "<li>Email: " . htmlspecialchars($_POST['email'] ?? '') . "</li>";
        echo "<li>Phone: " . htmlspecialchars($_POST['phone'] ?? '') . "</li>";
        echo "<li>Birthday: " . htmlspecialchars($_POST['birthday'] ?? '') . "</li>";
        if (isset($_FILES['user_picture']) && $_FILES['user_picture']['error'] === UPLOAD_ERR_OK) {
            $fileName = htmlspecialchars($_FILES['user_picture']['name']);
            echo "<li>Profile Picture: " . $fileName . "</li>";
        } else {
            echo "<li>Profile Picture: No file uploaded</li>";
        }
        echo "</ul>";
        echo "</div>";
        echo "</body>";
        echo "</html>";
        exit(); // Stop further processing after echoing data
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Register - Rangantodapp</title>
    <link rel="stylesheet" href="rangantodapp.css" />
</head>
<body class="register-page">
    <div class="container">
        <div class="logo-container">
            <img src="img/datodalogo.jpg" alt="DATODA Logo" />
            <h1>DATODA</h1>
        </div>
        <h2>Register</h2>
        <form method="POST" action="test.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="register" />

            <label for="user_type">User Type:</label>
            <select id="user_type" name="user_type" required>
                <option value="">Select user type</option>
                <option value="official" <?php if (($_POST['user_type'] ?? '') === 'official') echo 'selected'; ?>>Official</option>
                <option value="operator" <?php if (($_POST['user_type'] ?? '') === 'operator') echo 'selected'; ?>>Operator</option>
                <option value="driver" <?php if (($_POST['user_type'] ?? '') === 'driver') echo 'selected'; ?>>Driver</option>
                <option value="passenger" <?php if (($_POST['user_type'] ?? '') === 'passenger') echo 'selected'; ?>>Passenger</option>
            </select>

            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required />

            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required />

            <label for="middle_name">Middle Name:</label>
            <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>" />

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required />

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required />

            <label for="phone">Phone Number:</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required />

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required />

            <label for="birthday">Birthday:</label>
            <input type="date" id="birthday" name="birthday" value="<?php echo htmlspecialchars($_POST['birthday'] ?? ''); ?>" required />

            <label for="user_picture">Profile Picture:</label>
            <input type="file" id="user_picture" name="user_picture" accept="image/*" />

            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>
