<!-- File: /modal/drop/modal_edit_item.php - Bootstrap Icons VERSION -->
<div class="modal" id="editModal" style="display:none;">
    <div class="modal-content large">
        <span class="close" data-target="editModal">&times;</span>
        <h2><i class="bi bi-pencil-square"></i> Edit Pesanan</h2>

        <form method="POST" action="../actions/drop/drop_edit.php" class="grid-form" id="editForm">
            <input type="hidden" name="drop_id" id="edit_drop_id">
            <input type="hidden" name="customer_id" id="edit_customer_id">

            <!-- Customer Info Section (NOW EDITABLE) -->
            <div class="customer-info-section" style="grid-column: 1 / -1;">
                <h3 style="margin: 0 0 16px 0; color: #b45309; font-size: 16px; font-weight: 700;">
                    <i class="bi bi-person-circle"></i> Informasi Pelanggan
                </h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label>Nama Pelanggan</label>
                        <input type="text" id="edit_customer_name" name="customer_name" placeholder="Nama pelanggan"
                            required>
                    </div>
                    <div>
                        <label>No. Handphone</label>
                        <input type="text" id="edit_customer_phone" name="phone_number" placeholder="08xxxxxxxxxx"
                            pattern="[0-9]{10,13}" title="Nomor HP harus 10-13 digit angka" required>
                    </div>
                </div>
                <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b;">
                    <i class="bi bi-info-circle"></i> Perubahan nomor HP akan membuat customer baru jika nomor belum
                    terdaftar
                </p>
            </div>

            <!-- Items Container -->
            <div id="editItemsContainer" style="grid-column: 1 / -1;">
                <!-- Items will be loaded dynamically via JavaScript -->
            </div>

            <!-- Add Item Button -->
            <div style="grid-column: 1 / -1; text-align: center; margin: 20px 0;">
                <button type="button" id="editAddItemBtn"
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                    <i class="bi bi-plus-circle"></i> Tambah Barang
                </button>
            </div>

            <!-- Order Summary Section -->
            <div class="order-summary" style="grid-column: 1 / -1;">
                <h3 style="margin: 0 0 16px 0; color: #0369a1; font-size: 16px; font-weight: 700;">
                    <i class="bi bi-clipboard-check"></i> Ringkasan Pesanan
                </h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <div>
                        <label>Total Item</label>
                        <input type="text" id="editTotalItems" readonly value="0"
                            style="background:#fff; font-weight: 600; color: #0369a1;">
                    </div>
                    <div>
                        <label>Total Harga</label>
                        <input type="text" id="editTotalPriceDisplay" readonly value="Rp 0"
                            style="background:#fff; font-weight: 600; color: #0369a1; font-size: 16px;">
                        <input type="hidden" name="total_amount" id="editTotalPrice" value="0">
                    </div>
                    <div>
                        <label>Estimasi Terlama</label>
                        <input type="text" id="editMaxEstimate" readonly value="-"
                            style="background:#fff; font-weight: 600;">
                        <input type="hidden" name="max_duration" id="editMaxDuration" value="0">
                    </div>
                    <div>
                        <label>Tanggal Estimasi Selesai</label>
                        <input type="date" id="editFinalEstDate" name="est_finish_date" readonly
                            style="background:#fff; font-weight: 600;">
                    </div>
                </div>
            </div>

            <!-- Payment & Employee Section -->
            <div class="payment-section" style="grid-column: 1 / -1;">
                <h3 style="margin: 0 0 16px 0; color: #7c3aed; font-size: 16px; font-weight: 700;">
                    <i class="bi bi-credit-card"></i> Informasi Pembayaran
                </h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <div>
                        <label>Status Pembayaran</label>
                        <select name="payment_status" id="edit_payment_status">
                            <option value="Belum Lunas">Belum Lunas</option>
                            <option value="Lunas">Lunas</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>

                    <div>
                        <label>Tanggal Pembayaran</label>
                        <input type="date" id="edit_payment_date_display" readonly disabled
                            style="background:#f1f5f9; cursor: not-allowed;">
                        <input type="hidden" id="edit_payment_date_hidden" name="payment_date">
                    </div>

                    <div>
                        <label>Metode Pembayaran</label>
                        <select name="payment_method" id="edit_payment_method" required>
                            <option value="Tunai">Tunai</option>
                            <option value="Transfer">Transfer</option>
                            <option value="QRIS">QRIS</option>
                            <option value="Debit">Debit</option>
                            <option value="Credit">Credit</option>
                        </select>
                    </div>

                    <div>
                        <label>Nominal Pembayaran</label>
                        <input type="text" id="edit_amount_paid_display" placeholder="Rp 0">
                        <input type="hidden" name="amount_paid" id="edit_amount_paid">
                    </div>

                    <!-- KARYAWAN SECTION - ACTIVE EMPLOYEES DROPDOWN -->
                    <div style="grid-column: 1 / -1;">
                        <label><i class="bi bi-person-badge"></i> Karyawan yang Menangani</label>
                        <?php
                        // Query untuk mendapatkan SEMUA karyawan yang aktif (tidak hanya kasir)
                        $activeEmployeesQuery = "
                            SELECT id_employee, name, employee_code, status
                            FROM employees 
                            WHERE status = 'Aktif'
                            ORDER BY name ASC
                        ";

                        $activeEmployees = $conn->query($activeEmployeesQuery);
                        $activeEmployeeCount = $activeEmployees->num_rows;

                        if ($activeEmployeeCount === 0) {
                            // Tidak ada karyawan aktif
                            echo "<div style='padding: 12px; background: #fef3c7; border: 2px solid #fbbf24; border-radius: 8px; color: #92400e;'>
                                    <i class='bi bi-exclamation-triangle'></i> <strong>Tidak ada karyawan aktif.</strong><br>
                                    <span style='font-size: 13px;'>Tidak dapat mengubah karyawan saat tidak ada yang aktif.</span>
                                  </div>";
                            echo "<input type='hidden' name='employee_id' id='edit_employee_id' value=''>";

                        } elseif ($activeEmployeeCount === 1) {
                            // Hanya 1 karyawan aktif - auto select
                            $emp = $activeEmployees->fetch_assoc();
                            echo "<input type='hidden' name='employee_id' id='edit_employee_id' value='{$emp['id_employee']}'>
                                  <div style='padding: 12px; background: #dcfce7; border: 2px solid #22c55e; border-radius: 8px;'>
                                    <div style='display: flex; align-items: center; gap: 10px;'>
                                        <i class='bi bi-person-circle' style='font-size: 24px; color: #15803d;'></i>
                                        <div>
                                            <div style='font-weight: 600; color: #15803d; font-size: 15px;'>{$emp['name']}</div>
                                            <div style='font-size: 12px; color: #16a34a;'>Kode: {$emp['employee_code']}</div>
                                        </div>
                                        <div style='margin-left: auto; background: #22c55e; color: white; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600;'>
                                            AKTIF
                                        </div>
                                    </div>
                                  </div>";

                        } else {
                            // Multiple karyawan aktif - show dropdown
                            echo "<select name='employee_id' id='edit_employee_id' required 
                                    style='padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;'>
                                    <option value=''>-- Pilih Karyawan --</option>";

                            $activeEmployees->data_seek(0); // Reset pointer
                            while ($emp = $activeEmployees->fetch_assoc()) {
                                echo "<option value='{$emp['id_employee']}'>{$emp['name']} ({$emp['employee_code']})</option>";
                            }

                            echo "</select>";
                            echo "<p style='margin: 8px 0 0 0; font-size: 12px; color: #64748b;'>
                                    <i class='bi bi-info-circle'></i> Menampilkan semua karyawan yang sedang aktif (sudah check-in)
                                  </p>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="full-width">
                <label><i class="bi bi-pencil-square"></i> Catatan Umum Pesanan</label>
                <textarea name="note" id="edit_note" rows="2" placeholder="Catatan umum untuk seluruh pesanan..."
                    style="width:100%; padding:12px; border-radius:8px; border:2px solid #e2e8f0; resize: vertical;"></textarea>
            </div>

            <div class="full-width">
                <div class="button-container">
                    <button type="submit" class="save-btn" id="editSaveOnlyBtn">
                        <i class="bi bi-save"></i> Simpan Perubahan
                    </button>
                    <button type="button" class="print-btn save-and-print" id="editSaveAndPrintBtn"
                        style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                        <i class="bi bi-printer"></i> Simpan & Cetak
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>