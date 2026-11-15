<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "dbconfig.php";

function require_login() {
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit();
    }
}

function current_user_id() {
    return $_SESSION["user_id"] ?? null;
}

function log_activity($pdo, $action, $entity, $record_id, $description) {
    $sql = "INSERT INTO activity_logs
            (user_id, action, entity, record_id, description)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        current_user_id(),
        $action,
        $entity,
        $record_id,
        $description
    ]);
}
?>