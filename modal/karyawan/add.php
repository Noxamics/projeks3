<!-- 
  Add Employee Modal - COMPLETE VERSION
  Location: modal/karyawan/add.php
-->
<div id="modalAdd" class="modal">
    <div class="modal-box">
        <h3>➕ Tambah Karyawan Baru</h3>

        <!-- Alert for messages -->
        <div id="add_alert" class="alert" style="display: none;"></div>

        <form id="formAdd" method="POST" enctype="multipart/form-data">

            <!-- Employee Code -->
            <label for="add_employee_code">
                Kode Karyawan <span style="color: red;">*</span>
            </label>
            <input type="text" id="add_employee_code" name="employee_code" placeholder="Contoh: EMP001" required
                maxlength="20" pattern="[A-Z0-9]+" title="Gunakan huruf KAPITAL dan angka saja (contoh: EMP001)"
                style="text-transform: uppercase;">
            <small style="color: #666; font-size: 12px;">Format: Huruf kapital + angka (EMP001, KRY002, dll)</small>

            <!-- Name -->
            <label for="add_name">
                Nama Lengkap <span style="color: red;">*</span>
            </label>
            <input type="text" id="add_name" name="name" placeholder="Contoh: Ahmad Wijaya" required maxlength="100">
            <small style="color: #666; font-size: 12px;">Nama lengkap sesuai KTP</small>

            <!-- Phone -->
            <label for="add_phone">
                Nomor HP <span style="color: red;">*</span>
            </label>
            <input type="tel" id="add_phone" name="phone" placeholder="Contoh: 081234567890" required
                pattern="[0-9]{10,15}" title="Masukkan nomor HP yang valid (10-15 digit angka)" maxlength="15">
            <small style="color: #666; font-size: 12px;">Format: 10-15 digit angka tanpa spasi atau tanda baca</small>

            <!-- Join Date -->
            <label for="add_join_date">
                Tanggal Bergabung <span style="color: red;">*</span>
            </label>
            <input type="date" id="add_join_date" name="join_date" required max="<?php echo date('Y-m-d'); ?>">
            <small style="color: #666; font-size: 12px;">Tanggal mulai bekerja</small>

            <!-- Roles (Multiple Selection) -->
            <label style="margin-top: 20px;">
                Role / Tugas <span style="color: red;">*</span>
            </label>
            <small style="color: #666; font-size: 12px; display: block; margin-bottom: 10px;">
                Pilih minimal 1 role (bisa lebih dari 1)
            </small>
            <div
                style="display: flex; flex-direction: column; gap: 10px; padding: 15px; background: #f8fbff; border-radius: 10px; border: 2px solid #e3f2fd;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                    <input type="checkbox" name="roles[]" value="cleaning" id="add_role_cleaning" checked
                        style="width: 18px; height: 18px; cursor: pointer;">
                    <span style="font-weight: 600;">🧹 Cleaning</span>
                    <small style="color: #666;">(Pembersihan sepatu)</small>
                </label>

                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                    <input type="checkbox" name="roles[]" value="reglue" id="add_role_reglue"
                        style="width: 18px; height: 18px; cursor: pointer;">
                    <span style="font-weight: 600;">🔧 Reglue</span>
                    <small style="color: #666;">(Perekat sepatu)</small>
                </label>

                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                    <input type="checkbox" name="roles[]" value="repaint" id="add_role_repaint"
                        style="width: 18px; height: 18px; cursor: pointer;">
                    <span style="font-weight: 600;">🎨 Repaint</span>
                    <small style="color: #666;">(Pengecatan sepatu)</small>
                </label>

                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                    <input type="checkbox" name="roles[]" value="kasir" id="add_role_kasir"
                        style="width: 18px; height: 18px; cursor: pointer;">
                    <span style="font-weight: 600;">💰 Kasir</span>
                    <small style="color: #666;">(Transaksi pembayaran)</small>
                </label>
            </div>

            <!-- Status -->
            <label for="add_status">
                Status Karyawan <span style="color: red;">*</span>
            </label>
            <select id="add_status" name="status" required>
                <option value="Aktif" selected>✅ Aktif - Sedang bekerja</option>
                <option value="Cuti">🏖️ Cuti - Sedang libur</option>
                <option value="Non-Aktif">❌ Non-Aktif - Tidak aktif</option>
            </select>
            <small style="color: #666; font-size: 12px;">Status keaktifan karyawan saat ini</small>

            <!-- Password -->
            <label for="add_password">
                Password <span style="color: red;">*</span>
            </label>
            <input type="password" id="add_password" name="password" placeholder="Minimal 6 karakter" required
                minlength="6" maxlength="50">
            <small style="color: #666; font-size: 12px;">
                Password untuk login dan absensi (min. 6 karakter)
            </small>

            <!-- Show Password Toggle -->
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-top: 10px;">
                <input type="checkbox" id="add_show_password" onclick="togglePasswordVisibility('add_password', this)"
                    style="width: 16px; height: 16px;">
                <span style="font-size: 13px; color: #666;">Tampilkan password</span>
            </label>

            <!-- Photo -->
            <label for="add_photo" style="margin-top: 20px;">
                Foto Karyawan (Opsional)
            </label>
            <input type="file" id="add_photo" name="photo" accept="image/jpeg,image/png,image/jpg"
                onchange="previewAddPhoto(this)">
            <small style="color: #666; font-size: 12px;">
                Format: JPG, JPEG, PNG • Maksimal 5MB • Rasio 3:4 (foto portrait)
            </small>

            <!-- Photo Preview -->
            <div id="add_photo_preview" style="display: none; margin-top: 15px; text-align: center;">
                <p style="font-size: 13px; color: #0066cc; margin-bottom: 10px;">
                    <strong>Preview Foto:</strong>
                </p>
                <img id="add_photo_preview_img" src="" alt="Preview"
                    style="max-width: 200px; max-height: 250px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 3px solid #e3f2fd;">
                <button type="button" onclick="removePhotoPreview()"
                    style="display: block; margin: 10px auto 0; padding: 8px 16px; background: #e74c3c; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 13px;">
                    ❌ Hapus Foto
                </button>
            </div>

            <!-- Actions -->
            <div class="modal-actions" style="margin-top: 30px;">
                <button type="submit" class="btn-save" id="btn_add_save">
                    💾 Simpan Karyawan
                </button>
                <button type="button" class="btn-cancel" onclick="closeAddModal()">
                    ❌ Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Toggle password visibility
    function togglePasswordVisibility(inputId, checkbox) {
        const input = document.getElementById(inputId);
        input.type = checkbox.checked ? 'text' : 'password';
    }

    // Preview photo before upload
    function previewAddPhoto(input) {
        const preview = document.getElementById('add_photo_preview');
        const previewImg = document.getElementById('add_photo_preview_img');
        const alertDiv = document.getElementById('add_alert');

        if (input.files && input.files[0]) {
            const file = input.files[0];

            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alertDiv.className = 'alert alert-error';
                alertDiv.textContent = '⚠️ Ukuran file terlalu besar! Maksimal 5MB.';
                alertDiv.style.display = 'block';
                input.value = '';
                preview.style.display = 'none';

                setTimeout(() => {
                    alertDiv.style.display = 'none';
                }, 4000);
                return;
            }

            // Validate file type
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!validTypes.includes(file.type)) {
                alertDiv.className = 'alert alert-error';
                alertDiv.textContent = '⚠️ Tipe file tidak valid! Gunakan JPG, JPEG, atau PNG.';
                alertDiv.style.display = 'block';
                input.value = '';
                preview.style.display = 'none';

                setTimeout(() => {
                    alertDiv.style.display = 'none';
                }, 4000);
                return;
            }

            // Show preview
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    }

    // Remove photo preview
    function removePhotoPreview() {
        document.getElementById('add_photo').value = '';
        document.getElementById('add_photo_preview').style.display = 'none';
    }

    // Form submission with AJAX
    document.addEventListener('DOMContentLoaded', function () {
        const formAdd = document.getElementById('formAdd');

        if (formAdd) {
            formAdd.addEventListener('submit', function (e) {
                e.preventDefault();

                // Validate at least one role selected
                const roles = document.querySelectorAll('input[name="roles[]"]:checked');
                if (roles.length === 0) {
                    showAddAlert('⚠️ Pilih minimal 1 role untuk karyawan!', 'error');
                    return;
                }

                const submitBtn = document.getElementById('btn_add_save');
                const originalText = submitBtn.innerHTML;

                // Show loading
                submitBtn.innerHTML = '⏳ Menyimpan...';
                submitBtn.disabled = true;

                // Submit form
                fetch('../actions/karyawan/add.php', {
                    method: 'POST',
                    body: new FormData(this)
                })
                    .then(response => response.text())
                    .then(text => {
                        console.log('Server response:', text);
                        const trimmed = text.trim();

                        if (trimmed === 'success') {
                            showAddAlert('✅ Karyawan berhasil ditambahkan!', 'success');

                            // Reset form
                            formAdd.reset();
                            removePhotoPreview();

                            // Reload page after 1.5 seconds
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);

                        } else {
                            // Handle errors
                            let errorMsg = '❌ Gagal menambahkan karyawan!';

                            if (trimmed.includes('duplicate')) {
                                errorMsg = '⚠️ Kode karyawan atau nomor HP sudah terdaftar!';
                            } else if (trimmed.includes('invalid_file_type')) {
                                errorMsg = '⚠️ Tipe file tidak valid! Gunakan JPG, JPEG, atau PNG.';
                            } else if (trimmed.includes('file_too_large')) {
                                errorMsg = '⚠️ Ukuran file terlalu besar! Maksimal 5MB.';
                            } else if (trimmed.includes('password_too_short')) {
                                errorMsg = '⚠️ Password terlalu pendek! Minimal 6 karakter.';
                            } else if (trimmed.includes('missing_roles')) {
                                errorMsg = '⚠️ Pilih minimal 1 role untuk karyawan!';
                            }

                            showAddAlert(errorMsg, 'error');

                            // Re-enable button
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAddAlert('❌ Terjadi kesalahan: ' + error.message, 'error');

                        // Re-enable button
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
            });
        }
    });

    // Show alert in modal
    function showAddAlert(message, type) {
        const alertDiv = document.getElementById('add_alert');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        alertDiv.style.display = 'block';

        // Scroll to top of modal to see alert
        const modalBox = document.querySelector('#modalAdd .modal-box');
        if (modalBox) {
            modalBox.scrollTop = 0;
        }

        // Auto hide success alerts
        if (type === 'success') {
            setTimeout(() => {
                alertDiv.style.display = 'none';
            }, 3000);
        }
    }
</script>