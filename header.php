<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$username = $_SESSION['username'] ?? 'Guest';
?>
<!-- Admin Header -->
<div class="driver-header">
    <img src="img/datodalogo.jpg" alt="Datoda Logo">
    <h1>RANGANTODAPP - Dashboard</h1>
    <div style="margin-left:auto; color: white; font-weight: bold; font-size: 1.2rem; align-self: center;">
      Welcome, <?php echo htmlspecialchars($username); ?>
    </div>
</div>
