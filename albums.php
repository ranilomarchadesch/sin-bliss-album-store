<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "auth.php";
require_login();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"];

    if ($action === "insert") {
        $store_id = (int) $_POST["store_id"];
        $album_title = trim($_POST["album_title"]);
        $artist = trim($_POST["artist"]);
        $album_version = trim($_POST["album_version"]);
        $price = (float) $_POST["price"];
        $stock = (int) $_POST["stock"];
        $image_path = null;

if (isset($_FILES["album_image"]) && $_FILES["album_image"]["error"] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . "/uploads/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

    $allowed_types = ["image/jpeg", "image/png", "image/gif", "image/webp"];
    $file_type = mime_content_type($_FILES["album_image"]["tmp_name"]);

    if (in_array($file_type, $allowed_types)) {
        $extension = pathinfo($_FILES["album_image"]["name"], PATHINFO_EXTENSION);
        $filename = uniqid("album_") . "." . strtolower($extension);
        $target_file = $upload_dir . $filename;

        if (move_uploaded_file($_FILES["album_image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        }
    }
}

        $stmt = $pdo->prepare(
    "INSERT INTO albums
    (store_id, album_title, artist, album_version, price, stock, added_by, date_added, image_path)
    VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, ?)"
);

$stmt->execute([
    $store_id,
    $album_title,
    $artist,
    $album_version,
    $price,
    $stock,
    current_user_id(),
    $image_path
]);

        $album_id = $pdo->lastInsertId();

        log_activity(
            $pdo,
            "INSERT",
            "albums",
            $album_id,
            "Added album: " . $album_title
        );

        $message = "Album successfully added.";
    }

    if ($action === "update") {
        $album_id = (int) $_POST["album_id"];
        $store_id = (int) $_POST["store_id"];
        $album_title = trim($_POST["album_title"]);
        $artist = trim($_POST["artist"]);
        $album_version = trim($_POST["album_version"]);
        $price = (float) $_POST["price"];
        $stock = (int) $_POST["stock"];

        $image_path = $_POST["existing_image"] ?? null;

if (isset($_FILES["album_image"]) && $_FILES["album_image"]["error"] === UPLOAD_ERR_OK) {
    $upload_dir = "uploads/";

    $allowed_types = ["image/jpeg", "image/png", "image/gif", "image/webp"];
    $file_type = mime_content_type($_FILES["album_image"]["tmp_name"]);

    if (in_array($file_type, $allowed_types)) {
        $extension = pathinfo($_FILES["album_image"]["name"], PATHINFO_EXTENSION);
        $filename = uniqid("album_") . "." . strtolower($extension);
        $target_file = $upload_dir . $filename;

        if (move_uploaded_file($_FILES["album_image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        }
    }
}
       $stmt = $pdo->prepare(
    "UPDATE albums
     SET store_id = ?,
         album_title = ?,
         artist = ?,
         album_version = ?,
         price = ?,
         stock = ?,
         image_path = ?,
         last_updated = CURRENT_TIMESTAMP,
         last_updated_by = ?
     WHERE album_id = ?"
);

$stmt->execute([
    $store_id,
    $album_title,
    $artist,
    $album_version,
    $price,
    $stock,
    $image_path,
    current_user_id(),
    $album_id
]);

$stmt->execute([
    $store_id,
    $album_title,
    $artist,
    $album_version,
    $price,
    $stock,
    $image_path,
    current_user_id(),
    $album_id
]);

        log_activity(
            $pdo,
            "UPDATE",
            "albums",
            $album_id,
            "Updated album: " . $album_title
        );

        $message = "Album successfully updated.";
    }


    if ($action === "delete") {
        $album_id = (int) $_POST["album_id"];

        $stmt = $pdo->prepare(
            "SELECT album_title
             FROM albums
             WHERE album_id = ?
             AND deleted_at IS NULL"
        );

        $stmt->execute([$album_id]);
        $album = $stmt->fetch();

        if ($album) {
            $delete = $pdo->prepare(
                "UPDATE albums
                 SET deleted_by = ?,
                     deleted_at = CURRENT_TIMESTAMP
                 WHERE album_id = ?"
            );

            $delete->execute([
                current_user_id(),
                $album_id
            ]);

            log_activity(
                $pdo,
                "DELETE",
                "albums",
                $album_id,
                "Moved album to recycle bin: " . $album["album_title"]
            );

            $message = "Album successfully moved to recycle bin.";
        }
    }
}

$edit_album = null;
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare(
        "SELECT * FROM albums WHERE album_id = ?"
    );
    $stmt->execute([(int) $_GET["edit"]]);
    $edit_album = $stmt->fetch();
}

$stores = $pdo->query(
    "SELECT * FROM stores ORDER BY store_name"
)->fetchAll();

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}


$stmt = $pdo->prepare(
    "SELECT a.*,
            s.store_name,
            CONCAT(u1.first_name, ' ', u1.last_name) AS added_by_name,
            CONCAT(u2.first_name, ' ', u2.last_name) AS updated_by_name
     FROM albums a
     LEFT JOIN stores s ON a.store_id = s.store_id
     LEFT JOIN users u1 ON a.added_by = u1.user_id
     LEFT JOIN users u2 ON a.last_updated_by = u2.user_id
     WHERE a.deleted_at IS NULL
     AND (
         a.album_title LIKE ?
         OR a.artist LIKE ?
         OR a.album_version LIKE ?
         OR s.store_name LIKE ?
     )
     ORDER BY a.album_id DESC"
);

$stmt->execute([
    "%" . $search . "%",
    "%" . $search . "%",
    "%" . $search . "%",
    "%" . $search . "%"
]);

$albums = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Albums</title>
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
    <h1>Manage Albums</h1>

    <?php if ($message): ?>
        <p class="success"><?php echo cleanOutput($message); ?></p>
    <?php endif; ?>

    <div class="form-card">
        <h2><?php echo $edit_album ? "Update Album" : "Add New Album"; ?></h2>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action"
                   value="<?php echo $edit_album ? "update" : "insert"; ?>">

            <?php if ($edit_album): ?>
             <input type="hidden" name="album_id"
           value="<?php echo $edit_album["album_id"]; ?>">

           <input type="hidden" name="existing_image"
            value="<?php echo htmlspecialchars($edit_album["image_path"] ?? ""); ?>">
            <?php endif; ?>

            <label>Store</label>
            <select name="store_id" required>
                <option value="">Select Store</option>

                <?php foreach ($stores as $store): ?>
                    <option value="<?php echo $store["store_id"]; ?>"
                        <?php echo (
                            isset($edit_album["store_id"]) &&
                            $edit_album["store_id"] == $store["store_id"]
                        ) ? "selected" : ""; ?>>
                        <?php echo cleanOutput($store["store_name"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Album Title</label>
            <input type="text" name="album_title"
                   value="<?php echo cleanOutput($edit_album["album_title"] ?? ""); ?>"
                   required>

            <label>Artist</label>
            <input type="text" name="artist"
                   value="<?php echo cleanOutput($edit_album["artist"] ?? ""); ?>"
                   required>

            <label>Album Version</label>
            <input type="text" name="album_version"
                   value="<?php echo cleanOutput($edit_album["album_version"] ?? ""); ?>"
                   required>

            <label>Price</label>
            <input type="number" name="price" step="0.01" min="0"
                   value="<?php echo htmlspecialchars($edit_album["price"] ?? ""); ?>"
                   required>

            <label>Stock Quantity</label>
            <input type="number" name="stock" min="0"
                   value="<?php echo htmlspecialchars($edit_album["stock"] ?? ""); ?>"
                   required>
            <label>Album Cover Image</label>
            <input type="file" name="album_image" accept="image/*">

            <button type="submit">
                <?php echo $edit_album ? "Update Album" : "Add Album"; ?>
            </button>

            <?php if ($edit_album): ?>
                <a href="albums.php"</a>
            <?php endif; ?>
        </form>
    </div>

    <h2>Album Records</h2>

    <form method="GET" action="albums.php">
        <input
            type="text"
            name="search"
            placeholder="Search album title, artist, version, or store"
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <button type="submit">Search</button>
        <a href="albums.php">Clear Search</a>
    </form>

    <div class="table-container">
        <table>
            <tr>
                <th>ID</th>
                <th>Album Cover</th>
                <th>Store</th>
                <th>Album Title</th>
                <th>Artist</th>
                <th>Version</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Added By</th>
                <th>Last Updated By</th>
                <th>Date Added</th>
                <th>Actions</th>
            </tr>

            <?php foreach ($albums as $album): ?>
                <tr>
                    <td><?php echo $album["album_id"]; ?></td>

                    <td>
                        <?php if (!empty($album["image_path"]) && file_exists(__DIR__ . "/" . $album["image_path"])): ?>
                            <img src="<?php echo htmlspecialchars($album["image_path"]); ?>"
                                alt="Album Cover"
                                style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                        <?php else: ?>
                            No Image
                        <?php endif; ?>
                    </td>

                    <td><?php echo cleanOutput($album["store_name"] ?? "Unknown"); ?></td>
                    <td><?php echo cleanOutput($album["album_title"]); ?></td>
                    <td><?php echo cleanOutput($album["artist"]); ?></td>
                    <td><?php echo cleanOutput($album["album_version"]); ?></td>
                    <td><?php echo number_format((float) $album["price"], 2); ?></td>
                    <td><?php echo $album["stock"]; ?></td>
                    <td><?php echo cleanOutput($album["added_by_name"] ?? "Unknown"); ?></td>
                    <td><?php echo cleanOutput($album["updated_by_name"] ?? "Not updated"); ?></td>
                    <td><?php echo htmlspecialchars($album["date_added"] ?? "Not available"); ?></td>
                    <td>
                        <a href="albums.php?edit=<?php echo $album["album_id"]; ?>">Edit</a>

                        <form method="POST" class="inline-form"
                              onsubmit="return confirm('Delete this album?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="album_id"
                                   value="<?php echo $album["album_id"]; ?>">
                            <button type="submit" class="delete-button">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

</body>
</html>
