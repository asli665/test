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
  <title>Funds Tracking - Rangantodapp</title>
  <link rel="stylesheet" href="driver.css" />
</head>
<body>

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content" style="padding: 30px; padding-top: 120px;">
  <h1>Funds Tracking</h1>

  <div style="background: #ffffff; border-radius: 10px; padding: 20px; color: #000;">
    <table style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr style="background-color: #28a745; color: white;">
          <th style="padding: 10px; border: 1px solid #ddd;">Date</th>
          <th style="padding: 10px; border: 1px solid #ddd;">Description</th>
          <th style="padding: 10px; border: 1px solid #ddd;">Amount</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td style="padding: 10px; border: 1px solid #ddd;">2025-04-29</td>
          <td style="padding: 10px; border: 1px solid #ddd;">Daily Earnings</td>
          <td style="padding: 10px; border: 1px solid #ddd;">₱500</td>
        </tr>
        <tr>
          <td style="padding: 10px; border: 1px solid #ddd;">2025-04-28</td>
          <td style="padding: 10px; border: 1px solid #ddd;">Fuel Expense</td>
          <td style="padding: 10px; border: 1px solid #ddd;">-₱200</td>
        </tr>
        <!-- More funds data can be added later dynamically -->
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
