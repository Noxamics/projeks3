<!-- File: modal_confirm_delete.php - Bootstrap Icons VERSION -->
<div class="modal" id="confirmDeleteModal" style="display:none;">
    <div class="modal-content" style="max-width: 500px;">
        <h2 style="color: #dc2626; margin-bottom: 16px;">
            <i class="bi bi-exclamation-triangle"></i> Konfirmasi Hapus
        </h2>

        <div class="modal-message" style="margin-bottom: 24px; color: #374151;">
            <p style="margin-bottom: 12px;">Anda akan menghapus pesanan yang dipilih.</p>
            <p style="color: #dc2626; font-weight: 600;">
                <i class="bi bi-exclamation-triangle-fill"></i> Data yang dihapus tidak dapat dikembalikan!
            </p>
        </div>

        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button type="button" id="confirmCancel"
                style="padding: 10px 24px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                <i class="bi bi-x-lg"></i> Batal
            </button>
            <button type="button" id="confirmOk"
                style="padding: 10px 24px; background: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                <i class="bi bi-trash"></i> Hapus
            </button>
        </div>
    </div>
</div>