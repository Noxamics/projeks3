<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sengkuclean</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="../a/img/Logo.png">
  
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <link rel="stylesheet" href="../css/style.css">
  
  <style>
    /* ===== REMOVE TAP HIGHLIGHT ===== */
    * {
      -webkit-tap-highlight-color: transparent;
      -webkit-touch-callout: none;
      -webkit-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
    }

    /* ===== MOBILE MENU STYLES ===== */
    .mobile-menu-toggle {
      display: none;
      background: none;
      border: none;
      color: #fff;
      font-size: 24px;
      cursor: pointer;
      padding: 8px;
      z-index: 1001;
      transition: all 0.3s ease;
      -webkit-tap-highlight-color: transparent;
    }

    .mobile-menu-toggle:active {
      transform: scale(0.95);
    }

    header.scrolled .mobile-menu-toggle {
      color: #1d56a7;
    }

    /* Mobile Navigation Menu */
    .nav-mobile {
      position: fixed;
      top: 0;
      right: -100%;
      width: 280px;
      height: 100vh;
      background: linear-gradient(135deg, #1d56a7 0%, #2563eb 100%);
      z-index: 999;
      padding: 80px 30px 30px;
      transition: right 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
      box-shadow: -5px 0 15px rgba(0, 0, 0, 0.3);
      overflow-y: auto;
    }

    .nav-mobile.active {
      right: 0;
    }

    /* Mobile Navigation Links */
    .nav-mobile a {
      display: block;
      color: #fff;
      text-decoration: none;
      font-size: 16px;
      font-weight: 600;
      padding: 15px 20px;
      margin-bottom: 5px;
      border-radius: 10px;
      transition: all 0.3s ease;
      border-left: 3px solid transparent;
      letter-spacing: 0.5px;
      -webkit-tap-highlight-color: transparent;
    }

    .nav-mobile a:hover,
    .nav-mobile a.active {
      background: rgba(255, 255, 255, 0.15);
      border-left-color: #fff;
      transform: translateX(5px);
    }

    /* Catalog Link Highlight */
    .nav-mobile .catalog-link {
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.2);
    }

    .nav-mobile .catalog-link a {
      background: rgba(255, 255, 255, 0.1);
      text-align: center;
      font-weight: 700;
    }

    .nav-mobile .catalog-link a:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: translateX(0) scale(1.05);
    }

    /* ===== RESPONSIVE MEDIA QUERIES ===== */
    @media (max-width: 768px) {
      .mobile-menu-toggle {
        display: block;
      }

      .nav-right {
        display: none !important;
      }

      header .container {
        padding: 0 20px;
      }

      #logo-img {
        height: 50px;
      }

      header.scrolled #logo-img {
        height: 35px;
      }
    }

    @media (max-width: 480px) {
      .nav-mobile {
        width: 100%;
        right: -100%;
      }

      .nav-mobile.active {
        right: 0;
      }

      #logo-img {
        height: 45px;
      }

      header.scrolled #logo-img {
        height: 30px;
      }
    }

    /* Prevent body scroll when menu is open */
    body.menu-open {
      overflow: hidden;
    }

    /* Animation for hamburger/close icon */
    .mobile-menu-toggle i {
      transition: all 0.3s ease;
      display: inline-block;
    }

    /* Rotate icon when menu is active */
    .mobile-menu-toggle.active i {
      transform: rotate(180deg);
    }

    /* Scale animation on click */
    .mobile-menu-toggle:active i {
      transform: scale(0.9);
    }

    .mobile-menu-toggle.active:active i {
      transform: rotate(180deg) scale(0.9);
    }
  </style>
</head>

<body>

  <header id="main-header">
    <div class="container">
      <div class="logo">
        <a href="index.php">
          <img id="logo-img" src="../a/img/LGG.png" alt="Logo" />
        </a>
      </div>

      <!-- Desktop Navigation -->
      <nav class="nav-right">
        <a href="#heroSection" class="active">HOME</a>
        <a href="#about">ABOUT</a>
        <a href="#karyawan">EMPLOYEE</a>
        <a href="#service">SERVICE</a>
        <a href="#care">CARE</a>
        <a href="#member">MEMBER</a>
        <a href="catalog.php">CATALOG</a>
      </nav>

      <!-- Mobile Menu Toggle Button -->
      <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
      </button>
    </div>
  </header>

  <!-- Mobile Navigation Menu -->
  <nav class="nav-mobile" id="navMobile">
    <a href="#heroSection">HOME</a>
    <a href="#about">ABOUT</a>
    <a href="#karyawan">EMPLOYEE</a>
    <a href="#service">SERVICE</a>
    <a href="#care">CARE</a>
    <a href="#member">MEMBER</a>
    
    <div class="catalog-link">
      <a href="catalog.php">CATALOG</a>
    </div>
  </nav>

</body>
</html>