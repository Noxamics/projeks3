<!-- modal/karyawan/work_log.php -->
<div id="modalWorkLog" class="modal">
    <div class="modal-box modal-work-log">
        <h3>
            <span class="modal-icon">👟</span>
            Catat Pekerjaan Sepatu
        </h3>

        <!-- Start New Work -->
        <div class="work-section">
            <h4>Mulai Mengerjakan Sepatu Baru</h4>

            <form id="formStartWork" class="work-form">
                <input type="hidden" id="work_employee_id" name="employee_id">
                <input type="hidden" id="work_attendance_id" name="attendance_id">

                <div class="form-group">
                    <label for="work_transaction_id">
                        <span class="label-icon">🧾</span>
                        ID Transaksi (Opsional)
                    </label>
                    <input type="text" id="work_transaction_id" name="transaction_id"
                        placeholder="Masukkan ID transaksi jika ada">
                    <p class="field-note">Kosongkan jika tidak ada transaksi</p>
                </div>

                <div class="form-group">
                    <label for="work_shoe_type">
                        <span class="label-icon">👟</span>
                        Jenis Sepatu
                    </label>
                    <input type="text" id="work_shoe_type" name="shoe_type" required
                        placeholder="Contoh: Nike Air Max, Converse Chuck Taylor">
                </div>

                <div class="form-group">
                    <label for="work_service_type">
                        <span class="label-icon">🛠️</span>
                        Jenis Service
                    </label>
                    <select id="work_service_type" name="service_type" required>
                        <option value="">-- Pilih Service --</option>
                        <option value="cleaning">🧼 Cleaning (Pembersihan)</option>
                        <option value="reglue">🔧 Reglue (Lem ulang)</option>
                        <option value="repaint">🎨 Repaint (Cat ulang)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="work_notes">
                        <span class="label-icon">📝</span>
                        Catatan
                    </label>
                    <textarea id="work_notes" name="notes" rows="3"
                        placeholder="Kondisi sepatu, kendala, dll..."></textarea>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-start-work">
                        <span>▶️</span> Mulai Kerja
                    </button>
                </div>
            </form>
        </div>

        <!-- Current In-Progress Work -->
        <div class="work-section">
            <h4>Pekerjaan Sedang Berlangsung</h4>
            <div id="in_progress_list" class="work-list">
                <!-- Dynamically loaded -->
            </div>
        </div>

        <!-- Completed Work Today -->
        <div class="work-section">
            <h4>Selesai Hari Ini</h4>
            <div id="completed_list" class="work-list">
                <!-- Dynamically loaded -->
            </div>
        </div>

        <button class="btn-cancel" onclick="closeWorkLogModal()">Tutup</button>
    </div>
</div>

<style>
    .modal-work-log {
        max-width: 900px;
    }

    .work-section {
        background: #f8fbff;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        border: 2px solid #e3f2fd;
    }

    .work-section h4 {
        color: #0066cc;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .work-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .work-item {
        background: white;
        padding: 15px;
        border-radius: 10px;
        border: 2px solid #e3f2fd;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s;
    }

    .work-item:hover {
        border-color: #0066cc;
        box-shadow: 0 4px 15px rgba(0, 102, 204, 0.1);
    }

    .work-item-info {
        flex: 1;
    }

    .work-item-info h5 {
        color: #0066cc;
        margin-bottom: 5px;
        font-size: 16px;
    }

    .work-item-info p {
        color: #666;
        font-size: 13px;
        margin: 3px 0;
    }

    .work-timer {
        font-size: 24px;
        font-weight: 700;
        color: #f57c00;
        font-family: 'Courier New', monospace;
        min-width: 80px;
        text-align: right;
    }

    .work-actions {
        display: flex;
        gap: 8px;
    }

    .btn-pause,
    .btn-complete,
    .btn-resume {
        padding: 8px 16px;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-pause {
        background: #f57c00;
        color: white;
    }

    .btn-pause:hover {
        background: #ef6c00;
        transform: translateY(-2px);
    }

    .btn-complete {
        background: #2e7d32;
        color: white;
    }

    .btn-complete:hover {
        background: #1b5e20;
        transform: translateY(-2px);
    }

    .btn-resume {
        background: #0066cc;
        color: white;
    }

    .btn-resume:hover {
        background: #0052a3;
        transform: translateY(-2px);
    }

    .btn-start-work {
        width: 100%;
        padding: 14px;
        background: #2e7d32;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-start-work:hover {
        background: #1b5e20;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(46, 125, 50, 0.4);
    }

    .service-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        margin-right: 5px;
    }

    .service-cleaning {
        background: #e3f2fd;
        color: #0066cc;
    }

    .service-reglue {
        background: #fff3e0;
        color: #f57c00;
    }

    .service-repaint {
        background: #f3e5f5;
        color: #7b1fa2;
    }

    .work-status {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }

    .status-in_progress {
        background: #fff3cd;
        color: #856404;
    }

    .status-paused {
        background: #f8d7da;
        color: #721c24;
    }

    .status-completed {
        background: #d4edda;
        color: #155724;
    }

    .empty-state {
        text-align: center;
        padding: 40px;
        color: #999;
    }

    .empty-state-icon {
        font-size: 48px;
        margin-bottom: 10px;
    }
</style>