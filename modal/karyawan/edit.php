<!-- ================================================
     Modal Edit Karyawan - Complete Version
     File: modal/karyawan/edit.php
     
     CSS: css/karyawan/modal.css
          css/karyawan/modal-form.css
     ================================================ -->

<link rel="stylesheet" href="../css/karyawan/notification.css">

<div id="modalEdit" class="modal">
    <div class="modal-box">
        <h3>Edit Data Karyawan</h3>

        <form id="formEditEmployee" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_employee" id="edit_id">

            <!-- Employee Code -->
            <div class="form-group">
                <label for="edit_employee_code" class="required">Kode Karyawan</label>
                <input type="text" id="edit_employee_code" name="employee_code" class="form-control" readonly
                    style="background:#eee; cursor:not-allowed;">
                <small class="form-hint">Kode karyawan tidak dapat diubah</small>
            </div>

            <!-- Name -->
            <div class="form-group">
                <label for="edit_name" class="required">Nama Lengkap</label>
                <input type="text" id="edit_name" name="name" class="form-control" placeholder="Nama Lengkap Karyawan"
                    required maxlength="100">
            </div>

            <!-- Phone -->
            <div class="form-group">
                <label for="edit_phone" class="required">Nomor HP</label>
                <input type="tel" id="edit_phone" name="phone" class="form-control" placeholder="08XX-XXXX-XXXX"
                    required pattern="[0-9]{10,15}" maxlength="15" title="Masukkan nomor HP 10-15 digit">
            </div>

            <!-- Email (Optional) -->
            <div class="form-group">
                <label for="edit_email">Email</label>
                <input type="email" id="edit_email" name="email" class="form-control" placeholder="email@example.com"
                    maxlength="100">
                <small class="form-hint">Opsional</small>
            </div>

            <!-- Address (Optional) -->
            <div class="form-group">
                <label for="edit_address">Alamat</label>
                <textarea id="edit_address" name="address" class="form-control" rows="3"
                    placeholder="Alamat Karyawan"></textarea>
                <small class="form-hint">Opsional</small>
            </div>

            <!-- Birth Date (Optional) -->
            <div class="form-group">
                <label for="edit_birth_date">Tanggal Lahir</label>
                <input type="date" id="edit_birth_date" name="birth_date" class="form-control">
                <small class="form-hint">Opsional</small>
            </div>

            <!-- Emergency Contact (Optional) -->
            <div class="form-group">
                <label for="edit_emergency_contact">Kontak Darurat</label>
                <input type="tel" id="edit_emergency_contact" name="emergency_contact" class="form-control"
                    placeholder="08XX-XXXX-XXXX" pattern="[0-9]{10,15}" maxlength="15">
                <small class="form-hint">Nomor Cadangan (Opsional)</small>
            </div>

            <!-- Base Salary -->
            <div class="form-group">
                <label for="edit_base_salary">Gaji Pokok</label>
                <input type="number" id="edit_base_salary" name="base_salary" class="form-control" placeholder="0"
                    min="0" step="0.01" value="0.00">
                <small class="form-hint">Dalam Rupiah. Default: 0.00</small>
            </div>

            <!-- Password (Optional) -->
            <div class="form-group">
                <label for="edit_password">Password Baru</label>
                <div class="password-container">
                    <input type="password" id="edit_password" name="password" class="form-control password-input"
                        placeholder="Biarkan kosong jika tidak ingin mengubah" minlength="6">
                    <button type="button" class="password-toggle-btn" onclick="togglePassword('edit_password', this)">
                        <svg class="icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                <small class="form-hint">Kosongkan jika tidak ingin mengubah password. Minimal 6 karakter</small>
            </div>

            <!-- Join Date -->
            <div class="form-group">
                <label for="edit_join_date" class="required">Tanggal Bergabung</label>
                <input type="date" id="edit_join_date" name="join_date" class="form-control" required>
            </div>

            <!-- Status -->
            <div class="form-group">
                <label for="edit_status" class="required">Status</label>
                <select id="edit_status" name="status" class="form-control" required>
                    <option value="Aktif">Aktif</option>
                    <option value="Cuti">Cuti</option>
                    <option value="Non-Aktif">Non-Aktif</option>
                </select>
            </div>

            <!-- Roles (Multiple Selection) -->
            <div class="form-group">
                <label class="required">Role / Posisi</label>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="roles[]" value="cleaning" id="edit_role_cleaning">
                        <span class="role-badge role-cleaning">Cleaning</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="roles[]" value="reglue" id="edit_role_reglue">
                        <span class="role-badge role-reglue">Reglue</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="roles[]" value="repaint" id="edit_role_repaint">
                        <span class="role-badge role-repaint">Repaint</span>
                    </label>
                </div>
                <small class="form-hint">Pilih Minimal 1 Role</small>
            </div>

            <!-- Current Photo Display -->
            <div class="form-group">
                <label>Foto Saat Ini</label>
                <div id="edit_current_photo" style="display: none;">
                    <img id="edit_current_photo_img" src="" alt="Current Photo" class="preview-image">
                </div>
            </div>

            <!-- Photo Upload (Optional) -->
            <div class="form-group">
                <label for="edit_photo">Ubah Foto Karyawan</label>
                <input type="file" id="edit_photo" name="photo" class="form-control"
                    accept="image/jpeg,image/jpg,image/png"
                    onchange="previewImageWithEditor(this, 'edit_photo_preview')">
                <small class="form-hint">Format: JPG, JPEG, PNG. Max: 2MB (Opsional)</small>

                <!-- Photo Preview with Editor -->
                <div id="edit_photo_preview" class="photo-preview-editor" style="display: none;">
                    <div class="preview-controls">
                        <button type="button" class="btn-control" onclick="rotateImage('edit_photo_preview', -90)"
                            title="Rotate Left">
                            ↶
                        </button>
                        <button type="button" class="btn-control" onclick="rotateImage('edit_photo_preview', 90)"
                            title="Rotate Right">
                            ↷
                        </button>
                        <button type="button" class="btn-control"
                            onclick="flipImage('edit_photo_preview', 'horizontal')" title="Flip Horizontal">
                            ↔
                        </button>
                        <button type="button" class="btn-control" onclick="flipImage('edit_photo_preview', 'vertical')"
                            title="Flip Vertical">
                            ↕
                        </button>
                        <button type="button" class="btn-control" onclick="zoomImage('edit_photo_preview', 0.1)"
                            title="Zoom In">
                            +
                        </button>
                        <button type="button" class="btn-control" onclick="zoomImage('edit_photo_preview', -0.1)"
                            title="Zoom Out">
                            -
                        </button>
                        <button type="button" class="btn-control" onclick="resetImage('edit_photo_preview')"
                            title="Reset">
                            ⟲
                        </button>
                    </div>
                    <div class="preview-container">
                        <img src="" alt="Preview" class="preview-image" data-rotation="0" data-scale="1" data-flip-h="1"
                            data-flip-v="1">
                    </div>
                    <button type="button" class="btn-remove-preview"
                        onclick="removePreviewEditor('edit_photo', 'edit_photo_preview')">
                        Hapus Foto
                    </button>
                </div>
            </div>

            <!-- Form Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-cancel" onclick="closeEditModal()">
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

<!-- JavaScript Functions -->
<script>
    // Form validation and submit
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formEditEmployee');

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                // Check if at least one role is selected
                const roles = document.querySelectorAll('#modalEdit input[name="roles[]"]:checked');
                if (roles.length === 0) {
                    alert('Pilih minimal 1 role untuk karyawan!');
                    return;
                }

                // Show loading on submit button
                const submitBtn = this.querySelector('.btn-save');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.classList.add('loading');
                submitBtn.innerHTML = 'Menyimpan...';

                // Submit form via AJAX
                const formData = new FormData(this);

                fetch('../actions/karyawan/edit.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message || 'Data karyawan berhasil diupdate!');
                            closeEditModal();
                            window.location.reload();
                        } else {
                            alert('Error: ' + (data.message || 'Gagal mengupdate karyawan'));
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('loading');
                            submitBtn.innerHTML = originalText;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat mengupdate karyawan');
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('loading');
                        submitBtn.innerHTML = originalText;
                    });
            });
        }
    });
</script>