<?php
require_once "auth.php";
require_login();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sin Bliss Album Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
    <h2>Sin Bliss Album Store</h2>

    <div>
        <a href="index.php">Home</a>
        <a href="stores.php">Stores</a>
        <a href="albums.php">Albums</a>
        <a href="activity_logs.php">Activity Logs</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION["first_name"]); ?>!</h1>

    <p>Welcome to Sin Bliss Album Store Management System.</p>
    <p>Manage your K-pop stores and albums using the navigation links above.</p>

    <div class="dashboard">
        <div class="dashboard-card">
            <h2>Stores</h2>
            <p>Manage your album stores.</p>
            <a href="stores.php">Manage Stores</a>
        </div>

        <div class="dashboard-card">
            <h2>Albums</h2>
            <p>Manage your K-pop albums.</p>
            <a href="albums.php">Manage Albums</a>
        </div>

        <div class="dashboard-card">
            <h2>Activity Logs</h2>
            <p>View recorded user activities.</p>
            <a href="activity_logs.php">View Logs</a>
        </div>
    </div>
</div>

</body>
</html>