<?php
/**
 * 7-Step Universal Candidate & Worker Registration Modal
 * Matches Flutter Mobile App Universal Registration Screen (EPFO Form 11, Biometrics, Statutory KYC)
 */
?>
<!-- UNIVERSAL REGISTRATION MODAL STYLES -->
<style>
  #modal-universal-register .modal-card {
    width: 960px;
    max-width: 95vw;
    max-height: 92vh;
    padding: 0;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: #ffffff;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);
  }

  /* Stepper Progress Bar */
  .ureg-stepper {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 12px 20px;
    display: flex;
    gap: 6px;
    overflow-x: auto;
    scrollbar-width: thin;
  }
  .ureg-step-tab {
    flex: 1;
    min-width: 120px;
    padding: 8px 10px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    user-select: none;
  }
  .ureg-step-tab:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
  }
  .ureg-step-tab.active {
    background: #2563eb;
    border-color: #2563eb;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
  }
  .ureg-step-tab.completed {
    background: #f0fdf4;
    border-color: #bbf7d0;
  }
  .ureg-step-num {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 800;
    background: #e2e8f0;
    color: #475569;
    flex-shrink: 0;
  }
  .ureg-step-tab.active .ureg-step-num {
    background: #ffffff;
    color: #2563eb;
  }
  .ureg-step-tab.completed .ureg-step-num {
    background: #22c55e;
    color: #ffffff;
  }
  .ureg-step-title {
    font-size: 11.5px;
    font-weight: 700;
    line-height: 1.2;
    color: #334155;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .ureg-step-tab.active .ureg-step-title {
    color: #ffffff;
  }
  .ureg-step-tab.completed .ureg-step-title {
    color: #15803d;
  }

  /* Form Panels */
  .ureg-step-panel {
    display: none;
    animation: fadeInStep 0.25s ease;
  }
  .ureg-step-panel.active {
    display: block;
  }
  @keyframes fadeInStep {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .ureg-section-head {
    font-size: 14px;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f1f5f9;
  }
  .ureg-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 14px;
  }
  .ureg-grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 16px;
    margin-bottom: 14px;
  }
  .ureg-grid-4 {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 14px;
  }
  .ureg-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
  .ureg-field label {
    font-size: 12px;
    font-weight: 700;
    color: #475569;
  }
  .ureg-field input, .ureg-field select, .ureg-field textarea {
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 13px;
    color: #0f172a;
    background: #ffffff;
    transition: all 0.2s;
  }
  .ureg-field input:focus, .ureg-field select:focus, .ureg-field textarea:focus {
    border-color: #2563eb;
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
  }
  .ureg-field .hint {
    font-size: 11px;
    color: #64748b;
  }

  /* Document Cards */
  .ureg-doc-card {
    border: 2px dashed #cbd5e1;
    border-radius: 10px;
    padding: 14px;
    text-align: center;
    background: #f8fafc;
    transition: all 0.2s;
    position: relative;
  }
  .ureg-doc-card:hover {
    border-color: #2563eb;
    background: #eff6ff;
  }
  .ureg-doc-preview {
    width: 100%;
    height: 110px;
    object-fit: cover;
    border-radius: 6px;
    margin-top: 8px;
    display: none;
    border: 1px solid #e2e8f0;
  }
  .ureg-custom-file-btn {
    display: inline-block;
    padding: 6px 12px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
    margin-top: 6px;
  }

  /* Review Summary Cards */
  .ureg-review-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 14px;
  }
  .ureg-review-card h4 {
    font-size: 13px;
    font-weight: 800;
    color: #1e293b;
    margin: 0 0 10px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .ureg-review-card h4 a {
    font-size: 11.5px;
    color: #2563eb;
    cursor: pointer;
    text-decoration: underline;
  }
  .ureg-review-row {
    display: flex;
    justify-content: space-between;
    font-size: 12.5px;
    padding: 4px 0;
    border-bottom: 1px dashed #e2e8f0;
  }
  .ureg-review-row:last-child {
    border-bottom: none;
  }
  .ureg-review-label {
    color: #64748b;
    font-weight: 600;
  }
  .ureg-review-val {
    color: #0f172a;
    font-weight: 700;
    text-align: right;
  }
</style>

<!-- MODAL MARKUP -->
<div class="modal-overlay" id="modal-universal-register" style="z-index: 99999;">
  <div class="modal-card">
    
    <!-- 1. Header -->
    <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155;">
      <div>
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="display: inline-block; width: 8px; height: 8px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 8px #22c55e;"></span>
          <span style="font-size: 11px; font-weight: 800; letter-spacing: 1px; color: #94a3b8; text-transform: uppercase;">TrooGood HRMS Master Enrollment</span>
        </div>
        <h2 style="font-size: 18px; font-weight: 800; margin: 4px 0 0 0; color: #f8fafc;">Universal Worker Registration (EPFO Form 11 & Biometric)</h2>
      </div>
      <button onclick="closeUniversalRegisterModal()" style="background: rgba(255,255,255,0.1); border: none; color: #fff; width: 32px; height: 32px; border-radius: 50%; font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center;" title="Close Modal">&times;</button>
    </div>

    <!-- 2. Stepper Bar -->
    <div class="ureg-stepper">
      <div class="ureg-step-tab active" id="ureg-tab-1" onclick="setUniversalStep(1)">
        <div class="ureg-step-num">1</div>
        <div class="ureg-step-title">1. Job & Position</div>
      </div>
      <div class="ureg-step-tab" id="ureg-tab-2" onclick="setUniversalStep(2)">
        <div class="ureg-step-num">2</div>
        <div class="ureg-step-title">2. Personal Profile</div>
      </div>
      <div class="ureg-step-tab" id="ureg-tab-3" onclick="setUniversalStep(3)">
        <div class="ureg-step-num">3</div>
        <div class="ureg-step-title">3. Identity & KYC</div>
      </div>
      <div class="ureg-step-tab" id="ureg-tab-4" onclick="setUniversalStep(4)">
        <div class="ureg-step-num">4</div>
        <div class="ureg-step-title">4. PF & ESIC</div>
      </div>
      <div class="ureg-step-tab" id="ureg-tab-5" onclick="setUniversalStep(5)">
        <div class="ureg-step-num">5</div>
        <div class="ureg-step-title">5. Nominee & Family</div>
      </div>
      <div class="ureg-step-tab" id="ureg-tab-6" onclick="setUniversalStep(6)">
        <div class="ureg-step-num">6</div>
        <div class="ureg-step-title">6. Declarations</div>
      </div>
      <div class="ureg-step-tab" id="ureg-tab-7" onclick="setUniversalStep(7)">
        <div class="ureg-step-num">7</div>
        <div class="ureg-step-title">7. Review & Submit</div>
      </div>
    </div>

    <!-- 3. Scrollable Form Content -->
    <div style="padding: 22px 26px; overflow-y: auto; flex: 1;">
      <form id="form-universal-register" autocomplete="off" onsubmit="event.preventDefault(); submitUniversalRegistration();">

        <!-- ==================== STEP 1: JOB & POSITION ==================== -->
        <div class="ureg-step-panel active" id="ureg-step-1">
          <div class="ureg-section-head">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
            Job & Factory Assignment Details
          </div>

          <div class="ureg-grid-2">
            <div class="ureg-field">
              <label>Worker / Employee Code <span style="font-weight:normal; color:#64748b; font-size:12px;">(Leave blank to auto-generate)</span></label>
              <input type="text" id="ureg-worker-code" placeholder="e.g. 561 or EMP-101 (leave blank to auto-generate)">
              <span class="hint">Optional: Enter custom employee code, or leave blank to automatically assign the next number.</span>
            </div>
            <div class="ureg-field">
              <label>Date of Joining *</label>
              <input type="date" id="ureg-doj" required>
            </div>
          </div>

          <div class="ureg-grid-2">
            <div class="ureg-field">
              <label>Factory Unit *</label>
              <select id="ureg-unit" required>
                <?php if (!empty($portalUnits)): ?>
                  <?php foreach ($portalUnits as $u): ?>
                    <option value="<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars($u['id']) ?><?= (!empty($u['unit_name']) && $u['unit_name'] !== $u['id']) ? ' (' . htmlspecialchars($u['unit_name']) . ')' : '' ?></option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <option value="Corporate">Corporate</option>
                  <option value="TG-Mega">TG-Mega</option>
                  <option value="TGH-1">TGH-1</option>
                  <option value="TGK-1">TGK-1</option>
                  <option value="TGK-2">TGK-2</option>
                  <option value="TGK-3">TGK-3</option>
                  <option value="TGK-4">TGK-4</option>
                  <option value="TGR">TGR</option>
                  <option value="TGT">TGT</option>
                <?php endif; ?>
              </select>
            </div>
            <div class="ureg-field">
              <label>Shift Timing *</label>
              <select id="ureg-shift" required>
                <option value="Shift A">Shift A (06:00 - 14:00)</option>
                <option value="General Shift">General Shift (09:00 - 18:00)</option>
                <option value="Shift B">Shift B (14:00 - 22:00)</option>
              </select>
              <span class="hint">Loaded dynamically from active Shift Master.</span>
            </div>
          </div>

          <div class="ureg-grid-2">
            <div class="ureg-field">
              <label>Department / Functional Role *</label>
              <select id="ureg-role" required>
                <option value="Production" selected>Production</option>
                <option value="Packaging">Packaging</option>
                <option value="Quality Assurance">Quality Assurance</option>
                <option value="Logistics & Warehouse">Logistics & Warehouse</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Accounts & Finance">Accounts & Finance</option>
                <option value="HR & Admin">HR & Admin</option>
                <option value="IT">IT</option>
              </select>
            </div>
            <div class="ureg-field">
              <label>Designation Title</label>
              <input type="text" id="ureg-designation" placeholder="e.g. Machine Operator, Production Associate">
            </div>
          </div>

          <div class="ureg-grid-2">
            <div class="ureg-field">
              <label>Reporting To (Supervisor)</label>
              <input type="text" id="ureg-reporting" placeholder="e.g. Plant Supervisor / Shift Incharge" value="Plant Supervisor">
            </div>
            <div class="ureg-field">
              <label>Engagement Type *</label>
              <select id="ureg-engagement" onchange="handleEngagementChange()" required>
                <option value="On TrooGood Rolls">On TrooGood Rolls (Permanent)</option>
                <option value="Contract" selected>Contract (Third-Party Agency)</option>
                <option value="Consultant">Consultant</option>
                <option value="Trainee">Trainee / Apprentice</option>
              </select>
            </div>
          </div>

          <div id="ureg-contractor-box" class="ureg-field" style="margin-top: 4px;">
            <label>Contractor / Staffing Agency *</label>
            <div style="display: flex; gap: 8px;">
              <select id="ureg-contractor" style="flex: 1;" onchange="handleContractorSelect(this)">
                <option value="Sri Sai Manpower">Sri Sai Manpower</option>
                <option value="Apex Logistics">Apex Logistics</option>
                <option value="Venkateshwara Enterprises">Venkateshwara Enterprises</option>
                <option value="Global Workforce Solutions">Global Workforce Solutions</option>
                <option value="Balaji Security & Services">Balaji Security & Services</option>
                <option value="Sunrise Facility Management">Sunrise Facility Management</option>
                <option value="__OTHER__">+ Enter Other Agency</option>
              </select>
              <input type="text" id="ureg-other-contractor" placeholder="Agency Name" style="display: none; flex: 1;">
            </div>
          </div>
        </div>

        <!-- ==================== STEP 2: PERSONAL PROFILE ==================== -->
        <div class="ureg-step-panel" id="ureg-step-2">
          <div class="ureg-section-head">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            Personal Information & Contact Profile
          </div>

          <div class="ureg-grid-2">
            <div class="ureg-field">
              <label>Full Name (as per Aadhaar Card) *</label>
              <input type="text" id="ureg-full-name" placeholder="e.g. Ramesh Kumar" required onkeyup="syncSignatoryName()">
            </div>
            <div class="ureg-field">
              <label>Date of Birth *</label>
              <div style="display: flex; align-items: center; gap: 8px;">
                <input type="date" id="ureg-dob" required onchange="handleDobChange(this.value)" style="flex: 1;">
                <span id="ureg-age-badge" class="badge badge-p" style="font-size: 11px; padding: 6px 10px;">Age: --</span>
              </div>
            </div>
          </div>

          <div class="ureg-grid-3">
            <div class="ureg-field">
              <label>Gender *</label>
              <select id="ureg-gender">
                <option value="Male" selected>Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="ureg-field">
              <label>Marital Status</label>
              <select id="ureg-marital">
                <option value="Unmarried" selected>Unmarried</option>
                <option value="Married">Married</option>
                <option value="Widow or widower">Widow or Widower</option>
                <option value="Divorced">Divorced</option>
              </select>
            </div>
            <div class="ureg-field">
              <label>Blood Group</label>
              <select id="ureg-blood-group">
                <option value="O+" selected>O+</option>
                <option value="A+">A+</option>
                <option value="B+">B+</option>
                <option value="AB+">AB+</option>
                <option value="O-">O-</option>
                <option value="A-">A-</option>
                <option value="B-">B-</option>
                <option value="AB-">AB-</option>
              </select>
            </div>
          </div>

          <div class="ureg-grid-2">
            <div class="ureg-field">
              <label>Father or Husband's Name *</label>
              <input type="text" id="ureg-father-name" placeholder="Father's / Spouse's Name" required>
            </div>
            <div class="ureg-field">
              <label>Mobile Number (10 digits) *</label>
              <div style="position: relative;">
                <input type="tel" id="ureg-mobile" maxlength="10" placeholder="e.g. 9876543210" required onkeyup="checkUniversalMobileAvailability()" style="padding-right: 90px;">
                <span id="ureg-mobile-status" style="position: absolute; right: 8px; top: 7px; font-size: 10.5px; font-weight: 700; padding: 2px 6px; border-radius: 4px; display: none;"></span>
              </div>
            </div>
          </div>

          <div class="ureg-grid-2">
            <div class="ureg-field">
              <label>Alternate Mobile Number</label>
              <input type="tel" id="ureg-alt-mobile" maxlength="10" placeholder="Optional backup contact">
            </div>
            <div class="ureg-field">
              <label>Email Address</label>
              <input type="email" id="ureg-email" placeholder="e.g. employee@troogood.com">
            </div>
          </div>

          <div class="ureg-field" style="margin-bottom: 14px;">
            <label>Languages Spoken</label>
            <input type="text" id="ureg-languages" placeholder="e.g. Telugu, Hindi, English">
          </div>

          <div class="ureg-field" style="margin-bottom: 14px;">
            <label>Present Residential Address *</label>
            <textarea id="ureg-present-address" rows="2" placeholder="House no, Street, Landmark, Village/Town, City, PIN" required onkeyup="handleSameAddressAutoSync()"></textarea>
          </div>

          <div style="margin-bottom: 8px;">
            <label style="font-size: 12.5px; font-weight: 700; color: #334155; display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
              <input type="checkbox" id="ureg-same-address-chk" onchange="handleSameAddressToggle(this.checked)">
              Permanent Address is identical to Present Address
            </label>
          </div>

          <div class="ureg-field" id="ureg-permanent-address-box" style="margin-bottom: 14px;">
            <label>Permanent Home Address</label>
            <textarea id="ureg-permanent-address" rows="2" placeholder="Native place address as per Aadhaar Card"></textarea>
          </div>

          <div class="ureg-section-head" style="font-size: 13px; margin-top: 14px;">
            Emergency Contact Information
          </div>
          <div class="ureg-grid-3">
            <div class="ureg-field">
              <label>Contact Person Name</label>
              <input type="text" id="ureg-emergency-name" placeholder="Emergency contact">
            </div>
            <div class="ureg-field">
              <label>Relationship</label>
              <input type="text" id="ureg-emergency-rel" placeholder="e.g. Brother, Mother">
            </div>
            <div class="ureg-field">
              <label>Emergency Mobile</label>
              <input type="tel" id="ureg-emergency-mobile" maxlength="10" placeholder="10-digit number">
            </div>
          </div>
        </div>

        <!-- ==================== STEP 3: IDENTITY & BANK KYC ==================== -->
        <div class="ureg-step-panel" id="ureg-step-3">
          <div class="ureg-section-head">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
            Statutory Identity Documents & Bank Account KYC
          </div>

          <div class="ureg-grid-2">
            <div class="ureg-field">
              <label>Aadhaar Card Number (12 Digits) *</label>
              <input type="text" id="ureg-aadhaar" maxlength="14" placeholder="0000 0000 0000" onkeyup="formatAadhaarInput(this)" required>
              <span class="hint">Mandatory for EPFO Universal Account Number (UAN) seeding.</span>
            </div>
            <div class="ureg-field">
              <label>PAN Card Number</label>
              <input type="text" id="ureg-pan" maxlength="10" placeholder="e.g. ABCDE1234F" style="text-transform: uppercase;">
              <span class="hint">10-character alphanumeric PAN issued by Income Tax Dept.</span>
            </div>
          </div>

          <div class="ureg-grid-2">
            <div class="ureg-field">
              <label>Bank Name & Branch *</label>
              <input type="text" id="ureg-bank-name" placeholder="e.g. State Bank of India, Medchal Branch" required>
            </div>
            <div class="ureg-field">
              <label>IFSC Code (11 Digits) *</label>
              <input type="text" id="ureg-ifsc" maxlength="11" placeholder="e.g. SBIN0001234" style="text-transform: uppercase;" required>
            </div>
          </div>

          <div class="ureg-grid-2">
            <div class="ureg-field">
              <label>Bank Account Number *</label>
              <input type="password" id="ureg-bank-account" placeholder="Enter bank account number" required onkeyup="validateBankMatch()">
            </div>
            <div class="ureg-field">
              <label>Confirm Bank Account Number *</label>
              <div style="position: relative;">
                <input type="text" id="ureg-confirm-account" placeholder="Re-enter bank account number" required onkeyup="validateBankMatch()">
                <span id="ureg-bank-match-badge" style="position: absolute; right: 8px; top: 7px; font-size: 10.5px; font-weight: 700; padding: 2px 6px; border-radius: 4px; display: none;"></span>
              </div>
            </div>
          </div>

          <div class="ureg-section-head" style="font-size: 13px; margin-top: 18px;">
            Document Attachments & Photo Proofs (Instant Image Upload)
          </div>

          <div class="ureg-grid-4">
            <!-- 1. Passport Photo -->
            <div class="ureg-doc-card">
              <div style="font-size: 12px; font-weight: 800; color: #1e293b;">Passport Photo</div>
              <input type="file" id="ureg-file-passport" accept="image/*" style="display: none;" onchange="handleDocFileChange(this, 'ureg-img-passport', 'ureg-b64-passport')">
              <input type="hidden" id="ureg-b64-passport">
              <label for="ureg-file-passport" class="ureg-custom-file-btn">Choose Photo</label>
              <img id="ureg-img-passport" class="ureg-doc-preview" alt="Passport Photo">
            </div>

            <!-- 2. Aadhaar Front -->
            <div class="ureg-doc-card">
              <div style="font-size: 12px; font-weight: 800; color: #1e293b;">Aadhaar Front</div>
              <input type="file" id="ureg-file-aadhaar-front" accept="image/*" style="display: none;" onchange="handleDocFileChange(this, 'ureg-img-aadhaar-front', 'ureg-b64-aadhaar-front')">
              <input type="hidden" id="ureg-b64-aadhaar-front">
              <label for="ureg-file-aadhaar-front" class="ureg-custom-file-btn">Front Image</label>
              <img id="ureg-img-aadhaar-front" class="ureg-doc-preview" alt="Aadhaar Front">
            </div>

            <!-- 3. Aadhaar Back -->
            <div class="ureg-doc-card">
              <div style="font-size: 12px; font-weight: 800; color: #1e293b;">Aadhaar Back</div>
              <input type="file" id="ureg-file-aadhaar-back" accept="image/*" style="display: none;" onchange="handleDocFileChange(this, 'ureg-img-aadhaar-back', 'ureg-b64-aadhaar-back')">
              <input type="hidden" id="ureg-b64-aadhaar-back">
              <label for="ureg-file-aadhaar-back" class="ureg-custom-file-btn">Back Image</label>
              <img id="ureg-img-aadhaar-back" class="ureg-doc-preview" alt="Aadhaar Back">
            </div>

            <!-- 4. Cheque / Passbook -->
            <div class="ureg-doc-card">
              <div style="font-size: 12px; font-weight: 800; color: #1e293b;">Bank Proof</div>
              <input type="file" id="ureg-file-cheque" accept="image/*" style="display: none;" onchange="handleDocFileChange(this, 'ureg-img-cheque', 'ureg-b64-cheque')">
              <input type="hidden" id="ureg-b64-cheque">
              <label for="ureg-file-cheque" class="ureg-custom-file-btn">Cheque / Passbook</label>
              <img id="ureg-img-cheque" class="ureg-doc-preview" alt="Bank Proof">
            </div>
          </div>
        </div>

        <!-- ==================== STEP 4: PF & ESIC STATUTORY ==================== -->
        <div class="ureg-step-panel" id="ureg-step-4">
          <div class="ureg-section-head">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            Provident Fund (EPFO) & ESIC Statutory Compliance (Form 11)
          </div>

          <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 16px;">
            <label style="font-weight: 700; font-size: 13px; color: #1e293b; display: block; margin-bottom: 6px;">
              Has worked previously in any establishment covered under EPF Act 1952?
            </label>
            <div style="display: flex; gap: 20px; font-size: 13px; font-weight: 600;">
              <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                <input type="radio" name="ureg_worked" value="no" checked onchange="togglePrevWork(false)"> No (First Time Employment)
              </label>
              <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                <input type="radio" name="ureg_worked" value="yes" onchange="togglePrevWork(true)"> Yes (Existing Member)
              </label>
            </div>
          </div>

          <div id="ureg-prev-work-box" class="ureg-grid-2" style="display: none;">
            <div class="ureg-field">
              <label>Previous Employer Name</label>
              <input type="text" id="ureg-prev-employer" placeholder="Name of previous company">
            </div>
            <div class="ureg-field">
              <label>Last Working Date</label>
              <input type="date" id="ureg-last-work-date">
            </div>
          </div>

          <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 16px;">
            <label style="font-weight: 700; font-size: 13px; color: #1e293b; display: block; margin-bottom: 6px;">
              Does the employee have an existing Universal Account Number (UAN) / PF Account?
            </label>
            <div style="display: flex; gap: 20px; font-size: 13px; font-weight: 600;">
              <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                <input type="radio" name="ureg_pf" value="no" checked onchange="togglePfBox(false)"> No
              </label>
              <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                <input type="radio" name="ureg_pf" value="yes" onchange="togglePfBox(true)"> Yes
              </label>
            </div>
          </div>

          <div id="ureg-pf-box" class="ureg-grid-2" style="display: none;">
            <div class="ureg-field">
              <label>Universal Account Number (UAN - 12 Digits)</label>
              <input type="text" id="ureg-uan" maxlength="12" placeholder="e.g. 100123456789">
            </div>
            <div class="ureg-field">
              <label>EPS Pension Scheme Member Status</label>
              <select id="ureg-eps">
                <option value="No">No</option>
                <option value="Yes">Yes</option>
                <option value="Not sure">Not sure</option>
              </select>
            </div>
          </div>

          <div class="ureg-grid-2">
            <div class="ureg-field">
              <label>ESIC Insurance Number (17 Digits)</label>
              <input type="text" id="ureg-esic" maxlength="17" placeholder="e.g. 52001234560000001">
              <span class="hint">Employees' State Insurance Corporation IP number if previously allotted.</span>
            </div>
            <div class="ureg-field" style="justify-content: center;">
              <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; font-weight: 700; cursor: pointer; margin-top: 14px;">
                <input type="checkbox" id="ureg-opt-out">
                Opt-Out of PF & ESI statutory deductions (Salary > ₹15,000 statutory limit)
              </label>
            </div>
          </div>
        </div>

        <!-- ==================== STEP 5: NOMINEE & FAMILY ==================== -->
        <div class="ureg-step-panel" id="ureg-step-5">
          <div class="ureg-section-head">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            Statutory Nominee & Family Member Particulars
          </div>

          <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 18px;">
            <div style="font-size: 12.5px; font-weight: 800; color: #1e293b; margin-bottom: 10px;">Primary Statutory Beneficiary / Nominee</div>
            <div class="ureg-grid-2">
              <div class="ureg-field">
                <label>Nominee Full Name *</label>
                <input type="text" id="ureg-nominee-name" placeholder="Full name of beneficiary" required>
              </div>
              <div class="ureg-field">
                <label>Relationship with Worker *</label>
                <select id="ureg-nominee-rel" required>
                  <option value="Spouse">Spouse (Wife / Husband)</option>
                  <option value="Son">Son</option>
                  <option value="Daughter">Daughter</option>
                  <option value="Father">Father</option>
                  <option value="Mother">Mother</option>
                  <option value="Brother">Brother</option>
                  <option value="Sister">Sister</option>
                  <option value="Other">Other</option>
                </select>
              </div>
            </div>
            <div class="ureg-grid-2">
              <div class="ureg-field">
                <label>Nominee Date of Birth *</label>
                <input type="date" id="ureg-nominee-dob" required>
              </div>
              <div class="ureg-field">
                <label>Share of Benefits (%)</label>
                <input type="text" id="ureg-nominee-share" value="100%" placeholder="100%">
              </div>
            </div>
          </div>

          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <div style="font-size: 13px; font-weight: 800; color: #1e293b;">Dependent Family Members</div>
            <button type="button" class="btn btn-secondary" style="padding: 4px 10px; font-size: 11.5px;" onclick="addUniversalFamilyRow()">+ Add Family Member</button>
          </div>

          <div class="table-responsive" style="margin-bottom: 14px;">
            <table>
              <thead>
                <tr>
                  <th>FULL NAME</th>
                  <th>RELATIONSHIP</th>
                  <th>DATE OF BIRTH</th>
                  <th>ACTION</th>
                </tr>
              </thead>
              <tbody id="ureg-family-tbody">
                <!-- Dynamically generated rows -->
              </tbody>
            </table>
          </div>
        </div>

        <!-- ==================== STEP 6: DECLARATIONS & SIGNATURE ==================== -->
        <div class="ureg-step-panel" id="ureg-step-6">
          <div class="ureg-section-head">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Statutory Declarations & Candidate Digital Signature
          </div>

          <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 18px;">
            <label style="display: flex; align-items: flex-start; gap: 10px; font-size: 12.5px; color: #334155; line-height: 1.4; cursor: pointer;">
              <input type="checkbox" id="ureg-decl-truth" checked required style="margin-top: 2px;">
              <span><strong>Declaration of Truthfulness:</strong> I hereby declare that all particulars furnished above are true and complete to the best of my knowledge and belief.</span>
            </label>
            <label style="display: flex; align-items: flex-start; gap: 10px; font-size: 12.5px; color: #334155; line-height: 1.4; cursor: pointer;">
              <input type="checkbox" id="ureg-decl-safety" checked required style="margin-top: 2px;">
              <span><strong>Factory Safety & Conduct:</strong> I agree to strictly observe all food manufacturing hygiene, safety, sanitation, and industrial discipline standards set by TrooGood.</span>
            </label>
            <label style="display: flex; align-items: flex-start; gap: 10px; font-size: 12.5px; color: #334155; line-height: 1.4; cursor: pointer;">
              <input type="checkbox" id="ureg-decl-bank" checked required style="margin-top: 2px;">
              <span><strong>Direct Salary Deposit:</strong> I confirm that the bank account and IFSC details furnished belong to me, and consent to direct electronic salary disbursement.</span>
            </label>
            <label style="display: flex; align-items: flex-start; gap: 10px; font-size: 12.5px; color: #334155; line-height: 1.4; cursor: pointer;">
              <input type="checkbox" id="ureg-decl-epfo" checked required style="margin-top: 2px;">
              <span><strong>Aadhaar EPFO Consent:</strong> I voluntarily provide my Aadhaar number for identity authentication and statutory Form 11 seeding as mandated by EPFO/ESIC.</span>
            </label>
            <label style="display: flex; align-items: flex-start; gap: 10px; font-size: 12.5px; color: #1e40af; background: #eff6ff; border: 1px solid #bfdbfe; padding: 10px 12px; border-radius: 8px; line-height: 1.4; cursor: pointer;">
              <input type="checkbox" id="ureg-decl-biometric" checked required style="margin-top: 2px;">
              <span><strong>Facial Biometrics & DPDP Act Consent:</strong> I explicitly consent to facial biometric vectors being generated and stored securely for shift attendance recognition across TrooGood factory kiosk terminals.</span>
            </label>
          </div>

          <div class="ureg-section-head" style="font-size: 13px;">Digital Signature Authorization</div>
          <div class="ureg-grid-3">
            <div class="ureg-field">
              <label>Signatory Full Name *</label>
              <input type="text" id="ureg-sig-name" placeholder="Typed full name" required>
            </div>
            <div class="ureg-field">
              <label>Place / City *</label>
              <input type="text" id="ureg-sig-city" value="Hyderabad" placeholder="City" required>
            </div>
            <div class="ureg-field">
              <label>Signature Timestamp</label>
              <input type="text" id="ureg-sig-time" readonly style="background: #f1f5f9; color: #64748b; font-family: monospace; font-size: 11.5px;">
            </div>
          </div>
        </div>

        <!-- ==================== STEP 7: REVIEW & SUBMIT ==================== -->
        <div class="ureg-step-panel" id="ureg-step-7">
          <div class="ureg-section-head">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
            Review Profile & Statutory Registration Dossier
          </div>

          <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 22px;">&#10004;</span>
            <div style="font-size: 12.5px; color: #15803d; line-height: 1.4;">
              <strong>Ready for Enrollment:</strong> Review all 6 steps below. Submitting will save the employee into <strong>People Master</strong>, statutory <strong>EPFO Form 11</strong>, update the <strong>Onboarding Pipeline</strong> to Joined, and instantly synchronize biometric recognition vectors with factory kiosk hardware.
            </div>
          </div>

          <div class="ureg-grid-2">
            <!-- Review Card 1: Job -->
            <div class="ureg-review-card">
              <h4>Job & Unit Scope <a onclick="setUniversalStep(1)">Edit</a></h4>
              <div class="ureg-review-row"><span class="ureg-review-label">Worker Code:</span><span class="ureg-review-val" id="rev-worker-code">-</span></div>
              <div class="ureg-review-row"><span class="ureg-review-label">Factory Unit:</span><span class="ureg-review-val" id="rev-unit">-</span></div>
              <div class="ureg-review-row"><span class="ureg-review-label">Department:</span><span class="ureg-review-val" id="rev-role">-</span></div>
              <div class="ureg-review-row"><span class="ureg-review-label">Shift Timing:</span><span class="ureg-review-val" id="rev-shift">-</span></div>
              <div class="ureg-review-row"><span class="ureg-review-label">Engagement:</span><span class="ureg-review-val" id="rev-engagement">-</span></div>
              <div class="ureg-review-row"><span class="ureg-review-label">Contractor Agency:</span><span class="ureg-review-val" id="rev-contractor">-</span></div>
            </div>

            <!-- Review Card 2: Personal -->
            <div class="ureg-review-card">
              <h4>Personal Details <a onclick="setUniversalStep(2)">Edit</a></h4>
              <div class="ureg-review-row"><span class="ureg-review-label">Full Name:</span><span class="ureg-review-val" id="rev-name">-</span></div>
              <div class="ureg-review-row"><span class="ureg-review-label">Date of Birth:</span><span class="ureg-review-val" id="rev-dob">-</span></div>
              <div class="ureg-review-row"><span class="ureg-review-label">Gender / Status:</span><span class="ureg-review-val" id="rev-gender">-</span></div>
              <div class="ureg-review-row"><span class="ureg-review-label">Mobile Number:</span><span class="ureg-review-val" id="rev-mobile">-</span></div>
              <div class="ureg-review-row"><span class="ureg-review-label">Father / Spouse:</span><span class="ureg-review-val" id="rev-father">-</span></div>
              <div class="ureg-review-row"><span class="ureg-review-label">Blood Group:</span><span class="ureg-review-val" id="rev-blood">-</span></div>
            </div>
          </div>

          <div class="ureg-grid-2">
            <!-- Review Card 3: Bank & KYC -->
            <div class="ureg-review-card">
              <h4>Identity & Bank KYC <a onclick="setUniversalStep(3)">Edit</a></h4>
              <div class="ureg-review-row"><span class="ureg-review-label">Aadhaar Card:</span><span class="ureg-review-val" id="rev-aadhaar">-</span></div>
              <div class="ureg-review-row"><span class="ureg-review-label">PAN Number:</span><span class="ureg-review-val" id="rev-pan">-</span></div>
              <div class="ureg-review-row"><span class="ureg-review-label">Bank Name:</span><span class="ureg-review-val" id="rev-bank">-</span></div>
              <div class="ureg-review-row"><span class="ureg-review-label">Account Number:</span><span class="ureg-review-val" id="rev-account">-</span></div>
              <div class="ureg-review-row"><span class="ureg-review-label">IFSC Code:</span><span class="ureg-review-val" id="rev-ifsc">-</span></div>
            </div>

            <!-- Review Card 4: Statutory & Documents -->
            <div class="ureg-review-card">
              <h4>Statutory & Proofs <a onclick="setUniversalStep(4)">Edit</a></h4>
              <div class="ureg-review-row"><span class="ureg-review-label">UAN / PF Status:</span><span class="ureg-review-val" id="rev-uan">-</span></div>
              <div class="ureg-review-row"><span class="ureg-review-label">ESIC Number:</span><span class="ureg-review-val" id="rev-esic">-</span></div>
              <div class="ureg-review-row"><span class="ureg-review-label">Nominee:</span><span class="ureg-review-val" id="rev-nominee">-</span></div>
              <div class="ureg-review-row"><span class="ureg-review-label">Biometric Vectors:</span><span class="ureg-review-val" style="color: #16a34a;">8-Angle ArcFace 192-d</span></div>
              <div class="ureg-review-row"><span class="ureg-review-label">Document Proofs:</span><span class="ureg-review-val" id="rev-docs-status">4 Documents Attached</span></div>
            </div>
          </div>
        </div>

      </form>
    </div>

    <!-- 4. Footer Action Controls -->
    <div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 14px 24px; display: flex; justify-content: space-between; align-items: center;">
      <button type="button" id="ureg-btn-prev" class="btn btn-secondary" onclick="prevUniversalStep()" style="display: none; padding: 8px 16px;">
        &larr; Previous Step
      </button>
      <div style="margin-left: auto; display: flex; gap: 10px;">
        <button type="button" class="btn btn-secondary" onclick="closeUniversalRegisterModal()" style="padding: 8px 16px;">Cancel</button>
        <button type="button" id="ureg-btn-next" class="btn btn-primary" onclick="nextUniversalStep()" style="padding: 8px 20px;">
          Next Step &rarr;
        </button>
        <button type="button" id="ureg-btn-submit" class="btn btn-primary" onclick="submitUniversalRegistration()" style="display: none; background: #16a34a; border-color: #16a34a; padding: 8px 22px; font-weight: 800;">
          Save & Sync Employee
        </button>
      </div>
    </div>

  </div>
</div>

<!-- UNIVERSAL REGISTRATION JAVASCRIPT LOGIC -->
<script>
  let currentUniversalStep = 1;
  let universalFamilyRows = [];

  function openUniversalRegisterModal() {
    currentUniversalStep = 1;
    
    // Auto-calculate next numeric employee code placeholder
    let maxCode = 0;
    if (typeof peopleMasterList !== 'undefined' && Array.isArray(peopleMasterList)) {
      peopleMasterList.forEach(p => {
        const num = parseInt(p.worker_code, 10);
        if (!isNaN(num) && num > maxCode) maxCode = num;
      });
    }
    const nextCode = maxCode > 0 ? (maxCode + 1) : 1;
    const codeInput = document.getElementById('ureg-worker-code');
    if (codeInput) {
      codeInput.value = '';
      codeInput.placeholder = `e.g. ${nextCode} (leave blank to auto-generate)`;
    }

    // Date of Joining defaults to today
    const dojInput = document.getElementById('ureg-doj');
    if (dojInput && !dojInput.value) {
      const today = new Date().toISOString().split('T')[0];
      dojInput.value = today;
    }

    // Populate Shifts from Shift Master options
    const shiftSel = document.getElementById('ureg-shift');
    if (shiftSel && typeof shiftMasterOptions !== 'undefined' && Array.isArray(shiftMasterOptions) && shiftMasterOptions.length > 0) {
      shiftSel.innerHTML = shiftMasterOptions.map(s => `
        <option value="${s.shift_name}">${s.shift_name}</option>
      `).join('');
    }

    // Populate Units dropdown
    const unitSel = document.getElementById('ureg-unit');
    if (unitSel && typeof dynamicUnitsList !== 'undefined' && Array.isArray(dynamicUnitsList) && dynamicUnitsList.length > 0) {
      const cur = unitSel.value;
      unitSel.innerHTML = dynamicUnitsList.map(u => `
        <option value="${u.id}">${u.unit_name || u.id}</option>
      `).join('');
      if (cur && unitSel.querySelector(`option[value="${cur}"]`)) unitSel.value = cur;
    }

    // Fetch Contractors
    fetch('/api/contractors')
      .then(r => r.json())
      .then(d => {
        if (d.data?.contractors) {
          const sel = document.getElementById('ureg-contractor');
          if (sel) {
            sel.innerHTML = d.data.contractors.map(c => `<option value="${c}">${c}</option>`).join('') +
              '<option value="__OTHER__">+ Enter Other Agency</option>';
          }
        }
      })
      .catch(e => console.log('Contractors fetch skipped', e));

    // Reset Family Members with 1 blank row
    universalFamilyRows = [{ name: '', rel: 'Spouse', dob: '' }];
    renderUniversalFamilyTable();

    // Set Live Timestamp for Signature
    const sigTimeInput = document.getElementById('ureg-sig-time');
    if (sigTimeInput) sigTimeInput.value = new Date().toLocaleString('en-IN', { timeZone: 'Asia/Kolkata' }) + ' IST';

    setUniversalStep(1);

    const modal = document.getElementById('modal-universal-register');
    if (modal) modal.classList.add('active');
  }

  function closeUniversalRegisterModal() {
    const modal = document.getElementById('modal-universal-register');
    if (modal) modal.classList.remove('active');
  }

  function setUniversalStep(stepIndex) {
    if (stepIndex < 1 || stepIndex > 7) return;
    
    // If moving forward, validate current step
    if (stepIndex > currentUniversalStep) {
      if (!validateStep(currentUniversalStep)) return;
    }

    currentUniversalStep = stepIndex;

    // Toggle Tab classes
    for (let i = 1; i <= 7; i++) {
      const tab = document.getElementById(`ureg-tab-${i}`);
      const panel = document.getElementById(`ureg-step-${i}`);
      if (tab) {
        tab.classList.remove('active');
        if (i < currentUniversalStep) tab.classList.add('completed');
        else tab.classList.remove('completed');
      }
      if (panel) {
        panel.classList.remove('active');
      }
    }

    const curTab = document.getElementById(`ureg-tab-${stepIndex}`);
    const curPanel = document.getElementById(`ureg-step-${stepIndex}`);
    if (curTab) curTab.classList.add('active');
    if (curPanel) curPanel.classList.add('active');

    // Controls
    const btnPrev = document.getElementById('ureg-btn-prev');
    const btnNext = document.getElementById('ureg-btn-next');
    const btnSubmit = document.getElementById('ureg-btn-submit');

    if (btnPrev) btnPrev.style.display = (stepIndex > 1) ? 'inline-block' : 'none';
    if (btnNext) btnNext.style.display = (stepIndex < 7) ? 'inline-block' : 'none';
    if (btnSubmit) btnSubmit.style.display = (stepIndex === 7) ? 'inline-block' : 'none';

    if (stepIndex === 7) {
      populateReviewStep();
    }
  }

  function nextUniversalStep() {
    setUniversalStep(currentUniversalStep + 1);
  }

  function prevUniversalStep() {
    setUniversalStep(currentUniversalStep - 1);
  }

  function validateStep(step) {
    if (step === 1) {
      const doj = document.getElementById('ureg-doj').value;
      if (!doj) { showToast('Please enter date of joining', 'warning'); return false; }
      return true;
    }
    if (step === 2) {
      const name = document.getElementById('ureg-full-name').value.trim();
      const dob = document.getElementById('ureg-dob').value;
      const father = document.getElementById('ureg-father-name').value.trim();
      const mobile = document.getElementById('ureg-mobile').value.trim();
      const address = document.getElementById('ureg-present-address').value.trim();

      if (!name) { showToast('Please enter Full Name as per Aadhaar Card', 'warning'); return false; }
      if (!dob) { showToast('Please enter Date of Birth', 'warning'); return false; }
      if (!father) { showToast('Please enter Father or Husband Name', 'warning'); return false; }
      if (!mobile || mobile.length < 10) { showToast('Please enter valid 10-digit mobile number', 'warning'); return false; }
      if (!address) { showToast('Please enter Present Residential Address', 'warning'); return false; }
      return true;
    }
    if (step === 3) {
      const aadhaar = document.getElementById('ureg-aadhaar').value.replace(/\s+/g, '');
      const bank = document.getElementById('ureg-bank-name').value.trim();
      const ifsc = document.getElementById('ureg-ifsc').value.trim();
      const acc = document.getElementById('ureg-bank-account').value.trim();
      const confirmAcc = document.getElementById('ureg-confirm-account').value.trim();

      if (!aadhaar || aadhaar.length < 12) { showToast('Please enter valid 12-digit Aadhaar Number', 'warning'); return false; }
      if (!bank) { showToast('Please enter Bank Name & Branch', 'warning'); return false; }
      if (!ifsc) { showToast('Please enter Bank IFSC Code', 'warning'); return false; }
      if (!acc) { showToast('Please enter Bank Account Number', 'warning'); return false; }
      if (acc !== confirmAcc) { showToast('Bank Account numbers do not match!', 'error'); return false; }
      return true;
    }
    if (step === 5) {
      const nominee = document.getElementById('ureg-nominee-name').value.trim();
      const dob = document.getElementById('ureg-nominee-dob').value;
      if (!nominee) { showToast('Please enter Nominee Full Name', 'warning'); return false; }
      if (!dob) { showToast('Please enter Nominee Date of Birth', 'warning'); return false; }
      return true;
    }
    if (step === 6) {
      const sig = document.getElementById('ureg-sig-name').value.trim();
      if (!sig) { showToast('Please enter typed signature name', 'warning'); return false; }
      return true;
    }
    return true;
  }

  // Real-time Mobile Uniqueness Verification
  let mobileCheckTimeout = null;
  function checkUniversalMobileAvailability() {
    clearTimeout(mobileCheckTimeout);
    const mobile = document.getElementById('ureg-mobile').value.trim();
    const badge = document.getElementById('ureg-mobile-status');
    if (!badge) return;

    if (mobile.length < 10) {
      badge.style.display = 'none';
      return;
    }

    mobileCheckTimeout = setTimeout(async () => {
      try {
        const res = await fetch(`/api/v1/enrollment/verify_mobile.php?mobile=${encodeURIComponent(mobile)}`);
        const json = await res.json();
        badge.style.display = 'inline-block';
        if (json.exists) {
          badge.innerText = 'Already Registered';
          badge.style.background = '#fee2e2';
          badge.style.color = '#b91c1c';
        } else {
          badge.innerText = 'Mobile Available';
          badge.style.background = '#dcfce7';
          badge.style.color = '#15803d';
        }
      } catch (err) {
        badge.style.display = 'none';
      }
    }, 300);
  }

  // DOB Age Calculation
  function handleDobChange(dobStr) {
    const badge = document.getElementById('ureg-age-badge');
    if (!dobStr || !badge) return;
    const dob = new Date(dobStr);
    const diffMs = Date.now() - dob.getTime();
    const ageDt = new Date(diffMs);
    const age = Math.abs(ageDt.getUTCFullYear() - 1970);
    badge.innerText = `Age: ${isNaN(age) ? '--' : age} yrs`;
  }

  // Auto-sync Signatory Name from Full Name
  function syncSignatoryName() {
    const fn = document.getElementById('ureg-full-name').value;
    const sn = document.getElementById('ureg-sig-name');
    if (sn && (!sn.value || sn.dataset.autoFilled === 'true')) {
      sn.value = fn;
      sn.dataset.autoFilled = 'true';
    }
  }

  // Address Duplicate Sync
  function handleSameAddressToggle(checked) {
    const permBox = document.getElementById('ureg-permanent-address-box');
    const permInput = document.getElementById('ureg-permanent-address');
    const presInput = document.getElementById('ureg-present-address');
    if (checked) {
      if (permInput) permInput.value = presInput.value;
      if (permBox) permBox.style.display = 'none';
    } else {
      if (permBox) permBox.style.display = 'block';
    }
  }

  function handleSameAddressAutoSync() {
    const chk = document.getElementById('ureg-same-address-chk');
    if (chk && chk.checked) {
      document.getElementById('ureg-permanent-address').value = document.getElementById('ureg-present-address').value;
    }
  }

  // Engagement Type Selection
  function handleEngagementChange() {
    const eng = document.getElementById('ureg-engagement').value;
    const box = document.getElementById('ureg-contractor-box');
    if (box) {
      box.style.display = (eng === 'Contract') ? 'block' : 'none';
    }
  }

  function handleContractorSelect(sel) {
    const other = document.getElementById('ureg-other-contractor');
    if (other) {
      other.style.display = (sel.value === '__OTHER__') ? 'inline-block' : 'none';
      if (sel.value === '__OTHER__') other.focus();
    }
  }

  // Aadhaar Input Masking (0000 0000 0000)
  function formatAadhaarInput(el) {
    let v = el.value.replace(/\D/g, '').substring(0, 12);
    let parts = [];
    for (let i = 0; i < v.length; i += 4) {
      parts.push(v.substring(i, i + 4));
    }
    el.value = parts.join(' ');
  }

  // Bank Match Validation
  function validateBankMatch() {
    const acc = document.getElementById('ureg-bank-account').value;
    const confirmAcc = document.getElementById('ureg-confirm-account').value;
    const badge = document.getElementById('ureg-bank-match-badge');
    if (!badge || !confirmAcc) {
      if (badge) badge.style.display = 'none';
      return;
    }
    badge.style.display = 'inline-block';
    if (acc === confirmAcc) {
      badge.innerText = 'Matched';
      badge.style.background = '#dcfce7';
      badge.style.color = '#15803d';
    } else {
      badge.innerText = 'Mismatch';
      badge.style.background = '#fee2e2';
      badge.style.color = '#b91c1c';
    }
  }

  // Document File Image Preview & Base64 Converter
  function handleDocFileChange(input, previewImgId, b64HiddenId) {
    if (input.files && input.files[0]) {
      const file = input.files[0];
      const reader = new FileReader();
      reader.onload = function(e) {
        const img = document.getElementById(previewImgId);
        if (img) {
          img.src = e.target.result;
          img.style.display = 'block';
        }
        const hidden = document.getElementById(b64HiddenId);
        if (hidden) hidden.value = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  }

  // Prev Work & PF Toggles
  function togglePrevWork(show) {
    const box = document.getElementById('ureg-prev-work-box');
    if (box) box.style.display = show ? 'grid' : 'none';
  }

  function togglePfBox(show) {
    const box = document.getElementById('ureg-pf-box');
    if (box) box.style.display = show ? 'grid' : 'none';
  }

  // Family Members Table Management
  function renderUniversalFamilyTable() {
    const tbody = document.getElementById('ureg-family-tbody');
    if (!tbody) return;
    if (universalFamilyRows.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:12px; color:#94a3b8; font-size:12px;">No dependent family members added.</td></tr>';
      return;
    }
    tbody.innerHTML = universalFamilyRows.map((f, idx) => `
      <tr>
        <td><input type="text" value="${f.name || ''}" placeholder="Member name" style="width:100%; padding:4px 8px; border:1px solid #cbd5e1; border-radius:4px; font-size:12px;" onchange="universalFamilyRows[${idx}].name = this.value"></td>
        <td>
          <select style="width:100%; padding:4px 8px; border:1px solid #cbd5e1; border-radius:4px; font-size:12px;" onchange="universalFamilyRows[${idx}].rel = this.value">
            <option value="Spouse" ${f.rel === 'Spouse' ? 'selected' : ''}>Spouse</option>
            <option value="Son" ${f.rel === 'Son' ? 'selected' : ''}>Son</option>
            <option value="Daughter" ${f.rel === 'Daughter' ? 'selected' : ''}>Daughter</option>
            <option value="Father" ${f.rel === 'Father' ? 'selected' : ''}>Father</option>
            <option value="Mother" ${f.rel === 'Mother' ? 'selected' : ''}>Mother</option>
            <option value="Brother" ${f.rel === 'Brother' ? 'selected' : ''}>Brother</option>
            <option value="Sister" ${f.rel === 'Sister' ? 'selected' : ''}>Sister</option>
          </select>
        </td>
        <td><input type="date" value="${f.dob || ''}" style="width:100%; padding:4px 8px; border:1px solid #cbd5e1; border-radius:4px; font-size:12px;" onchange="universalFamilyRows[${idx}].dob = this.value"></td>
        <td style="text-align:center;">
          <button type="button" class="btn btn-secondary" style="padding:2px 6px; font-size:11px; color:#b91c1c;" onclick="removeUniversalFamilyRow(${idx})">&times;</button>
        </td>
      </tr>
    `).join('');
  }

  function addUniversalFamilyRow() {
    universalFamilyRows.push({ name: '', rel: 'Son', dob: '' });
    renderUniversalFamilyTable();
  }

  function removeUniversalFamilyRow(idx) {
    universalFamilyRows.splice(idx, 1);
    renderUniversalFamilyTable();
  }

  // Step 7 Review Populate
  function populateReviewStep() {
    const wCode = (document.getElementById('ureg-worker-code').value || '').trim();
    setVal('rev-worker-code', wCode ? '#' + wCode : 'Auto-generated upon submit');
    setVal('rev-unit', document.getElementById('ureg-unit').value);
    setVal('rev-role', document.getElementById('ureg-role').value + (document.getElementById('ureg-designation').value ? ` (${document.getElementById('ureg-designation').value})` : ''));
    setVal('rev-shift', document.getElementById('ureg-shift').value);
    setVal('rev-engagement', document.getElementById('ureg-engagement').value);
    
    let contractorStr = '-';
    if (document.getElementById('ureg-engagement').value === 'Contract') {
      const cSel = document.getElementById('ureg-contractor').value;
      contractorStr = (cSel === '__OTHER__') ? document.getElementById('ureg-other-contractor').value : cSel;
    }
    setVal('rev-contractor', contractorStr);

    setVal('rev-name', document.getElementById('ureg-full-name').value);
    setVal('rev-dob', document.getElementById('ureg-dob').value);
    setVal('rev-gender', document.getElementById('ureg-gender').value + ' / ' + document.getElementById('ureg-marital').value);
    setVal('rev-mobile', document.getElementById('ureg-mobile').value);
    setVal('rev-father', document.getElementById('ureg-father-name').value);
    setVal('rev-blood', document.getElementById('ureg-blood-group').value);

    setVal('rev-aadhaar', document.getElementById('ureg-aadhaar').value);
    setVal('rev-pan', document.getElementById('ureg-pan').value || 'Not provided');
    setVal('rev-bank', document.getElementById('ureg-bank-name').value);
    setVal('rev-account', document.getElementById('ureg-bank-account').value);
    setVal('rev-ifsc', document.getElementById('ureg-ifsc').value);

    setVal('rev-uan', document.getElementById('ureg-uan').value || 'New EPFO Member');
    setVal('rev-esic', document.getElementById('ureg-esic').value || 'To be generated');
    setVal('rev-nominee', document.getElementById('ureg-nominee-name').value + ' (' + document.getElementById('ureg-nominee-rel').value + ' - ' + document.getElementById('ureg-nominee-share').value + ')');

    let docsCount = 0;
    if (document.getElementById('ureg-b64-passport').value) docsCount++;
    if (document.getElementById('ureg-b64-aadhaar-front').value) docsCount++;
    if (document.getElementById('ureg-b64-aadhaar-back').value) docsCount++;
    if (document.getElementById('ureg-b64-cheque').value) docsCount++;
    setVal('rev-docs-status', `${docsCount} of 4 Documents Attached`);
  }

  // Final Registration Submission
  async function submitUniversalRegistration() {
    const btnSubmit = document.getElementById('ureg-btn-submit');
    if (btnSubmit) {
      btnSubmit.disabled = true;
      btnSubmit.innerText = 'Registering & Syncing...';
    }

    try {
      const code = document.getElementById('ureg-worker-code').value.trim();
      const fullName = document.getElementById('ureg-full-name').value.trim();
      const unit = document.getElementById('ureg-unit').value;
      const role = document.getElementById('ureg-role').value;
      const desig = document.getElementById('ureg-designation').value.trim() || role;
      const reporting = document.getElementById('ureg-reporting').value.trim() || 'Plant Supervisor';
      const shift = document.getElementById('ureg-shift').value;
      const engagement = document.getElementById('ureg-engagement').value;

      let contractor = '';
      if (engagement === 'Contract') {
        const cVal = document.getElementById('ureg-contractor').value;
        contractor = (cVal === '__OTHER__') ? document.getElementById('ureg-other-contractor').value.trim() : cVal;
      }

      const payload = {
        employee_code: code,
        worker_code: code,
        date_of_joining: document.getElementById('ureg-doj').value,
        unit_work_at: unit,
        unit_id: unit,
        role_designation: role,
        department: role,
        designation_title: desig,
        reporting_to: reporting,
        shift_timing: shift,
        engagement_type: engagement,
        category: engagement,
        contractor_agency: contractor,

        full_name_aadhaar: fullName,
        full_name: fullName,
        date_of_birth: document.getElementById('ureg-dob').value,
        gender: document.getElementById('ureg-gender').value,
        marital_status: document.getElementById('ureg-marital').value,
        father_or_husband_name: document.getElementById('ureg-father-name').value.trim(),
        mobile_number: document.getElementById('ureg-mobile').value.trim(),
        alternate_mobile: document.getElementById('ureg-alt-mobile').value.trim(),
        email_address: document.getElementById('ureg-email').value.trim(),
        blood_group: document.getElementById('ureg-blood-group').value,
        languages_spoken: document.getElementById('ureg-languages').value.trim(),
        present_address: document.getElementById('ureg-present-address').value.trim(),
        permanent_address: document.getElementById('ureg-permanent-address').value.trim() || document.getElementById('ureg-present-address').value.trim(),
        emergency_contact_name: document.getElementById('ureg-emergency-name').value.trim(),
        emergency_relationship: document.getElementById('ureg-emergency-rel').value.trim(),
        emergency_mobile: document.getElementById('ureg-emergency-mobile').value.trim(),

        aadhaar_number: document.getElementById('ureg-aadhaar').value.trim(),
        pan_card_number: document.getElementById('ureg-pan').value.trim(),
        bank_name_branch: document.getElementById('ureg-bank-name').value.trim(),
        bank_account_number: document.getElementById('ureg-bank-account').value.trim(),
        ifsc_code: document.getElementById('ureg-ifsc').value.trim(),

        passport_photo_base64: document.getElementById('ureg-b64-passport').value || '',
        aadhaar_front_base64: document.getElementById('ureg-b64-aadhaar-front').value || '',
        aadhaar_back_base64: document.getElementById('ureg-b64-aadhaar-back').value || '',
        cheque_passbook_base64: document.getElementById('ureg-b64-cheque').value || '',

        has_worked_previously: document.querySelector('input[name="ureg_worked"]:checked')?.value === 'yes' ? 1 : 0,
        previous_employer: document.getElementById('ureg-prev-employer').value.trim(),
        last_working_date: document.getElementById('ureg-last-work-date').value,
        has_pf_account: document.querySelector('input[name="ureg_pf"]:checked')?.value === 'yes' ? 1 : 0,
        uan_number: document.getElementById('ureg-uan').value.trim(),
        eps_pension_status: document.getElementById('ureg-eps').value,
        esic_number: document.getElementById('ureg-esic').value.trim(),
        opt_out_pf_esi: document.getElementById('ureg-opt-out').checked ? 1 : 0,

        nominee_full_name: document.getElementById('ureg-nominee-name').value.trim(),
        nominee_relationship: document.getElementById('ureg-nominee-rel').value.trim(),
        nominee_dob: document.getElementById('ureg-nominee-dob').value,
        nominee_share_percentage: document.getElementById('ureg-nominee-share').value.trim() || '100%',
        family_members: universalFamilyRows.filter(f => f.name && f.name.trim() !== ''),

        signature_typed_name: document.getElementById('ureg-sig-name').value.trim() || fullName,
        signature_place_city: document.getElementById('ureg-sig-city').value.trim() || 'Hyderabad',
        signature_timestamp: new Date().toISOString(),

        approval_status: 'pending'
      };

      const res = await fetch('/api/v1/enrollment/universal_register.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Device-ID': 'WEB-HR-PORTAL'
        },
        body: JSON.stringify(payload)
      });

      const json = await res.json();
      if (json.success || json.status === 'success') {
        closeUniversalRegisterModal();
        showToast(`Employee ${fullName} (#${json.data?.worker_code || code}) registered successfully and synced!`, 'success');

        // Dynamically refresh views
        if (typeof loadOnboardingPipeline === 'function') loadOnboardingPipeline();
        if (typeof loadPeople === 'function') loadPeople();
        if (typeof loadApprovals === 'function') loadApprovals();
        if (typeof loadDashboard === 'function') loadDashboard();

      } else {
        showToast('Registration error: ' + (json.message || 'Unknown error'), 'error');
      }
    } catch (err) {
      showToast('Registration failed: ' + err, 'error');
    } finally {
      if (btnSubmit) {
        btnSubmit.disabled = false;
        btnSubmit.innerText = 'Save & Sync Employee';
      }
    }
  }
</script>
