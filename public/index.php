<?php
include_once "../partials/header-lp.php";
?>
<link rel="stylesheet" href="../css/landing-page/main.css">

<!-- Hero Section -->
<section class="hero" id="heroSection">
  <div class="hero-container">
    <div class="hero-left">
      <h1 class="hero-title">
        <span class="brand-name">SengkuClean</span>
      </h1>

      <p class="hero-description">Premium Shoes Laundry dengan perawatan profesional untuk sepatu kesayangan Anda</p>

      <div class="hero-buttons">
        <button class="btn-login" onclick="window.location.href='../login/login.php'">Login</button>
        <button class="btn-location"
          onclick="window.open('https://www.google.com/maps/dir//Sengkuclean+Jl.+Bengawan+Solo+No.31+Tegal+Boto+Lor,+Sumbersari+Kec.+Sumbersari,+Kabupaten+Jember,+Jawa+Timur/@-8.1677831,113.7088894,17z/data=!4m8!4m7!1m0!1m5!1m1!1s0x2dd695c52e73d537:0x3b7dcad7e00c765!2m2!1d113.7088894!2d-8.1677831?entry=ttu&g_ep=EgoyMDI1MTEyMy4xIKXMDSoASAFQAw%3D%3D', '_blank')">Our
          Location</button>
      </div>
    </div>

    <div class="hero-right">
      <div class="time-card">
        <div class="time-card-inner">
          <div class="clock-wrapper">
            <svg class="clock-icon" viewBox="0 0 120 120" width="140" height="140">
              <!-- Outer circle with blue gradient -->
              <defs>
                <linearGradient id="clockGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
                  <stop offset="100%" style="stop-color:#2563eb;stop-opacity:1" />
                </linearGradient>
                <filter id="shadow">
                  <feDropShadow dx="0" dy="4" stdDeviation="6" flood-opacity="0.3" />
                </filter>
              </defs>

              <circle cx="60" cy="60" r="55" fill="url(#clockGradient)" filter="url(#shadow)" />
              <circle cx="60" cy="60" r="48" fill="white" />

              <!-- Clock ticks -->
              <g stroke="#2563eb" stroke-width="2.5" stroke-linecap="round">
                <!-- 12 o'clock -->
                <line x1="60" y1="20" x2="60" y2="28" />
                <!-- 3 o'clock -->
                <line x1="100" y1="60" x2="92" y2="60" />
                <!-- 6 o'clock -->
                <line x1="60" y1="100" x2="60" y2="92" />
                <!-- 9 o'clock -->
                <line x1="20" y1="60" x2="28" y2="60" />

                <!-- Additional ticks -->
                <line x1="82" y1="30" x2="78" y2="34" />
                <line x1="90" y1="48" x2="85" y2="50" />
                <line x1="90" y1="72" x2="85" y2="70" />
                <line x1="82" y1="90" x2="78" y2="86" />
                <line x1="38" y1="90" x2="42" y2="86" />
                <line x1="30" y1="72" x2="35" y2="70" />
                <line x1="30" y1="48" x2="35" y2="50" />
                <line x1="38" y1="30" x2="42" y2="34" />
              </g>

              <!-- Clock hands pointing to 3:00 (15:00) -->
              <!-- Hour hand -->
              <line x1="60" y1="60" x2="75" y2="60" stroke="#1e40af" stroke-width="4" stroke-linecap="round" />
              <!-- Minute hand pointing up (00 minutes) -->
              <line x1="60" y1="60" x2="60" y2="35" stroke="#2563eb" stroke-width="3" stroke-linecap="round" />

              <!-- Center dot -->
              <circle cx="60" cy="60" r="4" fill="#1e40af" />
            </svg>
          </div>

          <div class="time-text">
            <p class="time-label">Open from</p>
            <p class="time-value">15:00 <span class="time-to">to</span><br>22:00</p>
          </div>
        </div>
      </div>
    </div>
  </div>


  <!-- Decorative elements -->
  <div class="decoration-circle circle-1"></div>
  <div class="decoration-circle circle-2"></div>
  <div class="decoration-circle circle-3"></div>
</section>

<!-- About Section -->
<section class="about-section" id="about">
  <h2>About Us</h2>
  <div class="divider"></div>
  <p class="desc">
    <strong>Sengkuclean</strong> adalah layanan laundry sepatu profesional
    yang hadir untuk menjawab kebutuhan kamu akan perawatan sepatu yang
    bersih, wangi, dan tampak seperti baru. Kami memahami bahwa sepatu bukan
    hanya pelindung kaki, tapi juga bagian dari gaya hidup dan kepercayaan
    diri. Oleh karena itu, <strong>Sengkuclean</strong> hadir dengan layanan
    premium yang mengutamakan kualitas, ketelitian, dan kepuasan pelanggan.
  </p>
  <p class="desc">
    Didukung oleh tim ahli yang berpengalaman dalam dunia perawatan sepatu,
    <strong>Sengkuclean</strong> melayani berbagai jenis sepatu mulai dari
    sneakers, boots, hingga sepatu kulit. Kami menggunakan peralatan modern
    dan bahan pembersih ramah lingkungan yang tidak merusak bahan asli
    sepatu, serta menjaga warna dan teksturnya tetap terjaga.
  </p>
  <p class="desc">
    Di <strong>Sengkuclean</strong> tidak hanya mencuci, tetapi juga
    melakukan proses detailing, deodorizing, dan perbaikan ringan jika
    diperlukan. Kami juga menyediakan layanan antar-jemput sepatu agar kamu
    tidak perlu repot keluar rumah. Dengan proses yang transparan, harga
    yang terjangkau, dan hasil yang memuaskan,
    <strong>Sengkuclean</strong> menjadi pilihan terpercaya bagi banyak
    pelanggan dari berbagai kalangan pelajar, pekerja kantoran, hingga
    kolektor sepatu.
  </p>
</section>

<section class="karyawan-section" id="karyawan">
  <h2>Employee</h2>
  <div class="divider-emp"></div>
  <div class="kotak-wrapper">
    <div class="kotak">
      <img src="../a/img/me.png" alt="James Smith" />
      <h3>James Smith</h3>
      <p class="role">Middle UI/UX</p>
      <div class="social-icons-emp">
        <a href="https://facebook.com" target="_blank">
          <img src="../a/assets/Facebook Biru.png" class="icon-emp" data-hover="../a/assets/FB Real.png"
            alt="Facebook" />
        </a>
        <a href="https://instagram.com" target="_blank">
          <img src="../a/assets/IG Biru.png" class="icon-emp" data-hover="../a/assets/IG Real.png" alt="Instagram" />
        </a>
        <a href="https://tiktok.com" target="_blank">
          <img src="../a/assets/Tiktok Biru.png" class="icon-emp" data-hover="../a/assets/Tiktok Real.png"
            alt="Tiktok" />
        </a>
      </div>
    </div>

    <div class="kotak">
      <img src="../a/img/me.png" alt="Kevin Kim" />
      <h3>Kevin Kim</h3>
      <p class="role">Senior Graphic</p>
      <div class="social-icons-emp">
        <a href="https://facebook.com" target="_blank">
          <img src="../a/assets/Facebook Biru.png" class="icon-emp" data-hover="../a/assets/FB Real.png"
            alt="Facebook" />
        </a>
        <a href="https://instagram.com" target="_blank">
          <img src="../a/assets/IG Biru.png" class="icon-emp" data-hover="../a/assets/IG Real.png" alt="Instagram" />
        </a>
        <a href="https://tiktok.com" target="_blank">
          <img src="../a/assets/Tiktok Biru.png" class="icon-emp" data-hover="../a/assets/Tiktok Real.png"
            alt="Tiktok" />
        </a>
      </div>
    </div>

    <div class="kotak">
      <img src="../a/img/me.png" alt="Lissa Shulz" />
      <h3>Lissa Shulz</h3>
      <p class="role">Junior Graphic</p>
      <div class="social-icons-emp">
        <a href="https://facebook.com" target="_blank">
          <img src="../a/assets/Facebook Biru.png" class="icon-emp" data-hover="../a/assets/FB Real.png"
            alt="Facebook" />
        </a>
        <a href="https://instagram.com" target="_blank">
          <img src="../a/assets/IG Biru.png" class="icon-emp" data-hover="../a/assets/IG Real.png" alt="Instagram" />
        </a>
        <a href="https://tiktok.com" target="_blank">
          <img src="../a/assets/Tiktok Biru.png" class="icon-emp" data-hover="../a/assets/Tiktok Real.png"
            alt="Tiktok" />
        </a>
      </div>
    </div>

    <div class="kotak">
      <img src="../a/img/me.png" alt="Kevin Kim" />
      <h3>Kevin Kim</h3>
      <p class="role">Senior Graphic</p>
      <div class="social-icons-emp">
        <a href="https://facebook.com" target="_blank">
          <img src="../a/assets/Facebook Biru.png" class="icon-emp" data-hover="../a/assets/FB Real.png"
            alt="Facebook" />
        </a>
        <a href="https://instagram.com" target="_blank">
          <img src="../a/assets/IG Biru.png" class="icon-emp" data-hover="../a/assets/IG Real.png" alt="Instagram" />
        </a>
        <a href="https://tiktok.com" target="_blank">
          <img src="../a/assets/Tiktok Biru.png" class="icon-emp" data-hover="../a/assets/Tiktok Real.png"
            alt="Tiktok" />
        </a>
      </div>
    </div>

    <div class="kotak">
      <img src="../a/img/me.png" alt="Lissa Shulz" />
      <h3>Lissa Shulz</h3>
      <p class="role">Junior Graphic</p>
      <div class="social-icons-emp">
        <a href="https://facebook.com" target="_blank">
          <img src="../a/assets/Facebook Biru.png" class="icon-emp" data-hover="../a/assets/FB Real.png"
            alt="Facebook" />
        </a>
        <a href="https://instagram.com" target="_blank">
          <img src="../a/assets/IG Biru.png" class="icon-emp" data-hover="../a/assets/IG Real.png" alt="Instagram" />
        </a>
        <a href="https://tiktok.com" target="_blank">
          <img src="../a/assets/Tiktok Biru.png" class="icon-emp" data-hover="../a/assets/Tiktok Real.png"
            alt="Tiktok" />
        </a>
      </div>
    </div>
  </div>
</section>

<section class="journey-section">
  <h2>Follow Our Journey</h2>
  <div class="divider"></div>
  <p>
    Kami bukan sekadar tempat laundry sepatu, tetapi bagian dari gaya hidup para pecinta fashion. <br />
    Ikuti perjalanan kami di media sosial dan jadi saksi bagaimana setiap sepatu kami kembalikan ke kondisi terbaiknya.
    <br />
    Mulai dari proses pencucian, tips perawatan, sampai kisah pelanggan, semuanya kami bagikan untuk kamu. <br />
  </p>
  <div class="social-icons">
    <a href="https://www.facebook.com/p/sengkuclean-100064133437924/" target="_blank">
      <img src="../a/assets/FB Putih.png" class="icon" data-hover="../a/assets/FB Real.png" alt="Facebook" />
    </a>
    <a href="https://www.instagram.com/sengkuclean/" target="_blank">
      <img src="../a/assets/IG Putih.png" class="icon" data-hover="../a/assets/IG Real.png" alt="Instagram" />
    </a>
    <a href="https://www.tiktok.com/@sengkuclean?lang=en" target="_blank">
      <img src="../a/assets/Tiktok Putih.png" class="icon" data-hover="../a/assets/Tiktok Real.png" alt="Tiktok" />
    </a>
  </div>
</section>

<section class="catalog-section" id="catalog">
  <h2>Services</h2>
  <div class="divider"></div>
  <p class="desc">
    Tidak hanya sepatu saja, Kami juga melanyani pencucian untuk <strong>Tas</strong> dan juga
    <strong>Topi</strong>. <br />Berikut jasa yang kami tawarkan:
  </p>

  <div class="catalog">
    <a href="Service.html?category=sepatu" class="catalog-box-link">
      <div class="catalog-box">
        <div class="catalog-img-wrapper">
          <img src="../a/catalog/Sepatu 10.png" alt="Sepatu" />
          <div class="overlay">
            <span class="zoom-icon">
              <img src="../a/svg/Search.svg" alt="Zoom Icon" />
            </span>
          </div>
        </div>
        <p class="title">Sepatu</p>
        <p class="detail">Pulihkan kondisi sepatu favorit Anda dari kusam, lepas sol, hingga warna pudar; kami
          menawarkan layanan cleaning mendalam, reglue kuat, dan repaint presisi agar sepatu Anda siap beraksi kembali.
        </p>
      </div>
    </a>

    <a href="Service.html?category=tas" class="catalog-box-link">
      <div class="catalog-box">
        <div class="catalog-img-wrapper">
          <img src="../a/catalog/Sepatu 10.png" alt="Sepatu" />
          <div class="overlay">
            <span class="zoom-icon">
              <img src="../a/svg/Search.svg" alt="Zoom Icon" />
            </span>
          </div>
        </div>
        <p class="title">Tas</p>
        <p class="detail">
          Serahkan tas kesayangan Anda pada layanan deep cleaning profesional kami untuk menghilangkan noda membandel
          dan mengembalikan kilau serta kualitas materialnya.
        </p>
      </div>
    </a>

    <a href="Service.html?category=topi" class="catalog-box-link">
      <div class="catalog-box">
        <div class="catalog-img-wrapper">
          <img src="../a/catalog/Sepatu 10.png" alt="Sepatu" />
          <div class="overlay">
            <span class="zoom-icon">
              <img src="../a/svg/Search.svg" alt="Zoom Icon" />
            </span>
          </div>
        </div>
        <p class="title">Topi</p>
        <p class="detail">
          Jaga bentuk dan higienitas topi koleksi Anda dengan layanan cleaning spesialis kami yang efektif menghilangkan
          noda keringat, bakteri, dan bau tanpa merusak struktur.
        </p>
      </div>
    </a>
  </div>
</section>

<div class="take-care-wrapper">
  <div class="tc-wrapper">
    <section class="take-care" id="care">
      <h2 class="utama">Care Of Your Shoes</h2>
      <div class="divider-care"></div>
      <div class="gallery">
        <div class="catalog-scroll-container">
          <div class="catalog-scroll-track">
            <img src="../a/catalog/Sepatu 1.png" alt="Gambar 1" />
            <img src="../a/catalog/Sepatu 2.png" alt="Gambar 2" />
            <img src="../a/catalog/Sepatu 3.png" alt="Gambar 3" />
            <img src="../a/catalog/Sepatu 4.png" alt="Gambar 4" />
            <img src="../a/catalog/Sepatu 5.png" alt="Gambar 5" />
            <!-- Ulangi jika ingin infinite loop -->
            <img src="../a/catalog/Sepatu 1.png" alt="Gambar 1" />
            <img src="../a/catalog/Sepatu 2.png" alt="Gambar 2" />
            <img src="../a/catalog/Sepatu 3.png" alt="Gambar 3" />
            <img src="../a/catalog/Sepatu 4.png" alt="Gambar 4" />
            <img src="../a/catalog/Sepatu 5.png" alt="Gambar 5" />
          </div>
        </div>
      </div>

      <h2 class="kedua">Bagaimana Kami Merawat Barang Kesayanganmu?</h2>
      <p>Sengkuclean ...</p>

      <div class="cards">
        <div class="card">
          <div class="icon">🛡️</div>
          <h4>Ditangani oleh Para Ahli</h4>
          <p>
            Berpengalaman lebih dari 10 tahun di industri jasa cuci sepatu.
          </p>
        </div>
        <div class="card">
          <div class="icon">🎧</div>
          <h4>Dukungan Customer Service</h4>
          <p>Selalu siap membantu kamu. Kapan pun, di mana pun.</p>
        </div>
        <div class="card">
          <div class="icon">🚚</div>
          <h4>Gratis Jemput & Antar</h4>
          <p>Layanan antar jemput gratis hingga 5 KM dari lokasi kamu.</p>
        </div>
        <div class="card">
          <div class="icon">✅</div>
          <h4>Jaminan Garansi Layanan</h4>
          <p>Jaminan garansi apabila terjadi kerusakan selama pelayanan.</p>
        </div>
      </div>
    </section>
  </div>
</div>

<!-- ====== MEMBER SECTION ====== -->
<section class="member-section" id="member">
  <h2>Keuntungan Member</h2>
  <div class="divider"></div>
  <p class="desc">
    Setiap pelanggan yang melakukan transaksi pertama dengan total pembelian lebih dari Rp80.000 berhak mendapatkan
    kartu member sebagai bentuk apresiasi dari kami. Kartu member ini dapat digunakan untuk menikmati berbagai
    keuntungan dan program loyalitas pada kunjungan berikutnya.
  </p>
  <p class="desc">
    Untuk transaksi kedua dan seterusnya, pelanggan yang sudah memiliki kartu member dan kembali melakukan transaksi
    dengan nilai di atas Rp80.000 akan memperoleh keuntungan tambahan, seperti potongan harga khusus atau bahkan layanan
    gratis sesuai dengan promo yang sedang berlaku. Program ini dibuat untuk memberikan pengalaman yang lebih
    menyenangkan dan menguntungkan bagi pelanggan setia kami.
  </p>
  </div>
</section>

<?php
include_once "../partials/footer.php";
?>