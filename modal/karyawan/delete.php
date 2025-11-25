<!-- projeks3/modal/karyawan/delete_modern.php -->
<div id="modalDelete" class="modal">
    <div class="modal-box" style="max-width: 450px;">
        <h3>🗑️ Konfirmasi Hapus</h3>
        <p id="delete_text" style="font-size: 16px; color: #636e72; margin: 20px 0;">Apakah yakin ingin menghapus?</p>
        <form id="formDelete">
            <input type="hidden" name="id_employee" id="delete_id">
            <div class="modal-actions">
                <button type="submit" class="btn-delete-confirm">🗑️ Ya, Hapus</button>
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">❌ Batal</button>
            </div>
        </form>
    </div>
</div>