<?php
require_once "auth.php";
require_login();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST["action"] === "restore") {
    $album_id = (int) $_POST["album_id"];

    $stmt = $pdo->prepare(
        "UPDATE albums
         SET deleted_by = NULL,
             deleted_at = NULL
         WHERE album_id = ?
         AND deleted_at IS NOT NULL"
    );

    $stmt->execute([$album_id]);

    if ($stmt->rowCount() > 0) {
        log_activity(
            $pdo,
            "RESTORE",
            "albums",
            $album_id,
            "Restored album ID: " . $album_id
        );

        $message = "Album successfully restored.";
    }
}

$stmt = $pdo->query(
    "SELECT a.*,
            CONCAT(u.first_name, ' ', u.last_name) AS deleted_by_name
     FROM albums a
     LEFT JOIN users u ON a.deleted_by = u.user_id
     WHERE a.deleted_at IS NOT NULL
     ORDER BY a.deleted_at DESC"
);

$deleted_albums = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Recycle Bin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
    <h2>Sin Bliss Album Store</h2>
    <div>
        <a href="index.php">Home</a>
        <a href="stores.php">Stores</a>
        <a href="albums.php">Albums</a>
        <a href="recycle_bin.php">Recycle Bin</a>
        <a href="activity_logs.php">Activity Logs</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <h1>Recycle Bin</h1>

    <?php if ($message): ?>
        <p class="success">
            <?php echo cleanOutput($message); ?>
        </p>
    <?php endif; ?>

    <h2>Deleted Albums</h2>

    <div class="table-container">
        <table>
            <tr>
                <th>ID</th>
                <th>Album Title</th>
                <th>Artist</th>
                <th>Version</th>
                <th>Deleted By</th>
                <th>Date Deleted</th>
                <th>Action</th>
            </tr>

            <?php foreach ($deleted_albums as $album): ?>
                <tr>
                    <td><?php echo $album["album_id"]; ?></td>
                    <td><?php echo cleanOutput($album["album_title"]); ?></td>
                    <td><?php echo cleanOutput($album["artist"]); ?></td>
                    <td><?php echo cleanOutput($album["album_version"]); ?></td>
                    <td><?php echo cleanOutput($album["deleted_by_name"] ?? "Unknown"); ?></td>
                    <td><?php echo cleanOutput($album["deleted_at"]); ?></td>
                    <td>
                        <form method="POST"
                              onsubmit="return confirm('Restore this album?');">
                            <input type="hidden" name="action" value="restore">
                            <input type="hidden" name="album_id"
                                   value="<?php echo $album["album_id"]; ?>">
                            <button type="submit">Restore</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($deleted_albums)): ?>
                <tr>
                    <td colspan="7"</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

</body>
</html>
