<!-- File: /modal/drop/modal_add.php - FIXED FINAL v2 -->
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
                            <!-- Display field (readonly) -->
                            <input type="text" class="item-est-date-display" readonly value="-"
                                style="background: #f1f5f9; font-weight: 600; color: #0369a1;">
                            <!-- Hidden field untuk kirim ke server -->
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

                    <div style="grid-column: 1 / -1;">
                        <label>👷 Karyawan</label>
                        <?php
                        $activeEmployees = $conn->query("SELECT id_employee, name FROM employees WHERE status = 'Aktif'");
                        $employeeCount = $activeEmployees->num_rows;

                        if ($employeeCount === 0) {
                            echo "<input type='text' value='Tidak ada karyawan aktif' readonly style='background:#f9f9f9; color:#888;'>";
                        } elseif ($employeeCount === 1) {
                            $emp = $activeEmployees->fetch_assoc();
                            echo "<input type='hidden' name='employee_id' value='{$emp['id_employee']}'>
                                  <input type='text' value='{$emp['name']}' readonly style='background:#f9f9f9;'>";
                        } else {
                            echo "<select name='employee_id' required>
                                <option value=''>-- Pilih Karyawan --</option>";
                            $activeEmployees->data_seek(0);
                            while ($emp = $activeEmployees->fetch_assoc()) {
                                echo "<option value='{$emp['id_employee']}'>{$emp['name']}</option>";
                            }
                            echo "</select>";
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