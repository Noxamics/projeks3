<!-- ================================================
     Modal Edit Karyawan - Complete Version
     File: modal/karyawan/edit.php
     
     CSS: css/karyawan/modal.css
          css/karyawan/modal-form.css
     ================================================ -->

<div id="modalEdit" class="modal">
    <div class="modal-box">
        <h3>✏️ Edit Data Karyawan</h3>

        <form id="formEditEmployee" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_employee" id="edit_id">

            <!-- Employee Code -->
            <div class="form-group">
                <label for="edit_employee_code" class="required">Kode Karyawan</label>
                <input type="text" 
                       id="edit_employee_code" 
                       name="employee_code" 
                       class="form-control" 
                       placeholder="Contoh: EMP001" 
                       required
                       pattern="[A-Z0-9]+"
                       title="Gunakan huruf kapital dan angka"
                       readonly>
                <small class="form-hint">Kode karyawan tidak dapat diubah</small>
            </div>

            <!-- Name -->
            <div class="form-group">
                <label for="edit_name" class="required">Nama Lengkap</label>
                <input type="text" 
                       id="edit_name" 
                       name="name" 
                       class="form-control" 
                       placeholder="Nama lengkap karyawan" 
                       required
                       maxlength="100">
            </div>

            <!-- Phone -->
            <div class="form-group">
                <label for="edit_phone" class="required">Nomor HP</label>
                <input type="tel" 
                       id="edit_phone" 
                       name="phone" 
                       class="form-control" 
                       placeholder="08xxxxxxxxxx" 
                       required
                       pattern="[0-9]{10,13}"
                       maxlength="20">
            </div>

            <!-- Email (Optional) -->
            <div class="form-group">
                <label for="edit_email">Email</label>
                <input type="email" 
                       id="edit_email" 
                       name="email" 
                       class="form-control" 
                       placeholder="email@example.com"
                       maxlength="100">
                <small class="form-hint">Opsional</small>
            </div>

            <!-- Address (Optional) -->
            <div class="form-group">
                <label for="edit_address">Alamat</label>
                <textarea id="edit_address" 
                          name="address" 
                          class="form-control" 
                          rows="3" 
                          placeholder="Alamat lengkap karyawan"></textarea>
                <small class="form-hint">Opsional</small>
            </div>

            <!-- Birth Date (Optional) -->
            <div class="form-group">
                <label for="edit_birth_date">Tanggal Lahir</label>
                <input type="date" 
                       id="edit_birth_date" 
                       name="birth_date" 
                       class="form-control">
                <small class="form-hint">Opsional</small>
            </div>

            <!-- Emergency Contact (Optional) -->
            <div class="form-group">
                <label for="edit_emergency_contact">Kontak Darurat</label>
                <input type="tel" 
                       id="edit_emergency_contact" 
                       name="emergency_contact" 
                       class="form-control" 
                       placeholder="08xxxxxxxxxx"
                       pattern="[0-9]{10,13}"
                       maxlength="20">
                <small class="form-hint">Nomor yang dapat dihubungi dalam keadaan darurat (Opsional)</small>
            </div>

            <!-- Base Salary -->
            <div class="form-group">
                <label for="edit_base_salary">Gaji Pokok</label>
                <input type="number" 
                       id="edit_base_salary" 
                       name="base_salary" 
                       class="form-control" 
                       placeholder="0" 
                       min="0" 
                       step="0.01"
                       value="0.00">
                <small class="form-hint">Dalam Rupiah</small>
            </div>

            <!-- Password (Optional) -->
            <div class="form-group">
                <label for="edit_password">Password Baru</label>
                <div class="password-input-wrapper">
                    <input type="password" 
                           id="edit_password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Biarkan kosong jika tidak ingin mengubah"
                           minlength="6"
                           maxlength="255">
                    <button type="button" 
                            class="btn-toggle-password" 
                            onclick="togglePassword('edit_password')"
                            title="Tampilkan/Sembunyikan Password">
                        👁️
                    </button>
                </div>
                <small class="form-hint">Kosongkan jika tidak ingin mengubah password</small>
            </div>

            <!-- Join Date -->
            <div class="form-group">
                <label for="edit_join_date" class="required">Tanggal Bergabung</label>
                <input type="date" 
                       id="edit_join_date" 
                       name="join_date" 
                       class="form-control" 
                       required>
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
                        <input type="checkbox" 
                               name="roles[]" 
                               value="cleaning" 
                               id="edit_role_cleaning">
                        <span class="role-badge role-cleaning">Cleaning</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" 
                               name="roles[]" 
                               value="reglue" 
                               id="edit_role_reglue">
                        <span class="role-badge role-reglue">Reglue</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" 
                               name="roles[]" 
                               value="repaint" 
                               id="edit_role_repaint">
                        <span class="role-badge role-repaint">Repaint</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" 
                               name="roles[]" 
                               value="kasir" 
                               id="edit_role_kasir">
                        <span class="role-badge role-kasir">Kasir</span>
                    </label>
                </div>
                <small class="form-hint">Pilih minimal 1 role</small>
            </div>

            <!-- Current Photo Display -->
            <div class="form-group">
                <label>Foto Saat Ini</label>
                <div id="edit_current_photo" style="display: none;">
                    <img id="edit_current_photo_img" 
                         src="" 
                         alt="Current Photo" 
                         class="preview-image">
                </div>
            </div>

            <!-- Photo Upload (Optional) -->
            <div class="form-group">
                <label for="edit_photo">Ubah Foto Karyawan</label>
                <input type="file" 
                       id="edit_photo" 
                       name="photo" 
                       class="form-control" 
                       accept="image/jpeg,image/jpg,image/png"
                       onchange="previewImage(this, 'edit_photo_preview')">
                <small class="form-hint">Upload jika ingin mengubah foto. Format: JPG, JPEG, PNG. Max: 2MB</small>
                
                <!-- Photo Preview -->
                <div id="edit_photo_preview" class="photo-preview" style="display: none;">
                    <img src="" alt="Preview" class="preview-image">
                    <button type="button" 
                            class="btn-remove-preview" 
                            onclick="removePreview('edit_photo', 'edit_photo_preview')">
                        ✕ Hapus
                    </button>
                </div>
            </div>

            <!-- Form Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-cancel" onclick="closeEditModal()">
                    ✕ Batal
                </button>
                <button type="submit" class="btn btn-primary btn-save">
                    💾 Update Karyawan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript Functions -->
<script>
// Form validation and submit
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formEditEmployee');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Check if at least one role is selected
            const roles = document.querySelectorAll('#modalEdit input[name="roles[]"]:checked');
            if (roles.length === 0) {
                alert('⚠️ Pilih minimal 1 role untuk karyawan!');
                return;
            }
            
            // Show loading on submit button
            const submitBtn = this.querySelector('.btn-save');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '⏳ Menyimpan...';
            
            // Submit form via AJAX
            const formData = new FormData(this);
            
            fetch('../actions/karyawan/edit.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + (data.message || 'Data karyawan berhasil diupdate!'));
                    closeEditModal();
                    window.location.reload();
                } else {
                    alert('❌ Error: ' + (data.message || 'Gagal mengupdate karyawan'));
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('loading');
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Terjadi kesalahan saat mengupdate karyawan');
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
                submitBtn.innerHTML = originalText;
            });
        });
    }
});
</script>