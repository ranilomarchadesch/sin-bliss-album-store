<?php
require_once "auth.php";

if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$message = "";

if (isset($_GET["registered"])) {
    $message = "Registration successful! You can now log in.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {
        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["user_id"];
        $_SESSION["first_name"] = $user["first_name"];
        $_SESSION["last_name"] = $user["last_name"];

        log_activity(
            $pdo,
            "LOGIN",
            "users",
            $user["user_id"],
            "User logged in."
        );

        header("Location: index.php");
        exit();
    } else {
        $message = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Sin Bliss Album Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-container">
    <h1>Welcome Back!</h1>
    <p>Login to your account.</p>

    <?php if ($message): ?>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <p>Don't have an account? <a href="register.php">Register here</a></p>
</div>

</body>
</html>