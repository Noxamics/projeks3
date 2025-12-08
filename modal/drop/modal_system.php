<!-- modal/drop/modal_system.php -->

<!-- Alert Modal -->
<div id="customAlertModal" class="modal-backdrop">
    <div class="modal-box">
        <div id="alertIcon" class="modal-icon"></div>
        <h3 id="alertTitle"></h3>
        <p id="alertMessage"></p>
        <button id="alertOkBtn" class="btn btn-primary">OK</button>
    </div>
</div>

<!-- Confirm Modal -->
<div id="customConfirmModal" class="modal-backdrop">
    <div class="modal-box">
        <div id="confirmIcon" class="modal-icon"></div>
        <h3 id="confirmTitle"></h3>
        <p id="confirmMessage"></p>
        <div class="btn-group">
            <button id="confirmOkBtn" class="btn btn-primary">Ya</button>
            <button id="confirmCancelBtn" class="btn btn-secondary">Tidak</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="customSuccessModal" class="modal-backdrop">
    <div class="modal-box success-box">
        <div class="modal-icon success"></div>
        <p id="successMessage"></p>
        <button id="successOkBtn" class="btn btn-success">OK</button>
    </div>
</div>

<!-- Error Modal -->
<div id="customErrorModal" class="modal-backdrop">
    <div class="modal-box error-box">
        <div class="modal-icon error"></div>
        <p id="errorMessage"></p>
        <button id="errorOkBtn" class="btn btn-danger">OK</button>
    </div>
</div>

<!-- Loading Modal -->
<div id="customLoadingModal" class="modal-backdrop">
    <div class="loading-modal">
        <div class="loader"></div>
        <p id="loadingMessage"></p>
    </div>
</div>