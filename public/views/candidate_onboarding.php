<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Onboarding Portal | TrooGood HRMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0F172A;
            --primary-light: #1E293B;
            --accent: #2563EB;
            --accent-hover: #1D4ED8;
            --secondary: #0D9488;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --bg-page: #F8FAFC;
            --card-bg: #FFFFFF;
            --border: #E2E8F0;
            --border-focus: #3B82F6;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --text-light: #94A3B8;
            --radius-md: 12px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.08), 0 2px 4px -2px rgb(0 0 0 / 0.06);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.08);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background-color: var(--bg-page);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.5;
        }

        /* Top Brand Header */
        .brand-header {
            background: #FFFFFF;
            color: var(--text-main);
            padding: 1.5rem 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 0.75rem;
            border-bottom: 1.5px solid var(--border);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .brand-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            color: var(--text-main);
            text-align: center;
        }

        .brand-logo-card {
            background: #FFFFFF;
            padding: 6px 20px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .brand-logo-img {
            height: 56px;
            max-height: 64px;
            width: auto;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .brand-title {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: #0F172A;
            margin-top: 0.2rem;
        }

        .brand-subtitle {
            font-size: 0.825rem;
            color: #64748B;
            font-weight: 600;
        }

        .header-meta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            font-size: 0.8rem;
            color: #475569;
            flex-wrap: wrap;
        }

        .tag-pill {
            background: #F1F5F9;
            color: #334155;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border: 1px solid #CBD5E1;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        }

        /* Main Container */
        .portal-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            width: 100%;
            flex: 1;
        }

        /* Stepper Component */
        .stepper-nav {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 1rem 1.5rem;
            margin-bottom: 1.75rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            overflow-x: auto;
            gap: 0.5rem;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            cursor: pointer;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            transition: all 0.2s;
            white-space: nowrap;
            user-select: none;
        }

        .step-item:hover {
            background: #F1F5F9;
        }

        .step-bubble {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #E2E8F0;
            color: #64748B;
            font-weight: 700;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .step-item.active .step-bubble {
            background: var(--accent);
            color: white;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
        }

        .step-item.completed .step-bubble {
            background: var(--success);
            color: white;
        }

        .step-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .step-item.active .step-name {
            color: var(--text-main);
            font-weight: 700;
        }

        .step-item.completed .step-name {
            color: #059669;
        }

        .step-divider {
            height: 2px;
            background: #E2E8F0;
            flex: 1;
            min-width: 16px;
        }

        /* Form Card */
        .form-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 2.25rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            margin-bottom: 2rem;
            position: relative;
        }

        .card-header {
            margin-bottom: 1.75rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1.25rem;
        }

        .section-tag {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.05em;
            color: var(--accent);
            margin-bottom: 0.25rem;
        }

        .card-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }

        .card-desc {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-top: 0.35rem;
        }

        /* Form Layout */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
        }

        .col-span-2 {
            grid-column: span 2 / span 2;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .form-label {
            font-size: 0.825rem;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .form-label .req {
            color: var(--danger);
            margin-left: 0.2rem;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.65rem 0.85rem;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 0.9rem;
            color: var(--text-main);
            background: #FAFAFA;
            transition: all 0.2s;
            outline: none;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            background: #FFFFFF;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .form-input[readonly], .form-select[disabled] {
            background: #F1F5F9;
            color: #64748B;
            cursor: not-allowed;
        }

        .form-helper {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.2rem;
        }

        .form-error {
            font-size: 0.75rem;
            color: var(--danger);
            font-weight: 600;
            margin-top: 0.2rem;
            display: none;
        }

        /* Radio Buttons & Chips */
        .chip-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .chip-option {
            flex: 1;
            min-width: 120px;
            text-align: center;
            padding: 0.6rem 0.8rem;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 0.825rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            background: #FAFAFA;
        }

        .chip-option:hover {
            border-color: #CBD5E1;
        }

        .chip-option.selected {
            border-color: var(--accent);
            background: #EFF6FF;
            color: var(--accent);
        }

        /* Upload Container */
        .upload-box {
            border: 2px dashed #CBD5E1;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            background: #F8FAFC;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }

        .upload-box:hover {
            border-color: var(--accent);
            background: #F0F9FF;
        }

        .upload-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--accent);
        }

        .upload-text {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .upload-sub {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.2rem;
        }

        .preview-img {
            max-width: 100%;
            max-height: 180px;
            border-radius: 8px;
            margin-top: 0.75rem;
            box-shadow: var(--shadow-sm);
            display: none;
        }

        /* Action Buttons */
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
            font-weight: 700;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
        }

        .btn-prev {
            background: white;
            color: var(--text-main);
            border: 1.5px solid var(--border);
        }

        .btn-prev:hover {
            background: #F1F5F9;
        }

        .btn-next {
            background: var(--accent);
            color: white;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
        }

        .btn-next:hover {
            background: var(--accent-hover);
        }

        .btn-submit {
            background: linear-gradient(135deg, #0D9488 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
        }

        .btn-submit:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        /* Consent Card */
        .consent-card {
            background: #F8FAFC;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .consent-card input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: var(--accent);
            margin-top: 0.2rem;
            cursor: pointer;
        }

        .consent-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .consent-desc {
            font-size: 0.775rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
            line-height: 1.4;
        }

        /* Success Card */
        .success-panel {
            text-align: center;
            padding: 3.5rem 2rem;
            display: none;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #ECFDF5;
            color: #059669;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 1.5rem;
            box-shadow: 0 0 0 10px rgba(16, 185, 129, 0.1);
        }

        .success-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .success-subtitle {
            font-size: 0.95rem;
            color: var(--text-muted);
            max-width: 500px;
            margin: 0 auto 2rem;
        }

        .summary-box {
            background: #F8FAFC;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            max-width: 500px;
            margin: 0 auto 2rem;
            text-align: left;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.4rem 0;
            font-size: 0.85rem;
            border-bottom: 1px solid #EDF2F7;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-row .label {
            color: var(--text-muted);
            font-weight: 600;
        }

        .summary-row .val {
            color: var(--text-main);
            font-weight: 700;
        }

        /* Loading Spinner */
        .loading-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.7);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 100;
            color: white;
            display: none;
        }

        .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-bottom: 1rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .col-span-2 {
                grid-column: span 1;
            }
            .brand-header {
                padding: 1rem;
            }
            .form-card {
                padding: 1.5rem;
            }
            .stepper-nav {
                padding: 0.75rem;
            }
            .step-name {
                display: none;
            }
        }
    </style>
</head>
<body>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <div style="font-weight: 700; font-size: 1.1rem;" id="loadingText">Verifying Invitation Token...</div>
    </div>

    <!-- Header -->
    <header class="brand-header">
        <a href="#" class="brand-logo">
            <div class="brand-logo-card">
                <img src="/images/TrooGood_Logo.png" alt="TrooGood Logo" class="brand-logo-img">
            </div>
            <div>
                <div class="brand-title">TrooGood HRMS</div>
                <div class="brand-subtitle">Official Candidate Joining & Statutory Onboarding Portal</div>
            </div>
        </a>
        <div class="header-meta">
            <div class="tag-pill">🔒 Secure Statutory Onboarding</div>
            <div class="tag-pill" id="candidateBadge" style="display: none; background: #ECFDF5; color: #059669; border-color: #A7F3D0;">👤 Candidate Verified</div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="portal-container">

        <!-- Stepper Navigation -->
        <nav class="stepper-nav" id="stepperNav">
            <div class="step-item active" onclick="goToStep(1)">
                <div class="step-bubble">1</div>
                <div class="step-name">Job & Position</div>
            </div>
            <div class="step-divider"></div>
            <div class="step-item" onclick="goToStep(2)">
                <div class="step-bubble">2</div>
                <div class="step-name">Personal Info</div>
            </div>
            <div class="step-divider"></div>
            <div class="step-item" onclick="goToStep(3)">
                <div class="step-bubble">3</div>
                <div class="step-name">Bank & Aadhaar</div>
            </div>
            <div class="step-divider"></div>
            <div class="step-item" onclick="goToStep(4)">
                <div class="step-bubble">4</div>
                <div class="step-name">PF & ESIC</div>
            </div>
            <div class="step-divider"></div>
            <div class="step-item" onclick="goToStep(5)">
                <div class="step-bubble">5</div>
                <div class="step-name">Nominee</div>
            </div>
            <div class="step-divider"></div>
            <div class="step-item" onclick="goToStep(6)">
                <div class="step-bubble">6</div>
                <div class="step-name">Declarations</div>
            </div>
        </nav>

        <!-- Form Card -->
        <div class="form-card" id="formCard">

            <!-- SECTION 1: JOB & POSITION -->
            <div class="form-step" id="step1">
                <div class="card-header">
                    <div class="section-tag">Step 1 of 6</div>
                    <h2 class="card-title">Your Job & Position</h2>
                    <p class="card-desc">Confirm your joining details and employment assignment at TrooGood.</p>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="req">*</span></label>
                        <input type="text" class="form-input" id="cand_name" required placeholder="Enter your full name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address <span class="req">*</span></label>
                        <input type="email" class="form-input" id="cand_email" required placeholder="candidate@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Joining <span class="req">*</span></label>
                        <input type="date" class="form-input" id="date_of_joining" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Factory Unit / Plant <span class="req">*</span></label>
                        <select class="form-select" id="unit_id">
                            <option value="">Loading factory units...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department <span class="req">*</span></label>
                        <select class="form-select" id="department" onchange="filterDesignations()">
                            <option value="">Select Department</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Your Role / Designation <span class="req">*</span></label>
                        <select class="form-select" id="role_designation">
                            <option value="">Select Department First</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reporting Manager</label>
                        <input type="text" class="form-input" id="reporting_to" placeholder="e.g. Chandra (Plant Lead)">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assigned Shift <span class="req">*</span></label>
                        <select class="form-select" id="shift">
                            <option value="General Shift (09:00 - 18:00)">General Shift (09:00 - 18:00)</option>
                            <option value="Morning Shift (06:00 - 14:00)">Morning Shift (06:00 - 14:00)</option>
                            <option value="Evening Shift (14:00 - 22:00)">Evening Shift (14:00 - 22:00)</option>
                            <option value="Night Shift (22:00 - 06:00)">Night Shift (22:00 - 06:00)</option>
                        </select>
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">Employment Type / Engagement <span class="req">*</span></label>
                        <div class="chip-group">
                            <div class="chip-option selected" onclick="selectEngagement('On TrooGood Rolls', this)">On TrooGood Rolls</div>
                            <div class="chip-option" onclick="selectEngagement('Contract', this)">Contract (Agency)</div>
                            <div class="chip-option" onclick="selectEngagement('Consultant', this)">Consultant</div>
                            <div class="chip-option" onclick="selectEngagement('Intern', this)">Intern</div>
                        </div>
                    </div>
                    <div class="form-group col-span-2" id="contractorField" style="display: none;">
                        <label class="form-label">Contractor Agency (from master) <span class="req">*</span></label>
                        <select class="form-select" id="contractor_agency">
                            <option value="">Select Contractor / Engagement Agency</option>
                        </select>
                        <div class="form-helper">Options include verified master contractor agencies, Consultant, and Intern assignments.</div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: PERSONAL & CONTACT -->
            <div class="form-step" id="step2" style="display: none;">
                <div class="card-header">
                    <div class="section-tag">Step 2 of 6</div>
                    <h2 class="card-title">Personal & Contact Details</h2>
                    <p class="card-desc">Provide your government ID verified personal information and photo.</p>
                </div>
                <div class="form-grid">
                    <div class="form-group col-span-2">
                        <label class="form-label">Passport Size ID Photograph <span class="req">*</span></label>
                        <div class="upload-box" onclick="document.getElementById('photo_file').click()">
                            <div class="upload-icon">📷</div>
                            <div class="upload-text">Click to Upload Passport Photo</div>
                            <div class="upload-sub">Clear front face, white/light background (JPG/PNG max 5MB)</div>
                            <input type="file" id="photo_file" accept="image/*" style="display: none;" onchange="previewUpload(this, 'photo_preview')">
                            <img id="photo_preview" class="preview-img" style="margin: 0 auto; width: 100px; height: 120px; object-fit: cover;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Full Name (As per Aadhaar Card) <span class="req">*</span></label>
                        <input type="text" class="form-input" id="full_name_aadhaar" required placeholder="Full official name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth <span class="req">*</span> (18+ Years Old)</label>
                        <input type="date" class="form-input" id="date_of_birth" required onchange="calculateAge()">
                        <div class="form-helper" id="ageIndicator">Must be at least 18 years of age.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender <span class="req">*</span></label>
                        <select class="form-select" id="gender">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Father's or Husband's Name <span class="req">*</span></label>
                        <input type="text" class="form-input" id="father_name" required placeholder="Enter father's/husband's name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">10-Digit Mobile Number <span class="req">*</span></label>
                        <input type="tel" class="form-input" id="mobile_number" maxlength="10" required placeholder="9876543210" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alternate Mobile Number</label>
                        <input type="tel" class="form-input" id="alt_mobile_number" maxlength="10" placeholder="Optional" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Blood Group</label>
                        <select class="form-select" id="blood_group">
                            <option value="O+">O+</option>
                            <option value="A+">A+</option>
                            <option value="B+">B+</option>
                            <option value="AB+">AB+</option>
                            <option value="O-">O-</option>
                            <option value="A-">A-</option>
                            <option value="B-">B-</option>
                            <option value="AB-">AB-</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Languages Spoken</label>
                        <input type="text" class="form-input" id="languages" placeholder="e.g. Telugu, Hindi, English">
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">Present Residential Address <span class="req">*</span></label>
                        <textarea class="form-textarea" id="present_address" rows="2" placeholder="Door no, Street, Landmark, City, State, PIN code"></textarea>
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">Permanent Address <span class="req">*</span></label>
                        <textarea class="form-textarea" id="permanent_address" rows="2" placeholder="Permanent address as per Aadhaar"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Emergency Contact Name <span class="req">*</span></label>
                        <input type="text" class="form-input" id="emergency_name" placeholder="Contact person name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Emergency Relationship <span class="req">*</span></label>
                        <select class="form-select" id="emergency_rel">
                            <option value="Father">Father</option>
                            <option value="Mother">Mother</option>
                            <option value="Spouse">Spouse</option>
                            <option value="Brother">Brother</option>
                            <option value="Sister">Sister</option>
                            <option value="Friend / Relative">Friend / Relative</option>
                        </select>
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">Emergency Contact Mobile <span class="req">*</span></label>
                        <input type="tel" class="form-input" id="emergency_mobile" maxlength="10" placeholder="10-digit mobile number" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                </div>
            </div>

            <!-- SECTION 3: BANK & AADHAAR -->
            <div class="form-step" id="step3" style="display: none;">
                <div class="card-header">
                    <div class="section-tag">Step 3 of 6</div>
                    <h2 class="card-title">Bank & Aadhaar KYC</h2>
                    <p class="card-desc">Enter bank account details for salary disbursement and statutory identity records.</p>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">12-Digit Aadhaar Number <span class="req">*</span></label>
                        <input type="text" class="form-input" id="aadhaar_number" maxlength="12" placeholder="123456789012" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                    <div class="form-group">
                        <label class="form-label">PAN Card Number</label>
                        <input type="text" class="form-input" id="pan_number" maxlength="10" placeholder="ABCDE1234F" style="text-transform: uppercase;">
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">Bank Name & Branch <span class="req">*</span></label>
                        <select class="form-select" id="bank_name">
                            <option value="">Select Bank</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bank Account Number <span class="req">*</span></label>
                        <input type="text" class="form-input" id="bank_account" placeholder="Account Number" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Bank Account Number <span class="req">*</span></label>
                        <input type="text" class="form-input" id="confirm_account" placeholder="Re-enter Account Number" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">IFSC Code <span class="req">*</span></label>
                        <input type="text" class="form-input" id="ifsc_code" maxlength="11" placeholder="e.g. SBIN0001234" style="text-transform: uppercase;">
                    </div>
                </div>
            </div>

            <!-- SECTION 4: PF & ESIC -->
            <div class="form-step" id="step4" style="display: none;">
                <div class="card-header">
                    <div class="section-tag">Step 4 of 6</div>
                    <h2 class="card-title">Provident Fund & Social Security (Form 11)</h2>
                    <p class="card-desc">Statutory Employee Provident Fund (EPFO) and ESIC declaration.</p>
                </div>
                <div class="form-grid">
                    <div class="form-group col-span-2">
                        <label class="form-label">Have you worked previously in any organization?</label>
                        <div class="chip-group">
                            <div class="chip-option" onclick="togglePrevWork(true, this)">Yes, Previously Employed</div>
                            <div class="chip-option selected" onclick="togglePrevWork(false, this)">No, First Employment (Fresher)</div>
                        </div>
                    </div>
                    <div class="form-group col-span-2" id="prevWorkDetails" style="display: none;">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Previous Employer Name</label>
                                <input type="text" class="form-input" id="prev_employer" placeholder="Company Name">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Last Working Date</label>
                                <input type="date" class="form-input" id="last_work_date">
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">Do you already have a Provident Fund (PF) Account / UAN?</label>
                        <div class="chip-group">
                            <div class="chip-option" onclick="togglePf(true, this)">Yes, I have an existing UAN</div>
                            <div class="chip-option selected" onclick="togglePf(false, this)">No, Create New UAN</div>
                        </div>
                    </div>
                    <div class="form-group col-span-2" id="uanField" style="display: none;">
                        <label class="form-label">Universal Account Number (12-Digit UAN)</label>
                        <input type="text" class="form-input" id="uan_number" maxlength="12" placeholder="100123456789" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">ESIC Insurance IP Number (If existing)</label>
                        <input type="text" class="form-input" id="esic_number" maxlength="17" placeholder="17-Digit ESIC number (Optional)">
                    </div>
                </div>
            </div>

            <!-- SECTION 5: NOMINEE DETAILS -->
            <div class="form-step" id="step5" style="display: none;">
                <div class="card-header">
                    <div class="section-tag">Step 5 of 6</div>
                    <h2 class="card-title">Nominee & Family Details</h2>
                    <p class="card-desc">Designate a primary beneficiary nominee for statutory benefits.</p>
                </div>
                <div class="form-grid">
                    <div class="form-group col-span-2">
                        <label class="form-label">Nominee Full Name <span class="req">*</span></label>
                        <input type="text" class="form-input" id="nominee_name" placeholder="Full name of beneficiary">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Relationship with Candidate <span class="req">*</span></label>
                        <select class="form-select" id="nominee_rel">
                            <option value="Father">Father</option>
                            <option value="Mother">Mother</option>
                            <option value="Spouse">Spouse</option>
                            <option value="Son">Son</option>
                            <option value="Daughter">Daughter</option>
                            <option value="Brother">Brother</option>
                            <option value="Sister">Sister</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nominee Date of Birth <span class="req">*</span></label>
                        <input type="date" class="form-input" id="nominee_dob">
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">Share Percentage <span class="req">*</span></label>
                        <input type="number" class="form-input" id="nominee_share" value="100" readonly>
                    </div>
                </div>
            </div>

            <!-- SECTION 6: DECLARATIONS & CONSENT -->
            <div class="form-step" id="step6" style="display: none;">
                <div class="card-header">
                    <div class="section-tag">Step 6 of 6</div>
                    <h2 class="card-title">Statutory Declarations & Consent</h2>
                    <p class="card-desc">Review and accept legal undertakings under Indian labor and data protection laws.</p>
                </div>
                <div>
                    <div class="consent-card">
                        <input type="checkbox" id="truth_consent" checked>
                        <div>
                            <div class="consent-title">Declaration of Truthfulness (EPFO Form 11 Undertaking)</div>
                            <div class="consent-desc">I hereby solemnly declare that all particulars stated above are true, complete and correct to the best of my knowledge and belief. I agree that in case any information is found incorrect or false, my employment will be liable for disciplinary and legal action.</div>
                        </div>
                    </div>
                    <div class="consent-card">
                        <input type="checkbox" id="dpdp_consent" checked>
                        <div>
                            <div class="consent-title">Statutory Biometric Consent (DPDP Act, 2023)</div>
                            <div class="consent-desc">I voluntarily agree to provide facial biometrics to TrooGood HRMS solely for gate attendance verification and workplace identity validation. I understand my data is stored securely and processed in compliance with the Digital Personal Data Protection Act, 2023.</div>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label class="form-label">Type Full Name as Digital Signature <span class="req">*</span></label>
                        <input type="text" class="form-input" id="typed_signature" placeholder="Type your full official name">
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label class="form-label">Place / City <span class="req">*</span></label>
                        <input type="text" class="form-input" id="place_city" placeholder="e.g. Hyderabad / Rajkot">
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="button" class="btn btn-prev" id="btnPrev" style="display: none;" onclick="goToPrevStep()">← Previous</button>
                <div style="font-size: 0.825rem; font-weight: 600; color: var(--text-muted);" id="stepCounter">Step 1 of 6 (16%)</div>
                <button type="button" class="btn btn-next" id="btnNext" onclick="goToNextStep()">Continue →</button>
                <button type="button" class="btn btn-submit" id="btnSubmit" style="display: none;" onclick="submitApplication()">✓ Submit Application to HRMS</button>
            </div>

        </div>

        <!-- Submission Success Card -->
        <div class="form-card success-panel" id="successPanel">
            <div class="success-icon">🎉</div>
            <h2 class="success-title">Onboarding Submitted Successfully!</h2>
            <p class="success-subtitle">Thank you for submitting your statutory joining details. HR Operations has received your application for document verification and official offer release.</p>
            
            <div class="summary-box">
                <div class="summary-row">
                    <span class="label">Candidate Name:</span>
                    <span class="val" id="res_name">-</span>
                </div>
                <div class="summary-row">
                    <span class="label">Factory Unit:</span>
                    <span class="val" id="res_unit">-</span>
                </div>
                <div class="summary-row">
                    <span class="label">Department & Role:</span>
                    <span class="val" id="res_role">-</span>
                </div>
                <div class="summary-row">
                    <span class="label">Pipeline Status:</span>
                    <span class="val" style="color: #0D9488;">Details Submitted (Under HR Review)</span>
                </div>
            </div>

            <p style="font-size: 0.85rem; color: var(--text-muted);">You will receive updates on your registered email address once HR verifies your credentials.</p>
        </div>

    </main>

    <script>
        let currentStep = 1;
        let token = new URLSearchParams(window.location.search).get('token') || '';
        let candidateData = null;
        let masters = { departments: [], designations: [], contractors: [], banks: [], units: [] };
        let engagementType = 'On TrooGood Rolls';
        let hasPrevWork = false;
        let hasPf = false;

        document.addEventListener('DOMContentLoaded', async () => {
            if (!token) {
                alert('⚠️ No candidate invite token found in URL. Please use the valid onboarding link provided by HR.');
                return;
            }
            await verifyToken();
        });

        async function verifyToken() {
            showLoading(true, 'Verifying Candidate Invitation...');
            try {
                const res = await fetch(`/api/candidates/verify-token?token=${encodeURIComponent(token)}`);
                const json = await res.json();
                if (!json.success) {
                    alert('⚠️ ' + (json.message || 'Invitation invalid or expired.'));
                    return;
                }
                candidateData = json.data.candidate;
                masters = json.data.masters || {};

                // Populate Candidate Basics
                document.getElementById('cand_name').value = candidateData.candidate_name || '';
                document.getElementById('cand_email').value = candidateData.email || '';
                document.getElementById('full_name_aadhaar').value = candidateData.candidate_name || '';
                document.getElementById('typed_signature').value = candidateData.candidate_name || '';
                const mob = candidateData.mobile_number || candidateData.mobile || '';
                if (mob) document.getElementById('mobile_number').value = mob;

                // Sync Aadhaar name and Signature if candidate edits their full name
                const candNameEl = document.getElementById('cand_name');
                if (candNameEl) {
                    candNameEl.addEventListener('input', function() {
                        const val = this.value;
                        const aadhaarEl = document.getElementById('full_name_aadhaar');
                        if (aadhaarEl && (!aadhaarEl.value || aadhaarEl.value === candidateData.candidate_name)) {
                            aadhaarEl.value = val;
                        }
                        const sigEl = document.getElementById('typed_signature');
                        if (sigEl && (!sigEl.value || sigEl.value === candidateData.candidate_name)) {
                            sigEl.value = val;
                        }
                    });
                }

                // Populate Dropdowns from Master
                populateUnits();
                populateDepartments();
                populateContractors();
                populateBanks();

                // Select default Department & Role if invited with them
                if (candidateData.department) {
                    document.getElementById('department').value = candidateData.department;
                    filterDesignations();
                    const desig = candidateData.designation || candidateData.position || '';
                    if (desig) {
                        document.getElementById('role_designation').value = desig;
                    }
                }

                document.getElementById('candidateBadge').style.display = 'inline-flex';
            } catch (e) {
                alert('Network error communicating with HRMS server.');
            } finally {
                showLoading(false);
            }
        }

        function populateUnits() {
            const el = document.getElementById('unit_id');
            if (!el) return;
            el.innerHTML = '<option value="">Select Factory Unit / Plant</option>';
            if (masters.units && masters.units.length) {
                masters.units.forEach(u => {
                    const opt = document.createElement('option');
                    const val = u.id || u.unit_code;
                    opt.value = val;
                    const label = u.city ? `${u.unit_name || u.id} (${u.city})` : (u.unit_name || u.id);
                    opt.textContent = label;
                    el.appendChild(opt);
                });
            }
            if (candidateData && candidateData.unit_id) {
                el.value = candidateData.unit_id;
            }
        }

        function populateDepartments() {
            const el = document.getElementById('department');
            el.innerHTML = '<option value="">Select Department</option>';
            if (masters.departments) {
                masters.departments.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.name;
                    opt.dataset.deptId = d.id;
                    opt.textContent = d.name;
                    el.appendChild(opt);
                });
            }
        }

        function filterDesignations() {
            const deptSelect = document.getElementById('department');
            const selectedDeptName = deptSelect.value;
            const selectedOption = deptSelect.options[deptSelect.selectedIndex];
            const deptId = selectedOption ? selectedOption.dataset.deptId : null;
            const el = document.getElementById('role_designation');
            el.innerHTML = '<option value="">Select Designation / Role</option>';

            if (!selectedDeptName) return;

            if (masters.designations) {
                const filtered = deptId 
                    ? masters.designations.filter(des => des.department_id == deptId)
                    : masters.designations.filter(des => !des.department_name || des.department_name === selectedDeptName);
                
                const listToUse = filtered.length ? filtered : masters.designations;
                listToUse.forEach(des => {
                    const opt = document.createElement('option');
                    opt.value = des.name;
                    opt.textContent = des.name;
                    el.appendChild(opt);
                });
            }
        }

        function populateContractors() {
            const el = document.getElementById('contractor_agency');
            el.innerHTML = '<option value="">Select Contractor / Engagement Agency</option>' +
                '<option value="TrooGood In-House (Company Rolls)">TrooGood In-House (Company Rolls)</option>' +
                '<option value="Consultant">Consultant</option>' +
                '<option value="Intern">Intern</option>';
            if (masters.contractors) {
                masters.contractors.forEach(c => {
                    const opt = document.createElement('option');
                    const val = c.agency_name || c.name;
                    opt.value = val;
                    opt.textContent = val;
                    el.appendChild(opt);
                });
            }
        }

        function populateBanks() {
            const el = document.getElementById('bank_name');
            el.innerHTML = '<option value="">Select Bank</option>';
            if (masters.banks) {
                masters.banks.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.bank_name;
                    opt.textContent = b.bank_name;
                    el.appendChild(opt);
                });
            }
        }

        function selectEngagement(type, el) {
            engagementType = type;
            document.querySelectorAll('#step1 .chip-option').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            const showContractor = (type === 'Contract' || type === 'Consultant' || type === 'Intern');
            document.getElementById('contractorField').style.display = showContractor ? 'block' : 'none';
            const sel = document.getElementById('contractor_agency');
            if (type === 'Consultant') {
                sel.value = 'Consultant';
            } else if (type === 'Intern') {
                sel.value = 'Intern';
            } else if (type === 'On TrooGood Rolls') {
                sel.value = 'TrooGood In-House (Company Rolls)';
            }
        }

        function togglePrevWork(hasWork, el) {
            hasPrevWork = hasWork;
            document.querySelectorAll('#prevWorkDetails').forEach(e => e.style.display = hasWork ? 'block' : 'none');
            const parent = el.closest('.chip-group');
            parent.querySelectorAll('.chip-option').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
        }

        function togglePf(hasExisting, el) {
            hasPf = hasExisting;
            document.getElementById('uanField').style.display = hasExisting ? 'block' : 'none';
            const parent = el.closest('.chip-group');
            parent.querySelectorAll('.chip-option').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
        }

        function calculateAge() {
            const dobVal = document.getElementById('date_of_birth').value;
            if (!dobVal) return;
            const dob = new Date(dobVal);
            const now = new Date();
            let age = now.getFullYear() - dob.getFullYear();
            const m = now.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && now.getDate() < dob.getDate())) {
                age--;
            }
            const ind = document.getElementById('ageIndicator');
            if (age < 18) {
                ind.innerHTML = `<span style="color: var(--danger); font-weight: 700;">⚠️ Age: ${age} years. Candidate must be 18+ years old to join.</span>`;
            } else {
                ind.innerHTML = `<span style="color: var(--success); font-weight: 700;">✓ Calculated Age: ${age} years old (Eligible).</span>`;
            }
        }

        function previewUpload(input, previewId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById(previewId);
                    img.src = e.target.result;
                    img.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function goToStep(s) {
            if (s > currentStep && !validateCurrentStep()) {
                return;
            }
            currentStep = s;
            updateStepView();
        }

        function goToNextStep() {
            if (!validateCurrentStep()) return;
            if (currentStep < 6) {
                currentStep++;
                updateStepView();
            }
        }

        function goToPrevStep() {
            if (currentStep > 1) {
                currentStep--;
                updateStepView();
            }
        }

        function updateStepView() {
            for (let i = 1; i <= 6; i++) {
                const stepEl = document.getElementById(`step${i}`);
                if (stepEl) stepEl.style.display = (i === currentStep) ? 'block' : 'none';
            }

            // Update stepper navigation
            const items = document.querySelectorAll('.step-item');
            items.forEach((item, idx) => {
                const stepNum = idx + 1;
                item.classList.remove('active', 'completed');
                if (stepNum === currentStep) item.classList.add('active');
                else if (stepNum < currentStep) item.classList.add('completed');
            });

            // Action buttons
            document.getElementById('btnPrev').style.display = (currentStep > 1) ? 'inline-flex' : 'none';
            document.getElementById('btnNext').style.display = (currentStep < 6) ? 'inline-flex' : 'none';
            document.getElementById('btnSubmit').style.display = (currentStep === 6) ? 'inline-flex' : 'none';
            document.getElementById('stepCounter').textContent = `Step ${currentStep} of 6 (${Math.round((currentStep / 6) * 100)}%)`;

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function validateCurrentStep() {
            if (currentStep === 1) {
                if (!document.getElementById('cand_name').value.trim()) {
                    alert('⚠️ Please enter your Full Name.');
                    return false;
                }
                const emailVal = document.getElementById('cand_email').value.trim();
                if (!emailVal || !emailVal.includes('@')) {
                    alert('⚠️ Please enter a valid Email Address.');
                    return false;
                }
                if (!document.getElementById('date_of_joining').value) {
                    alert('⚠️ Please select Date of Joining.');
                    return false;
                }
                if (!document.getElementById('unit_id').value) {
                    alert('⚠️ Please select Factory Unit / Plant.');
                    return false;
                }
                if (!document.getElementById('department').value) {
                    alert('⚠️ Please select Department.');
                    return false;
                }
                if (!document.getElementById('role_designation').value) {
                    alert('⚠️ Please select Your Role / Designation.');
                    return false;
                }
                if ((engagementType === 'Contract' || engagementType === 'Consultant' || engagementType === 'Intern') && !document.getElementById('contractor_agency').value) {
                    alert('⚠️ Please select Contractor / Engagement Agency.');
                    return false;
                }
            } else if (currentStep === 2) {
                if (!document.getElementById('full_name_aadhaar').value.trim()) {
                    alert('⚠️ Full Name as per Aadhaar is required.');
                    return false;
                }
                const dob = document.getElementById('date_of_birth').value;
                if (!dob) {
                    alert('⚠️ Please enter Date of Birth.');
                    return false;
                }
                const age = calculateAgeRaw(dob);
                if (age < 18) {
                    alert(`⚠️ Candidate must be at least 18 years of age (Calculated age: ${age} years).`);
                    return false;
                }
                if (!document.getElementById('father_name').value.trim()) {
                    alert('⚠️ Father\'s or Husband\'s name is required.');
                    return false;
                }
                const mob = document.getElementById('mobile_number').value.trim();
                if (mob.length !== 10) {
                    alert('⚠️ Please enter a valid 10-digit mobile number.');
                    return false;
                }
                if (!document.getElementById('present_address').value.trim()) {
                    alert('⚠️ Present address is required.');
                    return false;
                }
                if (!document.getElementById('emergency_name').value.trim() || document.getElementById('emergency_mobile').value.trim().length !== 10) {
                    alert('⚠️ Emergency contact name and valid 10-digit phone number are required.');
                    return false;
                }
            } else if (currentStep === 3) {
                const aadhaar = document.getElementById('aadhaar_number').value.trim();
                if (aadhaar.length !== 12) {
                    alert('⚠️ Please enter a valid 12-digit numeric Aadhaar number.');
                    return false;
                }
                if (!document.getElementById('bank_name').value) {
                    alert('⚠️ Please select your bank.');
                    return false;
                }
                const acc = document.getElementById('bank_account').value.trim();
                const conf = document.getElementById('confirm_account').value.trim();
                if (!acc || acc !== conf) {
                    alert('⚠️ Bank account number and confirm account number do not match.');
                    return false;
                }
                if (!document.getElementById('ifsc_code').value.trim()) {
                    alert('⚠️ Please enter IFSC code.');
                    return false;
                }
            } else if (currentStep === 5) {
                if (!document.getElementById('nominee_name').value.trim()) {
                    alert('⚠️ Nominee full name is required.');
                    return false;
                }
                if (!document.getElementById('nominee_dob').value) {
                    alert('⚠️ Nominee date of birth is required.');
                    return false;
                }
            } else if (currentStep === 6) {
                if (!document.getElementById('truth_consent').checked) {
                    alert('⚠️ You must accept the EPFO Form 11 Declaration of Truthfulness.');
                    return false;
                }
                if (!document.getElementById('dpdp_consent').checked) {
                    alert('⚠️ You must accept the Statutory Biometric Consent under DPDP Act 2023.');
                    return false;
                }
                if (!document.getElementById('typed_signature').value.trim()) {
                    alert('⚠️ Please type your full name as Digital Signature.');
                    return false;
                }
            }
            return true;
        }

        function calculateAgeRaw(dobStr) {
            const dob = new Date(dobStr);
            const now = new Date();
            let age = now.getFullYear() - dob.getFullYear();
            const m = now.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && now.getDate() < dob.getDate())) {
                age--;
            }
            return age;
        }

        async function submitApplication() {
            if (!validateCurrentStep()) return;

            showLoading(true, 'Submitting Statutory Application to HRMS...');

            const candNameVal = document.getElementById('cand_name').value.trim();
            const candEmailVal = document.getElementById('cand_email').value.trim();
            const aadhaarNameVal = document.getElementById('full_name_aadhaar').value.trim() || candNameVal;

            const payload = {
                token: token,
                candidate_name: candNameVal,
                email: candEmailVal,
                date_of_joining: document.getElementById('date_of_joining').value,
                unit_id: document.getElementById('unit_id').value,
                department: document.getElementById('department').value,
                role_designation: document.getElementById('role_designation').value,
                reporting_to: document.getElementById('reporting_to').value,
                shift: document.getElementById('shift').value,
                engagement_type: engagementType,
                contractor_agency: (engagementType === 'Contract' || engagementType === 'Consultant' || engagementType === 'Intern') ? document.getElementById('contractor_agency').value : 'TrooGood In-House (Company Rolls)',
                full_name: aadhaarNameVal,
                date_of_birth: document.getElementById('date_of_birth').value,
                gender: document.getElementById('gender').value,
                father_name: document.getElementById('father_name').value.trim(),
                mobile_number: document.getElementById('mobile_number').value.trim(),
                alternate_mobile: document.getElementById('alt_mobile_number').value.trim(),
                blood_group: document.getElementById('blood_group').value,
                languages: document.getElementById('languages').value.trim(),
                present_address: document.getElementById('present_address').value.trim(),
                permanent_address: document.getElementById('permanent_address').value.trim(),
                emergency_name: document.getElementById('emergency_name').value.trim(),
                emergency_relationship: document.getElementById('emergency_rel').value,
                emergency_mobile: document.getElementById('emergency_mobile').value.trim(),
                aadhaar_number: document.getElementById('aadhaar_number').value.trim(),
                pan_card_number: document.getElementById('pan_number').value.trim().toUpperCase(),
                bank_name: document.getElementById('bank_name').value,
                bank_account_number: document.getElementById('bank_account').value.trim(),
                ifsc_code: document.getElementById('ifsc_code').value.trim().toUpperCase(),
                has_worked_previously: hasPrevWork ? 1 : 0,
                previous_employer: document.getElementById('prev_employer').value.trim(),
                last_working_date: document.getElementById('last_work_date').value,
                has_pf_account: hasPf ? 1 : 0,
                uan_number: document.getElementById('uan_number').value.trim(),
                esic_number: document.getElementById('esic_number').value.trim(),
                nominee_name: document.getElementById('nominee_name').value.trim(),
                nominee_relationship: document.getElementById('nominee_rel').value,
                nominee_dob: document.getElementById('nominee_dob').value,
                nominee_share: 100,
                truth_declaration: document.getElementById('truth_consent').checked ? 1 : 0,
                biometric_consent: document.getElementById('dpdp_consent').checked ? 1 : 0,
                digital_signature: document.getElementById('typed_signature').value.trim(),
                place_city: document.getElementById('place_city').value.trim(),
            };

            try {
                const res = await fetch('/api/candidates/submit-form', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.success) {
                    document.getElementById('formCard').style.display = 'none';
                    document.getElementById('stepperNav').style.display = 'none';
                    document.getElementById('res_name').textContent = payload.full_name;
                    document.getElementById('res_unit').textContent = payload.unit_id;
                    document.getElementById('res_role').textContent = `${payload.department} · ${payload.role_designation}`;
                    document.getElementById('successPanel').style.display = 'block';
                } else {
                    alert('Submission error: ' + (json.message || 'Could not submit.'));
                }
            } catch (e) {
                alert('Network error submitting onboarding form.');
            } finally {
                showLoading(false);
            }
        }

        function showLoading(show, text) {
            const el = document.getElementById('loadingOverlay');
            if (text) document.getElementById('loadingText').textContent = text;
            el.style.display = show ? 'flex' : 'none';
        }
    </script>
</body>
</html>
