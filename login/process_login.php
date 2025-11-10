<?php
// process_login.php (diperbarui)
// Pastikan file ini disimpan dalam encoding UTF-8 tanpa BOM

session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

// Optional CSRF check: only if token was set in session/form
if (isset($_SESSION['csrf_token'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: login.php?error=invalid");
        exit();
    }
}

$login_type = $_POST['login_type'] ?? '';

if ($login_type === 'customer') {
    $email = trim($_POST['email'] ?? '');
    $order_code = trim($_POST['order_code'] ?? '');

    if (empty($email) || empty($order_code)) {
        header("Location: login.php?error=empty");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: login.php?error=invalid");
        exit();
    }

    $stmt = $conn->prepare("
        SELECT c.id_customer, c.name, c.email, c.phone, d.order_code
        FROM customers c
        INNER JOIN drops d ON c.id_customer = d.customer_id
        WHERE c.email = ? AND d.order_code = ?
        LIMIT 1
    ");

    if (!$stmt) {
        header("Location: login.php?error=invalid");
        exit();
    }

    $stmt->bind_param("ss", $email, $order_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $customer = $result->fetch_assoc();

        session_regenerate_id(true);
        $_SESSION['user_id'] = $customer['id_customer'];
        $_SESSION['user_type'] = 'customer';
        $_SESSION['user_name'] = $customer['name'];
        $_SESSION['user_email'] = $customer['email'];
        $_SESSION['user_phone'] = $customer['phone'];
        $_SESSION['login_time'] = time();

        unset($_SESSION['csrf_token']);
        $stmt->close();

        // CORRECT PATH: dari /login/process_login.php ke /customer/dashboard/dashboard.php
        header("Location: ../customer/dashboard.php");
        exit();
    } else {
        $stmt->close();
        header("Location: login.php?error=invalid");
        exit();
    }

} elseif ($login_type === 'admin') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        header("Location: login.php?error=empty");
        exit();
    }

    $stmt = $conn->prepare("
        SELECT id_admin, username, password, full_name, email
        FROM admin
        WHERE username = ?
        LIMIT 1
    ");

    if (!$stmt) {
        header("Location: login.php?error=invalid");
        exit();
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        if ($password === $admin['password']) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $admin['id_admin'];
            $_SESSION['user_type'] = 'admin';
            $_SESSION['user_name'] = $admin['full_name'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['user_email'] = $admin['email'];
            $_SESSION['login_time'] = time();

            unset($_SESSION['csrf_token']);
            $stmt->close();

            // CORRECT PATH: dari /login/process_login.php ke /admin/dashboard/dashboard.php
            header("Location: ../admin/dashboard.php");
            exit();
        } else {
            $stmt->close();
            header("Location: login.php?error=invalid");
            exit();
        }
    } else {
        $stmt->close();
        header("Location: login.php?error=invalid");
        exit();
    }

} else {
    header("Location: login.php?error=invalid");
    exit();
}
