<?php
session_start();
include '../partials/headerAdmin.php';
?>

<link rel="stylesheet" href="../css/dashboard.css">

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Dashboard</h1>
    </div>

    <div class="dashboard-content">
        <!-- TIMELINE SECTION -->
        <div class="timeline-card">
            <h2>Timeline</h2>

            <div class="filter-row">
                <select>
                    <option>All</option>
                    <option>Cleaning</option>
                    <option>Reglue</option>
                    <option>Repaint</option>
                </select>

                <select>
                    <option>All</option>
                    <option>Proses</option>
                    <option>Selesai</option>
                </select>

                <div class="search-box">
                    <input type="text" placeholder="Cari...">
                    <button><i class="fa fa-search"></i></button>
                </div>
            </div>

            <div class="filter-type">
                <button class="active">All</button>
                <button>Cleaning</button>
                <button>Reglue</button>
                <button>Repaint</button>
            </div>

            <div class="timeline-body">
                <p>Belum ada data untuk ditampilkan.</p>
            </div>
        </div>

        <!-- CALENDAR + DEADLINE SECTION -->
        <div class="calendar-card">
            <div class="calendar-header">
                <h3>January 2025</h3>
                <div class="calendar-nav">
                    <button>&lt;</button>
                    <button>&gt;</button>
                </div>
            </div>

            <div class="calendar-grid">
                <div class="day">S</div><div class="day">M</div><div class="day">T</div>
                <div class="day">W</div><div class="day">T</div><div class="day">F</div><div class="day">S</div>
                <!-- Sample calendar days -->
                <?php for ($i=1; $i<=31; $i++): ?>
                    <div class="date <?php echo $i==7 ? 'active' : ''; ?>"><?php echo $i; ?></div>
                <?php endfor; ?>
            </div>

            <div class="deadline-section">
                <h3>Deadline</h3>
                <div class="deadline-item red">
                    <div>
                        <h4>Nama Pelanggan</h4>
                        <p>Selasa, 7 Januari 2025</p>
                    </div>
                    <i class="fa fa-angle-right"></i>
                </div>
                <div class="deadline-item yellow">
                    <div>
                        <h4>Nama Pelanggan</h4>
                        <p>Jum’at, 10 Januari 2025</p>
                    </div>
                    <i class="fa fa-angle-right"></i>
                </div>
                <div class="deadline-item green">
                    <div>
                        <h4>Nama Pelanggan</h4>
                        <p>Rabu, 15 Januari 2025</p>
                    </div>
                    <i class="fa fa-angle-right"></i>
                </div>
                <div class="deadline-item blue">
                    <div>
                        <h4>Nama Pelanggan</h4>
                        <p>Kamis, 30 Januari 2025</p>
                    </div>
                    <i class="fa fa-angle-right"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../partials/footer.php'; ?>
