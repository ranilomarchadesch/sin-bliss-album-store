<?php
require_once "auth.php";
require_login();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"];

    if ($action === "insert") {
        $store_name = trim($_POST["store_name"]);
        $location = trim($_POST["location"]);
        $contact_number = trim($_POST["contact_number"]);

        if ($store_name !== "") {
            $stmt = $pdo->prepare(
                "INSERT INTO stores
                (store_name, location, contact_number, added_by, date_added)
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)"
            );

            $stmt->execute([
                $store_name,
                $location,
                $contact_number,
                current_user_id()
            ]);

            $store_id = $pdo->lastInsertId();

            log_activity(
                $pdo,
                "INSERT",
                "stores",
                $store_id,
                "Added store: " . $store_name
            );

            $message = "Store successfully added.";
        }
    }

    if ($action === "update") {
        $store_id = (int) $_POST["store_id"];
        $store_name = trim($_POST["store_name"]);
        $location = trim($_POST["location"]);
        $contact_number = trim($_POST["contact_number"]);

        $stmt = $pdo->prepare(
            "UPDATE stores
 SET store_name = ?,
     location = ?,
     contact_number = ?,
     last_updated = CURRENT_TIMESTAMP,
     last_updated_by = ?
 WHERE store_id = ?"
        );

        $stmt->execute([
            $store_name,
            $location,
            $contact_number,
            current_user_id(),
            $store_id
        ]);

        log_activity(
            $pdo,
            "UPDATE",
            "stores",
            $store_id,
            "Updated store: " . $store_name
        );

        $message = "Store successfully updated.";
    }

    if ($action === "delete") {
        $store_id = (int) $_POST["store_id"];

        $stmt = $pdo->prepare(
            "SELECT store_name FROM stores WHERE store_id = ?"
        );
        $stmt->execute([$store_id]);
        $store = $stmt->fetch();

        if ($store) {
            try {
                $pdo->beginTransaction();

                $albumStmt = $pdo->prepare(
                    "SELECT album_id, album_title
                     FROM albums
                     WHERE store_id = ?"
                );
                $albumStmt->execute([$store_id]);
                $albums = $albumStmt->fetchAll();

                foreach ($albums as $album) {
                    log_activity(
                        $pdo,
                        "DELETE",
                        "albums",
                        $album["album_id"],
                        "Album deleted because its store was deleted: " .
                        $album["album_title"]
                    );
                }

                $delete = $pdo->prepare(
                    "DELETE FROM stores WHERE store_id = ?"
                );
                $delete->execute([$store_id]);

                log_activity(
                    $pdo,
                    "DELETE",
                    "stores",
                    $store_id,
                    "Deleted store: " . $store["store_name"]
                );

                $pdo->commit();
                $message = "Store successfully deleted.";
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $message = "Unable to delete store: " . $e->getMessage();
            }
        }
    }
}

$edit_store = null;

if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare(
        "SELECT * FROM stores WHERE store_id = ?"
    );
    $stmt->execute([(int) $_GET["edit"]]);
    $edit_store = $stmt->fetch();
}

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

$stmt = $pdo->prepare(
    "SELECT s.*,
            CONCAT(u1.first_name, ' ', u1.last_name) AS added_by_name,
            CONCAT(u2.first_name, ' ', u2.last_name) AS updated_by_name
     FROM stores s
     LEFT JOIN users u1 ON s.added_by = u1.user_id
     LEFT JOIN users u2 ON s.last_updated_by = u2.user_id
     WHERE s.store_name LIKE ?
        OR s.location LIKE ?
        OR s.contact_number LIKE ?
     ORDER BY s.store_id DESC"
);

$stmt->execute([
    "%" . $search . "%",
    "%" . $search . "%",
    "%" . $search . "%"
]);

$stores = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Stores</title>
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
    <h1>Manage Stores</h1>

    <?php if ($message): ?>
        <p class="success"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <div class="form-card">
        <h2><?php echo $edit_store ? "Update Store" : "Add New Store"; ?></h2>

        <form method="POST">
            <input type="hidden" name="action"
                   value="<?php echo $edit_store ? "update" : "insert"; ?>">

            <?php if ($edit_store): ?>
                <input type="hidden" name="store_id"
                       value="<?php echo $edit_store["store_id"]; ?>">
            <?php endif; ?>

            <label>Store Name</label>
            <input type="text" name="store_name"
                   value="<?php echo htmlspecialchars($edit_store["store_name"] ?? ""); ?>"
                   required>

            <label>Location</label>
            <input type="text" name="location"
                   value="<?php echo htmlspecialchars($edit_store["location"] ?? ""); ?>">

            <label>Contact Number</label>
            <input type="text" name="contact_number"
                   value="<?php echo htmlspecialchars($edit_store["contact_number"] ?? ""); ?>">

            <button type="submit">
                <?php echo $edit_store ? "Update Store" : "Add Store"; ?>
            </button>

            <?php if ($edit_store): ?>
                <a href="stores.php">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <h2>Store Records</h2>

<form method="GET" action="stores.php">
    <input
        type="text"
        name="search"
        placeholder="Search store name, location, or contact number"
        value="<?php echo htmlspecialchars($search); ?>"
    >

    <button type="submit">Search</button>
    <a href="stores.php">Clear Search</a>
</form>

    <div class="table-container">
        <table>
            <tr>
                <th>ID</th>
                <th>Store Name</th>
                <th>Location</th>
                <th>Contact Number</th>
                <th>Date Added</th>
                <th>Added By</th>
                <th>Last Updated By</th>
                <th>Actions</th>
            </tr>

            <?php foreach ($stores as $store): ?>
                <tr>
                    <td><?php echo $store["store_id"]; ?></td>
                    <td><?php echo htmlspecialchars($store["store_name"]); ?></td>
                    <td><?php echo htmlspecialchars($store["location"]); ?></td>
                    <td><?php echo htmlspecialchars($store["contact_number"]); ?></td>
                    <td><?php echo htmlspecialchars($store["date_added"] ?? "Not available"); ?></td>
                    <td><?php echo htmlspecialchars($store["added_by_name"] ?? "Unknown"); ?></td>
                    <td><?php echo htmlspecialchars($store["updated_by_name"] ?? "Not updated"); ?></td>
                    <td>
                        <a href="stores.php?edit=<?php echo $store["store_id"]; ?>">Edit</a>

                        <form method="POST" class="inline-form"
                              onsubmit="return confirm('Delete this store and its albums?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="store_id"
                                   value="<?php echo $store["store_id"]; ?>">
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