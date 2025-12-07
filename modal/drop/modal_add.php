<!-- File: /modal/drop/modal_add.php - AUTO KASIR VERSION -->
<div class="modal" id="addModal" style="display:none;">
    <div class="modal-content large">
        <span class="close" data-target="addModal">&times;</span>
        <h2>➕ Tambah Pesanan Baru</h2>

        <form method="POST" action="../actions/drop/drop_add.php" class="grid-form" id="addForm">
            <!-- Customer Info Section -->
            <div class="customer-info-section" style="grid-column: 1 / -1;">
                <h3 style="margin: 0 0 16px 0; color: #b45309; font-size: 16px; font-weight: 700;">
                    👤 Informasi Pelanggan
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
                        <button type="button" class="remove-item-btn" data-item="1" style="display: none;">✕
                            Hapus</button>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 40px;">
                        <div>
                            <label>🏷️ Brand / Merk</label>
                            <input type="text" name="items[1][brand]" class="item-brand" required
                                placeholder="Contoh: Nike, Adidas">
                        </div>

                        <div>
                            <label>🛠️ Layanan</label>
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
                            <label>💰 Harga</label>
                            <input type="text" class="item-price-display" readonly value="Rp 0"
                                style="background: #f1f5f9; font-weight: 600; color: #0369a1;">
                            <input type="hidden" name="items[1][price]" class="item-price" value="0">
                        </div>

                        <div>
                            <label>⏱️ Estimasi Selesai</label>
                            <input type="text" class="item-estimate" readonly value="-"
                                style="background: #f1f5f9; font-weight: 600;">
                            <input type="hidden" name="items[1][duration]" class="item-duration" value="0">
                        </div>

                        <div>
                            <label>📅 Tgl. Transaksi</label>
                            <input type="date" name="items[1][trans_date]" class="item-trans-date"
                                value="<?= date('Y-m-d') ?>">
                        </div>

                        <div>
                            <label>📆 Tanggal Estimasi Selesai</label>
                            <input type="text" class="item-est-date-display" readonly value="-"
                                style="background: #f1f5f9; font-weight: 600; color: #0369a1;">
                            <input type="hidden" name="items[1][est_finish_date]" class="item-est-date-hidden" value="">
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <label>📊 Status</label>
                            <select name="items[1][status_id]" class="item-status" required>
                                <option value="">Pilih Status</option>
                                <?php
                                $st = $conn->query("SELECT * FROM statuses ORDER BY id_status ASC");
                                while ($s = $st->fetch_assoc()) {
                                    echo "<option value='{$s['id_status']}'>{$s['status_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <label>📝 Catatan Item</label>
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
                    ➕ Tambah Barang
                </button>
            </div>

            <!-- Order Summary Section -->
            <div class="order-summary" style="grid-column: 1 / -1;">
                <h3 style="margin: 0 0 16px 0; color: #0369a1; font-size: 16px; font-weight: 700;">📋 Ringkasan
                    Pesanan</h3>
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
                        <label>📆 Tanggal Estimasi Selesai</label>
                        <input type="text" class="item-est-date-display" readonly value="-"
                            style="background: #f1f5f9; font-weight: 600; color: #0369a1;">
                    </div>
                </div>
            </div>

            <!-- Payment & Employee Section -->
            <div class="payment-section" style="grid-column: 1 / -1;">
                <h3 style="margin: 0 0 16px 0; color: #7c3aed; font-size: 16px; font-weight: 700;">💳 Informasi
                    Pembayaran</h3>
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
                            <option value="Tunai">💵 Tunai</option>
                            <option value="Transfer">🏦 Transfer</option>
                            <option value="QRIS">📱 QRIS</option>
                            <option value="Debit">💳 Debit</option>
                            <option value="Credit">💳 Credit</option>
                        </select>
                    </div>

                    <div>
                        <label>Nominal Pembayaran</label>
                        <input type="text" id="amount_paid_display" placeholder="Rp 0">
                        <input type="hidden" name="amount_paid" id="amount_paid">
                    </div>

                    <!-- KARYAWAN SECTION - AUTO KASIR ONLY -->
                    <div style="grid-column: 1 / -1;">
                        <label>👷 Karyawan (Kasir)</label>
                        <?php
                        // Query untuk mendapatkan karyawan dengan role kasir yang aktif
                        $kasirQuery = "
                            SELECT DISTINCT e.id_employee, e.name, e.employee_code 
                            FROM employees e
                            INNER JOIN employee_roles er ON e.id_employee = er.employee_id
                            WHERE e.status = 'Aktif' 
                            AND er.role_type = 'kasir'
                            ORDER BY e.name ASC
                        ";

                        $kasirEmployees = $conn->query($kasirQuery);
                        $kasirCount = $kasirEmployees->num_rows;

                        if ($kasirCount === 0) {
                            // Tidak ada kasir aktif
                            echo "<div style='padding: 12px; background: #fef3c7; border: 2px solid #fbbf24; border-radius: 8px; color: #92400e;'>
                                    ⚠️ <strong>Tidak ada kasir aktif.</strong><br>
                                    <span style='font-size: 13px;'>Pastikan ada karyawan yang sudah check-in hari ini.</span>
                                  </div>";
                            echo "<input type='hidden' name='employee_id' value=''>";

                        } elseif ($kasirCount === 1) {
                            // Hanya 1 kasir - auto select
                            $kasir = $kasirEmployees->fetch_assoc();
                            echo "<input type='hidden' name='employee_id' value='{$kasir['id_employee']}'>
                                  <div style='padding: 12px; background: #dcfce7; border: 2px solid #22c55e; border-radius: 8px;'>
                                    <div style='display: flex; align-items: center; gap: 10px;'>
                                        <span style='font-size: 24px;'>👤</span>
                                        <div>
                                            <div style='font-weight: 600; color: #15803d; font-size: 15px;'>{$kasir['name']}</div>
                                            <div style='font-size: 12px; color: #16a34a;'>Kode: {$kasir['employee_code']} | Role: Kasir</div>
                                        </div>
                                        <div style='margin-left: auto; background: #22c55e; color: white; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600;'>
                                            AKTIF
                                        </div>
                                    </div>
                                  </div>";

                        } else {
                            // Multiple kasir - show dropdown
                            echo "<select name='employee_id' required style='padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px;'>
                                    <option value=''>-- Pilih Kasir --</option>";
                            $kasirEmployees->data_seek(0); // Reset pointer
                            while ($kasir = $kasirEmployees->fetch_assoc()) {
                                echo "<option value='{$kasir['id_employee']}'>{$kasir['name']} ({$kasir['employee_code']})</option>";
                            }
                            echo "</select>";
                            echo "<p style='margin: 8px 0 0 0; font-size: 12px; color: #64748b;'>
                                    ℹ️ Menampilkan karyawan dengan role kasir yang sedang aktif
                                  </p>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="full-width">
                <label>📝 Catatan Umum Pesanan</label>
                <textarea name="note" rows="2" placeholder="Catatan umum untuk seluruh pesanan..."
                    style="width:100%; padding:12px; border-radius:8px; border:2px solid #e2e8f0; resize: vertical;"></textarea>
            </div>

            <div class="full-width">
                <div class="button-container">
                    <button type="submit" class="save-btn" id="saveOnlyBtn">💾 Simpan</button>
                    <button type="button" class="print-btn save-and-print" id="saveAndPrintBtn"
                        style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">🖨️ Simpan &
                        Cetak</button>
                </div>
            </div>
        </form>
    </div>
</div>