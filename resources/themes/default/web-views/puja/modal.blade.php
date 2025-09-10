<div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="bookingForm">
        <div class="modal-header">
          <h5 class="modal-title">Booking Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body step1">
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" name="name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Mobile Number</label>
            <input type="text" class="form-control" name="mobile" maxlength="10" pattern="\d{10}" required>
          </div>
          <input type="hidden" name="package" id="selectedPackage">
          <input type="hidden" name="amount" id="selectedPrice">
        </div>

        <div class="modal-body step2 d-none">
          <p><strong>Name:</strong> <span id="confirmName"></span></p>
          <p><strong>Mobile:</strong> <span id="confirmMobile"></span></p>
          <p><strong>Package:</strong> <span id="confirmPackage"></span></p>
          <p><strong>Amount:</strong> ₹<span id="confirmAmount"></span></p>
        </div>

        <div class="modal-body step3 d-none text-center">
          <div class="spinner-border text-primary"></div>
          <p class="mt-3">Processing Payment...</p>
        </div>

        <div class="modal-body step4 d-none text-center text-success">
          <i class="bi bi-check-circle-fill fs-1"></i>
          <p class="mt-3">Payment Successful! Your booking is confirmed.</p>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary d-none step2-btn" id="backBtn">Back</button>
          <button type="submit" class="btn btn-primary step1-btn">Continue</button>
          <button type="button" class="btn btn-success d-none step2-btn" id="confirmPayBtn">Confirm & Pay</button>
          <button type="button" class="btn btn-primary d-none step4-btn" data-bs-dismiss="modal">Done</button>
        </div>
      </form>
    </div>
  </div>
</div>
