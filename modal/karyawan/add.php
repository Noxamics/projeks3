<!-- projeks3/modal/karyawan/add_modern.php -->
<div id="modalAdd" class="modal">
    <div class="modal-box">
        <h3>➕ Tambah Karyawan Baru</h3>
        <form id="formAdd" enctype="multipart/form-data">
            <label>Kode Karyawan *</label>
            <input name="employee_code" required placeholder="Contoh: EMP001">

            <label>Nama Lengkap *</label>
            <input name="name" required placeholder="Masukkan nama lengkap">

            <label>Nomor HP *</label>
            <input name="phone" required placeholder="08xxxxxxxxxx">

            <label>Password *</label>
            <input type="password" name="password" required placeholder="Minimal 6 karakter">

            <label>Tanggal Bergabung</label>
            <input type="date" name="join_date" value="<?= date('Y-m-d') ?>">

            <label>Status</label>
            <select name="status">
                <option value="Aktif" selected>✅ Aktif</option>
                <option value="Cuti">⏸️ Cuti</option>
                <option value="Non-Aktif">❌ Non-Aktif</option>
            </select>

            <label>Foto Profil</label>
            <input type="file" name="photo" accept="image/*">

            <div class="modal-actions">
                <button type="submit" class="btn-save">💾 Simpan</button>
                <button type="button" class="btn-cancel" onclick="closeAddModal()">❌ Batal</button>
            </div>
        </form>
    </div>
</div>