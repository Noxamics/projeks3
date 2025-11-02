<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login | SengkuClean</title>
    <link rel="stylesheet" href="../css/login.css" />
</head>

<body>
    <!-- =============================
       HEADER
  ============================== -->
    <header>
        <div class="container">
            <div class="logo">
                <a href="index.php">
                    <img src="../a/img/Logo Sengku.png" alt="SengkuClean Logo" />
                </a>
            </div>
            <div class="nav-right">
                <a href="index.php">HOME</a>
            </div>
        </div>
    </header>

    <!-- =============================
       LOGIN PAGE
  ============================== -->
    <div class="login-page">
        <div class="login-box">
            <img src="../a/img/Logo Sengku.png" alt="SengkuClean Logo" class="logo-login" />

            <form action="login_process.php" method="POST">
                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Masukkan password" required />
                        <span class="toggle-password" id="toggle-password">
                            <img src="../a/svg/eye-off.svg" alt="Show Password" id="eye-icon" />
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn-login">MASUK</button>
            </form>
        </div>
    </div>


    <script src="../js/login.js"></script>
</body>

</html>