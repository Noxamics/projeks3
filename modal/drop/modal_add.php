<!-- File: /modal/drop/modal_add.php - WITH AUTO STATUS "Barang Baru Masuk" -->

<!-- Success Modal (Modern Design) -->
<div class="modal-overlay" id="successModal" style="display: none;">
    <div class="modal-container">
        <button class="modal-close" onclick="hideSuccessModal()"></button>
        <div class="success-icon">
            <div class="success-circle">
                <div class="success-checkmark"></div>
            </div>
        </div>
        <h2 class="modal-title">Berhasil!</h2>
        <p class="modal-message" id="successMessage">Pesanan berhasil ditambahkan</p>
        <p class="modal-order-code" id="successOrderCode"></p>
        <button class="modal-btn" onclick="hideSuccessModal()">OK, Mengerti</button>
    </div>
</div>

<!-- Add Order Modal -->
<div class="modal" id="addModal" style="display:none;">
    <div class="modal-content large">
        <span class="close" data-target="addModal">&times;</span>
        <h2><i class="bi bi-plus-circle"></i>Tambah Pesanan Baru</h2>

        <form method="POST" action="../actions/drop/drop_add.php" class="grid-form" id="addForm">
            <!-- Customer Info Section -->
            <div class="customer-info-section" style="grid-column: 1 / -1;">
                <h3 style="margin: 0 0 16px 0; color: #b45309; font-size: 16px; font-weight: 700;">
                    Informasi Pelanggan
                </h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label>Nama Pelanggan</label>
                        <input type="text" id="customer_name" name="customer_name" required
                            placeholder="Masukkan nama pelanggan">
                    </div>
                    <div>
                        <label>No. Handphone</label>
                        <input type="text" id="phone_number" name="phone_number" required placeholder="08xxxxxxxxxx">
                    </div>
                </div>
            </div>

            <!-- Items Container -->
            <div id="itemsContainer" style="grid-column: 1 / -1;">
                <!-- Item 1 (Default) -->
                <div class="item-group" data-item-index="1">
                    <div style="position: absolute; top: 10px; right: 10px; display: flex; gap: 10px;">
                        <span class="item-badge">Item #1</span>
                        <button type="button" class="remove-item-btn" data-item="1" style="display: none;">
                            <i class="bi bi-x-lg"></i> Hapus
                        </button>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 40px;">
                        <div>
                            <label><i class="bi bi-tag"></i> Brand / Merk</label>
                            <input type="text" name="items[1][brand]" class="item-brand" required
                                placeholder="Contoh: Nike, Adidas">
                        </div>

                        <div>
                            <label><i class="bi bi-tools"></i> Layanan</label>
                            <select name="items[1][service_id]" class="item-service" required>
                                <option value="">-- Pilih Layanan --</option>
                                <?php
                                $order = "FIELD(category, 'cleaning', 'reglue', 'repaint', 'bag', 'cap'), service_name";
                                $query = "SELECT id_service, category, service_name, price_min, duration FROM services ORDER BY $order";
                                $result = mysqli_query($conn, $query);
                                while ($r = mysqli_fetch_assoc($result)) {
                                    $displayName = ucfirst($r['category']) . " - " . ucfirst($r['service_name']);
                                    echo "<option value='{$r['id_service']}' data-price='{$r['price_min']}' data-duration='{$r['duration']}'>{$displayName}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div>
                            <label><i class="bi bi-cash-coin"></i> Harga</label>
                            <input type="text" class="item-price-display" readonly value="Rp 0"
                                style="background: #f1f5f9; font-weight: 600; color: #0369a1;">
                            <input type="hidden" name="items[1][price]" class="item-price" value="0">
                        </div>

                        <div>
                            <label><i class="bi bi-clock-history"></i> Estimasi Selesai</label>
                            <input type="text" class="item-estimate" readonly value="-"
                                style="background: #f1f5f9; font-weight: 600;">
                            <input type="hidden" name="items[1][duration]" class="item-duration" value="0">
                        </div>

                        <div>
                            <label><i class="bi bi-calendar-event"></i> Tgl. Transaksi</label>
                            <input type="date" name="items[1][trans_date]" class="item-trans-date"
                                value="<?= date('Y-m-d') ?>">
                        </div>

                        <div>
                            <label><i class="bi bi-calendar-check"></i> Tanggal Estimasi Selesai</label>
                            <input type="text" class="item-est-date-display" readonly value="-"
                                style="background: #f1f5f9; font-weight: 600; color: #0369a1;">
                            <input type="hidden" name="items[1][est_finish_date]" class="item-est-date-hidden" value="">
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <label><i class="bi bi-bar-chart-steps"></i> Status</label>
                            <select name="items[1][status_id]" class="item-status" required>
                                <option value="">Pilih Status</option>
                                <?php
                                $st = $conn->query("SELECT * FROM statuses ORDER BY id_status ASC");
                                while ($s = $st->fetch_assoc()) {
                                    // Auto-select "Barang Baru Masuk"
                                    $selected = ($s['status_name'] == 'Barang Baru Masuk') ? 'selected' : '';
                                    echo "<option value='{$s['id_status']}' {$selected}>{$s['status_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <label><i class="bi bi-pencil-square"></i> Catatan Item</label>
                            <textarea name="items[1][notes]" class="item-notes" rows="2"
                                placeholder="Catatan khusus untuk item ini..."
                                style="width:100%; padding:10px; border-radius:6px; border:1px solid #d6dee9; resize: vertical;"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Item Button -->
            <div style="grid-column: 1 / -1; text-align: center; margin: 20px 0;">
                <button type="button" id="addItemBtn"
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                    <i class="bi bi-plus-circle"></i> Tambah Barang
                </button>
            </div>

            <!-- Order Summary Section -->
            <div class="order-summary" style="grid-column: 1 / -1;">
                <h3 style="margin: 0 0 16px 0; color: #0369a1; font-size: 16px; font-weight: 700;">
                    Ringkasan Pesanan
                </h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <div>
                        <label>Total Item</label>
                        <input type="text" id="totalItems" readonly value="1"
                            style="background:#fff; font-weight: 600; color: #0369a1;">
                    </div>
                    <div>
                        <label>Total Harga</label>
                        <input type="text" id="totalPriceDisplay" readonly value="Rp 0"
                            style="background:#fff; font-weight: 600; color: #0369a1; font-size: 16px;">
                        <input type="hidden" name="total_amount" id="totalPrice" value="0">
                    </div>
                    <div>
                        <label>Estimasi Terlama</label>
                        <input type="text" id="maxEstimate" readonly value="-"
                            style="background:#fff; font-weight: 600;">
                        <input type="hidden" name="max_duration" id="maxDuration" value="0">
                    </div>
                    <div>
                        <label><i class="bi bi-calendar-check"></i> Tanggal Estimasi Selesai</label>
                        <input type="text" class="item-est-date-display" readonly value="-"
                            style="background: #f1f5f9; font-weight: 600; color: #0369a1;">
                    </div>
                </div>
            </div>

            <!-- Payment & Employee Section -->
            <div class="payment-section" style="grid-column: 1 / -1;">
                <h3 style="margin: 0 0 16px 0; color: #7c3aed; font-size: 16px; font-weight: 700;">
                    Informasi Pembayaran
                </h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <div>
                        <label>Status Pembayaran</label>
                        <select name="payment_status" id="payment_status">
                            <option value="Belum Lunas">Belum Lunas</option>
                            <option value="Lunas">Lunas</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>

                    <div>
                        <label>Tanggal Pembayaran</label>
                        <input type="date" id="payment_date_display" readonly disabled
                            style="background:#f1f5f9; cursor: not-allowed;">
                        <input type="hidden" id="payment_date_hidden" name="payment_date">
                    </div>

                    <div>
                        <label>Metode Pembayaran</label>
                        <select name="payment_method" required>
                            <option value="Tunai"><i class="bi bi-cash"></i> Tunai</option>
                            <option value="Transfer"><i class="bi bi-bank"></i> Transfer</option>
                            <option value="QRIS"><i class="bi bi-qr-code"></i> QRIS</option>
                        </select>
                    </div>

                    <div>
                        <label>Nominal Pembayaran</label>
                        <input type="text" id="amount_paid_display" placeholder="Rp 0">
                        <input type="hidden" name="amount_paid" id="amount_paid">
                    </div>

                    <!-- KARYAWAN SECTION -->
                    <div style="grid-column: 1 / -1;">
                        <label><i class="bi bi-person-badge"></i> Karyawan (Kasir Aktif Hari Ini)</label>
                        <div id="kasirContainer">
                            <div
                                style='text-align: center; padding: 20px; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 8px;'>
                                <i class='bi bi-hourglass-split' style='font-size: 24px; color: #64748b;'></i>
                                <div style='font-size: 13px; color: #64748b; margin-top: 8px;'>Memuat data kasir
                                    aktif...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="full-width">
                <label><i class="bi bi-pencil-square"></i> Catatan Umum Pesanan</label>
                <textarea name="note" rows="2" placeholder="Catatan umum untuk seluruh pesanan..."
                    style="width:100%; padding:12px; border-radius:8px; border:2px solid #e2e8f0; resize: vertical;"></textarea>
            </div>

            <div class="full-width">
                <div class="button-container">
                    <button type="submit" class="save-btn" id="saveOnlyBtn">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                    <button type="button" class="print-btn save-and-print" id="saveAndPrintBtn"
                        style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                        <i class="bi bi-printer"></i> Simpan & Cetak
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Load active kasir when modal opens
    document.addEventListener('DOMContentLoaded', function () {
        const addModal = document.getElementById('addModal');
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.attributeName === 'style') {
                    const display = window.getComputedStyle(addModal).display;
                    if (display !== 'none') {
                        loadActiveKasir();
                        autoSelectBarangBaruMasuk(); // Auto-select status
                    }
                }
            });
        });

        observer.observe(addModal, { attributes: true });

        if (window.getComputedStyle(addModal).display !== 'none') {
            loadActiveKasir();
            autoSelectBarangBaruMasuk();
        }
    });

    // Auto-select "Barang Baru Masuk" for all items
    function autoSelectBarangBaruMasuk() {
        const allStatusSelects = document.querySelectorAll('.item-status');
        allStatusSelects.forEach(select => {
            // Find option with "Barang Baru Masuk"
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].text === 'Barang Baru Masuk') {
                    select.selectedIndex = i;
                    break;
                }
            }
        });
    }

    function loadActiveKasir() {
        const kasirContainer = document.getElementById('kasirContainer');

        kasirContainer.innerHTML = `
            <div style='text-align: center; padding: 20px; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 8px;'>
                <i class='bi bi-hourglass-split' style='font-size: 24px; color: #64748b; animation: spin 2s linear infinite;'></i>
                <div style='font-size: 13px; color: #64748b; margin-top: 8px;'>Memuat data kasir aktif...</div>
            </div>
        `;

        fetch('../actions/karyawan/get_active_kasir.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (!data.success || !data.has_active_kasir) {
                    kasirContainer.innerHTML = `
                        <div style='padding: 12px; background: #fef3c7; border: 2px solid #fbbf24; border-radius: 8px; color: #92400e;'>
                            <i class='bi bi-exclamation-triangle'></i> <strong>Tidak ada kasir aktif.</strong><br>
                            <span style='font-size: 13px;'>Karyawan pertama yang check-in hari ini akan menjadi kasir. ${data.message || ''}</span>
                        </div>
                        <input type='hidden' name='employee_id' value='' id='employeeIdInput'>
                    `;
                } else {
                    const kasir = data.kasir_data;
                    const roleDisplay = kasir.role || 'N/A';
                    const hasKasirRoleBadge = kasir.has_kasir_role
                        ? '<span style="background: #3b82f6; color: white; padding: 2px 8px; border-radius: 8px; font-size: 10px; margin-left: 8px;">ROLE PERMANEN</span>'
                        : '<span style="background: #f59e0b; color: white; padding: 2px 8px; border-radius: 8px; font-size: 10px; margin-left: 8px;">KASIR SEMENTARA</span>';

                    kasirContainer.innerHTML = `
                        <input type='hidden' name='employee_id' value='${kasir.id}' id='employeeIdInput'>
                        <div style='padding: 12px; background: #dcfce7; border: 2px solid #22c55e; border-radius: 8px;'>
                            <div style='display: flex; align-items: center; gap: 10px;'>
                                <i class='bi bi-person-circle' style='font-size: 32px; color: #15803d;'></i>
                                <div style='flex: 1;'>
                                    <div style='font-weight: 700; color: #15803d; font-size: 16px;'>
                                        ${kasir.name}
                                        ${hasKasirRoleBadge}
                                    </div>
                                    <div style='font-size: 12px; color: #16a34a; margin-top: 4px;'>
                                        <strong>Kode:</strong> ${kasir.code} | 
                                        <strong>Role Hari Ini:</strong> ${roleDisplay} | 
                                        <strong>Check-in:</strong> ${kasir.check_in_time}
                                    </div>
                                </div>
                                <div style='background: #22c55e; color: white; padding: 6px 16px; border-radius: 12px; font-size: 11px; font-weight: 700; box-shadow: 0 2px 4px rgba(34, 197, 94, 0.3);'>
                                    ✓ KASIR AKTIF
                                </div>
                            </div>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading kasir:', error);
                kasirContainer.innerHTML = `
                    <div style='padding: 12px; background: #fee2e2; border: 2px solid #ef4444; border-radius: 8px; color: #991b1b;'>
                        <i class='bi bi-x-circle'></i> <strong>Error memuat data kasir</strong><br>
                        <span style='font-size: 12px;'>Silakan refresh halaman atau hubungi administrator.</span>
                    </div>
                    <input type='hidden' name='employee_id' value='' id='employeeIdInput'>
                `;
            });
    }

    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
</script>