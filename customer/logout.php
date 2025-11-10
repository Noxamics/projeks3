<?php
// customer/logout.php
require_once '../db.php';

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke halaman login
header("Location: ../login/login.php?success=logout");
exit();
?>