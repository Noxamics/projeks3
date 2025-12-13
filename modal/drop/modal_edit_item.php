<!-- File: /modal/drop/modal_edit_item.php - MODERN EMPLOYEE SECTION -->
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
                        </select>
                    </div>

                    <div>
                        <label>Nominal Pembayaran</label>
                        <input type="text" id="edit_amount_paid_display" placeholder="Rp 0">
                        <input type="hidden" name="amount_paid" id="edit_amount_paid">
                    </div>

                    <!-- KARYAWAN SECTION - MODERN STATIC VERSION -->
                    <div style="grid-column: 1 / -1;">
                        <label><i class="bi bi-person-badge"></i> Karyawan yang Menangani</label>
                        <div id="editKaryawanContainer">
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
                                // Tidak ada karyawan aktif - Modern Warning Card
                                echo "<div class='warning-card'>
                                    <i class='bi bi-exclamation-triangle'></i> 
                                    <strong>Tidak ada karyawan aktif.</strong>
                                    <span>Tidak dapat mengubah karyawan saat tidak ada yang aktif.</span>
                                </div>";
                                echo "<input type='hidden' name='employee_id' id='edit_employee_id' value=''>";

                            } elseif ($activeEmployeeCount === 1) {
                                // Hanya 1 karyawan aktif - Modern Active Card
                                $emp = $activeEmployees->fetch_assoc();
                                echo "<input type='hidden' name='employee_id' id='edit_employee_id' value='{$emp['id_employee']}'>
                                <div class='edit-employee-card'>
                                    <div class='employee-card-content'>
                                        <i class='bi bi-person-circle employee-icon'></i>
                                        <div class='employee-info'>
                                            <div class='employee-name'>
                                                {$emp['name']}
                                                <span class='role-badge active'>KARYAWAN AKTIF</span>
                                            </div>
                                            <div class='employee-details'>
                                                <strong>Kode:</strong> {$emp['employee_code']}
                                            </div>
                                        </div>
                                        <div class='employee-badge'>
                                            ✓ AKTIF
                                        </div>
                                    </div>
                                </div>";

                            } else {
                                // Multiple karyawan aktif - Modern Dropdown with Header Info
                                echo "<div class='multiple-employees-section'>
                                    <div class='employees-header'>
                                        <i class='bi bi-people-fill'></i>
                                        <div class='header-text'>
                                            <strong>{$activeEmployeeCount} Karyawan Aktif</strong>
                                            <span>Pilih karyawan yang menangani pesanan ini</span>
                                        </div>
                                    </div>
                                    
                                    <div class='modern-select-wrapper'>
                                        <i class='bi bi-person-badge select-icon'></i>
                                        <select name='employee_id' id='edit_employee_id' required class='modern-select'>
                                            <option value='' disabled selected>Pilih Karyawan...</option>";

                                $activeEmployees->data_seek(0); // Reset pointer
                                while ($emp = $activeEmployees->fetch_assoc()) {
                                    echo "<option value='{$emp['id_employee']}'>{$emp['name']} • {$emp['employee_code']}</option>";
                                }

                                echo "</select>
                                        <i class='bi bi-chevron-down select-arrow'></i>
                                    </div>
                                    
                                    <p class='info-text'>
                                        <i class='bi bi-info-circle'></i> 
                                        Menampilkan semua karyawan yang sedang aktif (sudah check-in)
                                    </p>
                                </div>";
                            }
                            ?>
                        </div>
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

<style>
    /* Modern Employee Card Styles */
    .edit-employee-card {
        padding: 14px;
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        border: 2px solid #3b82f6;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
        transition: all 0.3s ease;
    }

    .edit-employee-card:hover {
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        transform: translateY(-2px);
    }

    .employee-card-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .employee-icon {
        font-size: 36px;
        color: #1e40af;
        flex-shrink: 0;
    }

    .employee-info {
        flex: 1;
    }

    .employee-name {
        font-weight: 700;
        color: #1e40af;
        font-size: 17px;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .role-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.3px;
    }

    .role-badge.active {
        background: #3b82f6;
        color: white;
    }

    .employee-details {
        font-size: 13px;
        color: #2563eb;
        line-height: 1.6;
    }

    .employee-badge {
        background: #3b82f6;
        color: white;
        padding: 8px 18px;
        border-radius: 14px;
        font-size: 11px;
        font-weight: 700;
        box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3);
        white-space: nowrap;
    }

    .warning-card {
        padding: 14px;
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border: 2px solid #fbbf24;
        border-radius: 12px;
        color: #92400e;
        display: flex;
        flex-direction: column;
        gap: 6px;
        box-shadow: 0 2px 8px rgba(251, 191, 36, 0.15);
    }

    .warning-card i {
        font-size: 20px;
        margin-bottom: 4px;
        color: #f59e0b;
    }

    .warning-card strong {
        font-size: 14px;
    }

    .warning-card span {
        font-size: 13px;
        opacity: 0.9;
    }

    /* Multiple Employees Section */
    .multiple-employees-section {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .employees-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 2px solid #38bdf8;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(56, 189, 248, 0.15);
    }

    .employees-header i {
        font-size: 32px;
        color: #0284c7;
        flex-shrink: 0;
    }

    .header-text {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .header-text strong {
        font-size: 16px;
        color: #0c4a6e;
        font-weight: 700;
    }

    .header-text span {
        font-size: 13px;
        color: #0369a1;
    }

    /* Modern Select Dropdown Styling */
    .modern-select-wrapper {
        position: relative;
        display: block;
    }

    .select-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        font-size: 20px;
        pointer-events: none;
        z-index: 1;
        transition: all 0.3s ease;
    }

    .modern-select-wrapper:hover .select-icon {
        color: #3b82f6;
    }

    .modern-select {
        width: 100%;
        padding: 14px 50px 14px 48px;
        font-size: 15px;
        font-weight: 600;
        color: #1e293b;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 2px solid #cbd5e1;
        border-radius: 12px;
        appearance: none;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .modern-select:hover {
        border-color: #3b82f6;
        background: linear-gradient(135deg, #ffffff 0%, #dbeafe 100%);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        transform: translateY(-1px);
    }

    .modern-select:focus {
        outline: none;
        border-color: #3b82f6;
        background: white;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1), 0 4px 12px rgba(59, 130, 246, 0.2);
        transform: translateY(-1px);
    }

    .modern-select option {
        padding: 12px;
        font-weight: 500;
        color: #1e293b;
        background: white;
    }

    .modern-select option:first-child {
        color: #64748b;
        font-style: italic;
    }

    .modern-select option:not(:first-child) {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
    }

    .modern-select option:hover,
    .modern-select option:checked {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        color: #1e40af;
    }

    .select-arrow {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        font-size: 18px;
        font-weight: 700;
        pointer-events: none;
        transition: all 0.3s ease;
    }

    .modern-select-wrapper:hover .select-arrow {
        color: #3b82f6;
        transform: translateY(-50%) rotate(180deg);
    }

    .modern-select:focus~.select-arrow {
        color: #3b82f6;
        transform: translateY(-50%) rotate(180deg);
    }

    .info-text {
        margin: 10px 0 0 0;
        font-size: 12px;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }

    .info-text i {
        font-size: 14px;
        color: #3b82f6;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .employee-card-content {
            flex-direction: column;
            text-align: center;
        }

        .employee-badge {
            width: 100%;
        }

        .employee-name {
            justify-content: center;
        }

        .modern-select {
            font-size: 14px;
            padding: 10px 36px 10px 12px;
        }
    }
</style>