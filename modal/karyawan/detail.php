<!-- ================================================
     Detail Modal - FIXED VERSION (No Attendance Tab)
     File: modal/karyawan/detail.php
     ================================================ -->

<div id="modalDetail" class="modal" role="dialog" aria-labelledby="detailModalTitle" aria-hidden="true">
    <div class="modal-box" style="max-width: 800px;">
        <h3 id="detailModalTitle">Detail Karyawan</h3>

        <!-- Employee Header -->
        <div class="detail-header">
            <img id="detail_photo" src="" alt="Employee Photo" class="detail-photo">

            <div class="detail-info">
                <h3 id="detail_name">-</h3>
                <p id="detail_code">Kode: -</p>
                <p id="detail_phone">HP: -</p>
                <p id="detail_join">Bergabung: -</p>
                <div id="detail_roles" style="display: flex; gap: 5px; margin-top: 8px;"></div>
                <span id="detail_status" class="status-badge">-</span>
            </div>
        </div>

        <!-- Tabs (TANPA TAB ABSENSI) -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('info')">
                Info
            </button>
            <button class="tab-btn" onclick="switchTab('performance')">
                Performa
            </button>
        </div>

        <!-- Tab: Info -->
        <div id="tab-info" class="tab-content active">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div style="background: #f8fbff; padding: 15px; border-radius: 10px; border: 2px solid #e3f2fd;">
                    <strong style="color: #0066cc; display: block; margin-bottom: 8px;">Informasi Dasar</strong>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>ID:</strong> <span id="info_id">-</span>
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Kode:</strong> <span id="info_code">-</span>
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Nama:</strong> <span id="info_name">-</span>
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>No HP:</strong> <span id="info_phone">-</span>
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Email:</strong> <span id="info_email">-</span>
                    </p>
                </div>

                <div style="background: #f8fbff; padding: 15px; border-radius: 10px; border: 2px solid #e3f2fd;">
                    <strong style="color: #0066cc; display: block; margin-bottom: 8px;">Status & Role</strong>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Status:</strong> <span id="info_status">-</span>
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Bergabung:</strong> <span id="info_join">-</span>
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Tgl Lahir:</strong> <span id="info_birth">-</span>
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Role:</strong> <span id="info_roles">-</span>
                    </p>
                </div>
            </div>

            <!-- Additional Info -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div style="background: #f8fbff; padding: 15px; border-radius: 10px; border: 2px solid #e3f2fd;">
                    <strong style="color: #0066cc; display: block; margin-bottom: 8px;">Alamat & Kontak</strong>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Alamat:</strong><br>
                        <span id="info_address">-</span>
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Kontak Darurat:</strong><br>
                        <span id="info_emergency">-</span>
                    </p>
                </div>

                <div style="background: #f8fbff; padding: 15px; border-radius: 10px; border: 2px solid #e3f2fd;">
                    <strong style="color: #0066cc; display: block; margin-bottom: 8px;">Finansial</strong>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Gaji Pokok:</strong><br>
                        <span id="info_salary" style="font-size: 20px; color: #2ecc71; font-weight: 700;">-</span>
                    </p>
                </div>
            </div>

            <!-- Info Notice: Untuk Absensi -->
            <div
                style="background: #e3f2fd; border-left: 4px solid #0066cc; padding: 15px; margin-top: 20px; border-radius: 8px;">
                <strong style="color: #0066cc;">ℹ️ Absensi</strong>
                <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">
                    Untuk melakukan check in/out atau melihat riwayat absensi, gunakan tombol
                    <strong>Attendance</strong> di card karyawan
                </p>
            </div>
        </div>

        <!-- Tab: Performance -->
        <div id="tab-performance" class="tab-content">
            <div
                style="background: linear-gradient(135deg, #f8fbff 0%, #e3f2fd 100%); padding: 20px; border-radius: 15px; border: 2px solid #e3f2fd; margin-bottom: 20px;">
                <h4 style="color: #0066cc; margin-bottom: 15px;">Performa Hari Ini</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div style="background: white; padding: 15px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 36px; font-weight: 700; color: #0066cc;" id="perf_today_shoes">0</div>
                        <div style="color: #666; font-size: 14px;">Sepatu Selesai</div>
                    </div>
                    <div style="background: white; padding: 15px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 36px; font-weight: 700; color: #2ecc71;" id="perf_today_score">0%</div>
                        <div style="color: #666; font-size: 14px;">Performance Score</div>
                    </div>
                </div>
            </div>

            <div style="background: #f8fbff; padding: 20px; border-radius: 15px; border: 2px solid #e3f2fd;">
                <h4 style="color: #0066cc; margin-bottom: 15px;">Statistik 30 Hari Terakhir</h4>
                <div id="performance_stats">
                    <p style="color: #666; text-align: center;">Data akan ditampilkan di sini</p>
                </div>
            </div>
        </div>

        <!-- Close Button -->
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closeDetailModal()" style="width: 100%;">
                Tutup
            </button>
        </div>
    </div>
</div>