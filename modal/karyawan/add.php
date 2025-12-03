<link rel="stylesheet" href="../css/karyawan/notification.css">

<div id="modalAdd" class="modal">
    <div class="modal-box">
        <h3>Tambah Karyawan Baru</h3>

        <form id="formAddEmployee" method="POST" enctype="multipart/form-data">

            <!-- Employee Code -->
            <div class="form-group">
                <label for="add_employee_code" class="required">Kode Karyawan</label>
                <input type="text" id="add_employee_code" name="employee_code" class="form-control" readonly
                    style="background:#eee; cursor:not-allowed;">
            </div>

            <!-- Name -->
            <div class="form-group">
                <label for="add_name" class="required">Nama Lengkap</label>
                <input type="text" id="add_name" name="name" class="form-control" placeholder="Nama Lengkap Karyawan"
                    required maxlength="100">
            </div>

            <!-- Phone -->
            <div class="form-group">
                <label for="add_phone" class="required">Nomor HP</label>
                <input type="tel" id="add_phone" name="phone" class="form-control" placeholder="08XX-XXXX-XXXX" required
                    pattern="[0-9]{10,15}" maxlength="15" title="Masukkan nomor HP 10-15 digit">
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="add_email">Email</label>
                <input type="email" id="add_email" name="email" class="form-control" placeholder="email@example.com"
                    maxlength="100">
                <small class="form-hint">Opsional</small>
            </div>

            <!-- Address -->
            <div class="form-group">
                <label for="add_address">Alamat</label>
                <textarea id="add_address" name="address" class="form-control" rows="3"
                    placeholder="Alamat Karyawan"></textarea>
                <small class="form-hint">Opsional</small>
            </div>

            <!-- Birth Date -->
            <div class="form-group">
                <label for="add_birth_date">Tanggal Lahir</label>
                <input type="date" id="add_birth_date" name="birth_date" class="form-control">
                <small class="form-hint">Opsional</small>
            </div>

            <!-- Emergency Contact -->
            <div class="form-group">
                <label for="add_emergency_contact">Kontak Darurat</label>
                <input type="tel" id="add_emergency_contact" name="emergency_contact" class="form-control"
                    placeholder="08XX-XXXX-XXXX" pattern="[0-9]{10,15}" maxlength="15">
                <small class="form-hint">Nomor Cadangan (Opsional)</small>
            </div>

            <!-- Base Salary -->
            <div class="form-group">
                <label for="add_base_salary">Gaji Pokok</label>
                <input type="number" id="add_base_salary" name="base_salary" class="form-control" placeholder="0"
                    min="0" step="0.01" value="0.00">
                <small class="form-hint">Dalam Rupiah. Default: 0.00</small>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="add_password" class="required">Password</label>
                <div class="password-container">
                    <input type="password" id="add_password" name="password" class="form-control password-input"
                        placeholder="Masukkan Password" required minlength="6">
                    <button type="button" class="password-toggle-btn" onclick="togglePassword('add_password', this)">
                        <svg class="icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                <small class="form-hint">Minimal 6 karakter</small>
            </div>

            <!-- Join Date -->
            <div class="form-group">
                <label for="add_join_date" class="required">Tanggal Bergabung</label>
                <input type="date" id="add_join_date" name="join_date" class="form-control" required
                    value="<?php echo date('Y-m-d'); ?>">
            </div>

            <!-- Status -->
            <div class="form-group">
                <label for="add_status">Status Awal</label>
                <input type="text" id="add_status" name="status" class="form-control" value="Non-Aktif" readonly
                    style="background: #f0f0f0; cursor: not-allowed;">
                <small class="form-hint">Status otomatis "Non-Aktif". Akan berubah "Aktif" saat karyawan melakukan check
                    in absensi.</small>
            </div>

            <!-- Roles -->
            <div class="form-group">
                <label class="required">Role / Posisi</label>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="roles[]" value="cleaning" id="add_role_cleaning" checked>
                        <span class="role-badge role-cleaning">Cleaning</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="roles[]" value="reglue" id="add_role_reglue">
                        <span class="role-badge role-reglue">Reglue</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="roles[]" value="repaint" id="add_role_repaint">
                        <span class="role-badge role-repaint">Repaint</span>
                    </label>
                </div>
                <small class="form-hint">Pilih Minimal 1 Role</small>
            </div>

            <!-- Photo Upload -->
            <div class="form-group">
                <label for="add_photo">Foto Karyawan</label>
                <input type="file" id="add_photo" name="photo" class="form-control"
                    accept="image/jpeg,image/jpg,image/png"
                    onchange="previewImageWithEditor(this, 'add_photo_preview')">
                <small class="form-hint">Format: JPG, JPEG, PNG. Max: 2MB (Opsional)</small>

                <!-- Photo Preview with Editor -->
                <div id="add_photo_preview" class="photo-preview-editor" style="display: none;">
                    <div class="preview-controls">
                        <button type="button" class="btn-control" onclick="rotateImage('add_photo_preview', -90)"
                            title="Rotate Left">
                            ↶
                        </button>
                        <button type="button" class="btn-control" onclick="rotateImage('add_photo_preview', 90)"
                            title="Rotate Right">
                            ↷
                        </button>
                        <button type="button" class="btn-control" onclick="flipImage('add_photo_preview', 'horizontal')"
                            title="Flip Horizontal">
                            ↔
                        </button>
                        <button type="button" class="btn-control" onclick="flipImage('add_photo_preview', 'vertical')"
                            title="Flip Vertical">
                            ↕
                        </button>
                        <button type="button" class="btn-control" onclick="zoomImage('add_photo_preview', 0.1)"
                            title="Zoom In">
                            +
                        </button>
                        <button type="button" class="btn-control" onclick="zoomImage('add_photo_preview', -0.1)"
                            title="Zoom Out">
                            -
                        </button>
                        <button type="button" class="btn-control" onclick="resetImage('add_photo_preview')"
                            title="Reset">
                            ⟲
                        </button>
                    </div>
                    <div class="preview-container">
                        <img src="" alt="Preview" class="preview-image" data-rotation="0" data-scale="1" data-flip-h="1"
                            data-flip-v="1">
                    </div>
                    <button type="button" class="btn-remove-preview"
                        onclick="removePreviewEditor('add_photo', 'add_photo_preview')">
                        Hapus Foto
                    </button>
                </div>
            </div>

            <!-- Form Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-cancel" onclick="closeAddModal()">
                    Batal
                </button>
                <button type="submit" class="btn btn-primary btn-save">
                    Simpan Karyawan
                </button>
            </div>
        </form>
    </div>
</div>

<script src="../js/karyawan/notification.js"></script>
<script src="../js/karyawan/modal_karyawan.js"></script>