<?php
require_once "auth.php";

if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $email = trim($_POST["email"]);
    $address = trim($_POST["address"]);
    $age = $_POST["age"] !== "" ? (int) $_POST["age"] : null;
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if (
        $first_name === "" ||
        $last_name === "" ||
        $email === "" ||
        $password === ""
    ) {
        $message = "Please complete all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } elseif (
    strlen($password) < 8 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password)
) {
    $message = "Password must contain at least 8 characters, including uppercase, lowercase, and numbers.";
} elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $message = "Email is already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users
                    (first_name, last_name, email, password, address, age)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $first_name,
                $last_name,
                $email,
                $hashed_password,
                $address,
                $age
            ]);

            header("Location: login.php?registered=1");
            exit();

        if (
    strlen($password) < 8 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password)
) {
    $error = "Password must contain at least 8 characters, including uppercase, lowercase, and numbers.";
} else {

    $hashedPassword = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

}
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Sin Bliss Album Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-container">
    <h1>Create an Account</h1>
    <p>Register to access Sin Bliss Album Store.</p>

    <?php if ($message): ?>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>First Name</label>
        <input type="text" name="first_name" required>

        <label>Last Name</label>
        <input type="text" name="last_name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Address</label>
        <input type="text" name="address">

        <label>Age</label>
        <input type="number" name="age" min="1">

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Confirm Password</label>
        <input type="password" name="confirm_password" required>

        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="login.php">Login here</a></p>
</div>

</body>
</html>