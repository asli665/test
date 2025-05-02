<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Fare Matrix - Rangantodapp</title>
  <link rel="stylesheet" href="driver.css" />
</head>
<body>

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content" style="padding: 30px; padding-top: 120px;">
  <h1>Fare Matrix</h1>

  <div style="background: #ffffff; border-radius: 10px; padding: 20px; color: #000;">
    <table style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr style="background-color: #007bff; color: white;">
          <th style="padding: 10px; border: 1px solid #ddd;">Origin</th>
          <th style="padding: 10px; border: 1px solid #ddd;">Destination</th>
          <th style="padding: 10px; border: 1px solid #ddd;">Fare</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td style="padding: 10px; border: 1px solid #ddd;">Main Terminal</td>
          <td style="padding: 10px; border: 1px solid #ddd;">Barangay Hall</td>
          <td style="padding: 10px; border: 1px solid #ddd;">₱15</td>
        </tr>
        <tr>
          <td style="padding: 10px; border: 1px solid #ddd;">Main Terminal</td>
          <td style="padding: 10px; border: 1px solid #ddd;">School</td>
          <td style="padding: 10px; border: 1px solid #ddd;">₱20</td>
        </tr>
        <!-- More fare data can be added later dynamically -->
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
