<?php
require_once '../db.php';
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login_admin.php?error=unauthorized");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_email = trim($_POST['email']);
    $id_admin = $_SESSION['user_id'];

    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        header("Location: change_email.php?error=invalid");
        exit;
    }

    $stmt = $conn->prepare("UPDATE admin SET email=? WHERE id_admin=?");
    $stmt->bind_param("si", $new_email, $id_admin);
    $stmt->execute();

    header("Location: ../admin/dashboard.php?success=email_updated");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Ganti Email Admin</title>
</head>

<body>
    <div class="login-box">
        <h2>Ganti Email Admin</h2>
        <form action="" method="POST">
            <label>Email Baru</label>
            <input type="email" name="email" required>
            <button type="submit">Perbarui Email</button>
        </form>
    </div>
</body>

</html>