
<?php
require_once "auth.php";
require_login();

$sql = "SELECT a.*,
               CONCAT(u.first_name, ' ', u.last_name) AS user_name
        FROM activity_logs a
        LEFT JOIN users u ON a.user_id = u.user_id
        ORDER BY a.created_at DESC";

$logs = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Activity Logs</title>
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
    <h1>User Activity Logs</h1>

    <p>Records of user actions in the system.</p>

    <div class="table-container">
        <table>
            <tr>
                <th>Log ID</th>
                <th>User</th>
                <th>Action</th>
                <th>Entity</th>
                <th>Record ID</th>
                <th>Description</th>
                <th>Date and Time</th>
            </tr>

            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo (int) $log["log_id"]; ?></td>
                    <td><?php echo cleanOutput($log["user_name"] ?? "Unknown User"); ?></td>
                    <td><?php echo cleanOutput($log["action"]); ?></td>
                    <td><?php echo cleanOutput($log["entity"]); ?></td>
                    <td><?php echo cleanOutput($log["record_id"] ?? "N/A"); ?></td>
                    <td><?php echo cleanOutput($log["description"]); ?></td>
                    <td><?php echo cleanOutput($log["created_at"]); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

</body>
</html>
