<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar | SengkuClean</title>
    <link rel="icon" type="image/png" href="../a/img/Logo.png">
    <link rel="stylesheet" href="/projeks3/css/login.css">
</head>

<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="index.php">
                    <img src="../a/img/Logo Sengku.png" alt="SengkuClean Logo">
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

            <h2 style="text-align:center; margin-bottom:20px;">DAFTAR CUSTOMER</h2>

            <?php if (isset($_GET['error'])): ?>
                <p style="color:red; text-align:center; margin-bottom:15px;">
                    <?php
                    if ($_GET['error'] === 'exists')
                        echo "Nomor handphone sudah terdaftar!";
                    elseif ($_GET['error'] === 'failed')
                        echo "Terjadi kesalahan saat menyimpan data.";
                    ?>
                </p>
            <?php endif; ?>

            <form action="process_signup.php" method="POST">
                <div class="input-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" placeholder="Masukkan nama Anda" required>
                </div>

                <div class="input-group">
                    <label for="phone">Nomor Handphone</label>
                    <input type="tel" id="phone" name="phone" placeholder="Masukkan nomor handphone Anda"
                        pattern="[0-9]{10,13}" required>
                </div>

                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Masukkan email Anda" required>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Buat password Anda" required>
                        <span class="toggle-password" onclick="togglePassword('password', 'eye')">
                            <img src="../a/svg/eye-off.svg" alt="Show Password" id="eye">
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn-login">DAFTAR</button>

                <!-- ⬇️ Tambahan teks yang kamu minta -->
                <p style="text-align:center; margin-top:15px; font-size:14px;">
                    Masukkan nomor HP dan password yang akan Anda gunakan untuk login pada transaksi berikutnya.
                </p>

                <p style="text-align:center; margin-top:15px; font-size:14px;">
                    Sudah punya akun?
                    <a href="login.php" style="color:#4CAF50; text-decoration:none;">Masuk di sini</a>
                </p>
            </form>
        </div>
    </div>

    <script>
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