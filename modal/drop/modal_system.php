<!-- modal/drop/modal_system.php -->
<!-- VERSI TERBARU - Sudah include CSS inline -->

<!-- Alert Modal -->
<div id="customAlertModal" class="modal-backdrop">
    <div class="modal-box">
        <div id="alertIcon" class="modal-icon"></div>
        <h3 id="alertTitle" style="font-size: 20px; font-weight: 700; margin: 15px 0 10px; color: #1e293b;"></h3>
        <p id="alertMessage" style="font-size: 15px; line-height: 1.6; color: #475569; white-space: pre-line; margin: 10px 0 20px;"></p>
        <button id="alertOkBtn" class="btn btn-primary">OK</button>
    </div>
</div>

<!-- Confirm Modal -->
<div id="customConfirmModal" class="modal-backdrop">
    <div class="modal-box">
        <div id="confirmIcon" class="modal-icon"></div>
        <h3 id="confirmTitle" style="font-size: 20px; font-weight: 700; margin: 15px 0 10px; color: #1e293b;"></h3>
        <p id="confirmMessage" style="font-size: 15px; line-height: 1.6; color: #475569; white-space: pre-line; margin: 10px 0 20px;"></p>
        <div class="btn-group">
            <button id="confirmOkBtn" class="btn btn-primary">Ya</button>
            <button id="confirmCancelBtn" class="btn btn-secondary">Tidak</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="customSuccessModal" class="modal-backdrop">
    <div class="modal-box">
        <div class="modal-icon" style="color: #16a34a;">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
            </svg>
        </div>
        <h3 style="font-size: 20px; font-weight: 700; margin: 15px 0 10px; color: #15803d;">Berhasil!</h3>
        <p id="successMessage" style="font-size: 15px; line-height: 1.6; color: #475569; white-space: pre-line; margin: 10px 0 20px;"></p>
        <button id="successOkBtn" class="btn btn-success">OK</button>
    </div>
</div>

<!-- Error Modal -->
<div id="customErrorModal" class="modal-backdrop">
    <div class="modal-box">
        <div class="modal-icon" style="color: #ef4444;">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293z"/>
            </svg>
        </div>
        <h3 style="font-size: 20px; font-weight: 700; margin: 15px 0 10px; color: #dc2626;">Error!</h3>
        <p id="errorMessage" style="font-size: 15px; line-height: 1.6; color: #475569; white-space: pre-line; margin: 10px 0 20px;"></p>
        <button id="errorOkBtn" class="btn btn-danger">OK</button>
    </div>
</div>

<!-- Loading Modal -->
<div id="customLoadingModal" class="modal-backdrop">
    <div class="loading-modal">
        <div class="loader"></div>
        <p id="loadingMessage" style="margin-top: 15px; font-weight: 600; color: #475569; font-size: 15px;">Memproses...</p>
    </div>
</div>