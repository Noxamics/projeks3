<?php
session_start();
include('../db.php');

// Fetch all services from database
$query = "SELECT * FROM services ORDER BY category, service_name";
$result = mysqli_query($conn, $query);
$services = [];
while ($row = mysqli_fetch_assoc($result)) {
    $services[] = $row;
}

// Group services by category
$categorized_services = [];
foreach ($services as $service) {
    $category = $service['category'];
    if (!isset($categorized_services[$category])) {
        $categorized_services[$category] = [];
    }
    $categorized_services[$category][] = $service;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Catalog | SengkuClean</title>
    <link rel="stylesheet" href="../css/catalog.css" />
</head>

<body>
    <!-- =============================
       HEADER
  ============================== -->
    <header>
        <div class="container header-container">
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
       CATALOG SECTION
  ============================== -->
    <section class="menu-section">
        <div class="container">
            <h2>CATALOG</h2>
            <p class="desc">
                Kami memberikan berbagai macam layanan untuk perawatan barang kesayangan anda yang akan dikerjakan oleh
                tim kami yang sudah berpengalaman dan professional.
            </p>

            <!-- Filter Pills -->
            <div class="filter-pills">
                <button class="pill-btn active" onclick="filterCategory('all', this)">All</button>
                <?php 
                $categories = array_keys($categorized_services);
                foreach ($categories as $category): 
                ?>
                <button class="pill-btn" onclick="filterCategory('<?php echo strtolower($category); ?>', this)">
                    <?php echo ucfirst($category); ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Catalog Grid -->
            <div id="catalog-grid" class="catalog">
                <?php foreach ($services as $service): ?>
                <div class="catalog-box" data-category="<?php echo strtolower($service['category']); ?>">
                    <?php 
                    // Manual mapping untuk gambar berdasarkan service_name
                    $imageMap = [
                        'Deep' => 'Sepatu 1.png',
                        'Leather Care' => 'Sepatu 2.png',
                        'Regular' => 'Sepatu 3.png',
                        'Suede Care' => 'Sepatu 4.png',
                        'Unyellowing' => 'Sepatu 5.png',
                        'Whitening' => 'Sepatu 6.png',
                        'Deep Cleaning' => 'Sepatu 1.png',
                        'Leather Care Cleaning' => 'Sepatu 2.png',
                        'Regular Cleaning' => 'Sepatu 3.png',
                    ];
                    
                    // Cari gambar berdasarkan nama service
                    $imageName = 'sep.png'; // default
                    foreach ($imageMap as $key => $img) {
                        if (stripos($service['service_name'], $key) !== false) {
                            $imageName = $img;
                            break;
                        }
                    }
                    
                    $imagePath = '../a/catalog/' . $imageName;
                    ?>
                    <img src="<?php echo $imagePath; ?>" 
                         alt="<?php echo htmlspecialchars($service['service_name']); ?>" 
                         onerror="this.src='../a/catalog/sep.png'" />
                    
                    
                    <div class="catalog-content">
                        <p class="title"><?php echo htmlspecialchars($service['service_name']); ?></p>
                        <p class="category-label"><?php echo ucfirst(htmlspecialchars($service['category'])); ?></p>
                        <p class="detail"><?php echo htmlspecialchars($service['description']); ?></p>
                        <?php if (isset($service['price']) && $service['price'] > 0): ?>
                        <p class="price">Rp <?php echo number_format((float)$service['price'], 0, ',', '.'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($services)): ?>
            <div class="empty-state">
                <p>Belum ada layanan tersedia.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <script src="../js/catalog.js"></script>
</body>

</html>