<?php
require_once '../db.php';

// Jika sudah login, redirect ke dashboard masing-masing
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../customer/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login | SengkuClean</title>
    <link rel="icon" type="image/png" href="../a/img/Logo.png">
    <link rel="stylesheet" href="/projeks3/css/login.css" />
</head>

<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="index.php">
                    <img src="../a/img/Logo Sengku.png" alt="SengkuClean Logo" />
                </a>
            </div>
            <div class="nav-right">
                <a href="../public/index.php">HOME</a>
            </div>
        </div>
    </header>

    <div class="login-page">
        <div class="login-box">
            <img src="../a/img/Logo Sengku.png" alt="SengkuClean Logo" class="logo-login" />

            <h2 style="text-align:center; margin-bottom:20px;">LOGIN</h2>

            <!-- Pesan Error/Success -->
            <?php if (isset($_GET['error'])): ?>
                <p style="color:red; text-align:center; margin-bottom:15px;">
                    <?php
                    if ($_GET['error'] === 'invalid') {
                        echo "Login gagal! Periksa kembali data Anda.";
                    } elseif ($_GET['error'] === 'empty') {
                        echo "Mohon isi semua field!";
                    } elseif ($_GET['error'] === 'logout') {
                        echo "Anda telah logout.";
                    } elseif ($_GET['error'] === 'unauthorized') {
                        echo "Silakan login terlebih dahulu.";
                    }
                    ?>
                </p>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <p style="color:green; text-align:center; margin-bottom:15px;">
                    <?php
                    if ($_GET['success'] === 'logout') {
                        echo "Logout berhasil!";
                    } elseif ($_GET['success'] === 'registered') {
                        echo "Pendaftaran berhasil! Silakan login.";
                    }
                    ?>
                </p>
            <?php endif; ?>

            <!-- Tab untuk memilih tipe login -->
            <div class="login-tabs">
                <button class="tab-btn active" onclick="showLoginForm('customer')">Customer</button>
                <button class="tab-btn" onclick="showLoginForm('admin_email')">Admin</button>
            </div>

            <!-- Form Login Customer -->
            <form id="customer-form" class="login-form active" action="process_login.php" method="POST">
                <input type="hidden" name="login_type" value="customer">

                <div class="input-group">
                    <label for="customer_phone">Nomor Handphone</label>
                    <input type="tel" id="customer_phone" name="phone" placeholder="Masukkan nomor handphone Anda"
                        pattern="[0-9]{10,13}" required />
                </div>

                <div class="input-group">
                    <label for="customer_password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="customer_password" name="password"
                            placeholder="Masukkan password Anda" required />
                        <span class="toggle-password" onclick="togglePassword('customer_password', 'customer_eye')">
                            <img src="../a/svg/eye-off.svg" alt="Show Password" id="customer_eye" />
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn-login">MASUK</button>

                <p style="text-align:center; margin-top:15px; font-size:14px;">
                    Belum punya akun? <a href="signup.php" style="color:#4CAF50; text-decoration:none;">Daftar di
                        sini</a>
                </p>
            </form>

            <!-- Form Login Admin via Email -->
            <form id="admin_email-form" class="login-form" action="send_otp.php" method="POST">
                <div class="input-group">
                    <label for="admin_email">Email Admin</label>
                    <input type="email" id="admin_email" name="email" placeholder="Masukkan email admin" required />
                </div>

                <button type="submit" class="btn-login">Kirim Kode</button>

                <p style="text-align:center; margin-top:15px; font-size:14px;">
                    <a href="change_admin_email.php" style="color:#4CAF50; text-decoration:none;">
                        Ganti Email Admin
                    </a>
                </p>
            </form>
        </div>
    </div>

    <script>
        function showLoginForm(type) {
            // Remove active class from all tabs and forms
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.login-form').forEach(form => form.classList.remove('active'));

            // Add active class to selected tab and form
            event.target.classList.add('active');
            document.getElementById(type + '-form').classList.add('active');
        }

        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === 'password') {
                input.type = 'text';
                icon.src = '../a/svg/eye-on.svg';
            } else {
                input.type = 'password';
                icon.src = '../a/svg/eye-off.svg';
            }
        }
    </script>
</body>

</html>