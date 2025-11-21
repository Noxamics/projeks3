<!-- projeks3/modal/karyawan/edit_modern.php -->
<div id="modalEdit" class="modal">
    <div class="modal-box">
        <h3>✏️ Edit Data Karyawan</h3>
        <form id="formEdit" enctype="multipart/form-data">
            <input type="hidden" name="id_employee" id="edit_id">

            <label>Kode Karyawan *</label>
            <input name="employee_code" id="edit_employee_code" required>

            <label>Nama Lengkap *</label>
            <input name="name" id="edit_name" required>

            <label>Nomor HP *</label>
            <input name="phone" id="edit_phone" required>

            <label>Password (kosong jika tidak diubah)</label>
            <input type="password" name="password" id="edit_password"
                placeholder="Biarkan kosong jika tidak ingin mengubah">

            <label>Tanggal Bergabung</label>
            <input type="date" name="join_date" id="edit_join_date">

            <label>Status</label>
            <select name="status" id="edit_status">
                <option value="Aktif">✅ Aktif</option>
                <option value="Cuti">⏸️ Cuti</option>
                <option value="Non-Aktif">❌ Non-Aktif</option>
            </select>

            <label>Foto Profil (upload jika ingin mengubah)</label>
            <input type="file" name="photo" accept="image/*">

            <div class="modal-actions">
                <button type="submit" class="btn-save">💾 Update</button>
                <button type="button" class="btn-cancel" onclick="closeEditModal()">❌ Batal</button>
            </div>
        </form>
    </div>
</div>