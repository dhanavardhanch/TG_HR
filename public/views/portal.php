<?php
$portalUnits = [];
try {
    $pdoDb = getDbConnection();
    if ($pdoDb) {
        $portalUnits = $pdoDb->query("SELECT id, unit_name, unit_code, city, state FROM units WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TrooGood HRMS — Web Panel v2.1</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-base: #f4efe9;
      --bg-surface: #ffffff;
      --bg-sidebar: #171c26;
      --sidebar-active: #242c3d;
      --text-main: #19202e;
      --text-muted: #6a7487;
      --text-sidebar: #9fa9bc;
      --primary: #2952e3;
      --primary-dark: #1b3db8;
      --success: #15803d;
      --success-bg: #dcfce7;
      --warning: #b45309;
      --warning-bg: #fef3c7;
      --danger: #b91c1c;
      --danger-bg: #fee2e2;
      --info: #0284c7;
      --info-bg: #e0f2fe;
      --border: #e2dcd4;
      --border-dark: #2d3748;
      --radius-sm: 6px;
      --radius-md: 10px;
      --radius-lg: 14px;
      --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
      --shadow-md: 0 4px 12px rgba(0,0,0,0.06);
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
      background-color: var(--bg-base);
      color: var(--text-main);
      display: flex;
      height: 100vh;
      overflow: hidden;
    }

    /* SIDEBAR */
    .sidebar {
      width: 250px;
      background: var(--bg-sidebar);
      color: #fff;
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
      user-select: none;
    }
    .brand {
      padding: 22px 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .brand-logo {
      width: 36px;
      height: 36px;
      background: linear-gradient(135deg, #3b82f6, #1d4ed8);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 15px;
      color: #fff;
      letter-spacing: -0.5px;
    }
    .brand-text h1 { font-size: 15px; font-weight: 700; line-height: 1.2; }
    .brand-text span { font-size: 10px; color: #7e8b9f; letter-spacing: 0.5px; text-transform: uppercase; }

    .nav-list {
      flex: 1;
      padding: 16px 12px;
      overflow-y: auto;
      list-style: none;
    }
    .nav-item { margin-bottom: 4px; }
    .nav-link {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 14px;
      color: var(--text-sidebar);
      text-decoration: none;
      font-size: 13.5px;
      font-weight: 600;
      border-radius: var(--radius-md);
      transition: all 0.15s ease;
      cursor: pointer;
    }
    .nav-link svg {
      width: 18px !important;
      height: 18px !important;
      stroke-width: 2 !important;
      flex-shrink: 0 !important;
    }
    .nav-link:hover {
      background: rgba(255,255,255,0.06);
      color: #fff;
    }
    .nav-link.active {
      background: var(--sidebar-active);
      color: #fff;
    }
    .nav-badge {
      margin-left: auto;
      background: #ef4444;
      color: #fff;
      font-size: 10px;
      font-weight: 700;
      padding: 2px 7px;
      border-radius: 10px;
    }

    /* MAIN WRAPPER */
    .main-wrapper {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      background: var(--bg-base);
    }

    /* TOP HEADER */
    .topbar {
      height: 64px;
      background: var(--bg-surface);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 24px;
      gap: 16px;
      flex-shrink: 0;
    }
    .search-box {
      display: flex;
      align-items: center;
      gap: 10px;
      background: var(--bg-base);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 7px 16px;
      width: 320px;
    }
    .search-box input {
      border: none;
      background: transparent;
      outline: none;
      font-size: 13px;
      width: 100%;
      color: var(--text-main);
    }
    .search-box input::-webkit-search-decoration,
    .search-box input::-webkit-search-cancel-button,
    .search-box input::-webkit-search-results-button,
    .search-box input::-webkit-search-results-decoration {
      display: none;
    }
    .top-actions {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .unit-select {
      background: var(--bg-base);
      border: 1px solid var(--border);
      padding: 6px 14px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      font-weight: 600;
      color: var(--text-main);
      cursor: pointer;
      outline: none;
    }
    .user-pill {
      display: flex;
      align-items: center;
      gap: 8px;
      background: #f1f5f9;
      border: 1px solid #cbd5e1;
      border-radius: 20px;
      padding: 4px 12px 4px 6px;
      font-size: 12.5px;
      font-weight: 700;
    }
    .user-avatar {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      background: #0284c7;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
    }

    /* VIEW CONTENT */
    .view-container {
      flex: 1;
      padding: 24px;
      overflow-y: auto;
      display: none;
    }
    .view-container.active {
      display: block;
    }

    /* COMMON PAGE HEADERS & CARDS */
    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
    }
    .breadcrumb {
      font-size: 11px;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 4px;
      font-weight: 700;
    }
    .page-title {
      font-size: 24px;
      font-weight: 800;
      color: var(--text-main);
      letter-spacing: -0.5px;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 16px;
      border-radius: var(--radius-md);
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      border: none;
      transition: all 0.15s ease;
    }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-primary:hover { background: var(--primary-dark); }
    .btn-secondary { background: #fff; color: var(--text-main); border: 1px solid var(--border); }
    .btn-secondary:hover { background: #f8fafc; }
    .btn-success { background: var(--success); color: #fff; }
    .btn-danger { background: var(--danger); color: #fff; }

    /* METRICS ROW */
    .metrics-grid {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 14px;
      margin-bottom: 20px;
    }
    .metric-card {
      background: var(--bg-surface);
      border-radius: var(--radius-md);
      padding: 18px 16px;
      border: 1px solid var(--border);
      box-shadow: var(--shadow-sm);
    }
    .metric-label {
      font-size: 11.5px;
      font-weight: 600;
      color: var(--text-muted);
      margin-bottom: 6px;
    }
    .metric-value {
      font-size: 26px;
      font-weight: 800;
      color: var(--text-main);
      letter-spacing: -0.5px;
    }
    .metric-sub {
      font-size: 11px;
      color: var(--text-muted);
      margin-top: 4px;
    }

    /* GRID 2-COLUMN LAYOUT */
    .layout-grid-2 {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }
    .card {
      background: var(--bg-surface);
      border-radius: var(--radius-md);
      border: 1px solid var(--border);
      padding: 20px;
      box-shadow: var(--shadow-sm);
      margin-bottom: 20px;
    }
    .card-title {
      font-size: 15px;
      font-weight: 700;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    /* TABLES */
    .table-responsive { width: 100%; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th {
      text-align: left;
      padding: 10px 12px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--text-muted);
      border-bottom: 2px solid var(--border);
    }
    td {
      padding: 12px;
      border-bottom: 1px solid #f1ece5;
      font-weight: 500;
    }
    tr:hover td { background: #faf8f5; }

    /* BADGES */
    .badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
    }
    .badge-p { background: var(--success-bg); color: var(--success); }
    .badge-a { background: var(--danger-bg); color: var(--danger); }
    .badge-w { background: var(--warning-bg); color: var(--warning); }
    .badge-l { background: var(--info-bg); color: var(--info); }
    .badge-hd { background: #fed7aa; color: #c2410c; }
    .badge-chip { background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 12px; font-size: 10.5px; }

    /* MATRIX SPECIFICS */
    .matrix-cell {
      cursor: pointer;
      text-align: center;
      padding: 4px;
      font-family: 'JetBrains Mono', monospace;
      font-size: 11px;
      font-weight: 700;
      border-radius: 4px;
      transition: transform 0.1s ease;
    }
    .matrix-cell:hover { transform: scale(1.15); box-shadow: 0 2px 6px rgba(0,0,0,0.15); }

    /* KANBAN PIPELINE */
    .kanban-board {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 14px;
      overflow-x: auto;
      padding-bottom: 14px;
    }
    .kanban-col {
      background: #ebe6df;
      border-radius: var(--radius-md);
      padding: 12px;
      display: flex;
      flex-direction: column;
      gap: 10px;
      min-width: 170px;
    }
    .kanban-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 12px;
      font-weight: 700;
      color: var(--text-main);
    }
    .kanban-card {
      background: #fff;
      border-radius: var(--radius-sm);
      padding: 12px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.06);
      border: 1px solid #dfd8cf;
    }
    .kanban-card h4 { font-size: 13px; font-weight: 700; margin-bottom: 4px; }
    .kanban-card p { font-size: 11px; color: var(--text-muted); }

    /* APPROVALS INBOX (PREMIUM REDESIGN) */
    .approvals-layout {
      display: grid;
      grid-template-columns: 380px 1fr;
      gap: 24px;
      align-items: start;
    }
    .approval-tabs-bar {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 16px;
      overflow-x: auto;
      padding-bottom: 4px;
      scrollbar-width: none;
    }
    .approval-tabs-bar::-webkit-scrollbar { display: none; }
    .approval-tab-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 14px;
      border-radius: 20px;
      font-size: 12.5px;
      font-weight: 700;
      border: 1px solid var(--border);
      background: #fff;
      color: var(--text-main);
      cursor: pointer;
      white-space: nowrap;
      transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
      flex-shrink: 0;
      user-select: none;
    }
    .approval-tab-btn:hover {
      background: #f8fafc;
      border-color: #cbd5e1;
      transform: translateY(-1px);
    }
    .approval-tab-btn.active {
      background: var(--primary);
      color: #fff;
      border-color: var(--primary);
      box-shadow: 0 4px 12px rgba(41, 82, 227, 0.22);
    }
    .approval-tab-btn .tab-count {
      font-size: 11px;
      padding: 1px 7px;
      border-radius: 10px;
      background: #f1f5f9;
      color: #475569;
      font-weight: 800;
      transition: all 0.15s ease;
    }
    .approval-tab-btn.active .tab-count {
      background: rgba(255, 255, 255, 0.25);
      color: #fff;
    }
    .inbox-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      max-height: calc(100vh - 220px);
      overflow-y: auto;
      padding-right: 4px;
    }
    .inbox-item {
      background: #fff;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-md);
      padding: 16px;
      cursor: pointer;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
    }
    .inbox-item:hover {
      border-color: #93c5fd;
      transform: translateY(-2px);
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
    }
    .inbox-item.active {
      border-color: var(--primary);
      background: #fafcff;
      box-shadow: 0 4px 16px rgba(41, 82, 227, 0.14);
    }
    .empty-inbox-card {
      background: #fff;
      border: 1.5px dashed #cbd5e1;
      border-radius: var(--radius-lg);
      padding: 40px 24px;
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    .empty-icon-circle {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      background: #eff6ff;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 14px;
      box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
    }

    /* STEPPER */
    .stepper {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
      background: #fff;
      border-radius: var(--radius-md);
      padding: 14px 24px;
      border: 1px solid var(--border);
    }
    .step-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      font-weight: 700;
      color: var(--text-muted);
    }
    .step-item.active { color: var(--primary); }
    .step-item.completed { color: var(--success); }
    .step-badge {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: #e2e8f0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
    }
    .step-item.active .step-badge { background: var(--primary); color: #fff; }
    .step-item.completed .step-badge { background: var(--success); color: #fff; }

    /* ENTERPRISE ACL & ROLES ENGINE */
    .acl-container-grid {
      display: grid;
      grid-template-columns: 310px 1fr;
      gap: 24px;
      align-items: start;
    }
    .acl-sidebar {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 18px;
    }
    .acl-search-box {
      position: relative;
      margin-bottom: 14px;
    }
    .acl-search-box input {
      width: 100%;
      padding: 8px 12px 8px 32px;
      border-radius: var(--radius-md);
      border: 1px solid var(--border);
      font-size: 13px;
    }
    .acl-search-box svg {
      position: absolute;
      left: 10px;
      top: 10px;
      color: var(--text-muted);
    }
    .acl-group-title {
      font-size: 11px;
      font-weight: 800;
      color: #64748b;
      letter-spacing: 0.8px;
      text-transform: uppercase;
      margin: 14px 0 8px 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .role-list-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 12px;
      border-radius: var(--radius-md);
      cursor: pointer;
      border: 1.5px solid transparent;
      transition: all 0.15s ease;
      margin-bottom: 6px;
      position: relative;
    }
    .role-list-item:hover {
      background: #f8fafc;
      border-color: #e2e8f0;
      transform: translateY(-1px);
    }
    .role-list-item.active {
      background: #eff6ff;
      border-color: #3b82f6;
      box-shadow: 0 2px 8px rgba(59, 130, 246, 0.12);
    }
    .role-avatar-square {
      width: 34px;
      height: 34px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 14px;
      flex-shrink: 0;
    }
    .role-avatar-square.sys { background: #dbeafe; color: #1d4ed8; }
    .role-avatar-square.cust { background: #dcfce7; color: #15803d; }
    .role-info-meta {
      flex: 1;
      min-width: 0;
    }
    .role-info-title {
      font-size: 13.5px;
      font-weight: 700;
      color: var(--text-main);
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .role-info-code {
      font-size: 11px;
      font-family: monospace;
      color: #64748b;
      display: block;
      margin-top: 1px;
    }
    .badge-system-pill {
      font-size: 9.5px;
      font-weight: 800;
      background: #e0f2fe;
      color: #0369a1;
      padding: 1px 6px;
      border-radius: 4px;
      letter-spacing: 0.5px;
    }
    .badge-custom-pill {
      font-size: 9.5px;
      font-weight: 800;
      background: #fef3c7;
      color: #b45309;
      padding: 1px 6px;
      border-radius: 4px;
      letter-spacing: 0.5px;
    }

    /* ACL BATCH & HEADER */
    .acl-batch-toolbar {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: var(--radius-md);
      padding: 12px 18px;
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
    }
    .batch-btn-group {
      display: flex;
      gap: 8px;
      align-items: center;
      flex-wrap: wrap;
    }
    .batch-btn {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 11px;
      border-radius: 6px;
      font-size: 11.5px;
      font-weight: 700;
      background: #fff;
      border: 1px solid #cbd5e1;
      color: #334155;
      cursor: pointer;
      transition: all 0.15s ease;
      user-select: none;
    }
    .batch-btn:hover {
      background: #eff6ff;
      border-color: #93c5fd;
      color: var(--primary);
      transform: translateY(-1px);
    }
    .batch-btn.restrict {
      background: #fee2e2;
      border-color: #fca5a5;
      color: #991b1b;
    }
    .batch-btn.restrict:hover {
      background: #fecaca;
      border-color: #f87171;
    }

    /* ACL MATRIX TABLE & TOGGLES */
    .acl-matrix-card {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 24px;
    }
    .acl-module-row {
      display: grid;
      grid-template-columns: 290px repeat(6, 1fr);
      align-items: center;
      padding: 16px 12px;
      border-bottom: 1px solid #f1f5f9;
      transition: background 0.15s ease;
    }
    .acl-module-row:hover {
      background: #fafcff;
    }
    .acl-module-header {
      display: grid;
      grid-template-columns: 290px repeat(6, 1fr);
      padding: 10px 12px;
      background: #f1f5f9;
      border-radius: 8px;
      font-size: 11px;
      font-weight: 800;
      color: #475569;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
    }
    .acl-module-title {
      font-size: 13.5px;
      font-weight: 700;
      color: #0f172a;
      margin-bottom: 2px;
    }
    .acl-module-desc {
      font-size: 11.5px;
      color: #64748b;
      line-height: 1.35;
      padding-right: 14px;
    }

    /* TOGGLE SWITCHES */
    .toggle-switch {
      position: relative;
      display: inline-block;
      width: 40px;
      height: 22px;
      margin: 0 auto;
    }
    .toggle-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }
    .toggle-slider {
      position: absolute;
      cursor: pointer;
      top: 0; left: 0; right: 0; bottom: 0;
      background-color: #cbd5e1;
      transition: .2s;
      border-radius: 22px;
    }
    .toggle-slider:before {
      position: absolute;
      content: "";
      height: 16px;
      width: 16px;
      left: 3px;
      bottom: 3px;
      background-color: white;
      transition: .2s;
      border-radius: 50%;
      box-shadow: 0 1px 3px rgba(0,0,0,0.25);
    }
    .toggle-switch input:checked + .toggle-slider {
      background-color: #2563eb;
    }
    .toggle-switch input:checked + .toggle-slider:before {
      transform: translateX(18px);
    }
    .toggle-switch input:disabled + .toggle-slider {
      opacity: 0.65;
      cursor: not-allowed;
      background-color: #93c5fd;
    }

    /* JSON DIFF VIEWER */
    .diff-old { background: #fee2e2; color: #991b1b; padding: 2px 4px; border-radius: 3px; font-family: monospace; font-size: 11px; }
    .diff-new { background: #dcfce7; color: #166534; padding: 2px 4px; border-radius: 3px; font-family: monospace; font-size: 11px; }

    /* MODAL */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.5);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }
    .modal-overlay.active { display: flex; }
    .modal-card {
      background: #fff;
      border-radius: var(--radius-md);
      width: 480px;
      max-width: 90%;
      padding: 24px;
      box-shadow: var(--shadow-md);
    }
    /* TOAST NOTIFICATION SYSTEM */
    .toast-container {
      position: fixed;
      top: 24px;
      right: 24px;
      z-index: 999999;
      display: flex;
      flex-direction: column;
      gap: 12px;
      pointer-events: none;
    }
    .toast {
      pointer-events: auto;
      min-width: 300px;
      max-width: 440px;
      padding: 13px 18px;
      border-radius: var(--radius-md);
      color: #fff;
      font-size: 13.5px;
      font-weight: 600;
      box-shadow: 0 10px 30px rgba(0,0,0,0.22), 0 2px 6px rgba(0,0,0,0.08);
      display: flex;
      align-items: flex-start;
      gap: 12px;
      animation: slideInToast 0.3s cubic-bezier(0.16, 1, 0.3, 1);
      transition: all 0.25s ease;
      line-height: 1.4;
      backdrop-filter: blur(8px);
    }
    .toast.success { background: #0f766e; border-left: 5px solid #2dd4bf; }
    .toast.error { background: #991b1b; border-left: 5px solid #f87171; }
    .toast.info { background: #1e40af; border-left: 5px solid #60a5fa; }
    .toast.warning { background: #92400e; border-left: 5px solid #fbbf24; }
    .toast-icon {
      font-size: 16px;
      font-weight: 800;
      line-height: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-top: 1px;
    }
    .toast-close {
      background: none;
      border: none;
      color: rgba(255,255,255,0.7);
      cursor: pointer;
      font-size: 16px;
      line-height: 1;
      padding: 0 2px;
      margin-left: auto;
      transition: color 0.15s ease;
    }
    .toast-close:hover { color: #fff; }
    @keyframes slideInToast {
      from { transform: translateX(120%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }

    /* Gender Badges & Select Mode Styles */
    .badge-gender {
      display: inline-block;
      font-size: 11px;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 12px;
      letter-spacing: 0.2px;
    }
    .badge-gender.male { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .badge-gender.female { background: #fce7f3; color: #be185d; border: 1px solid #fbcfe8; }
    
    .col-select { display: none !important; }
    .multiselect-mode-on .col-select { display: table-cell !important; }

    .multiselect-banner {
      display: none;
      align-items: center;
      justify-content: space-between;
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      border-radius: var(--radius-md);
      padding: 10px 16px;
      margin-bottom: 14px;
      box-shadow: var(--shadow-sm);
    }
    .multiselect-banner.visible { display: flex; }

    /* BIG CARD CATEGORIZED PROFILE & IN-PLACE EDIT STYLES */
    .pf-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 24px;
      margin-bottom: 22px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .profile-edit-mode .pf-card {
      border-color: #cbd5e1;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    .pf-header {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 15px;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 1px solid #f1f5f9;
    }
    .pf-header svg {
      stroke: #2563eb;
      width: 18px;
      height: 18px;
      flex-shrink: 0;
    }
    .pf-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 16px 24px;
    }
    @media (max-width: 768px) {
      .pf-grid { grid-template-columns: 1fr; }
    }
    .pf-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .pf-group.full-width {
      grid-column: 1 / -1;
    }
    .pf-label {
      font-size: 12px;
      font-weight: 600;
      color: #475569;
      display: flex;
      align-items: center;
      gap: 4px;
    }
    .pf-req {
      color: #ef4444;
      font-weight: 700;
    }
    .pf-input {
      width: 100%;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      padding: 9px 12px;
      font-size: 13px;
      color: #0f172a;
      background: #f8fafc;
      font-family: inherit;
      transition: all 0.2s ease;
      opacity: 1;
      -webkit-text-fill-color: #0f172a;
    }
    .pf-input:disabled {
      cursor: default;
      background: #f8fafc;
      border-color: #e2e8f0;
    }
    .profile-edit-mode .pf-input:not([readonly]) {
      background: #ffffff !important;
      border-color: #94a3b8 !important;
      cursor: text;
    }
    .profile-edit-mode .pf-input:not([readonly]):focus {
      outline: none;
      border-color: #2563eb !important;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }
    .pf-textarea {
      min-height: 70px;
      resize: vertical;
    }
    .pf-sticky-bar {
      display: none;
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: #ffffff;
      border-top: 2px solid #2563eb;
      box-shadow: 0 -4px 20px rgba(0,0,0,0.12);
      padding: 12px 32px;
      justify-content: space-between;
      align-items: center;
      z-index: 9999;
    }
    .profile-edit-mode .pf-sticky-bar {
      display: flex;
    }
  </style>
</head>
<body>
  <div class="toast-container" id="toast-container"></div>

  <!-- LEFT SIDEBAR -->
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-logo">TG</div>
      <div class="brand-text">
        <h1>TrooGood HRMS</h1>
        <span>v2.1 Web Panel</span>
      </div>
    </div>
    <ul class="nav-list">
      <li class="nav-item">
        <a class="nav-link active" onclick="switchView('dashboard')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
          Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" onclick="switchView('people')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          People Master
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" onclick="switchView('attendance')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
          Attendance
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" onclick="switchView('leave-calculator')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="4" y="2" width="16" height="20" rx="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16" y2="18"></line><path d="M16 10h.01"></path><path d="M12 10h.01"></path><path d="M8 10h.01"></path><path d="M12 14h.01"></path><path d="M8 14h.01"></path><path d="M12 18h.01"></path><path d="M8 18h.01"></path></svg>
          Leave Calculator
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" onclick="switchView('holidays')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path></svg>
          Holiday Calendar
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" onclick="switchView('onboarding')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
          Onboarding
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" onclick="switchView('approvals')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
          Approvals <span class="nav-badge" id="side-approval-badge" style="display:none;">0</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" onclick="switchView('payroll')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
          Payroll
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" onclick="switchView('roles')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          Roles & Access
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" onclick="switchView('units-sync')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
          ERP Unit Sync
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" onclick="switchView('audit-log')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
          Immutable Audit Log
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" onclick="switchView('error-logs')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
          System Error Logs
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" onclick="switchView('portal-users')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          Kiosk Management
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" onclick="switchView('notifications')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
          Notifications <span class="nav-badge" id="side-notif-badge" style="display:none; background:#2563eb;">0</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" onclick="switchView('shifts')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          Shift Master
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" onclick="switchView('masters')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
          Masters Management
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" onclick="openSmtpSettingsModal()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
          Outbound Mail (SMTP)
        </a>
      </li>
    </ul>
  </aside>

  <!-- MAIN WRAPPER -->
  <div class="main-wrapper">
    <!-- TOP HEADER -->
    <header class="topbar">
      <div class="search-box">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <input type="search" id="topbar-search-input" name="portal_global_search_q" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-form-type="other" placeholder="Search employees, codes, units..." oninput="handleGlobalSearch(this.value)">
      </div>
      <div class="top-actions">
        <!-- Outbound SMTP Settings Trigger -->
        <button class="btn btn-secondary" style="font-size: 12px; padding: 6px 14px; font-weight: 700; color: #334155; background: #fff; cursor: pointer; display: flex; align-items: center; gap: 6px;" onclick="openSmtpSettingsModal()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
          SMTP Mail Server
        </button>

        <!-- Logged-in User Profile & Direct Logout Button -->
        <div style="display: flex; align-items: center; gap: 10px;">
          <div style="display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--border); padding: 5px 14px; border-radius: 20px;">
            <div id="topbar-user-avatar" style="width: 24px; height: 24px; border-radius: 50%; background: #2563eb; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 11px;">SA</div>
            <div style="display: flex; flex-direction: column; text-align: left;">
              <span id="topbar-user-name" style="font-size: 12.5px; font-weight: 700; color: var(--text-main); line-height: 1.1;">System Super Admin</span>
              <span id="topbar-user-role" style="font-size: 10px; color: #2563eb; font-weight: 600;">Super Admin</span>
            </div>
          </div>
          <button class="btn btn-secondary" style="font-size: 12px; padding: 6px 14px; font-weight: 700; color: #b91c1c; border-color: #fca5a5; background: #fff; cursor: pointer;" onclick="logoutPortal()">
            Logout
          </button>
        </div>
      </div>
    </header>

    <!-- W01: DASHBOARD VIEW -->
    <main class="view-container active" id="view-dashboard">
      <div class="page-header">
        <div>
          <div class="breadcrumb" id="dashboard-breadcrumb">Home / Dashboard &middot; Live Overview</div>
          <h2 class="page-title">Dashboard</h2>
        </div>
        <div style="display:flex; gap:10px;">
          <button class="btn btn-secondary" onclick="openUniversalRegisterModal('onboarding')">+ Invite Candidate</button>
          <button class="btn btn-primary" onclick="switchView('payroll')">Run Payroll</button>
        </div>
      </div>

      <!-- Metrics Row -->
      <div class="metrics-grid">
        <div class="metric-card"><div class="metric-label">Headcount</div><div class="metric-value" id="m-headcount">0</div><div class="metric-sub">Active workforce</div></div>
        <div class="metric-card"><div class="metric-label">Present today</div><div class="metric-value" style="color:var(--success)" id="m-present">0</div><div class="metric-sub" id="m-present-pct">0% of roster</div></div>
        <div class="metric-card"><div class="metric-label">Absent</div><div class="metric-value" style="color:var(--danger)" id="m-absent">0</div><div class="metric-sub">Unplanned</div></div>
        <div class="metric-card"><div class="metric-label">On leave</div><div class="metric-value" id="m-leave">0</div><div class="metric-sub">Approved leaves</div></div>
        <div class="metric-card"><div class="metric-label">OT this month</div><div class="metric-value" id="m-ot">0 h</div><div class="metric-sub">Production line</div></div>
        <div class="metric-card"><div class="metric-label">Payroll cost (Est)</div><div class="metric-value" id="m-cost">₹ 0</div><div class="metric-sub">Active staff</div></div>
      </div>

      <!-- 2-Column Grid -->
      <div class="layout-grid-2">
        <div class="card">
          <div class="card-title">
            <span>Unit-wise attendance today</span>
            <button class="btn btn-secondary" style="padding:4px 10px; font-size:12px;" onclick="switchView('attendance')">Export</button>
          </div>
          <div class="table-responsive">
            <table>
              <thead>
                <tr>
                  <th>Unit</th>
                  <th>Headcount</th>
                  <th>Present</th>
                  <th>Absent</th>
                  <th>Leave</th>
                  <th>Present %</th>
                </tr>
              </thead>
              <tbody id="unit-table-body"></tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <div class="card-title">Pending approvals</div>
          <div style="display:flex; flex-direction:column; gap:12px;" id="pending-approvals-box"></div>
          <button class="btn btn-primary" style="width:100%; margin-top:16px;" onclick="switchView('approvals')">Open approvals inbox</button>
        </div>
      </div>

      <!-- Bottom 3-Column Grid -->
      <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:20px;">
        <div class="card">
          <div class="card-title">Live punches (Gate Kiosk)</div>
          <div id="live-punches-list" style="display:flex; flex-direction:column; gap:10px;"></div>
        </div>
        <div class="card">
          <div class="card-title">Onboarding pipeline</div>
          <div style="display:flex; justify-content:space-between; text-align:center; padding:10px 0;" id="pipeline-counters"></div>
        </div>
        <div class="card">
          <div class="card-title" id="payroll-card-title">Payroll &middot; October 2026</div>
          <div style="display:flex; flex-direction:column; gap:10px; font-size:13px;">
            <div style="display:flex; justify-content:space-between;"><span>Attendance lock</span><strong style="color:var(--primary)" id="dash-lock-status">Active Month</strong></div>
            <div style="display:flex; justify-content:space-between;"><span>Payroll run</span><span id="dash-payroll-run-status">Pending Review</span></div>
            <div style="display:flex; justify-content:space-between;"><span>Payslips</span><span>Scheduled</span></div>
          </div>
        </div>
      </div>
    </main>

    <!-- W02: ATTENDANCE REGISTER MATRIX -->
    <main class="view-container" id="view-attendance">
      <div class="page-header">
        <div>
          <div class="breadcrumb">Attendance / Monthly Register</div>
          <h2 class="page-title">Attendance register</h2>
        </div>
        <div style="display:flex; gap:10px;">
          <button class="btn btn-secondary" onclick="exportAttendanceCsv()">Export CSV</button>
          <button class="btn btn-secondary" onclick="openAttendanceUploadModal()">Upload CSV</button>
          <button class="btn btn-primary" id="btn-lock-attendance" onclick="lockAttendanceMonth()">Lock Attendance</button>
        </div>
      </div>

      <div class="card" style="margin-bottom:16px;">
        <div style="display:flex; gap:14px; align-items:center; flex-wrap:wrap;">
          <select class="unit-select" id="attendance-month-select" onchange="changeAttendanceMonth(this.value)">
            <option value="2026-10" selected>October 2026</option>
            <option value="2026-09">September 2026</option>
            <option value="2026-08">August 2026</option>
            <option value="2026-07">July 2026</option>
          </select>
          <select class="unit-select" id="attendance-shift-select" onchange="loadAttendanceMatrix()">
            <option value="all">All shifts</option>
            <option value="SHIFT_A">Shift A</option>
            <option value="GENERAL">General Shift</option>
            <option value="SHIFT_B">Shift B</option>
          </select>
          <select class="unit-select" id="attendance-unit-select" onchange="loadAttendanceMatrix()">
            <option value="all">All Units</option>
            <?php foreach ($portalUnits as $u): ?>
              <option value="<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars($u['id']) ?></option>
            <?php endforeach; ?>
          </select>
          <select class="unit-select" id="attendance-dept-select" onchange="loadAttendanceMatrix()">
            <option value="all">All departments</option>
            <option value="Production">Production</option>
            <option value="Packaging">Packaging</option>
            <option value="Quality">Quality</option>
            <option value="Maintenance">Maintenance</option>
            <option value="Warehouse">Warehouse</option>
          </select>
          <div style="margin-left:auto; display:flex; gap:10px; font-size:12px; font-weight:700; flex-wrap:wrap;">
            <span class="badge badge-p">P Present</span>
            <span class="badge badge-a">A Absent</span>
            <span class="badge badge-w">W Weekly off</span>
            <span class="badge badge-l">L Leave</span>
            <span class="badge badge-hd">HD Half day</span>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="table-responsive">
          <table id="matrix-table">
            <thead>
              <tr id="matrix-header-row"></tr>
            </thead>
            <tbody id="matrix-body"></tbody>
          </table>
        </div>
        <div style="margin-top:14px; font-size:11.5px; color:var(--text-muted); display:flex; justify-content:space-between; align-items:center;">
          <span>* Real-time sync: mobile kiosk punches automatically populate this register in Indian Standard Time (IST). Click any cell to inspect punch timestamps or make supervisor corrections.</span>
          <span id="attendance-sync-indicator" style="font-weight:700; color:var(--success);">Live Sync Active</span>
        </div>
      </div>
    </main>

    <!-- W03: ONBOARDING PIPELINE -->
    <main class="view-container" id="view-onboarding">
      <div class="page-header">
        <div>
          <div class="breadcrumb">Onboarding / Candidates</div>
          <h2 class="page-title">Onboarding pipeline</h2>
        </div>
        <button class="btn btn-primary" onclick="openUniversalRegisterModal('onboarding')">+ Invite Candidate</button>
      </div>

      <div class="kanban-board" id="kanban-board-container"></div>
    </main>

    <!-- W05: APPROVALS INBOX (REDESIGNED UI) -->
    <main class="view-container" id="view-approvals">
      <div class="page-header">
        <div>
          <div class="breadcrumb">Approvals / Inbox</div>
          <h2 class="page-title">Approvals inbox</h2>
        </div>
        <div style="display:flex; gap:10px;">
          <button class="btn btn-secondary" onclick="loadApprovals()">Refresh</button>
          <button class="btn btn-primary" onclick="bulkApproveCurrentType()">Bulk Approve Pending</button>
        </div>
      </div>

      <div class="approval-tabs-bar" id="approval-filter-tabs">
        <button class="approval-tab-btn active" onclick="filterApprovals('all', this)">
          All Requests <span class="tab-count" id="cnt-all">0</span>
        </button>
        <button class="approval-tab-btn" onclick="filterApprovals('kiosk_worker', this)">
          Kiosk Registrations <span class="tab-count" id="cnt-kiosk">0</span>
        </button>
        <button class="approval-tab-btn" onclick="filterApprovals('leave', this)">
          Leave Requests <span class="tab-count" id="cnt-leave">0</span>
        </button>
        <button class="approval-tab-btn" onclick="filterApprovals('advance', this)">
          Salary Advances <span class="tab-count" id="cnt-advance">0</span>
        </button>
        <button class="approval-tab-btn" onclick="filterApprovals('correction', this)">
          Corrections <span class="tab-count" id="cnt-corr">0</span>
        </button>
        <button class="approval-tab-btn" onclick="filterApprovals('exit_noc', this)">
          Exit NOC <span class="tab-count" id="cnt-exit">0</span>
        </button>
      </div>

      <div class="approvals-layout">
        <div>
          <div class="inbox-list" id="inbox-items-container"></div>
        </div>
        <div class="card" id="approval-detail-card" style="min-height:420px;"></div>
      </div>
    </main>

    <!-- W06: PAYROLL RUN -->
    <main class="view-container" id="view-payroll">
      <div class="page-header">
        <div>
          <div class="breadcrumb" id="payroll-breadcrumb">Payroll / Dynamic Run Overview</div>
          <h2 class="page-title" id="payroll-title">Payroll run &middot; October 2026</h2>
        </div>
        <div style="display:flex; gap:10px;">
          <select class="unit-select" id="payroll-month-select" onchange="loadPayroll(this.value)">
            <option value="2026-10" selected>October 2026</option>
            <option value="2026-09">September 2026</option>
            <option value="2026-08">August 2026</option>
          </select>
          <button class="btn btn-secondary" onclick="showToast('Payroll summary report downloaded', 'info')">Download preview</button>
          <button class="btn btn-primary" onclick="showToast('Payroll submitted for management approval!', 'success')">Send for approval</button>
        </div>
      </div>

      <div class="stepper" id="payroll-stepper">
        <div class="step-item completed"><div class="step-badge">1</div> Select units</div>
        <div class="step-item active"><div class="step-badge">2</div> Attendance locked</div>
        <div class="step-item"><div class="step-badge">3</div> Preview & variances</div>
        <div class="step-item"><div class="step-badge">4</div> Approve</div>
        <div class="step-item"><div class="step-badge">5</div> Lock payroll</div>
        <div class="step-item"><div class="step-badge">6</div> Payslips & bank file</div>
      </div>

      <div class="metrics-grid" style="grid-template-columns: repeat(6, 1fr); margin-bottom:20px;">
        <div class="metric-card"><div class="metric-label">Employees</div><div class="metric-value" id="payroll-m-employees">0</div></div>
        <div class="metric-card"><div class="metric-label">Gross</div><div class="metric-value" id="payroll-m-gross">₹ 0</div></div>
        <div class="metric-card"><div class="metric-label">Statutory (PF/ESI/PT)</div><div class="metric-value" id="payroll-m-statutory">₹ 0</div></div>
        <div class="metric-card"><div class="metric-label">Advance recovery</div><div class="metric-value" id="payroll-m-advance">₹ 0</div></div>
        <div class="metric-card"><div class="metric-label">Net pay</div><div class="metric-value" style="color:var(--success)" id="payroll-m-net">₹ 0</div></div>
        <div class="metric-card"><div class="metric-label">Variance flags</div><div class="metric-value" style="color:var(--warning)" id="payroll-m-variance">0</div></div>
      </div>

      <div class="card">
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Employee</th>
                <th>Unit</th>
                <th>Paid Days</th>
                <th>Gross</th>
                <th>PF</th>
                <th>ESI</th>
                <th>PT</th>
                <th>TDS</th>
                <th>Advance</th>
                <th>Net Pay</th>
                <th>Check</th>
              </tr>
            </thead>
            <tbody id="payroll-items-body"></tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- W07: ACCESS CONTROL LIST (ACL) -->
    <main class="view-container" id="view-roles">
      <div class="page-header">
        <div>
          <div class="breadcrumb">Core & Access Control / Permissions Engine</div>
          <h2 class="page-title" style="display:flex; align-items:center; gap:8px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            Access Control List (ACL)
          </h2>
          <div style="font-size:12.5px; color:var(--text-muted); margin-top:2px;">
            Manage modular permissions, sidebar menus, and discover dynamic controller operation gates.
          </div>
        </div>
        <div style="display:flex; gap:10px;">
          <button class="btn btn-secondary" onclick="openCloneRoleModal()">Clone Role</button>
          <button class="btn btn-primary" onclick="openNewRoleModal()">+ Create Custom Role</button>
        </div>
      </div>

      <div class="acl-container-grid">
        <!-- Left Sidebar: Roles Directory -->
        <div class="acl-sidebar">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <strong style="font-size:13.5px; color:#0f172a; display:flex; align-items:center; gap:6px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
              Roles Directory
            </strong>
            <span class="badge badge-chip" id="acl-total-roles-count">5</span>
          </div>

          <div class="acl-search-box">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" id="acl-search-roles-input" placeholder="Search roles..." onkeyup="filterAclRoles(this.value)">
          </div>

          <!-- System Roles List -->
          <div class="acl-group-title">
            <span>SYSTEM ROLES</span>
            <span id="acl-system-count" style="font-size:10px; color:#94a3b8;">5</span>
          </div>
          <div id="acl-system-roles-list"></div>

          <!-- Custom Roles List -->
          <div class="acl-group-title" style="margin-top:18px;">
            <span>CUSTOM ROLES</span>
            <span id="acl-custom-count" style="font-size:10px; color:#94a3b8;">0</span>
          </div>
          <div id="acl-custom-roles-list"></div>
        </div>

        <!-- Right Main Panel: Role Matrix & CRUD Toggles -->
        <div class="acl-matrix-card">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; padding-bottom:14px; border-bottom:1px solid #f1f5f9;">
            <div>
              <div style="display:flex; align-items:center; gap:8px;">
                <h3 id="acl-selected-role-name" style="font-size:19px; font-weight:800; color:#0f172a;">Super Admin</h3>
                <code id="acl-selected-role-code" style="font-size:12px; background:#f1f5f9; padding:2px 8px; border-radius:4px; color:#334155;">super_admin</code>
                <span id="acl-selected-role-pill" class="badge-system-pill">SYSTEM</span>
              </div>
              <p id="acl-selected-role-desc" style="font-size:12.5px; color:#64748b; margin-top:4px;">
                System Super Administrator with absolute unconstrained bypass access across all modules, units, and actions.
              </p>
            </div>
            <div id="acl-role-actions-box" style="display:flex; gap:8px;">
              <!-- Dynamic delete button for custom roles -->
            </div>
          </div>

          <!-- Sub-tabs -->
          <div style="display:flex; gap:16px; border-bottom:2px solid #f1f5f9; margin-bottom:18px;">
            <button class="btn" style="background:none; border:none; border-bottom:2.5px solid var(--primary); color:var(--primary); font-weight:800; padding:8px 4px; border-radius:0; font-size:13px;">
              Module Permissions (CRUD Matrix)
            </button>
            <button class="btn" style="background:none; border:none; color:#64748b; font-weight:600; padding:8px 4px; border-radius:0; font-size:13px;" onclick="showToast('Discovered Controllers: 8 active controllers (WebPortal, Enrollment, Punch, Sync, Auth, People, Issue, CoreAccess)', 'info')">
              Discovered Controllers & Operations (Dynamic)
            </button>
          </div>

          <!-- Column Batch Operations Toolbar -->
          <div class="acl-batch-toolbar" id="acl-batch-toolbar">
            <div style="font-size:11.5px; font-weight:800; color:#475569; text-transform:uppercase;">
              COLUMN BATCH OPERATIONS (GRANT / RESTRICT ALL ROWS)
            </div>
            <div class="batch-btn-group">
              <button class="batch-btn" onclick="batchToggleColumn('menu_access', true)">Grant Menu</button>
              <button class="batch-btn" onclick="batchToggleColumn('can_view', true)">Grant View</button>
              <button class="batch-btn" onclick="batchToggleColumn('can_create', true)">Grant Create</button>
              <button class="batch-btn" onclick="batchToggleColumn('can_edit', true)">Grant Edit</button>
              <button class="batch-btn" onclick="batchToggleColumn('can_delete', true)">Grant Delete</button>
              <button class="batch-btn" onclick="batchToggleColumn('can_audit', true)">Grant Audit</button>
              <button class="batch-btn restrict" onclick="batchRestrictAll()">Restrict All</button>
            </div>
          </div>

          <!-- Matrix Headers -->
          <div class="acl-module-header">
            <div>MODULE AREA</div>
            <div style="text-align:center;">MENU ACCESS</div>
            <div style="text-align:center;">VIEW (INDEX)</div>
            <div style="text-align:center;">CREATE (ADD)</div>
            <div style="text-align:center;">EDIT (UPDATE)</div>
            <div style="text-align:center;">DELETE (VOID)</div>
            <div style="text-align:center;">AUDIT (CHECK)</div>
          </div>

          <!-- Matrix Rows Container -->
          <div id="acl-modules-matrix-container"></div>

          <!-- Save Matrix Footer -->
          <div style="display:flex; justify-content:space-between; align-items:center; margin-top:24px; padding-top:18px; border-top:1px solid #f1f5f9;">
            <div style="font-size:12px; color:#64748b;">
              * Super Admin bypasses all checks. Custom role permission updates take effect on user next page load.
            </div>
            <button class="btn btn-primary" id="btn-save-acl-matrix" onclick="submitSaveAclMatrix()">
              Save Permissions Matrix
            </button>
          </div>
        </div>
      </div>
    </main>

    <!-- 1. ERP UNIT SYNC VIEW (Phase 1) -->
    <main class="view-container" id="view-units-sync">
      <div class="page-header">
        <div>
          <div class="breadcrumb">Core & Access Control / Master ERP Integration</div>
          <h2 class="page-title">ERP Unit Sync</h2>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
          <span style="font-size:12px; color:var(--text-muted);" id="sync-last-timestamp">Last synced: checking...</span>
          <button class="btn btn-primary" id="btn-sync-now" onclick="triggerErpSync()">Sync Now</button>
        </div>
      </div>

      <div class="card">
        <div class="card-title">
          <span>Factory Units Roster & Sync Status</span>
          <span class="badge badge-p">Auto-sync: Enabled (/api/factories/troogood)</span>
        </div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Unit ID</th>
                <th>Unit Name</th>
                <th>City & State</th>
                <th>Sync Status</th>
                <th>Last Synced</th>
                <th>State</th>
              </tr>
            </thead>
            <tbody id="units-sync-table-body"></tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-title">unit_sync_log Viewer (Success / Fail History)</div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Sync ID</th>
                <th>Endpoint</th>
                <th>Trigger</th>
                <th>Started</th>
                <th>Completed</th>
                <th>+ Added</th>
                <th>~ Updated</th>
                <th>- Deactivated</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="sync-logs-table-body"></tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- 4. IMMUTABLE AUDIT LOG VIEW (Phase 1) -->
    <main class="view-container" id="view-audit-log">
      <div class="page-header">
        <div>
          <div class="breadcrumb">Core & Access Control / Forensic Trail</div>
          <h2 class="page-title">Immutable Audit Log</h2>
        </div>
        <button class="btn btn-secondary" onclick="loadAuditTimeline()">Refresh Timeline</button>
      </div>

      <div class="card" style="margin-bottom:16px;">
        <div style="display:flex; gap:14px; align-items:center; flex-wrap:wrap;">
          <input type="text" id="audit-filter-user" placeholder="Filter by User (e.g. admin)" style="border:1px solid var(--border); padding:6px 12px; border-radius:6px; font-size:13px;" onkeyup="loadAuditTimeline()">
          <select id="audit-filter-entity" class="unit-select" onchange="loadAuditTimeline()">
            <option value="">All Entities</option>
            <option value="users">Users & Kiosks</option>
            <option value="workers">Workers</option>
            <option value="attendance_punches">Attendance Punches</option>
            <option value="roles">Roles & ACL</option>
            <option value="units">Factory Units</option>
          </select>
          <input type="date" id="audit-filter-date" style="border:1px solid var(--border); padding:6px 12px; border-radius:6px; font-size:13px;" onchange="loadAuditTimeline()">
        </div>
      </div>

      <div class="card">
        <div class="card-title">Audit Timeline & JSON Diff Viewer (Old &rarr; New Highlighted)</div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Timestamp</th>
                <th>Actor</th>
                <th>Action</th>
                <th>Entity</th>
                <th>Details / JSON Diff (old &rarr; new)</th>
                <th>IP Address</th>
              </tr>
            </thead>
            <tbody id="audit-table-body"></tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- 5. PORTAL USERS VIEW (Phase 1) -->
    <main class="view-container" id="view-portal-users">
      <div class="page-header" style="flex-wrap:wrap; gap:12px;">
        <div>
          <div class="breadcrumb">Core & Access Control / Kiosk & User Management</div>
          <h2 class="page-title">Kiosk Management & Device Credentials</h2>
        </div>
        <div style="display:flex; gap:10px;">
          <button class="btn btn-secondary" onclick="switchView('roles')">Designations & Roles</button>
          <button class="btn btn-primary" onclick="openAddUserModal()">+ Add New User</button>
        </div>
      </div>

      <!-- Top Summary Stat Cards -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px;">
        <div class="card" style="padding: 16px 20px; display: flex; align-items: center; gap: 16px; margin-bottom: 0;">
          <div style="width: 44px; height: 44px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px;">
            ALL
          </div>
          <div>
            <div style="font-size: 11px; font-weight: 800; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">TOTAL USERS</div>
            <div id="portal-stat-total" style="font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1.1; margin-top: 2px;">0</div>
          </div>
        </div>

        <div class="card" style="padding: 16px 20px; display: flex; align-items: center; gap: 16px; margin-bottom: 0;">
          <div style="width: 44px; height: 44px; border-radius: 10px; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px;">
            ON
          </div>
          <div>
            <div style="font-size: 11px; font-weight: 800; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">ACTIVE ONLINE</div>
            <div id="portal-stat-active" style="font-size: 26px; font-weight: 800; color: #15803d; line-height: 1.1; margin-top: 2px;">0</div>
          </div>
        </div>

        <div class="card" style="padding: 16px 20px; display: flex; align-items: center; gap: 16px; margin-bottom: 0;">
          <div style="width: 44px; height: 44px; border-radius: 10px; background: #fee2e2; color: #b91c1c; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px;">
            OFF
          </div>
          <div>
            <div style="font-size: 11px; font-weight: 800; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">SUSPENDED</div>
            <div id="portal-stat-suspended" style="font-size: 26px; font-weight: 800; color: #b91c1c; line-height: 1.1; margin-top: 2px;">0</div>
          </div>
        </div>
      </div>

      <!-- Users Directory Table Card with Filters -->
      <div class="card">

        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px; padding-bottom:14px; border-bottom:1px solid #f1f5f9;">
          <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; flex:1;">
            <div style="position:relative; min-width:280px; flex:1;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" style="position:absolute; left:12px; top:10px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              <input type="text" id="user-search-input" placeholder="Search by name, email, role, designation..." style="width:100%; border:1px solid var(--border); padding:8px 12px 8px 34px; border-radius:8px; font-size:13px;" onkeyup="loadPortalUsers()">
            </div>
            <select id="user-role-filter" style="border:1px solid var(--border); padding:8px 12px; border-radius:8px; font-size:13px; font-weight:600; min-width:140px;" onchange="loadPortalUsers()">
              <option value="all">All Roles</option>
            </select>
            <select id="user-status-filter" style="border:1px solid var(--border); padding:8px 12px; border-radius:8px; font-size:13px; font-weight:600; min-width:130px;" onchange="loadPortalUsers()">
              <option value="all">All Statuses</option>
              <option value="active">Active</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
          <div style="display:flex; gap:8px; align-items:center;">
            <button class="btn btn-secondary" onclick="loadPortalUsers()" style="display:flex; align-items:center; gap:6px; font-weight:600;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
              Refresh
            </button>
            <button class="btn btn-primary" onclick="openAddUserModal()">+ Add New User</button>
          </div>
        </div>

        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>USER PROFILE</th>
                <th>ROLE & PERMISSIONS</th>
                <th>SUPERVISOR PIN</th>
                <th>UNIT SCOPE</th>
                <th>BOUND DEVICE LOCK</th>
                <th>ACCOUNT STATUS</th>
                <th>ACTIONS</th>
              </tr>
            </thead>
            <tbody id="portal-users-table-body"></tbody>
          </table>
        </div>
      </div>

      <!-- 2. Registered Kiosk Tablets & Hardware Devices Card -->
      <div class="card" style="margin-top:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:14px; padding-bottom:12px; border-bottom:1px solid #f1f5f9;">
          <div>
            <div class="card-title" style="margin-bottom:2px;">Registered Kiosk Tablets & Hardware Devices</div>
            <div style="font-size:12px; color:var(--text-muted);">Manage physical tablet hardware locks, active/disabled states, and remote de-registration. Deleting or disabling a kiosk logs it out automatically.</div>
          </div>
          <button class="btn btn-secondary" onclick="loadKioskDevices()" style="display:flex; align-items:center; gap:6px; font-weight:600; font-size:12px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            Refresh Devices
          </button>
        </div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>DEVICE ID / HARDWARE UUID</th>
                <th>ASSIGNED UNIT</th>
                <th>SUPERVISOR / OPERATOR</th>
                <th>DEVICE STATUS</th>
                <th>LAST GPS LOCATION</th>
                <th>LAST IP ADDRESS</th>
                <th>LAST HEARTBEAT</th>
                <th>ACTIONS</th>
              </tr>
            </thead>
            <tbody id="kiosk-devices-table-body"></tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- 6. SHIFT MASTER VIEW -->
    <main class="view-container" id="view-shifts">
      <div class="page-header">
        <div>
          <div class="breadcrumb">Attendance Configuration / Shift Master</div>
          <h2 class="page-title">Shift Master & Dynamic IST Rostering</h2>
        </div>
        <button class="btn btn-primary" onclick="openShiftModal()">+ Add New Shift</button>
      </div>

      <!-- Live IST Clock & Shift Allocation Banner -->
      <div class="card" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; border: 1px solid #334155; margin-bottom: 20px; padding: 20px 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
          <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
              <span style="display: inline-block; width: 10px; height: 10px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 10px #22c55e;"></span>
              <span style="font-size: 11px; font-weight: 800; letter-spacing: 1px; color: #94a3b8; text-transform: uppercase;">Live Indian Standard Time (IST)</span>
            </div>
            <div style="display: flex; align-items: baseline; gap: 14px;">
              <span id="shift-live-time" style="font-size: 28px; font-weight: 800; font-family: 'JetBrains Mono', monospace; color: #f8fafc;">--:--:-- --</span>
              <span id="shift-live-date" style="font-size: 14px; font-weight: 600; color: #cbd5e1;">-- --- ----</span>
            </div>
          </div>
          <div style="background: rgba(255, 255, 255, 0.08); padding: 12px 18px; border-radius: 10px; border: 1px solid rgba(255, 255, 255, 0.12);">
            <div style="font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Active Shift Window Now</div>
            <div id="shift-active-banner" style="font-size: 16px; font-weight: 800; color: #38bdf8; margin-top: 4px;">Loading active shift...</div>
          </div>
        </div>
        <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 8px;">
          
          <span><strong>Automatic Kiosk Allocation:</strong> When a worker punches IN at any factory kiosk, their attendance is automatically categorized into the active shift window based on their IST punch time. Workers can only punch IN once and OUT once per shift per day.</span>
        </div>
      </div>

      <!-- Shifts Table -->
      <div class="card">
        <div class="card-title">
          <span>Configured Plant Shifts</span>
          <button class="btn btn-secondary" style="padding:4px 10px; font-size:11.5px;" onclick="loadShiftsMaster()">Refresh Shifts</button>
        </div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Shift Code</th>
                <th>Shift Name</th>
                <th>Timings (IST)</th>
                <th>Working Hours</th>
                <th>Grace Period</th>
                <th>Thresholds (Half / Full)</th>
                <th>Status</th>
                <th>Current Live</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="shifts-table-body">
              <tr><td colspan="9" style="text-align:center; padding:20px;">Loading shifts...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- 7. PEOPLE MASTER VIEW -->
    <main class="view-container" id="view-people">
      <div class="page-header" style="flex-wrap:wrap; gap:12px;">
        <div>
          <div class="breadcrumb">Workforce Master / Employee Directory</div>
          <h2 class="page-title">People Master & Workforce Directory</h2>
        </div>
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
          <button class="btn btn-secondary" id="btn-toggle-multiselect" onclick="toggleMultiSelectMode()" style="display:flex; align-items:center; gap:6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
            <span id="btn-toggle-select-text">Select Multiple</span>
          </button>
          <button class="btn btn-secondary" onclick="loadPeople()" style="display:flex; align-items:center; gap:6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            Refresh Directory
          </button>
          <button class="btn btn-primary" onclick="openUniversalRegisterModal('people')" style="display:flex; align-items:center; gap:6px;">
            + Register Candidate
          </button>
        </div>
      </div>

      <!-- Multi-Select Action Banner (Appears only when selection mode is ON and items selected) -->
      <div id="people-multiselect-banner" class="multiselect-banner">
        <div style="display:flex; align-items:center; gap:10px;">
          <span style="background:#2952e3; color:#fff; font-size:12px; font-weight:700; padding:4px 12px; border-radius:14px;" id="people-selected-badge">0 Selected</span>
          <span style="font-size:12.5px; color:#334155; font-weight:600;">Batch workforce operations available</span>
        </div>
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
          <button class="btn btn-secondary" style="padding:6px 12px; font-size:12px; display:flex; align-items:center; gap:6px;" onclick="downloadSelectedPeopleZip()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Download ZIP
          </button>
          <button class="btn btn-secondary" style="padding:6px 12px; font-size:12px; display:flex; align-items:center; gap:6px;" onclick="openPeopleTransferModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 3 21 3 21 8"></polyline><line x1="4" y1="20" x2="21" y2="3"></line><polyline points="21 16 21 21 16 21"></polyline><line x1="15" y1="15" x2="21" y2="21"></line><line x1="4" y1="4" x2="9" y2="9"></line></svg>
            Transfer Unit
          </button>
          <button class="btn btn-secondary" style="padding:6px 12px; font-size:12px; display:flex; align-items:center; gap:6px;" onclick="openPeopleAssignModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
            Assign Login(s)
          </button>
          <button class="btn btn-secondary" style="padding:6px 10px; font-size:12px;" onclick="clearPeopleSelection()">Deselect All</button>
        </div>
      </div>

      <!-- Quick Metrics Summary -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:14px; margin-bottom:18px;">
        <div class="card" style="padding:14px 18px; margin-bottom:0;">
          <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Total Workforce</div>
          <div id="stat-people-total" style="font-size:24px; font-weight:800; color:var(--text-main); margin-top:4px;">--</div>
          <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">Across all units</div>
        </div>
        <div class="card" style="padding:14px 18px; margin-bottom:0;">
          <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">On Company Rolls</div>
          <div id="stat-people-rolls" style="font-size:24px; font-weight:800; color:#15803d; margin-top:4px;">--</div>
          <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">Permanent & Trainees</div>
        </div>
        <div class="card" style="padding:14px 18px; margin-bottom:0;">
          <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Contract Workforce</div>
          <div id="stat-people-contract" style="font-size:24px; font-weight:800; color:#b45309; margin-top:4px;">--</div>
          <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">Agency deployed</div>
        </div>
        <div class="card" style="padding:14px 18px; margin-bottom:0;">
          <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Login Assigned</div>
          <div id="stat-people-assigned" style="font-size:24px; font-weight:800; color:#2952e3; margin-top:4px;">--</div>
          <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">Custom sync logins</div>
        </div>
        <div class="card" style="padding:14px 18px; margin-bottom:0;">
          <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Approved Profiles</div>
          <div id="stat-people-approved" style="font-size:24px; font-weight:800; color:#0284c7; margin-top:4px;">--</div>
          <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">Kiosk active</div>
        </div>
      </div>

      <!-- Filters & People Table Card -->
      <div class="card" id="people-table-card">
        <div class="card-title" style="flex-wrap:wrap; gap:12px;">
          <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <select id="people-filter-unit" class="unit-select" onchange="loadPeople()" style="min-width:140px;">
              <option value="all">All Units (All Factories)</option>
              <?php foreach ($portalUnits as $u): ?>
                <option value="<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars($u['id']) ?></option>
              <?php endforeach; ?>
            </select>
            <select id="people-filter-type" class="unit-select" onchange="loadPeople()" style="min-width:140px;">
              <option value="all">All Employment Types</option>
              <option value="rolls">On Rolls Only</option>
              <option value="contract">Contract Worker Only</option>
            </select>
            <select id="people-filter-status" class="unit-select" onchange="loadPeople()" style="min-width:130px;">
              <option value="all">All Status</option>
              <option value="approved">Approved</option>
              <option value="pending">Pending Approval</option>
            </select>
          </div>
          <div style="display:flex; align-items:center; gap:8px;">
            <input type="text" id="people-search-input" placeholder="Search by name, ID, phone, Aadhaar, contractor..." style="border:1px solid var(--border); padding:7px 14px; border-radius:6px; font-size:12.5px; width:300px;" onkeyup="filterPeopleClient()">
          </div>
        </div>

        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th class="col-select" style="width:36px; text-align:center;"><input type="checkbox" id="people-select-all" onchange="toggleSelectAllPeople(this.checked)"></th>
                <th style="width:55px;">ID</th>
                <th style="min-width:115px;">Employee Code</th>
                <th style="min-width:190px;">Photo & Name</th>
                <th style="width:75px;">Unit</th>
                <th style="min-width:120px;">Shift</th>
                <th style="min-width:135px;">Designation</th>
                <th style="min-width:120px;">Mobile Number</th>
                <th style="width:90px;">Status</th>
                <th style="text-align:center; min-width:105px;">Action</th>
              </tr>
            </thead>
            <tbody id="people-table-body">
              <tr><td colspan="10" style="text-align:center; padding:30px; color:var(--text-muted);">Loading People Master directory...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- 7.1 SEPARATE DEDICATED EMPLOYEE PROFILE DETAIL VIEW -->
    <main class="view-container" id="view-person-detail">
      <!-- Top Action Navigation Bar -->
      <div style="margin-bottom: 20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:10px;">
          <button class="btn btn-secondary" onclick="exitProfileView()" style="display:flex; align-items:center; gap:8px; font-weight:700;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            <span id="pv-back-btn-text">Back to People Directory</span>
          </button>
          
          <!-- Normal View Actions -->
          <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;" id="pv-view-actions">
            <!-- Approval Quick Actions (Visible if worker approval is pending) -->
            <button class="btn btn-secondary" id="pv-btn-reject-top" onclick="rejectCurrentPersonFromProfile()" style="display:none; color:#b91c1c; border-color:#fca5a5; font-weight:700;">
              Reject
            </button>
            <button class="btn btn-success" id="pv-btn-approve-top" onclick="approveCurrentPersonFromProfile()" style="display:none; font-weight:700; background:#15803d; color:#fff; align-items:center; gap:6px; box-shadow:0 1px 3px rgba(21,128,61,0.25);">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
              Approve &amp; Activate
            </button>

            <button class="btn btn-primary" id="pv-btn-form11-top" onclick="downloadCurrentPersonForm11Pdf()" style="display:flex; align-items:center; gap:8px; background:#1d4ed8; color:#fff; font-weight:700;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
              EPFO Form 11 (PDF)
            </button>
            <button class="btn btn-primary" id="pv-btn-zip" onclick="downloadCurrentPersonZip()" style="display:flex; align-items:center; gap:8px;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
              Download Profile ZIP
            </button>
            <button class="btn btn-secondary" onclick="openSinglePersonTransferModal()" style="display:flex; align-items:center; gap:8px;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 3 21 3 21 8"></polyline><line x1="4" y1="20" x2="21" y2="3"></line><polyline points="21 16 21 21 16 21"></polyline><line x1="15" y1="15" x2="21" y2="21"></line><line x1="4" y1="4" x2="9" y2="9"></line></svg>
              Transfer Unit
            </button>
            <button class="btn btn-secondary" onclick="openSinglePersonAssignModal()" style="display:flex; align-items:center; gap:8px;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
              Assign Logins
            </button>
            <button class="btn btn-primary" id="pv-btn-edit" onclick="toggleProfileEditMode(true)" style="display:flex; align-items:center; gap:8px; background:var(--primary); font-weight:700;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
              Edit Profile
            </button>
          </div>

          <!-- In-Place Edit Actions (active when editing) -->
          <div style="display:none; gap:10px; align-items:center; flex-wrap:wrap;" id="pv-edit-actions">
            <span style="display:flex; align-items:center; gap:6px; font-weight:700; color:#d97706; font-size:13px; background:#fef3c7; border:1px solid #fde68a; padding:6px 14px; border-radius:6px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
              In-Place Edit Active
            </span>
            <button class="btn btn-secondary" onclick="toggleProfileEditMode(false)">Cancel</button>
            <button class="btn btn-primary" id="pv-btn-save-top" onclick="savePersonProfile()" style="background:#15803d; border-color:#15803d; font-weight:700; display:flex; align-items:center; gap:8px;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
              Save Changes
            </button>
          </div>
        </div>

        <!-- Pending Approval Callout Banner (shown when employee is pending) -->
        <div id="pv-pending-approval-banner" style="display:none; background:#fffbeb; border:1.5px solid #fde047; border-radius:12px; padding:16px 20px; margin-bottom:22px; box-shadow:0 2px 6px rgba(245,158,11,0.08);">
          <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
            <div style="display:flex; align-items:center; gap:14px;">
              <div style="width:40px; height:40px; border-radius:50%; background:#fef08a; display:flex; align-items:center; justify-content:center; color:#854d0e; flex-shrink:0;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              </div>
              <div>
                <div style="font-size:15px; font-weight:800; color:#92400e; display:flex; align-items:center; gap:8px;">
                  <span>Enrollment Pending HR Approval</span>
                  <span class="badge" style="background:#fef3c7; color:#b45309; font-size:10px; font-weight:800;">ACTION REQUIRED</span>
                </div>
                <div style="font-size:12.5px; color:#b45309; margin-top:2px;">
                  Review statutory onboarding data, 8-angle face vectors, and documents below. You can assign the MFM code and salary here, or approve directly.
                </div>
              </div>
            </div>
            <div style="display:flex; gap:10px; align-items:center;">
              <button class="btn btn-secondary" style="color:#b91c1c; border-color:#fca5a5; background:#fff; font-weight:700; padding:8px 16px;" onclick="rejectCurrentPersonFromProfile()">
                Reject
              </button>
              <button class="btn btn-success" style="font-weight:700; padding:8px 22px; background:#15803d; color:#fff; display:flex; align-items:center; gap:6px; box-shadow:0 2px 4px rgba(21,128,61,0.2);" onclick="approveCurrentPersonFromProfile()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                Approve &amp; Activate
              </button>
            </div>
          </div>
        </div>

        <!-- Employee Hero Profile Header Card -->
        <div class="card" style="margin-bottom:22px; padding:22px; background:#ffffff; border:1px solid var(--border); box-shadow:var(--shadow-sm);">
          <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:18px;">
            <div style="display:flex; gap:18px; align-items:center;">
              <div id="pv-avatar-box" style="width:78px; height:78px; border-radius:14px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; overflow:hidden; border:2px solid #cbd5e1; flex-shrink:0;">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
              </div>
              <div>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                  <h2 id="pv-name" style="font-size:22px; font-weight:800; color:var(--text-main); margin:0;">Employee Name</h2>
                  <span id="pv-status-badge" class="badge badge-p">Approved</span>
                  <span id="pv-emp-type-badge" class="badge" style="background:#f1f5f9; color:#334155; font-weight:700;">On Rolls</span>
                </div>
                <div id="pv-sub" style="font-size:13.5px; color:var(--text-muted); margin-top:4px; font-weight:600;">--</div>
                <div style="font-size:12.5px; color:var(--primary); font-weight:700; margin-top:6px; display:flex; gap:14px; flex-wrap:wrap; align-items:center;">
                  <span style="display:inline-flex; align-items:center; gap:6px;">
                    Employee Code (MFM): <code id="pv-code" style="font-weight:800; font-size:13.5px; background:#f8fafc; padding:3px 9px; border-radius:5px; border:1px solid #cbd5e1; color:#0f172a; font-family:monospace;">--</code>
                  </span>
                  <button class="btn btn-secondary btn-sm" onclick="openAssignMfmModal()" style="font-size:11.5px; font-weight:700; padding:3px 10px; border-radius:6px; color:#1d4ed8; border:1px solid #bfdbfe; background:#eff6ff; display:inline-flex; align-items:center; gap:5px; cursor:pointer;" title="Assign or Edit MFM Employee Code & Universal Salary">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    Assign / Edit MFM Code & Salary
                  </button>
                  <span>System ID: #<span id="pv-id">--</span></span>
                  <span>Unit: <strong id="pv-hero-unit">--</strong></span>
                </div>
              </div>
            </div>

            <!-- Salary & Biometrics Banner -->
            <div style="display:flex; gap:20px; align-items:center; background:#f8fafc; border:1px solid #e2e8f0; padding:12px 22px; border-radius:10px;">
              <div>
                <div style="display:flex; align-items:center; gap:6px;">
                  <span style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Universal Salary</span>
                  <span class="badge" style="font-size:9.5px; background:#dcfce7; color:#15803d; font-weight:700; padding:1px 6px; border-radius:4px;">Web Only</span>
                </div>
                <div id="pv-salary" style="font-size:20px; font-weight:800; color:#15803d; margin-top:2px;">₹ --</div>
              </div>
              <div style="width:1px; height:36px; background:#cbd5e1;"></div>
              <div>
                <div style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Biometric Status</div>
                <div id="pv-hero-vectors" style="font-size:13px; font-weight:700; color:#0369a1; margin-top:4px;">192-d MobileFaceNet</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- BIG CATEGORIZED PROFILE CARDS -->
      <!-- 1. Basic Details Card -->
      <div class="pf-card">
        <div class="pf-header">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          Basic Details
        </div>
        <div class="pf-grid">
          <div class="pf-group">
            <label class="pf-label">Full Name <span class="pf-req">*</span></label>
            <input type="text" id="pf-full_name" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Father / Husband Name</label>
            <input type="text" id="pf-father_or_husband_name" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Gender <span class="pf-req">*</span></label>
            <select id="pf-gender" class="pf-input" disabled>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="pf-group">
            <label class="pf-label">Date of Birth</label>
            <input type="date" id="pf-date_of_birth" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Blood Group</label>
            <select id="pf-blood_group" class="pf-input" disabled>
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
          <div class="pf-group">
            <label class="pf-label">Marital Status</label>
            <select id="pf-marital_status" class="pf-input" disabled>
              <option value="Single">Single</option>
              <option value="Married">Married</option>
              <option value="Divorced">Divorced</option>
              <option value="Widowed">Widowed</option>
            </select>
          </div>
          <div class="pf-group">
            <label class="pf-label">Mobile Number <span class="pf-req">*</span></label>
            <input type="text" id="pf-mobile_number" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Alternate Mobile Number</label>
            <input type="text" id="pf-alternate_mobile" class="pf-input" disabled>
          </div>
          <div class="pf-group full-width">
            <label class="pf-label">Email Address</label>
            <input type="email" id="pf-email_address" class="pf-input" disabled>
          </div>
          <div class="pf-group full-width">
            <label class="pf-label">Present Address <span class="pf-req">*</span></label>
            <textarea id="pf-present_address" class="pf-input pf-textarea" disabled></textarea>
          </div>
          <div class="pf-group full-width">
            <label class="pf-label">Permanent Address</label>
            <textarea id="pf-permanent_address" class="pf-input pf-textarea" disabled></textarea>
          </div>
          <div class="pf-group">
            <label class="pf-label">Emergency Contact Name</label>
            <input type="text" id="pf-emergency_name" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Emergency Relationship</label>
            <input type="text" id="pf-emergency_relationship" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Emergency Mobile Number</label>
            <input type="text" id="pf-emergency_mobile" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Nominee Full Name</label>
            <input type="text" id="pf-nominee_full_name" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Nominee Relationship</label>
            <input type="text" id="pf-nominee_relationship" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Nominee Date of Birth</label>
            <input type="date" id="pf-nominee_dob" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Nominee Share Percentage (%)</label>
            <input type="number" id="pf-nominee_share_percentage" class="pf-input" disabled>
          </div>
        </div>
      </div>

      <!-- 2. Employment & Job Details Card -->
      <div class="pf-card">
        <div class="pf-header">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
          Employment & Job Details
        </div>
        <div class="pf-grid">
          <div class="pf-group">
            <label class="pf-label">Worker / Employee Code (MFM Code) <span style="font-size:11px; font-weight:normal; color:#64748b;">(Optional)</span></label>
            <input type="text" id="pf-worker_code" class="pf-input" disabled placeholder="e.g. MFM258 (or leave empty if not allotted yet)" style="font-weight:700; font-family:monospace; text-transform:uppercase;">
            <div style="font-size:11px; color:#64748b; margin-top:2px;">Official MFM employee code (can be allotted now or left pending).</div>
          </div>
          <div class="pf-group">
            <label class="pf-label">Unit / Factory <span class="pf-req">*</span></label>
            <select id="pf-unit_id" class="pf-input" disabled>
              <?php foreach ($portalUnits as $u): ?>
                <option value="<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars($u['id']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="pf-group" id="pf-group-effective-date" style="display:none;">
            <label class="pf-label">Transfer Effective Date <span class="pf-req">*</span></label>
            <input type="date" id="pf-effective_date" class="pf-input" disabled>
            <div style="font-size:11px; color:#64748b; margin-top:2px;">* Date when unit transfer takes effect</div>
          </div>
          <div class="pf-group">
            <label class="pf-label">Department <span class="pf-req">*</span></label>
            <input type="text" id="pf-department" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Designation <span class="pf-req">*</span></label>
            <input type="text" id="pf-designation" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Shift <span class="pf-req">*</span></label>
            <select id="pf-shift" class="pf-input" disabled>
              <option value="Shift A">Shift A</option>
              <option value="General Shift">General Shift</option>
              <option value="Shift B">Shift B</option>
            </select>
          </div>
          <div class="pf-group">
            <label class="pf-label">Employment Category <span class="pf-req">*</span></label>
            <select id="pf-category" class="pf-input" disabled>
              <option value="On TrooGood Rolls">On TrooGood Rolls</option>
              <option value="Permanent">Permanent</option>
              <option value="Contract worker">Contract Worker</option>
              <option value="Trainee">Trainee</option>
              <option value="Consultant">Consultant</option>
            </select>
          </div>
          <div class="pf-group">
            <label class="pf-label">Contractor / Agency Name</label>
            <input type="text" id="pf-contractor_agency" class="pf-input" placeholder="e.g. TrooGood In-House or Agency" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Date of Joining</label>
            <input type="date" id="pf-date_of_joining" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Reporting Manager / Supervisor</label>
            <input type="text" id="pf-reporting_to" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label" style="display:flex; justify-content:space-between; align-items:center;">
              <span>Universal Salary (₹)</span>
              <span class="badge" style="background:#dcfce7; color:#15803d; font-size:10px; font-weight:700; padding:2px 6px;">Web Portal Only</span>
            </label>
            <input type="number" id="pf-monthly_salary" class="pf-input" placeholder="e.g. 18500" step="0.01" disabled style="font-weight:700; color:#15803d;">
            <div style="font-size:11px; color:#64748b; margin-top:2px;">* Universal monthly salary field. Cannot be filled from mobile app or candidate forms.</div>
          </div>
          <div class="pf-group">
            <label class="pf-label">Approval Status</label>
            <select id="pf-approval_status" class="pf-input" disabled>
              <option value="approved">Approved</option>
              <option value="pending">Pending Approval</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
          <div class="pf-group">
            <label class="pf-label">Attendance App Mode <span class="pf-req">*</span></label>
            <div style="display:flex; gap:8px; align-items:center;">
              <select id="pf-attendance_app_mode" class="pf-input" disabled onchange="handleAppModeChange(this.value)">
                <option value="kiosk">Kiosk Mode (Default)</option>
                <option value="individual">Individual Access</option>
              </select>
              <button type="button" class="btn btn-secondary" id="btn-cfg-individual" onclick="openIndividualAccessModal()" style="display:none; padding:6px 12px; font-size:11.5px; white-space:nowrap; background:#eff6ff; color:#1d4ed8; border-color:#93c5fd; font-weight:700;">
                ⚙️ Setup Access
              </button>
            </div>
            <div id="pf-individual-status-hint" style="font-size:11px; color:#2563eb; display:none; margin-top:2px;">
              Individual credentials active: <span id="pf-app-email-display" style="font-weight:700;"></span>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Bank & Statutory IDs Card -->
      <div class="pf-card">
        <div class="pf-header">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
          Bank & Statutory IDs
        </div>
        <div class="pf-grid">
          <div class="pf-group">
            <label class="pf-label">Aadhaar Number <span class="pf-req">*</span></label>
            <input type="text" id="pf-aadhaar_number" class="pf-input" placeholder="12-digit Aadhaar" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">PAN Card Number</label>
            <input type="text" id="pf-pan_number" class="pf-input" placeholder="10-digit PAN" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Bank Account Number</label>
            <input type="text" id="pf-account_number" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Bank IFSC Code</label>
            <input type="text" id="pf-ifsc_code" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Bank Name & Branch</label>
            <input type="text" id="pf-bank_name_branch" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">UAN Number (PF)</label>
            <input type="text" id="pf-uan_number" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">ESIC Number</label>
            <input type="text" id="pf-esi_number" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Health Insurance Number</label>
            <input type="text" id="pf-health_insurance_number" class="pf-input" disabled>
          </div>
          <div class="pf-group">
            <label class="pf-label">Has Active PF Account?</label>
            <select id="pf-has_pf_account" class="pf-input" disabled>
              <option value="1">Yes</option>
              <option value="0">No</option>
            </select>
          </div>
          <div class="pf-group">
            <label class="pf-label">EPS Pension Status</label>
            <select id="pf-eps_pension_status" class="pf-input" disabled>
              <option value="Yes">Yes</option>
              <option value="No">No</option>
            </select>
          </div>
          <div class="pf-group">
            <label class="pf-label">PF / ESI Opt-Out Status</label>
            <select id="pf-opt_out_pf_esi" class="pf-input" disabled>
              <option value="0">Standard PF/ESI Enrolled</option>
              <option value="1">Exempted (Opted Out)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- 3b. EPFO Form 11 Statutory Self-Declaration Card -->
      <div class="pf-card" id="pv-form11-card">
        <div class="pf-header" style="justify-content:space-between; flex-wrap:wrap; gap:10px;">
          <div style="display:flex; align-items:center; gap:10px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" width="20" height="20">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
              <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            <span>EPFO Form 11 Statutory Self-Declaration & KYC Undertaking</span>
          </div>
          <div style="display:flex; align-items:center; gap:8px;">
            <span id="pv-form11-status-badge" class="badge badge-p">Statutory Verified</span>
            <button type="button" class="btn btn-primary btn-sm" id="btn-download-form11-pdf" style="display:inline-flex; align-items:center; gap:6px; font-weight:700; padding:6px 14px; background:#1d4ed8; color:#fff;" onclick="downloadCurrentPersonForm11Pdf()">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
              Download Form 11 PDF
            </button>
          </div>
        </div>

        <div style="padding:16px 20px;">
          <!-- Digital Signature & Authenticity Banner -->
          <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px 18px; margin-bottom:18px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
            <div style="display:flex; align-items:center; gap:14px;">
              <div style="width:44px; height:44px; border-radius:50%; background:#eff6ff; border:2px solid #3b82f6; display:flex; align-items:center; justify-content:center; color:#1d4ed8;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
              </div>
              <div>
                <div style="font-size:12px; text-transform:uppercase; letter-spacing:0.5px; font-weight:700; color:#64748b;">Statutory Digital Signature</div>
                <div id="pv-f11-sig-name" style="font-size:16px; font-weight:800; color:#1e293b; font-family:Georgia, serif; font-style:italic;">--</div>
                <div style="font-size:11.5px; color:#64748b; margin-top:2px;">
                  Signed at: <strong id="pv-f11-sig-city">--</strong> · Timestamp: <strong id="pv-f11-sig-time">--</strong>
                </div>
              </div>
            </div>
            <div style="text-align:right;">
              <div style="font-size:11px; font-weight:700; color:#16a34a; display:flex; align-items:center; gap:5px; justify-content:flex-end;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                Valid Electronic Signature
              </div>
              <div style="font-size:10.5px; color:#64748b;">Sec 5 Information Technology Act, 2000</div>
              <div style="font-size:10.5px; color:#64748b;">EPFO Form 11 & DPDP Act 2023 Compliant</div>
            </div>
          </div>

          <!-- Statutory Confirmations / Declarations List -->
          <div style="margin-bottom:18px;">
            <div style="font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; color:#475569; margin-bottom:10px;">
              Statutory Declarations & Consent Confirmations
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:10px;">
              
              <div style="display:flex; gap:10px; padding:10px 12px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" style="flex-shrink:0; margin-top:2px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <div style="font-size:11.5px; color:#14532d; line-height:1.4;">
                  <strong>Truthfulness & Statutory Accuracy:</strong> Certified that all declarations, employment history, and PF/ESI details are true, correct, and complete under EPF Scheme 1952.
                </div>
              </div>

              <div style="display:flex; gap:10px; padding:10px 12px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" style="flex-shrink:0; margin-top:2px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <div style="font-size:11.5px; color:#14532d; line-height:1.4;">
                  <strong>Factory Safety & GMP Compliance:</strong> Agreed to strictly abide by plant hygiene, Good Manufacturing Practices (GMP), PPE regulations, and factory safety directives.
                </div>
              </div>

              <div style="display:flex; gap:10px; padding:10px 12px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" style="flex-shrink:0; margin-top:2px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <div style="font-size:11.5px; color:#14532d; line-height:1.4;">
                  <strong>Direct Bank Account Credit:</strong> Authorized direct credit of salary wages, statutory dues, and reimbursement allowances directly into verified bank account.
                </div>
              </div>

              <div style="display:flex; gap:10px; padding:10px 12px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" style="flex-shrink:0; margin-top:2px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <div style="font-size:11.5px; color:#14532d; line-height:1.4;">
                  <strong>Aadhaar EPFO Consent:</strong> Voluntary consent to seed Aadhaar number for statutory identity authentication, UAN seeding, and regulatory compliance.
                </div>
              </div>

              <div style="display:flex; gap:10px; padding:10px 12px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" style="flex-shrink:0; margin-top:2px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <div style="font-size:11.5px; color:#14532d; line-height:1.4;">
                  <strong>Facial Biometrics Consent (DPDP Act 2023):</strong> Voluntary consent to capture and process 192-dimensional facial biometric vectors solely for contactless kiosk attendance tracking and gate security.
                </div>
              </div>

            </div>
          </div>

          <!-- Statutory Form 11 Particulars Grid -->
          <div style="border-top:1px solid #e2e8f0; padding-top:14px;">
            <div style="font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; color:#475569; margin-bottom:10px;">
              Form 11 Regulatory Particulars & Prior Employment
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; font-size:12.5px;">
              <div style="background:#f8fafc; padding:10px 12px; border-radius:6px; border:1px solid #e2e8f0;">
                <div style="font-size:11px; color:#64748b; font-weight:700;">PREVIOUS EMPLOYMENT</div>
                <div id="pv-f11-prev-emp" style="font-weight:700; color:#1e293b; margin-top:2px;">--</div>
              </div>
              <div style="background:#f8fafc; padding:10px 12px; border-radius:6px; border:1px solid #e2e8f0;">
                <div style="font-size:11px; color:#64748b; font-weight:700;">PREVIOUS UAN NUMBER</div>
                <div id="pv-f11-uan" style="font-weight:700; color:#1e293b; margin-top:2px; font-family:monospace;">--</div>
              </div>
              <div style="background:#f8fafc; padding:10px 12px; border-radius:6px; border:1px solid #e2e8f0;">
                <div style="font-size:11px; color:#64748b; font-weight:700;">EPS PENSION SCHEME (1995)</div>
                <div id="pv-f11-eps" style="font-weight:700; color:#1e293b; margin-top:2px;">--</div>
              </div>
              <div style="background:#f8fafc; padding:10px 12px; border-radius:6px; border:1px solid #e2e8f0;">
                <div style="font-size:11px; color:#64748b; font-weight:700;">ESIC IP NUMBER</div>
                <div id="pv-f11-esic" style="font-weight:700; color:#1e293b; margin-top:2px; font-family:monospace;">--</div>
              </div>
              <div style="background:#f8fafc; padding:10px 12px; border-radius:6px; border:1px solid #e2e8f0;">
                <div style="font-size:11px; color:#64748b; font-weight:700;">PF/ESIC SCHEME STATUS</div>
                <div id="pv-f11-scheme" style="font-weight:700; color:#1e293b; margin-top:2px;">--</div>
              </div>
              <div style="background:#f8fafc; padding:10px 12px; border-radius:6px; border:1px solid #e2e8f0;">
                <div style="font-size:11px; color:#64748b; font-weight:700;">PRIMARY NOMINEE (FORM 2)</div>
                <div id="pv-f11-nominee" style="font-weight:700; color:#1e293b; margin-top:2px;">--</div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- 4. Uploaded Documents & Statutory Proofs Card -->
      <div class="pf-card">
        <div class="pf-header" style="justify-content:space-between; flex-wrap:wrap;">
          <div style="display:flex; align-items:center; gap:10px;">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
            Documents, Statutory Proofs & Exemption Forms
          </div>
          <span style="font-size:11.5px; color:var(--text-muted); font-weight:normal;">View attached documents or upload/replace files below</span>
        </div>
        <div id="pv-documents-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(170px, 1fr)); gap:14px;">
          <!-- Dynamically populated document cards -->
        </div>
      </div>

      <!-- 5. Biometrics & Attendance Sync Logins Card -->
      <div class="pf-card">
        <div class="pf-header" style="justify-content:space-between; flex-wrap:wrap;">
          <div style="display:flex; align-items:center; gap:10px;">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path><path d="M2 12h20"></path></svg>
            Biometrics (ArcFace 192-d) & Attendance Sync Assignments
          </div>
          <span id="pv-embedding-badge" class="badge badge-p">Active V2</span>
        </div>
        
        <div id="pv-biometrics-body" style="font-size:13px; color:var(--text-main); margin-bottom:18px;">--</div>

        <div style="border-top:1px solid #f1f5f9; padding-top:16px; margin-top:14px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
            <div style="font-size:13.5px; font-weight:700; color:var(--text-main); display:flex; align-items:center; gap:8px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
              Attendance Sync Logins & Inter-Unit Transfer History
            </div>
            <div style="display:flex; gap:8px;">
              <button class="btn btn-secondary" style="padding:5px 12px; font-size:12px;" onclick="openSinglePersonAssignModal()">Change Assigned Logins</button>
              <button class="btn btn-secondary" style="padding:5px 12px; font-size:12px;" onclick="openSinglePersonTransferModal()">Transfer Unit</button>
            </div>
          </div>
          <div style="margin-bottom:14px; font-size:13px;">
            <strong style="color:var(--text-muted);">Current Kiosk Sync Logins:</strong>
            <span id="pv-assignments-list" style="margin-left:6px;">--</span>
          </div>
          <div class="table-responsive">
            <table style="font-size:12.5px;">
              <thead>
                <tr>
                  <th>From Unit</th>
                  <th>To Unit</th>
                  <th>Transfer Reason</th>
                  <th>Transferred By</th>
                  <th>Effective Date</th>
                  <th>Timestamp</th>
                </tr>
              </thead>
              <tbody id="pv-transfers-table-body">
                <tr><td colspan="6" style="text-align:center; color:var(--text-muted);">No past transfers recorded.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Sticky Save Bar for In-Place Edit Mode -->
      <div id="pf-sticky-bar" class="pf-sticky-bar">
        <div style="display:flex; align-items:center; gap:12px;">
          <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#f59e0b;"></span>
          <span style="font-weight:700; color:#1e293b; font-size:13.5px;">Editing Employee Profile: <strong id="pf-sticky-name">--</strong></span>
        </div>
        <div style="display:flex; gap:10px;">
          <button class="btn btn-secondary" onclick="toggleProfileEditMode(false)">Cancel</button>
          <button class="btn btn-primary" onclick="savePersonProfile()" style="background:#15803d; border-color:#15803d; font-weight:700; display:flex; align-items:center; gap:6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
            Save Changes
          </button>
        </div>
      </div>
    </main>

    <!-- 7. MASTERS MANAGEMENT VIEW -->
    <main class="view-container" id="view-masters">
      <div class="page-header">
        <div>
          <div class="breadcrumb">Master Data & System Setup / Masters Directory</div>
          <h2 class="page-title">Organization Masters & Configuration</h2>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <button class="btn btn-secondary" onclick="exportMasterCsv()" style="display:flex; align-items:center; gap:6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Download Excel/CSV
          </button>
          <button class="btn btn-secondary" onclick="openMasterUploadModal()" style="display:flex; align-items:center; gap:6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            Upload Excel/CSV
          </button>
          <button class="btn btn-primary" onclick="openAddMasterModal()" id="btn-add-master-record">
            + Add New Record
          </button>
        </div>
      </div>

      <!-- Master Tabs Navigation -->
      <div style="display:flex; gap:10px; margin-bottom:18px; border-bottom:1px solid var(--border); padding-bottom:12px; overflow-x:auto;">
        <button class="btn btn-primary master-tab-btn" id="mtab-departments" onclick="switchMasterTab('departments')" style="font-weight:700;">🏢 Departments</button>
        <button class="btn btn-secondary master-tab-btn" id="mtab-designations" onclick="switchMasterTab('designations')" style="font-weight:700;">💼 Designations & Roles</button>
        <button class="btn btn-secondary master-tab-btn" id="mtab-contractors" onclick="switchMasterTab('contractors')" style="font-weight:700;">🚚 Contractors & Agencies</button>
        <button class="btn btn-secondary master-tab-btn" id="mtab-reporting_managers" onclick="switchMasterTab('reporting_managers')" style="font-weight:700;">👔 Reporting Managers</button>
        <button class="btn btn-secondary master-tab-btn" id="mtab-banks" onclick="switchMasterTab('banks')" style="font-weight:700;">🏦 Banks Master</button>
      </div>

      <!-- Filter & Search Toolbar -->
      <div class="card" style="padding:14px 18px; margin-bottom:18px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
          <div style="display:flex; align-items:center; gap:10px; flex:1; min-width:280px;">
            <div class="search-box" style="flex:1;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              <input type="text" id="master-search-input" placeholder="Search in active master..." oninput="handleMasterSearch()">
            </div>
            <div id="master-dept-filter-box" style="display:none;">
              <select id="master-dept-filter" onchange="loadMasterData()" style="padding:8px 12px; border:1px solid var(--border); border-radius:6px; font-size:13px; font-weight:600;">
                <option value="">All Departments</option>
              </select>
            </div>
          </div>
          <div style="display:flex; align-items:center; gap:12px; font-size:13px;">
            <span id="master-count-badge" class="badge-chip" style="font-weight:700; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;">Total: 0</span>
            <button class="btn btn-secondary" style="padding:6px 12px; font-size:12px;" onclick="loadMasterData()">🔄 Refresh</button>
          </div>
        </div>
      </div>

      <!-- Master Data Table -->
      <div class="card" style="padding:0; overflow:hidden;">
        <div class="table-responsive">
          <table id="master-data-table">
            <thead id="master-table-head">
              <tr><th>Loading...</th></tr>
            </thead>
            <tbody id="master-table-body">
              <tr><td style="text-align:center; padding:30px; color:#64748b;">Loading master data...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- W09: HOLIDAY CALENDAR VIEW -->
    <main class="view-container" id="view-holidays">
      <div class="page-header" style="flex-wrap:wrap; gap:12px;">
        <div>
          <div class="breadcrumb">Company & Compliance / Holiday Calendar</div>
          <h2 class="page-title">Annual Holiday Calendar & Off Days</h2>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <button class="btn btn-secondary" onclick="window.location.href='/api/holidays/template'">📥 Download Template</button>
          <button class="btn btn-secondary" onclick="openUploadHolidayModal()">📤 Upload CSV / Bulk Update</button>
          <button class="btn btn-primary" onclick="openAddHolidayModal()">+ Add Holiday</button>
        </div>
      </div>

      <!-- Statutory Attendance Rule Policy Banner -->
      <div style="background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%); border: 1px solid #bfdbfe; border-radius: var(--radius-md); padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 14px;">
        <div style="font-size: 22px; line-height: 1;">🎉</div>
        <div>
          <h4 style="font-size: 13.5px; font-weight: 800; color: #1e3a8a; margin-bottom: 3px;">Statutory Holiday Attendance & Compensatory Leave Policy</h4>
          <p style="font-size: 12px; color: #334155; line-height: 1.5; margin: 0;">
            Employees who do not work on a declared holiday are marked <strong>'H' (Paid Holiday)</strong>. If an employee <strong>punches attendance on a declared holiday or Sunday</strong>, their attendance is recorded as full-day <strong>Present ('P')</strong> AND <strong>+1 Compensatory Off leave credit</strong> is automatically added to their leave balance in the Leave Calculator.
          </p>
        </div>
      </div>

      <!-- Year Filter & Stat Bar -->
      <div class="card" style="margin-bottom: 16px; padding: 14px 18px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div style="display:flex; align-items:center; gap:10px;">
          <span style="font-size:12.5px; font-weight:700; color:#475569;">Calendar Year:</span>
          <select id="holiday-year-select" class="unit-select" onchange="loadHolidays()" style="font-weight:800; padding:6px 14px;">
            <option value="2025">2025</option>
            <option value="2026" selected>2026 (Current Active)</option>
            <option value="2027">2027</option>
          </select>
          <button class="btn btn-secondary" style="padding:6px 12px; font-size:12px;" onclick="loadHolidays()">Refresh</button>
        </div>
        <div style="display:flex; gap:16px; font-size:12.5px;">
          <div>Total Holidays: <strong id="holiday-stat-total" style="color:var(--primary); font-size:14px;">0</strong></div>
          <div>National: <strong id="holiday-stat-national" style="color:#b45309; font-size:14px;">0</strong></div>
          <div>Festivals: <strong id="holiday-stat-festival" style="color:#15803d; font-size:14px;">0</strong></div>
          <div>Company: <strong id="holiday-stat-company" style="color:#6d28d9; font-size:14px;">0</strong></div>
        </div>
      </div>

      <!-- Holidays Table Card -->
      <div class="card">
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th style="width:40px; text-align:center;">#</th>
                <th style="min-width:110px;">Date</th>
                <th style="width:100px;">Day</th>
                <th style="min-width:200px;">Holiday Name</th>
                <th style="width:120px; text-align:center;">Type</th>
                <th style="width:100px; text-align:center;">Status</th>
                <th style="min-width:160px; text-align:center;">Comp-Off Credit</th>
                <th style="min-width:200px;">Description</th>
                <th style="width:110px; text-align:center;">Actions</th>
              </tr>
            </thead>
            <tbody id="holidays-table-body">
              <tr><td colspan="9" style="text-align:center; padding:30px; color:#64748b;">Loading annual holiday calendar...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- W10: LEAVE CALCULATOR VIEW -->
    <main class="view-container" id="view-leave-calculator">
      <div class="page-header" style="flex-wrap:wrap; gap:12px;">
        <div>
          <div class="breadcrumb">Payroll & Compliance / Leave Management</div>
          <h2 class="page-title">Leave Balance & Compensatory Off Calculator</h2>
        </div>
        <div style="display:flex; gap:10px;">
          <button class="btn btn-secondary" onclick="loadLeaveCalculator()">Refresh</button>
          <button class="btn btn-primary" onclick="exportLeaveCalculatorCsv()">📥 Export to CSV</button>
        </div>
      </div>

      <!-- Formula Explainer Banner -->
      <div style="background:#fff; border:1px solid #cbd5e1; border-left:5px solid var(--primary); border-radius:var(--radius-md); padding:16px 20px; margin-bottom:20px; box-shadow:var(--shadow-sm);">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
          <div>
            <div style="font-size:11px; font-weight:800; color:#64748b; letter-spacing:0.5px; text-transform:uppercase;">Statutory Balance Formula</div>
            <div style="font-size:16px; font-weight:800; color:#0f172a; margin-top:2px;">
              Closing Balance = Opening (5.0) + Sunday/Holiday Duty (<span style="color:#15803d;">+1 per day</span>) + Monthly Accrual (<span style="color:#2563eb;">+1.5</span>) &minus; Leaves Availed (<span style="color:#b91c1c;">A/L</span>)
            </div>
          </div>
          <div style="font-size:12px; color:#475569; background:#f8fafc; border:1px solid #e2e8f0; padding:8px 14px; border-radius:8px;">
            Monthly Accrual: <strong>1.5 Days</strong> &middot; Opening Base: <strong>5.0 Days</strong>
          </div>
        </div>
      </div>

      <!-- Filters & Month Selector -->
      <div class="card" style="margin-bottom:16px; padding:14px 18px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
          <div>
            <label style="font-size:11px; font-weight:700; color:#64748b; display:block; margin-bottom:2px;">Month</label>
            <input type="month" id="leave-calc-month" class="unit-select" value="2026-10" onchange="loadLeaveCalculator()" style="font-weight:700;">
          </div>
          <div>
            <label style="font-size:11px; font-weight:700; color:#64748b; display:block; margin-bottom:2px;">Unit</label>
            <select id="leave-calc-unit" class="unit-select" onchange="loadLeaveCalculator()" style="font-weight:700;">
              <option value="all">All Units</option>
              <?php foreach ($portalUnits as $u): ?>
                <option value="<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars($u['id']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="font-size:11px; font-weight:700; color:#64748b; display:block; margin-bottom:2px;">Search Employee</label>
            <input type="text" id="leave-calc-search" placeholder="Filter by name or code..." class="unit-select" oninput="filterLeaveCalcTable(this.value)" style="width:200px;">
          </div>
        </div>
        <div style="display:flex; gap:16px; font-size:12px;" id="leave-calc-summary-pills">
          <div>Total Employees: <strong id="lc-stat-workers">0</strong></div>
          <div>Comp-Off Earned: <strong id="lc-stat-compoff" style="color:#15803d;">0</strong></div>
          <div>Leaves Availed: <strong id="lc-stat-availed" style="color:#b91c1c;">0</strong></div>
        </div>
      </div>

      <!-- Table Card -->
      <div class="card">
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th style="width:50px; text-align:center;">ID</th>
                <th style="min-width:180px;">Employee</th>
                <th style="width:80px; text-align:center;">Unit</th>
                <th style="width:130px;">Category</th>
                <th style="width:110px; text-align:center; background:#f8fafc;" title="Starting balance">Opening (5.0)</th>
                <th style="width:110px; text-align:center; background:#eff6ff;" title="Standard monthly credit">+ Accrual (1.5)</th>
                <th style="width:130px; text-align:center; background:#f0fdf4;" title="Worked on declared holidays or Sundays">+ Sunday/Holiday (+1)</th>
                <th style="width:110px; text-align:center; background:#fff1f2;" title="Total absences or approved leaves">&minus; Availed (A/L)</th>
                <th style="width:120px; text-align:center; background:#fefce8; font-weight:800;">Closing Balance</th>
                <th style="width:90px; text-align:center;">Status</th>
              </tr>
            </thead>
            <tbody id="leave-calc-table-body">
              <tr><td colspan="10" style="text-align:center; padding:30px; color:#64748b;">Loading dynamic leave balances...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- W11: SYSTEM ERROR LOGS VIEW -->
    <main class="view-container" id="view-error-logs">
      <div class="page-header" style="flex-wrap:wrap; gap:12px;">
        <div>
          <div class="breadcrumb">System & Diagnostics / Error Tracking</div>
          <h2 class="page-title">System Error Logs & Diagnostics</h2>
        </div>
        <div style="display:flex; gap:10px;">
          <button class="btn btn-secondary" onclick="loadSystemLogs()">Refresh</button>
          <button class="btn btn-secondary" onclick="clearSystemLogs()" style="color:#b91c1c; border-color:#fca5a5;">Clear Resolved Logs</button>
        </div>
      </div>

      <!-- Source Filter Tabs -->
      <div class="approval-tabs-bar" style="margin-bottom:16px;">
        <button class="approval-tab-btn active" id="log-tab-all" onclick="filterLogs('all', this)">
          All Errors <span class="tab-count" id="log-cnt-all">0</span>
        </button>
        <button class="approval-tab-btn" id="log-tab-mobile_app" onclick="filterLogs('mobile_app', this)">
          📱 Mobile App <span class="tab-count" id="log-cnt-mobile_app">0</span>
        </button>
        <button class="approval-tab-btn" id="log-tab-web_portal" onclick="filterLogs('web_portal', this)">
          💻 Web Portal <span class="tab-count" id="log-cnt-web_portal">0</span>
        </button>
        <button class="approval-tab-btn" id="log-tab-api" onclick="filterLogs('api', this)">
          🔌 API Gateway <span class="tab-count" id="log-cnt-api">0</span>
        </button>
        <button class="approval-tab-btn" id="log-tab-sync" onclick="filterLogs('sync', this)">
          🔄 Offline Sync <span class="tab-count" id="log-cnt-sync">0</span>
        </button>
      </div>

      <!-- Table Card -->
      <div class="card">
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th style="width:140px;">Timestamp</th>
                <th style="width:110px; text-align:center;">Source</th>
                <th style="width:90px; text-align:center;">Severity</th>
                <th style="min-width:240px;">Error Message</th>
                <th style="min-width:140px;">Endpoint / Screen</th>
                <th style="width:120px;">Device / User</th>
                <th style="width:90px; text-align:center;">Details</th>
              </tr>
            </thead>
            <tbody id="error-logs-table-body">
              <tr><td colspan="7" style="text-align:center; padding:30px; color:#64748b;">Loading system error telemetry...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- W12: NOTIFICATIONS VIEW -->
    <main class="view-container" id="view-notifications">
      <div class="page-header" style="flex-wrap:wrap; gap:12px;">
        <div>
          <div class="breadcrumb">Communication & Broadcast / Announcements</div>
          <h2 class="page-title">Broadcast Announcements & Alerts</h2>
        </div>
        <button class="btn btn-primary" onclick="openBroadcastModal()">+ Broadcast Announcement</button>
      </div>

      <div class="card" style="margin-bottom:20px; padding:16px 20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;">
        <div style="font-size:13px; color:#334155; line-height:1.5;">
          📢 Announcements broadcast from here instantly appear on mobile kiosk devices (top-left notification bell) and in the web portal topbar for supervisors and HR administrators.
        </div>
      </div>

      <div class="card">
        <div class="card-title">Broadcast History & Active Alerts</div>
        <div id="notifications-list-container" style="display:flex; flex-direction:column; gap:12px; margin-top:14px;">
          <div style="text-align:center; padding:30px; color:#64748b;">Loading notifications...</div>
        </div>
      </div>
    </main>
  </div>

  <!-- 2. Unit Transfer Modal -->
  <div class="modal-overlay" id="modal-people-transfer">
    <div class="modal-card" style="max-width: 480px;">
      <h3 style="font-size:16px; font-weight:800; margin-bottom:6px;">Unit Workforce Transfer</h3>
      <p id="transfer-modal-desc" style="font-size:12.5px; color:var(--text-muted); margin-bottom:14px;">Transfer selected employee(s) to a target factory unit. Biometric vectors will automatically synchronize to the new unit's kiosks.</p>
      
      <div style="display:flex; flex-direction:column; gap:12px; font-size:13px;">
        <div>
          <label style="display:block; font-weight:700; margin-bottom:4px;">Target Factory Unit *</label>
          <select id="transfer-target-unit" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px; font-weight:700;">
            <?php foreach ($portalUnits as $u): ?>
              <option value="<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars($u['id']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label style="display:block; font-weight:700; margin-bottom:4px;">Effective Date *</label>
          <input type="date" id="transfer-effective-date" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
          <div style="font-size:11.5px; color:#64748b; margin-top:4px; line-height:1.4;">
            * If effective today, any previous punch today in the old unit will be reset to <strong>Absent</strong>, and attendance will register in the new unit once they punch in at the new unit's kiosk.
          </div>
        </div>
        <div>
          <label style="display:block; font-weight:700; margin-bottom:4px;">Transfer Reason / Plant Order</label>
          <input type="text" id="transfer-reason" placeholder="e.g. Line expansion / Inter-plant shift allocation" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
        </div>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
        <button class="btn btn-secondary" onclick="closeModals()">Cancel</button>
        <button class="btn btn-primary" onclick="submitPeopleTransfer()">Execute Transfer</button>
      </div>
    </div>
  </div>

  <!-- Modal: Assign MFM Employee Code & Universal Salary -->
  <div class="modal-overlay" id="modal-assign-mfm">
    <div class="modal-card" style="max-width: 480px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <div style="display:flex; align-items:center; gap:8px;">
          <div style="width:34px; height:34px; border-radius:8px; background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:12px;">MFM</div>
          <div>
            <h3 style="font-size:16px; font-weight:800; color:#0f172a; margin:0;">Assign MFM Code & Universal Salary</h3>
            <div style="font-size:11.5px; color:#64748b;">Enterprise workforce identification & wage configuration</div>
          </div>
        </div>
        <button type="button" class="btn btn-secondary" onclick="closeModals()" style="padding:4px 8px; font-size:12px;">✕</button>
      </div>
      
      <p style="font-size:12px; color:#475569; margin-bottom:14px; line-height:1.4; background:#f8fafc; border:1px solid #e2e8f0; padding:8px 12px; border-radius:6px;">
        Universal salary and MFM employee code are strictly controlled by HR via this web portal. Candidate forms and mobile kiosks cannot alter these values.
      </p>
      
      <div style="display:flex; flex-direction:column; gap:12px; font-size:13px;">
        <div style="background:#f1f5f9; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px;">
          <div style="font-size:10.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.3px;">Selected Profile</div>
          <div id="quick-mfm-emp-name" style="font-weight:800; font-size:14px; color:#0f172a; margin-top:2px;">--</div>
          <div id="quick-mfm-emp-sub" style="font-size:12px; color:#475569; margin-top:2px;">--</div>
        </div>

        <div>
          <label style="display:block; font-weight:700; margin-bottom:4px; font-size:13px;">
            Employee Code (MFM Code) <span style="font-size:11px; font-weight:normal; color:#64748b;">(Optional)</span>
          </label>
          <input type="text" id="quick-mfm-code" placeholder="e.g. MFM258 (or leave blank if pending)" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:9px 12px; font-family:monospace; font-weight:700; font-size:14px; text-transform:uppercase;">
          <div style="font-size:11px; color:#64748b; margin-top:3px;">
            Enterprise employee code. If left blank, employee code remains 'Not Assigned'.
          </div>
        </div>

        <div>
          <label style="display:flex; justify-content:space-between; align-items:center; font-weight:700; margin-bottom:4px; font-size:13px;">
            <span>Universal Monthly Salary (₹)</span>
            <span class="badge" style="background:#dcfce7; color:#15803d; font-size:10px; font-weight:700; padding:2px 6px;">Web Portal Only</span>
          </label>
          <input type="number" id="quick-universal-salary" placeholder="e.g. 18500" step="0.01" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:9px 12px; font-weight:700; font-size:14px; color:#15803d;">
          <div style="font-size:11px; color:#64748b; margin-top:3px;">
            Official monthly base wage. Only filled from web portal; locked from candidate onboarding forms & mobile app.
          </div>
        </div>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:14px; border-top:1px solid #f1f5f9;">
        <button type="button" class="btn btn-secondary" onclick="closeModals()">Cancel</button>
        <button type="button" class="btn btn-primary" id="btn-save-quick-mfm" onclick="submitQuickMfmSalary()" style="background:#15803d; border-color:#15803d; font-weight:700;">
          Save MFM Code & Salary
        </button>
      </div>
    </div>
  </div>

  <!-- 3. Attendance Login Assignment Modal -->
  <div class="modal-overlay" id="modal-people-assign">
    <div class="modal-card" style="max-width: 520px; max-height:85vh; overflow-y:auto;">
      <h3 style="font-size:16px; font-weight:800; margin-bottom:6px;">Attendance Login Sync Assignment</h3>
      <p id="assign-modal-desc" style="font-size:12.5px; color:var(--text-muted); margin-bottom:14px;">Select which kiosk supervisor logins this employee's face vectors will sync to for biometric punch in/out.</p>

      <div style="display:flex; flex-direction:column; gap:12px; font-size:13px;">
        <div>
          <label style="display:block; font-weight:700; margin-bottom:4px;">Assignment Mode</label>
          <select id="assign-mode" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;" onchange="toggleAssignMode(this.value)">
            <option value="replace">Assign Specific Logins (Replace previous)</option>
            <option value="add">Add To Existing Assigned Logins</option>
            <option value="clear">Reset to Default (Sync to all kiosks in worker's unit)</option>
          </select>
        </div>

        <div id="assign-logins-box">
          <label style="display:block; font-weight:700; margin-bottom:6px;">Select Active Kiosk Logins:</label>
          <div id="assign-logins-checklist" style="border:1px solid var(--border); border-radius:8px; padding:10px; max-height:220px; overflow-y:auto; display:flex; flex-direction:column; gap:8px;">
            <!-- Dynamically populated login checkboxes -->
          </div>
        </div>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
        <button class="btn btn-secondary" onclick="closeModals()">Cancel</button>
        <button class="btn btn-primary" onclick="submitPeopleAssign()">Save Assignment</button>
      </div>
    </div>
  </div>

  <!-- (Modal person edit replaced by in-place Big Cards direct editing) -->

  <!-- Add/Edit Shift Modal -->
  <div class="modal-overlay" id="modal-shift">
    <div class="modal-card" style="max-width: 500px;">
      <h3 id="modal-shift-title" style="font-size:16px; font-weight:800; margin-bottom:14px;">Add New Shift</h3>
      <input type="hidden" id="shift-modal-id" value="">
      <div style="display:flex; flex-direction:column; gap:12px; font-size:13px;">
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Shift Code</label>
            <input type="text" id="shift-modal-code" placeholder="e.g. SHIFT_D" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px; font-weight:700;">
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Grace Period (Mins)</label>
            <input type="number" id="shift-modal-grace" value="15" min="0" max="60" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
          </div>
        </div>
        <div>
          <label style="display:block; font-weight:700; margin-bottom:4px;">Shift Display Name</label>
          <input type="text" id="shift-modal-name" placeholder="e.g. D Shift (Night Owl / Late Packaging)" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Start Time (IST)</label>
            <input type="time" id="shift-modal-start" value="06:00" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px; font-weight:600;">
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">End Time (IST)</label>
            <input type="time" id="shift-modal-end" value="14:00" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px; font-weight:600;">
          </div>
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Half Day Min (Mins)</label>
            <input type="number" id="shift-modal-half-day" value="240" min="60" max="600" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Full Day Min (Mins)</label>
            <input type="number" id="shift-modal-full-day" value="480" min="120" max="900" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
          </div>
        </div>
        <div>
          <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:700; margin-top:4px;">
            <input type="checkbox" id="shift-modal-active" checked style="width:16px; height:16px;">
            <span>Shift is Active & Available for Attendance Allocation</span>
          </label>
        </div>
      </div>
      <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
        <button class="btn btn-secondary" onclick="closeModals()">Cancel</button>
        <button class="btn btn-primary" onclick="submitSaveShift()">Save Shift</button>
      </div>
    </div>
  </div>

  <!-- New Role Modal -->
  <div class="modal-overlay" id="modal-new-role">
    <div class="modal-card">
      <h3 style="font-size:16px; font-weight:800; margin-bottom:14px;">Dynamic Role Creation</h3>
      <div style="display:flex; flex-direction:column; gap:12px; font-size:13px;">
        <div>
          <label style="display:block; font-weight:700; margin-bottom:4px;">Role Code (e.g. shift_lead)</label>
          <input type="text" id="new-role-code" placeholder="shift_lead" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
        </div>
        <div>
          <label style="display:block; font-weight:700; margin-bottom:4px;">Role Display Name</label>
          <input type="text" id="new-role-name" placeholder="Shift Lead" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
        </div>
        <div>
          <label style="display:block; font-weight:700; margin-bottom:4px;">Description</label>
          <textarea id="new-role-desc" placeholder="Shift lead managing floor rosters..." style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;"></textarea>
        </div>
      </div>
      <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:18px;">
        <button class="btn btn-secondary" onclick="closeModals()">Cancel</button>
        <button class="btn btn-primary" onclick="submitCreateRole()">Create Role</button>
      </div>
    </div>
  </div>

  <!-- Clone Role Modal -->
  <div class="modal-overlay" id="modal-clone-role">
    <div class="modal-card">
      <h3 style="font-size:16px; font-weight:800; margin-bottom:14px;">Clone Role</h3>
      <div style="display:flex; flex-direction:column; gap:12px; font-size:13px;">
        <div>
          <label style="display:block; font-weight:700; margin-bottom:4px;">New Role Code</label>
          <input type="text" id="clone-role-code" placeholder="hr_assistant" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
        </div>
        <div>
          <label style="display:block; font-weight:700; margin-bottom:4px;">New Role Name</label>
          <input type="text" id="clone-role-name" placeholder="HR Assistant" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
        </div>
      </div>
      <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:18px;">
        <button class="btn btn-secondary" onclick="closeModals()">Cancel</button>
        <button class="btn btn-primary" onclick="submitCloneRole()">Clone Now</button>
      </div>
    </div>
  </div>

  <!-- Edit User Modal -->
  <div class="modal-overlay" id="modal-edit-user">
    <div class="modal-card" style="width: 520px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3 style="font-size:16px; font-weight:800;">Edit User & Kiosk Credentials</h3>
        <button onclick="closeEditUserModal()" style="background:none; border:none; font-size:18px; cursor:pointer;" style="background:none; border:none; font-size:16px; font-weight:700; cursor:pointer; color:#64748b;">Close</button>
      </div>
      <form autocomplete="off" onsubmit="event.preventDefault(); submitEditUser();">
        <input type="hidden" id="edit-user-id">
        <div style="display:flex; flex-direction:column; gap:12px; font-size:13px;">
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Full Name *</label>
            <input type="text" id="edit-user-name" name="edit_user_name_fld" autocomplete="off" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Email ID (Login Credential) *</label>
            <input type="email" id="edit-user-email" name="edit_user_email_fld" autocomplete="off" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
          </div>
          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Role</label>
              <select id="edit-user-role" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
                <option value="supervisor">Supervisor</option>
                <option value="hr_manager">HR Manager</option>
                <option value="super_admin">Super Admin</option>
              </select>
            </div>
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Supervisor 4-Digit PIN</label>
              <input type="text" id="edit-user-pin" name="edit_user_pin_fld" autocomplete="off" maxlength="4" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
            </div>
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Assigned Factory Unit</label>
            <select id="edit-user-unit" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
              <?php foreach ($portalUnits as $u): ?>
                <option value="<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars($u['id']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Change Password <span style="font-weight:400; color:#888;">(leave blank to keep current)</span></label>
            <div style="position:relative;">
              <input type="password" id="edit-user-pass" name="edit_user_pass_fld" autocomplete="new-password" placeholder="Enter new password" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px 36px 8px 8px;">
              <button type="button" onclick="togglePassVisibility('edit-user-pass', this)" title="Show/Hide password" style="position:absolute; right:8px; top:7px; background:none; border:none; cursor:pointer; padding:2px; display:flex; align-items:center;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; display:inline-block;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></button>
            </div>
          </div>
          <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; display:flex; justify-content:space-between; align-items:center;">
            <div>
              <div style="font-weight:700; font-size:11px; color:#64748b; letter-spacing:0.5px;">HARDWARE DEVICE LOCK STATUS</div>
              <div id="edit-user-device-status" style="font-size:12.5px; font-weight:700; margin-top:2px;">(unbound)</div>
            </div>
            <button id="edit-user-release-btn" class="btn btn-secondary" style="font-size:11px; padding:4px 10px; color:#b91c1c; border-color:#fca5a5; display:none;" onclick="releaseDeviceLockFromEdit()">Release Lock</button>
          </div>
          <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:12px;">
            <button type="button" class="btn btn-secondary" onclick="closeEditUserModal()">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Dedicated Reset Password Modal -->
  <div class="modal-overlay" id="modal-reset-pass">
    <div class="modal-card" style="width: 420px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
        <h3 style="font-size:16px; font-weight:800;">Reset Password</h3>
        <button onclick="closeResetPassModal()" style="background:none; border:none; font-size:18px; cursor:pointer;" style="background:none; border:none; font-size:16px; font-weight:700; cursor:pointer; color:#64748b;">Close</button>
      </div>
      <form autocomplete="off" onsubmit="event.preventDefault(); submitResetPass();">
        <input type="hidden" id="reset-pass-user-id">
        <div style="margin-bottom:14px; background:#f1f5f9; padding:8px 12px; border-radius:6px; font-size:12.5px;">
          User: <strong id="reset-pass-user-email"></strong>
        </div>
        <div style="margin-bottom:16px;">
          <label style="display:block; font-weight:700; margin-bottom:4px; font-size:13px;">New Password *</label>
          <div style="position:relative;">
            <input type="password" id="reset-pass-input" name="reset_new_password_fld" autocomplete="new-password" placeholder="Enter new password" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px 36px 8px 8px; font-size:13px;">
            <button type="button" onclick="togglePassVisibility('reset-pass-input', this)" title="Show/Hide password" style="position:absolute; right:8px; top:7px; background:none; border:none; cursor:pointer; padding:2px; display:flex; align-items:center;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; display:inline-block;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></button>
          </div>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" class="btn btn-secondary" onclick="closeResetPassModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Password</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Confirm Action Modal -->
  <div class="modal-overlay" id="modal-confirm-action">
    <div class="modal-card" style="width: 400px; text-align:center;">
      <div style="font-size:24px; font-weight:800; color:#d97706; margin-bottom:8px;">Warning</div>
      <h3 id="confirm-title" style="font-size:16px; font-weight:800; margin-bottom:8px;">Confirm Action</h3>
      <p id="confirm-message" style="font-size:13px; color:#64748b; margin-bottom:18px; line-height:1.4;"></p>
      <div style="display:flex; justify-content:center; gap:10px;">
        <button class="btn btn-secondary" onclick="closeConfirmModal()">Cancel</button>
        <button id="confirm-action-btn" class="btn btn-primary">Proceed</button>
      </div>
    </div>
  </div>

  <!-- Master Record Add/Edit Modal -->
  <div class="modal-overlay" id="modal-master-item">
    <div class="modal-card" style="max-width:500px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3 id="modal-master-title" style="font-size:16px; font-weight:800;">Add Master Record</h3>
        <button onclick="closeMasterModal()" style="background:none; border:none; font-size:16px; font-weight:700; cursor:pointer; color:#64748b;">✕ Close</button>
      </div>
      <input type="hidden" id="master-form-id">
      <input type="hidden" id="master-form-type">
      <div id="master-dynamic-fields" style="display:flex; flex-direction:column; gap:12px; font-size:13px;">
        <!-- Injected dynamically by openAddMasterModal / openEditMasterModal -->
      </div>
      <div style="margin-top:12px;">
        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
          <input type="checkbox" id="master-form-active" checked style="width:16px; height:16px;">
          <span style="font-size:13px; font-weight:700; color:#1e293b;">Mark as Active</span>
        </label>
      </div>
      <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
        <button class="btn btn-secondary" onclick="closeMasterModal()">Cancel</button>
        <button class="btn btn-primary" onclick="submitMasterItem()" id="btn-save-master-item">Save Record</button>
      </div>
    </div>
  </div>

  <!-- Master CSV Upload Modal -->
  <div class="modal-overlay" id="modal-master-upload">
    <div class="modal-card" style="max-width:480px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
        <h3 style="font-size:16px; font-weight:800;">Upload Master CSV / Excel</h3>
        <button onclick="closeMasterUploadModal()" style="background:none; border:none; font-size:16px; font-weight:700; cursor:pointer; color:#64748b;">✕ Close</button>
      </div>
      <p style="font-size:12.5px; color:var(--text-muted); margin-bottom:14px;">
        Bulk upload or update records in <strong id="upload-master-name-label">Master</strong>. The CSV header will match automatically.
      </p>
      <div style="border:2px dashed var(--border); border-radius:10px; padding:20px; text-align:center; background:#f8fafc; margin-bottom:16px;">
        <input type="file" id="master-csv-file" accept=".csv" style="display:block; margin:0 auto; font-size:13px;">
      </div>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px;">
        <button class="btn btn-secondary" onclick="exportMasterCsv()" style="font-size:12px;">📥 Template CSV</button>
        <div style="display:flex; gap:10px;">
          <button class="btn btn-secondary" onclick="closeMasterUploadModal()">Cancel</button>
          <button class="btn btn-primary" onclick="submitMasterCsvUpload()" id="btn-upload-master-submit">Upload & Save</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add User Modal -->
  <div class="modal-overlay" id="modal-add-user">
    <div class="modal-card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3 style="font-size:16px; font-weight:800;">Create Login User & Kiosk Credential</h3>
        <button onclick="closeAddUserModal()" style="background:none; border:none; font-size:18px; cursor:pointer;" style="background:none; border:none; font-size:16px; font-weight:700; cursor:pointer; color:#64748b;">Close</button>
      </div>
      <form autocomplete="off" onsubmit="event.preventDefault(); submitAddUser();">
        <div style="display:flex; flex-direction:column; gap:12px; font-size:13px;">
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Full Name *</label>
            <input type="text" id="new-user-name" name="new_user_name_fld" autocomplete="off" placeholder="e.g. Ramesh Kumar" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Email ID (Login Credential) *</label>
            <input type="email" id="new-user-email" name="new_user_email_fld" autocomplete="off" placeholder="e.g. ramesh@troogood.com" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Password *</label>
            <div style="position:relative;">
              <input type="password" id="new-user-pass" name="new_user_pass_fld" autocomplete="new-password" placeholder="Enter initial login password" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px 36px 8px 8px;">
              <button type="button" onclick="togglePassVisibility('new-user-pass', this)" title="Show/Hide password" style="position:absolute; right:8px; top:7px; background:none; border:none; cursor:pointer; padding:2px; display:flex; align-items:center;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; display:inline-block;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></button>
            </div>
          </div>
          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Role</label>
              <select id="new-user-role" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
                <option value="supervisor" selected>Supervisor</option>
                <option value="hr_manager">HR Manager</option>
                <option value="super_admin">Super Admin</option>
              </select>
            </div>
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Supervisor 4-Digit PIN</label>
              <input type="text" id="new-user-pin" name="new_user_pin_fld" autocomplete="off" value="1234" maxlength="4" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
            </div>
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Assigned Factory Unit</label>
            <select id="new-user-unit" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
              <?php foreach ($portalUnits as $u): ?>
                <option value="<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars($u['id']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:14px;">
            <button type="button" class="btn btn-secondary" onclick="closeAddUserModal()">Cancel</button>
            <button type="submit" class="btn btn-primary">Save & Create User</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Attendance Override Modal -->
  <div class="modal-overlay" id="modal-attendance-override">
    <div class="modal-card" style="width: 440px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3 style="font-size:16px; font-weight:800;">Override Attendance Status</h3>
        <button onclick="closeAttendanceOverrideModal()" style="background:none; border:none; font-size:16px; font-weight:700; cursor:pointer; color:#64748b;">Close</button>
      </div>
      <input type="hidden" id="override-worker-id">
      <input type="hidden" id="override-date">
      <input type="hidden" id="override-day">
      <div style="margin-bottom:14px; background:#f1f5f9; padding:10px 14px; border-radius:6px; font-size:13px; line-height:1.4;">
        Day: <strong id="override-day-label">Day 1</strong> &nbsp;|&nbsp; Current Status: <span id="override-cur-status-badge" class="badge badge-p">P</span>
      </div>
      <div style="margin-bottom:14px;">
        <label style="display:block; font-weight:700; margin-bottom:6px; font-size:13px;">Select New Status *</label>
        <select id="override-status-select" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:9px; font-size:13.5px; font-weight:600;">
          <option value="P">P — Present (Full Day)</option>
          <option value="HD">HD — Half Day</option>
          <option value="A">A — Absent</option>
          <option value="W">W — Weekly Off</option>
          <option value="L">L — Approved Leave</option>
        </select>
      </div>
      <div style="margin-bottom:14px;">
        <label style="display:block; font-weight:700; margin-bottom:6px; font-size:13px;">Factory Unit (Where Attendance Was Recorded) *</label>
        <select id="override-unit-select" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:9px; font-size:13.5px; font-weight:600;">
          <?php foreach ($portalUnits as $u): ?>
            <option value="<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars($u['id']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="margin-bottom:18px;">
        <label style="display:block; font-weight:700; margin-bottom:6px; font-size:13px;">Reason for Override (Audit Trail) *</label>
        <input type="text" id="override-reason-input" value="Supervisor manual adjustment" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:9px; font-size:13px;">
      </div>
      <div style="display:flex; justify-content:flex-end; gap:8px;">
        <button class="btn btn-secondary" onclick="closeAttendanceOverrideModal()">Cancel</button>
        <button class="btn btn-primary" onclick="submitAttendanceOverride()">Save Override</button>
      </div>
    </div>
  </div>

  <!-- Attendance CSV Upload Modal -->
  <div class="modal-overlay" id="modal-attendance-upload">
    <div class="modal-card" style="width: 500px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3 style="font-size:16px; font-weight:800; color:#0f172a;">Upload Attendance CSV</h3>
        <button onclick="closeAttendanceUploadModal()" style="background:none; border:none; font-size:16px; font-weight:700; cursor:pointer; color:#64748b;">Close</button>
      </div>
      <p style="font-size:12.5px; color:#64748b; margin-bottom:14px; line-height:1.4;">
        Upload a monthly attendance register CSV. The uploaded file must match the same format exported by <strong>Export CSV</strong>.
      </p>
      <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 14px; margin-bottom:16px; font-size:12.5px;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <span>Target Month: <strong id="upload-modal-month-label" style="color:#0f172a;">October 2026</strong></span>
          <button class="btn btn-secondary" style="padding:4px 10px; font-size:11.5px;" onclick="exportAttendanceCsv()">Download Current CSV</button>
        </div>
      </div>
      <div style="margin-bottom:16px;">
        <label style="display:block; font-weight:700; margin-bottom:6px; font-size:13px;">Select Edited CSV File *</label>
        <input type="file" id="attendance-csv-file-input" accept=".csv" style="width:100%; border:1px dashed #cbd5e1; border-radius:8px; padding:14px; background:#fff; font-size:12.5px; cursor:pointer;">
      </div>
      <div style="font-size:11.5px; color:#64748b; margin-bottom:18px; line-height:1.4; background:#f1f5f9; padding:8px 12px; border-radius:6px;">
        <strong>Column format:</strong> <code>ID, Employee Code, Employee Name, Unit, Shift, 1, 2, ..., 31, Present, Sundays, Total</code><br>
        <strong>Valid Day Statuses:</strong> <code>P</code> (Present), <code>HD</code> (Half Day), <code>A</code> (Absent), <code>W</code> (Weekly Off), <code>L</code> (Leave)
      </div>
      <div style="display:flex; justify-content:flex-end; gap:8px;">
        <button class="btn btn-secondary" onclick="closeAttendanceUploadModal()">Cancel</button>
        <button class="btn btn-primary" id="btn-submit-attendance-upload" onclick="submitAttendanceUpload()">Upload & Apply</button>
      </div>
    </div>
  </div>

  <!-- Employee Attendance Detail Breakdown Modal -->
  <div class="modal-overlay" id="modal-employee-attendance-detail">
    <div class="modal-card" style="width: 840px; max-width: 95vw; max-height: 90vh; display: flex; flex-direction: column; padding: 22px; border-radius: 14px; box-shadow: 0 20px 45px rgba(0,0,0,0.2);">
      <!-- Header -->
      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px; border-bottom:1px solid #e2e8f0; padding-bottom:14px;">
        <div style="display:flex; align-items:center; gap:12px;">
          <div id="ead-avatar" style="width:48px; height:48px; border-radius:12px; background:linear-gradient(135deg, #2563eb, #1d4ed8); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:20px; box-shadow:0 4px 10px rgba(37,99,235,0.25);">
            W
          </div>
          <div>
            <div style="display:flex; align-items:center; gap:8px;">
              <h3 id="ead-full-name" style="font-size:17px; font-weight:800; color:#0f172a; margin:0;">Employee Name</h3>
              <span id="ead-code-badge" style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:800; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; padding:2px 7px; border-radius:5px;">#101</span>
              <span id="ead-unit-badge" class="badge-chip">TGK-2</span>
            </div>
            <div id="ead-subtitle" style="font-size:12px; color:#64748b; margin-top:3px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
              <span>Shift: <strong id="ead-shift">General Shift</strong></span>
              <span>Dept: <strong id="ead-dept">Operations</strong></span>
              <span>Phone: <strong id="ead-phone" style="color:#0f172a;">--</strong></span>
              <span>Target Month: <strong id="ead-month" style="color:var(--primary);">October 2026</strong></span>
            </div>
          </div>
        </div>
        <button onclick="closeEmployeeAttendanceDetail()" style="background:#f1f5f9; border:none; width:32px; height:32px; border-radius:8px; font-size:16px; font-weight:700; cursor:pointer; color:#64748b; display:flex; align-items:center; justify-content:center; transition:background 0.15s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">✕</button>
      </div>

      <!-- KPI Summary Cards -->
      <div style="display:grid; grid-template-columns: repeat(6, 1fr); gap:8px; margin-bottom:14px;">
        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:8px; text-align:center;">
          <div id="ead-kpi-p" style="font-size:18px; font-weight:900; color:#15803d;">0</div>
          <div style="font-size:9.5px; font-weight:800; color:#166534; letter-spacing:0.3px; margin-top:2px;">PRESENT (P)</div>
        </div>
        <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:8px; text-align:center;">
          <div id="ead-kpi-a" style="font-size:18px; font-weight:900; color:#b91c1c;">0</div>
          <div style="font-size:9.5px; font-weight:800; color:#991b1b; letter-spacing:0.3px; margin-top:2px;">ABSENT (A)</div>
        </div>
        <div style="background:#fefce8; border:1px solid #fef08a; border-radius:8px; padding:8px; text-align:center;">
          <div id="ead-kpi-w" style="font-size:18px; font-weight:900; color:#854d0e;">0</div>
          <div style="font-size:9.5px; font-weight:800; color:#713f12; letter-spacing:0.3px; margin-top:2px;">SUNDAYS (W)</div>
        </div>
        <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:8px; padding:8px; text-align:center;">
          <div id="ead-kpi-hd" style="font-size:18px; font-weight:900; color:#c2410c;">0</div>
          <div style="font-size:9.5px; font-weight:800; color:#9a3412; letter-spacing:0.3px; margin-top:2px;">HALF DAYS (HD)</div>
        </div>
        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:8px; text-align:center;">
          <div id="ead-kpi-total-days" style="font-size:18px; font-weight:900; color:#1d4ed8;">0</div>
          <div style="font-size:9.5px; font-weight:800; color:#1e40af; letter-spacing:0.3px; margin-top:2px;">PAID DAYS</div>
        </div>
        <div style="background:#faf5ff; border:1px solid #e9d5ff; border-radius:8px; padding:8px; text-align:center;">
          <div id="ead-kpi-hours" style="font-size:18px; font-weight:900; color:#7e22ce;">0h</div>
          <div style="font-size:9.5px; font-weight:800; color:#6b21a8; letter-spacing:0.3px; margin-top:2px;">TOTAL HOURS</div>
        </div>
      </div>

      <!-- Quick Sub-Filter Bar -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
        <div style="display:flex; gap:6px;">
          <button type="button" class="btn btn-secondary ead-filter-btn active" onclick="filterEadDays('ALL', this)" style="padding:4px 10px; font-size:11.5px; font-weight:700;">All Days</button>
          <button type="button" class="btn btn-secondary ead-filter-btn" onclick="filterEadDays('P', this)" style="padding:4px 10px; font-size:11.5px; font-weight:700;">Present Only</button>
          <button type="button" class="btn btn-secondary ead-filter-btn" onclick="filterEadDays('A', this)" style="padding:4px 10px; font-size:11.5px; font-weight:700;">Absent Only</button>
          <button type="button" class="btn btn-secondary ead-filter-btn" onclick="filterEadDays('W', this)" style="padding:4px 10px; font-size:11.5px; font-weight:700;">Sundays Only</button>
        </div>
        <div style="font-size:11px; color:#64748b;">
          * Punch IN = Present (Full Day). Punch OUT = Hours calculation only.
        </div>
      </div>

      <!-- Table Scroll Area -->
      <div style="flex:1; max-height:420px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:8px; background:#fff;">
        <table style="width:100%; border-collapse:collapse; font-size:12px; text-align:left;">
          <thead style="position:sticky; top:0; background:#f8fafc; z-index:2; border-bottom:1px solid #e2e8f0;">
            <tr>
              <th style="padding:8px 12px; font-weight:700; color:#475569; width:130px;">Date & Day</th>
              <th style="padding:8px 10px; font-weight:700; color:#475569; text-align:center; width:80px;">Status</th>
              <th style="padding:8px 10px; font-weight:700; color:#475569; text-align:center; width:90px;">Punch IN</th>
              <th style="padding:8px 10px; font-weight:700; color:#475569; text-align:center; width:90px;">Punch OUT</th>
              <th style="padding:8px 10px; font-weight:700; color:#475569; text-align:center; width:100px;">Working Hours</th>
              <th style="padding:8px 10px; font-weight:700; color:#475569; text-align:center; width:80px;">Unit</th>
              <th style="padding:8px 12px; font-weight:700; color:#475569;">Audit Remarks</th>
              <th style="padding:8px 10px; font-weight:700; color:#475569; text-align:center; width:70px;">Action</th>
            </tr>
          </thead>
          <tbody id="ead-table-body">
            <!-- Rendered dynamically -->
          </tbody>
        </table>
      </div>

      <!-- Modal Footer -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px; padding-top:10px; border-top:1px solid #f1f5f9;">
        <div style="font-size:11.5px; color:#64748b;">
          Click <strong>Edit</strong> on any day row to make a supervisor override or manual correction.
        </div>
        <button class="btn btn-secondary" onclick="closeEmployeeAttendanceDetail()">Close Window</button>
      </div>
    </div>
  </div>

  <!-- Login & Switch Account Modal -->
  <div class="modal-overlay" id="modal-portal-login">
    <div class="modal-card" style="width: 440px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <div style="display:flex; align-items:center; gap:10px;">
          <div style="width:36px; height:36px; border-radius:8px; background:linear-gradient(135deg, #2563eb, #1d4ed8); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:15px;">TG</div>
          <div>
            <h3 style="font-size:16px; font-weight:800; color:#0f172a; line-height:1.2;">TrooGood HRMS Authentication</h3>
            <div style="font-size:12px; color:#64748b;">Sign in with Email & Password or Supervisor PIN</div>
          </div>
        </div>
        <button onclick="closeModals()" style="background:none; border:none; font-size:16px; font-weight:700; cursor:pointer; color:#64748b;">Close</button>
      </div>

      <form autocomplete="off" onsubmit="event.preventDefault(); submitPortalLogin();">
        <div style="display:flex; flex-direction:column; gap:12px; font-size:13px;">
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Email ID or Username *</label>
            <input type="text" id="login-identifier" name="portal_login_user" autocomplete="username" placeholder="admin@troogood.com or employee email" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:9px; font-size:13px;">
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Password or 4-Digit PIN *</label>
            <div style="position:relative;">
              <input type="password" id="login-password" name="portal_login_pass" autocomplete="current-password" placeholder="Enter password or supervisor PIN" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:9px 36px 9px 9px; font-size:13px;">
              <button type="button" onclick="togglePassVisibility('login-password', this)" title="Show/Hide password" style="position:absolute; right:8px; top:8px; background:none; border:none; cursor:pointer; padding:2px; display:flex; align-items:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              </button>
            </div>
          </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; padding-top:14px; border-top:1px solid #f1f5f9;">
          <span style="font-size:11.5px; color:#64748b;">Super Admin: admin@troogood.com / PIN: 9999</span>
          <button type="submit" class="btn btn-primary" style="padding:8px 20px;">Sign In</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Add / Edit Holiday -->
  <div class="modal-overlay" id="modal-holiday-edit">
    <div class="modal-card" style="max-width: 500px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
        <h3 style="font-size:16px; font-weight:800;" id="holiday-modal-title">Add Holiday Record</h3>
        <button type="button" class="btn btn-secondary" onclick="closeModals()" style="padding:4px 8px; font-size:12px;">✕</button>
      </div>
      <form id="form-holiday-edit" onsubmit="event.preventDefault(); submitHolidayForm();">
        <input type="hidden" id="he-id" value="">
        <div style="display:flex; flex-direction:column; gap:12px; font-size:13px;">
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Holiday Date *</label>
            <input type="date" id="he-date" required style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-weight:700;">
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Holiday Name *</label>
            <input type="text" id="he-name" required placeholder="e.g. Independence Day, Diwali, New Year" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-weight:700;">
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Category / Type *</label>
              <select id="he-type" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-weight:700;">
                <option value="National">National Holiday</option>
                <option value="Festival" selected>Festival Holiday</option>
                <option value="Company">Company Holiday</option>
                <option value="Regional">Regional / State Holiday</option>
                <option value="Optional">Optional / Restricted</option>
              </select>
            </div>
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Factory Scope</label>
              <select id="he-unit" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-weight:700;">
                <option value="ALL">All Factory Units (Company-wide)</option>
                <?php foreach ($portalUnits as $u): ?>
                  <option value="<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars($u['id']) ?> Only</option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Description / Significance</label>
            <input type="text" id="he-desc" placeholder="Brief note or statutory remark..." style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px;">
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Status</label>
            <select id="he-active" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-weight:700;">
              <option value="1">Active Declared Holiday</option>
              <option value="0">Inactive / Suspended</option>
            </select>
          </div>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:14px; border-top:1px solid #f1f5f9;">
          <button type="button" class="btn btn-secondary" onclick="closeModals()">Cancel</button>
          <button type="submit" class="btn btn-primary" id="btn-save-holiday">Save Holiday</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Upload Holidays CSV -->
  <div class="modal-overlay" id="modal-holiday-upload">
    <div class="modal-card" style="max-width: 480px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
        <h3 style="font-size:16px; font-weight:800;">Bulk Upload Holiday Calendar CSV</h3>
        <button type="button" class="btn btn-secondary" onclick="closeModals()" style="padding:4px 8px; font-size:12px;">✕</button>
      </div>
      <p style="font-size:12.5px; color:#475569; margin-bottom:16px; line-height:1.4;">
        Upload a yearly CSV spreadsheet with columns: <code>holiday_date</code> (YYYY-MM-DD), <code>holiday_name</code>, <code>holiday_type</code>, <code>unit_id</code>, <code>description</code>.
      </p>
      <form id="form-holiday-upload" onsubmit="event.preventDefault(); submitUploadHolidays();">
        <div style="display:flex; flex-direction:column; gap:14px;">
          <div style="border:2px dashed #cbd5e1; border-radius:8px; padding:20px; text-align:center; background:#f8fafc;">
            <input type="file" id="holiday-csv-file" accept=".csv" required style="font-size:13px;">
          </div>
          <div style="font-size:11.5px; color:#64748b;">
            💡 Need the template? <a href="/api/holidays/template" download style="color:#2563eb; font-weight:700; text-decoration:underline;">Click here to download sample CSV template</a>.
          </div>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:14px; border-top:1px solid #f1f5f9;">
          <button type="button" class="btn btn-secondary" onclick="closeModals()">Cancel</button>
          <button type="submit" class="btn btn-primary" id="btn-upload-holiday-submit">Upload & Apply</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Invite Candidate for Standalone Onboarding -->
  <div class="modal-overlay" id="modal-invite-candidate">
    <div class="modal-card" style="max-width: 520px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
        <h3 id="inv-modal-title" style="font-size:16px; font-weight:800;">Invite Candidate for Onboarding</h3>
        <button type="button" class="btn btn-secondary" onclick="closeModals()" style="padding:4px 8px; font-size:12px;">✕</button>
      </div>
      <p id="inv-modal-desc" style="font-size:12px; color:#64748b; margin-bottom:14px; line-height:1.4;">
        Generate an invitation link. The candidate will complete their 6-step employee submission form on a standalone page.
      </p>

      <form id="form-invite-candidate" onsubmit="event.preventDefault(); submitCandidateInvite();">
        <div style="display:flex; flex-direction:column; gap:12px; font-size:13px;">
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Candidate Full Name *</label>
            <input type="text" id="inv-name" required placeholder="e.g. Ramesh Kumar" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-weight:700;">
          </div>
          <div id="inv-email-password-row" style="display:grid; grid-template-columns:1fr; gap:12px;">
            <div id="inv-email-wrapper">
              <label style="display:block; font-weight:700; margin-bottom:4px;">Email Address *</label>
              <input type="email" id="inv-email" required placeholder="ramesh@gmail.com" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px;">
            </div>
            <div id="inv-password-wrapper" style="display:none;">
              <label style="display:block; font-weight:700; margin-bottom:4px;">App Password / Access PIN *</label>
              <input type="text" id="inv-password" value="TrooGood@2026" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-weight:700; font-family:monospace;">
            </div>
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Mobile Phone</label>
              <input type="tel" id="inv-phone" placeholder="9876543210" maxlength="10" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px;">
            </div>
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Target Factory Unit *</label>
              <select id="inv-unit" required style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-weight:700;">
                <option value="">Loading synced factory units...</option>
              </select>
            </div>
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Department (from master) *</label>
              <select id="inv-department" required onchange="filterInviteDesignations()" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-weight:700;">
                <option value="">Loading departments...</option>
              </select>
            </div>
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Role / Designation (from master) *</label>
              <select id="inv-designation" required style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-weight:700;">
                <option value="">Select Department First</option>
              </select>
            </div>
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Contractor Agency (from master)</label>
            <select id="inv-contractor" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-weight:600;">
              <option value="TrooGood In-House">TrooGood In-House (Company Rolls)</option>
              <option value="Consultant">Consultant</option>
              <option value="Intern">Intern</option>
            </select>
          </div>
        </div>

        <!-- Generated Invite Result Box (shown after API responds) -->
        <div id="inv-result-box" style="display:none; margin-top:16px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:12px 14px;">
          <div id="inv-result-title" style="font-size:12px; font-weight:800; color:#166534; margin-bottom:4px;">✅ Candidate Invited Successfully!</div>
          <div id="inv-result-desc" style="font-size:11.5px; color:#14532d; margin-bottom:8px;">Share this standalone submission URL with the candidate:</div>
          <div style="display:flex; gap:8px;">
            <input type="text" id="inv-result-url" readonly style="flex:1; font-size:12px; font-family:monospace; background:#fff; border:1px solid #cbd5e1; padding:6px 10px; border-radius:6px;">
            <button type="button" class="btn btn-secondary" onclick="copyInviteUrl()" style="padding:6px 12px; font-size:12px; white-space:nowrap;">📋 Copy Link</button>
            <a id="inv-open-link" href="#" target="_blank" class="btn btn-primary" style="padding:6px 12px; font-size:12px; text-decoration:none; white-space:nowrap;">🚀 Open</a>
          </div>
          <div id="inv-result-cred-box" style="display:none; margin-top:10px; background:#ffffff; border:1px solid #86efac; border-radius:6px; padding:8px 12px; font-size:12px; color:#166534;">
            <div><strong>App Login Email:</strong> <span id="inv-result-cred-email"></span></div>
            <div><strong>App Password / Access PIN:</strong> <code id="inv-result-cred-password" style="font-family:monospace; font-weight:700;"></code></div>
          </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:14px; border-top:1px solid #f1f5f9;">
          <button type="button" class="btn btn-secondary" onclick="closeModals()">Close</button>
          <button type="submit" class="btn btn-primary" id="btn-invite-submit">Generate Invite & Link</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Configure Individual Employee App Access -->
  <div class="modal-overlay" id="modal-individual-access">
    <div class="modal-card" style="max-width: 480px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
        <h3 style="font-size:16px; font-weight:800;">Configure Individual App Access</h3>
        <button type="button" class="btn btn-secondary" onclick="closeModals()" style="padding:4px 8px; font-size:12px;">✕</button>
      </div>
      <p style="font-size:12.5px; color:#475569; margin-bottom:14px; line-height:1.4;">
        Enable personal mobile app attendance punching for this employee. Provide login credentials and send the APK download link.
      </p>
      <form id="form-individual-access" onsubmit="event.preventDefault(); submitIndividualAccess();">
        <input type="hidden" id="ind-worker-id" value="">
        <div style="display:flex; flex-direction:column; gap:12px; font-size:13px;">
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Employee Name</label>
            <input type="text" id="ind-worker-name" readonly style="width:100%; border:1px solid #e2e8f0; background:#f8fafc; border-radius:6px; padding:8px; font-weight:700;">
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">App Login Email ID *</label>
            <input type="email" id="ind-app-email" required placeholder="worker@troogoodhrms.com" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-weight:700;">
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">App Password / Access PIN *</label>
            <input type="text" id="ind-app-password" required value="TrooGood@App2026" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-weight:700; font-family:monospace;">
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">APK Download Link</label>
            <input type="text" id="ind-apk-link" value="https://troogoodhrms.com/downloads/TrooGood_HR_App.apk" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-size:12px; font-family:monospace;">
          </div>
          <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px; padding:10px 12px; font-size:11.5px; color:#1e40af;">
            ℹ️ When activated, the employee can log into their personal mobile app and punch attendance directly using GPS geolocation.
          </div>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:14px; border-top:1px solid #f1f5f9;">
          <button type="button" class="btn btn-secondary" onclick="closeModals()">Cancel</button>
          <button type="submit" class="btn btn-primary" id="btn-ind-access-save">Save & Dispatch Access</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: System Error Log Details -->
  <div class="modal-overlay" id="modal-error-log-detail">
    <div class="modal-card" style="max-width: 650px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
        <h3 style="font-size:16px; font-weight:800;">System Error Diagnostics & Stack Trace</h3>
        <button type="button" class="btn btn-secondary" onclick="closeModals()" style="padding:4px 8px; font-size:12px;">✕</button>
      </div>
      <div style="display:flex; flex-direction:column; gap:12px; font-size:13px;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; background:#f8fafc; padding:12px; border-radius:8px;">
          <div>Timestamp: <strong id="eld-time">--</strong></div>
          <div>Source: <span id="eld-source" class="badge">--</span></div>
          <div>Severity: <span id="eld-severity" class="badge">--</span></div>
          <div>Endpoint / Screen: <code id="eld-endpoint">--</code></div>
        </div>
        <div>
          <label style="font-weight:700; display:block; margin-bottom:4px;">Error Message</label>
          <div id="eld-msg" style="padding:10px; background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; border-radius:6px; font-weight:700; font-size:12.5px;">--</div>
        </div>
        <div>
          <label style="font-weight:700; display:block; margin-bottom:4px;">Stack Trace</label>
          <pre id="eld-stack" style="background:#0f172a; color:#f8fafc; padding:12px; border-radius:6px; font-size:11.5px; max-height:220px; overflow-y:auto; font-family:'JetBrains Mono',monospace; white-space:pre-wrap;"></pre>
        </div>
      </div>
      <div style="display:flex; justify-content:flex-end; margin-top:20px; padding-top:14px; border-top:1px solid #f1f5f9;">
        <button type="button" class="btn btn-primary" onclick="closeModals()">Close Diagnostics</button>
      </div>
    </div>
  </div>

  <!-- Modal: Broadcast Notification -->
  <div class="modal-overlay" id="modal-broadcast-notification">
    <div class="modal-card" style="max-width: 580px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
        <h3 style="font-size:16px; font-weight:800;">Broadcast Announcement</h3>
        <button type="button" class="btn btn-secondary" onclick="closeModals()" style="padding:4px 8px; font-size:12px;">✕</button>
      </div>
      <form id="form-broadcast-notif" onsubmit="event.preventDefault(); submitBroadcastNotification();">
        <div style="display:flex; flex-direction:column; gap:12px; font-size:13px;">
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Announcement Title *</label>
            <input type="text" id="bn-title" required placeholder="e.g. Factory Shift Reschedule Notice" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-weight:700;">
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Message Body *</label>
            <textarea id="bn-message" required rows="3" placeholder="Type notification content..." style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px;"></textarea>
          </div>
          <div>
            <label style="display:block; font-weight:700; margin-bottom:4px;">Priority Type</label>
            <select id="bn-type" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-weight:700;">
              <option value="ANNOUNCEMENT">Announcement (Normal)</option>
              <option value="URGENT">Urgent Alert</option>
              <option value="NOTICE">Notice / Memo</option>
            </select>
          </div>
          
          <!-- Multiple Factories Selector -->
          <div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
              <label style="display:block; font-weight:700; margin-bottom:0;">Target Factory Units *</label>
              <div style="display:flex; gap:6px;">
                <button type="button" class="btn btn-secondary" onclick="toggleAllBroadcastUnits(true)" style="padding:2px 8px; font-size:11px; font-weight:700; background:#fff;">Select All</button>
                <button type="button" class="btn btn-secondary" onclick="toggleAllBroadcastUnits(false)" style="padding:2px 8px; font-size:11px; background:#fff;">Clear All</button>
              </div>
            </div>

            <div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:10px 12px;">
              <!-- All Factories Master Checkbox -->
              <label style="display:flex; align-items:center; gap:8px; padding:7px 10px; background:#eff6ff; border:1.5px solid #93c5fd; border-radius:6px; margin-bottom:8px; cursor:pointer; font-weight:700; color:#1e40af; font-size:12.5px; user-select:none;">
                <input type="checkbox" id="bn-unit-all" onchange="handleBroadcastAllUnitsChange(this.checked)" checked style="width:16px; height:16px; accent-color:#2563eb; cursor:pointer;">
                <span>🌐 All Factory Units (Company-Wide)</span>
              </label>

              <!-- Individual Factory Checkboxes Grid -->
              <div id="broadcast-units-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap:6px; max-height:140px; overflow-y:auto; padding-right:2px;">
                <!-- Populated dynamically via renderBroadcastUnitsGrid() -->
              </div>
            </div>

            <!-- Dynamic Selected Summary -->
            <div id="bn-selected-summary" style="font-size:11.5px; color:#475569; margin-top:6px; font-weight:600; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
              <span>Selected:</span>
              <span class="badge" style="background:#eff6ff; color:#1d4ed8; font-weight:700;">All Factory Units (Global)</span>
            </div>
          </div>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:14px; border-top:1px solid #f1f5f9;">
          <button type="button" class="btn btn-secondary" onclick="closeModals()">Cancel</button>
          <button type="submit" class="btn btn-primary" id="btn-broadcast-submit">Broadcast to Kiosks & Portal</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Outbound Mail Server (SMTP) Settings -->
  <div class="modal-overlay" id="modal-smtp-settings">
    <div class="modal-card" style="max-width: 640px; width: 100%; border-radius: 14px; padding: 26px; box-shadow: 0 20px 40px rgba(0,0,0,0.18);">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 20px;">🛠️</span>
          <h3 style="font-size: 19px; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.01em;">
            Outbound Mail Server (SMTP) Settings
          </h3>
        </div>
        <button type="button" onclick="closeSmtpModal()" style="background: none; border: none; font-size: 18px; color: #64748b; cursor: pointer; padding: 2px 6px; border-radius: 6px; line-height: 1;">✕</button>
      </div>
      <p style="font-size: 12.5px; color: #64748b; margin: 0 0 20px 0; line-height: 1.4;">
        Configure outgoing SMTP credentials to route daily operations reports dynamically.
      </p>

      <form id="form-smtp-settings" onsubmit="event.preventDefault(); submitSmtpSettings();">
        <div style="display: flex; flex-direction: column; gap: 14px;">
          <!-- Row 1: Host (2 cols), Port (1 col), Encryption (1 col) -->
          <div style="display: grid; grid-template-columns: 2.2fr 1fr 1fr; gap: 12px;">
            <div>
              <label style="display: block; font-weight: 700; font-size: 12.5px; color: #0f172a; margin-bottom: 5px;">
                SMTP Outgoing Host <span style="color: #ef4444;">*</span>
              </label>
              <input type="text" id="smtp-host" required value="troogood.in" style="width: 100%; border: 1.5px solid #cbd5e1; border-radius: 8px; padding: 9px 12px; font-size: 13.5px; font-weight: 600; color: #0f172a; outline: none;">
            </div>
            <div>
              <label style="display: block; font-weight: 700; font-size: 12.5px; color: #0f172a; margin-bottom: 5px;">
                Port <span style="color: #ef4444;">*</span>
              </label>
              <input type="number" id="smtp-port" required value="465" style="width: 100%; border: 1.5px solid #cbd5e1; border-radius: 8px; padding: 9px 12px; font-size: 13.5px; font-weight: 600; color: #0f172a; outline: none;">
            </div>
            <div>
              <label style="display: block; font-weight: 700; font-size: 12.5px; color: #0f172a; margin-bottom: 5px;">
                Encryption
              </label>
              <select id="smtp-encryption" style="width: 100%; border: 1.5px solid #cbd5e1; border-radius: 8px; padding: 9px 12px; font-size: 13.5px; font-weight: 600; color: #0f172a; background: #fff; outline: none; cursor: pointer;">
                <option value="SSL" selected>SSL</option>
                <option value="TLS">TLS</option>
                <option value="NONE">None</option>
              </select>
            </div>
          </div>

          <!-- Row 2: Username & Password -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div>
              <label style="display: block; font-weight: 700; font-size: 12.5px; color: #0f172a; margin-bottom: 5px;">
                SMTP Username
              </label>
              <input type="text" id="smtp-username" value="dailymails@troogood.in" style="width: 100%; border: 1.5px solid #cbd5e1; border-radius: 8px; padding: 9px 12px; font-size: 13.5px; font-weight: 600; color: #0f172a; outline: none;">
            </div>
            <div>
              <label style="display: block; font-weight: 700; font-size: 12.5px; color: #0f172a; margin-bottom: 5px;">
                SMTP Password
              </label>
              <div style="position: relative;">
                <input type="password" id="smtp-password" value="TgMail@1234$#@!" style="width: 100%; border: 1.5px solid #cbd5e1; border-radius: 8px; padding: 9px 36px 9px 12px; font-size: 13.5px; font-weight: 600; color: #0f172a; outline: none;">
                <button type="button" onclick="togglePassVisibility('smtp-password', this)" style="position: absolute; right: 8px; top: 8px; background: none; border: none; cursor: pointer; color: #64748b;">
                  👁️
                </button>
              </div>
            </div>
          </div>

          <!-- Row 3: From Address & From Name -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div>
              <label style="display: block; font-weight: 700; font-size: 12.5px; color: #0f172a; margin-bottom: 5px;">
                Sender "From" Address <span style="color: #ef4444;">*</span>
              </label>
              <input type="email" id="smtp-from-address" required value="dailymails@troogood.in" style="width: 100%; border: 1.5px solid #cbd5e1; border-radius: 8px; padding: 9px 12px; font-size: 13.5px; font-weight: 600; color: #0f172a; outline: none;">
            </div>
            <div>
              <label style="display: block; font-weight: 700; font-size: 12.5px; color: #0f172a; margin-bottom: 5px;">
                Sender "From" Name <span style="color: #ef4444;">*</span>
              </label>
              <input type="text" id="smtp-from-name" required value="Troo Good Alerts" style="width: 100%; border: 1.5px solid #cbd5e1; border-radius: 8px; padding: 9px 12px; font-size: 13.5px; font-weight: 600; color: #0f172a; outline: none;">
            </div>
          </div>

          <!-- Current Active Summary Banner -->
          <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; margin-top: 4px; font-size: 12.5px; color: #334155; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div>
              <strong>Current Active:</strong>
              <span id="smtp-preview-host" style="font-weight: 600; color: #0f172a;">troogood.in:465</span> &middot;
              <span id="smtp-preview-user" style="font-weight: 600; color: #0f172a;">dailymails@troogood.in</span>
              <span id="smtp-preview-enc" style="display: inline-block; background: #e0e7ff; color: #4338ca; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 12px; margin: 0 4px;">SSL</span> &middot;
              From: <em id="smtp-preview-from" style="color: #475569;">Troo Good Alerts &lt;dailymails@troogood.in&gt;</em>
            </div>
            <div style="display: flex; gap: 6px;">
              <button type="button" class="btn btn-secondary" onclick="sendTestSmtpEmail()" id="btn-smtp-test" style="padding: 4px 10px; font-size: 11.5px; font-weight: 700; background: #fff; border-color: #cbd5e1; cursor: pointer;">
                ✉️ Test Connection
              </button>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
          <button type="button" class="btn btn-secondary" onclick="closeSmtpModal()" style="padding: 9px 20px; font-weight: 600; background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; border-radius: 8px;">
            Cancel
          </button>
          <button type="submit" class="btn" id="btn-save-smtp" style="background: #4f46e5; color: #ffffff; padding: 9px 24px; font-weight: 700; border: none; border-radius: 8px; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3); display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
            💾 Save SMTP Settings
          </button>
        </div>
      </form>
    </div>
  </div>

  <?php include __DIR__ . '/universal_register_modal.php'; ?>

  <script>
    window.APP_UNITS = <?= json_encode($portalUnits ?? []) ?>;

    // -------------------------------------------------------------------------
    // AUTHENTICATION & PORTAL USER SESSION
    // -------------------------------------------------------------------------
    let currentAuthUser = null;

    // Global fetch interceptor to propagate current authenticated user headers to backend API
    (function setupFetchUserHeaders() {
      const _origFetch = window.fetch;
      window.fetch = function(input, init = {}) {
        init = init || {};
        const user = currentAuthUser || {
          full_name: 'System Super Admin',
          role: 'super_admin',
          email: 'admin@troogood.com'
        };
        
        if (!init.headers) {
          init.headers = {};
        }

        if (init.headers instanceof Headers) {
          if (!init.headers.has('X-User-Name')) init.headers.set('X-User-Name', user.full_name || 'System Super Admin');
          if (!init.headers.has('X-User-Role')) init.headers.set('X-User-Role', user.role || 'super_admin');
          if (!init.headers.has('X-User-Email')) init.headers.set('X-User-Email', user.email || 'admin@troogood.com');
        } else if (Array.isArray(init.headers)) {
          init.headers.push(['X-User-Name', user.full_name || 'System Super Admin']);
          init.headers.push(['X-User-Role', user.role || 'super_admin']);
          init.headers.push(['X-User-Email', user.email || 'admin@troogood.com']);
        } else {
          if (!init.headers['X-User-Name']) init.headers['X-User-Name'] = user.full_name || 'System Super Admin';
          if (!init.headers['X-User-Role']) init.headers['X-User-Role'] = user.role || 'super_admin';
          if (!init.headers['X-User-Email']) init.headers['X-User-Email'] = user.email || 'admin@troogood.com';
        }
        return _origFetch.call(this, input, init);
      };
    })();

    function initUserAuth() {
      try {
        const saved = localStorage.getItem('tg_portal_user');
        if (saved) {
          currentAuthUser = JSON.parse(saved);
        } else {
          // Default to Super Admin for initial access
          currentAuthUser = {
            id: 1,
            full_name: 'System Super Admin',
            email: 'admin@troogood.com',
            role: 'super_admin',
            role_name: 'Super Admin',
            supervisor_pin: '9999',
            unit_id: 'ALL',
            all_units: 1,
            allowed_units: 'ALL'
          };
          localStorage.setItem('tg_portal_user', JSON.stringify(currentAuthUser));
        }
        updateTopBarUserUI();
      } catch (err) {
        console.error('Error initializing user auth:', err);
      }
    }

    function updateTopBarUserUI() {
      if (!currentAuthUser) return;
      const initial = (currentAuthUser.full_name || 'SA').split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase() || 'SA';
      const isSuper = (currentAuthUser.role === 'super_admin');
      
      const avatarEl = document.getElementById('topbar-user-avatar');
      const nameEl = document.getElementById('topbar-user-name');
      const roleEl = document.getElementById('topbar-user-role');
      
      const menuNameEl = document.getElementById('menu-user-name');
      const menuEmailEl = document.getElementById('menu-user-email');
      const menuRoleBadge = document.getElementById('menu-user-role-badge');
      const menuUnitBadge = document.getElementById('menu-user-unit-badge');

      if (avatarEl) {
        avatarEl.innerText = initial;
        avatarEl.style.background = isSuper ? '#2563eb' : '#0891b2';
      }
      if (nameEl) nameEl.innerText = currentAuthUser.full_name || 'Portal User';
      if (roleEl) {
        roleEl.innerText = isSuper ? 'Super Admin' : (currentAuthUser.role_name || currentAuthUser.role);
        roleEl.style.color = isSuper ? '#2563eb' : '#0891b2';
      }

      if (menuNameEl) menuNameEl.innerText = currentAuthUser.full_name || 'Portal User';
      if (menuEmailEl) menuEmailEl.innerText = currentAuthUser.email || '-';
      if (menuRoleBadge) {
        menuRoleBadge.innerText = currentAuthUser.role_name || currentAuthUser.role;
        menuRoleBadge.className = isSuper ? 'badge badge-p' : 'badge badge-l';
      }
      if (menuUnitBadge) {
        menuUnitBadge.innerText = isSuper ? 'Scope: All Units' : `Unit: ${currentAuthUser.unit_id || 'TGK-2'}`;
      }
    }

    function toggleUserDropdown() {
      const menu = document.getElementById('topbar-user-menu');
      if (!menu) return;
      menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }

    function closeUserDropdown() {
      const menu = document.getElementById('topbar-user-menu');
      if (menu) menu.style.display = 'none';
    }

    // Close user dropdown when clicking outside
    document.addEventListener('click', (e) => {
      const wrapper = document.querySelector('.user-pill-wrapper');
      if (wrapper && !wrapper.contains(e.target)) {
        closeUserDropdown();
      }
    });

        function openLoginModal() {
      document.getElementById('login-identifier').value = '';
      document.getElementById('login-password').value = '';
      document.getElementById('modal-portal-login').classList.add('active');
    }

    async function submitPortalLogin() {
      const ident = document.getElementById('login-identifier').value.trim();
      const pass = document.getElementById('login-password').value.trim();

      if (!ident) {
        showToast('Please enter Email ID or username.', 'warning');
        return;
      }

      try {
        const res = await fetch('/api/portal/auth/login', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ identifier: ident, password: pass })
        });
        const json = await res.json();
        if (json.success && json.data) {
          currentAuthUser = json.data;
          localStorage.setItem('tg_portal_user', JSON.stringify(currentAuthUser));
          closeModals();
          updateTopBarUserUI();
          showToast(`Welcome back, ${currentAuthUser.full_name}! Signed in as ${currentAuthUser.role_name || currentAuthUser.role}.`, 'success');
          refreshCurrentView();
        } else {
          showToast('Login failed: ' + (json.message || 'Invalid credentials'), 'error');
        }
      } catch (err) {
        showToast('Authentication error: ' + err, 'error');
      }
    }

    

    function logoutPortal() {
      showConfirmModal(
        'Log Out of TrooGood HRMS',
        'Are you sure you want to log out from this browser session?',
        () => {
          localStorage.removeItem('tg_portal_user');
          currentAuthUser = null;
          showToast('You have been logged out successfully.', 'info');
          openLoginModal();
        }
      );
    }

    // Current Active Unit Scope
    let currentUnitScope = 'all';

    function changeUnitScope(u) {
      currentUnitScope = u;
      refreshCurrentView();
    }

    function toggleAggregateUnits() {
      const sel = document.getElementById('unit-filter');
      sel.value = 'all';
      changeUnitScope('all');
    }

    // View Switching with Persistent URL Hash & LocalStorage Navigation
    function switchView(viewName, skipHashUpdate = false) {
      if (!viewName) viewName = 'dashboard';
      viewName = viewName.replace(/^#/, '').trim();
      if (viewName === 'kiosk-management') viewName = 'portal-users';

      let targetPersonId = null;
      if (viewName.startsWith('person-detail/')) {
        const parts = viewName.split('/');
        targetPersonId = parts[1];
        viewName = 'person-detail';
      }

      document.querySelectorAll('.view-container').forEach(el => el.classList.remove('active'));
      document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
      
      const target = document.getElementById('view-' + viewName);
      if (target) {
        target.classList.add('active');
        target.scrollTop = 0;
      }

      const links = document.querySelectorAll('.nav-link');
      links.forEach(l => {
        if (viewName === 'person-detail' && l.getAttribute('onclick')?.includes("'people'")) {
          l.classList.add('active');
        } else if (l.getAttribute('onclick')?.includes(`'${viewName}'`)) {
          l.classList.add('active');
        } else if (viewName === 'portal-users' && l.getAttribute('onclick')?.includes("'portal-users'")) {
          l.classList.add('active');
        }
      });

      if (!skipHashUpdate) {
        const hashVal = (viewName === 'person-detail' && currentViewingPerson)
          ? `person-detail/${currentViewingPerson.id}`
          : (targetPersonId ? `person-detail/${targetPersonId}` : viewName);
        if (window.location.hash !== '#' + hashVal) {
          history.replaceState(null, '', '#' + hashVal);
        }
        localStorage.setItem('tg_active_view', hashVal);
      }

      if (viewName === 'person-detail') {
        if (targetPersonId && (!currentViewingPerson || currentViewingPerson.id != targetPersonId)) {
          viewPerson(targetPersonId);
        }
      } else {
        refreshCurrentView();
      }
    }

    function refreshCurrentView() {
      const active = document.querySelector('.view-container.active');
      if (!active) return;
      const id = active.id;
      if (id === 'view-dashboard') loadDashboard();
      else if (id === 'view-attendance') { loadShiftOptionsFromMaster(); loadAttendanceMatrix(); }
      else if (id === 'view-onboarding') loadOnboardingPipeline();
      else if (id === 'view-approvals') loadApprovals();
      else if (id === 'view-payroll') loadPayroll();
      else if (id === 'view-roles') loadRoles();
      else if (id === 'view-units-sync') loadUnitsSync();
      else if (id === 'view-audit-log') loadAuditTimeline();
      else if (id === 'view-portal-users') { loadPortalUsers(); loadKioskDevices(); }
      else if (id === 'view-shifts') loadShiftsMaster();
      else if (id === 'view-people') loadPeople();
      else if (id === 'view-masters') loadMasterData();
      else if (id === 'view-holidays') loadHolidays();
      else if (id === 'view-leave-calculator') loadLeaveCalculator();
      else if (id === 'view-error-logs') loadSystemLogs();
      else if (id === 'view-notifications') loadNotificationsList();
    }

    // =========================================================================
    // MASTER DATA MANAGEMENT (Departments, Designations, Contractors, Managers, Banks)
    // =========================================================================
    let currentMasterType = 'departments';
    let masterCache = { items: [], departments: [] };

    function switchMasterTab(type) {
      currentMasterType = type;
      document.querySelectorAll('.master-tab-btn').forEach(b => {
        b.className = 'btn btn-secondary master-tab-btn';
      });
      const activeBtn = document.getElementById('mtab-' + type);
      if (activeBtn) activeBtn.className = 'btn btn-primary master-tab-btn';

      // Show/hide dept filter if designations or reporting_managers
      const deptFilterBox = document.getElementById('master-dept-filter-box');
      if (deptFilterBox) {
        deptFilterBox.style.display = (type === 'designations' || type === 'reporting_managers') ? 'block' : 'none';
      }

      // Update add button text
      const addBtn = document.getElementById('btn-add-master-record');
      const labelMap = {
        departments: '+ Add Department',
        designations: '+ Add Designation / Role',
        contractors: '+ Add Contractor Agency',
        reporting_managers: '+ Add Reporting Manager',
        banks: '+ Add Bank'
      };
      if (addBtn) addBtn.innerText = labelMap[type] || '+ Add Record';

      document.getElementById('master-search-input').value = '';
      loadMasterData();
    }

    let masterSearchDebounce = null;
    function handleMasterSearch() {
      clearTimeout(masterSearchDebounce);
      masterSearchDebounce = setTimeout(() => {
        loadMasterData();
      }, 300);
    }

    async function loadMasterData() {
      const q = document.getElementById('master-search-input')?.value || '';
      const deptId = document.getElementById('master-dept-filter')?.value || '';
      const tbody = document.getElementById('master-table-body');
      const thead = document.getElementById('master-table-head');
      if (!tbody) return;

      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:30px; color:#64748b;">Loading records...</td></tr>';

      try {
        const url = `/api/masters/list?type=${encodeURIComponent(currentMasterType)}&q=${encodeURIComponent(q)}&dept_id=${encodeURIComponent(deptId)}`;
        const res = await fetch(url);
        const json = await res.json();
        if (!json.success) {
          tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:20px; color:red;">${json.message || 'Error loading'}</td></tr>`;
          return;
        }

        masterCache = json.data || { items: [], departments: [] };
        const items = masterCache.items || [];

        // Update badge count
        const countBadge = document.getElementById('master-count-badge');
        if (countBadge) countBadge.innerText = `Total: ${items.length}`;

        // Populate dept filter dropdown if available and empty
        const deptFilter = document.getElementById('master-dept-filter');
        if (deptFilter && masterCache.departments && deptFilter.options.length <= 1) {
          const curVal = deptFilter.value;
          deptFilter.innerHTML = '<option value="">All Departments</option>' + masterCache.departments.map(d => `
            <option value="${d.id}">${d.department_name}</option>
          `).join('');
          if (curVal) deptFilter.value = curVal;
        }

        renderMasterTable(items);
      } catch (e) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:20px; color:red;">Error: ${e}</td></tr>`;
      }
    }

    function renderMasterTable(items) {
      const thead = document.getElementById('master-table-head');
      const tbody = document.getElementById('master-table-body');

      if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:36px; color:#64748b; font-size:13px;">No records found in this master. Click <strong>"+ Add Record"</strong> or upload via Excel/CSV above.</td></tr>';
        return;
      }

      switch (currentMasterType) {
        case 'departments':
          thead.innerHTML = `
            <tr>
              <th style="width:70px;">ID</th>
              <th>DEPARTMENT NAME</th>
              <th>CODE</th>
              <th>UNIT SCOPE</th>
              <th>DESIGNATIONS</th>
              <th>STATUS</th>
              <th style="width:160px;">ACTIONS</th>
            </tr>
          `;
          tbody.innerHTML = items.map(d => `
            <tr>
              <td><code>#${d.id}</code></td>
              <td><strong style="color:#0f172a; font-size:13.5px;">${d.department_name}</strong></td>
              <td><span class="badge-chip">${d.department_code || '-'}</span></td>
              <td>${d.unit_id || 'ALL'}</td>
              <td><span class="badge" style="background:#e0f2fe; color:#0369a1; font-weight:700;">${d.designation_count || 0} Roles</span></td>
              <td><span class="badge ${d.is_active ? 'badge-p' : 'badge-a'}">${d.is_active ? 'Active' : 'Inactive'}</span></td>
              <td>
                <div style="display:flex; gap:6px;">
                  <button class="btn btn-secondary" style="padding:4px 8px; font-size:11.5px;" onclick="openEditMasterModal(${d.id})">Edit</button>
                  <button class="btn btn-secondary" style="padding:4px 8px; font-size:11.5px; color:${d.is_active ? '#b91c1c' : '#15803d'};" onclick="toggleMasterActive(${d.id}, ${d.is_active})">${d.is_active ? 'Deactivate' : 'Activate'}</button>
                </div>
              </td>
            </tr>
          `).join('');
          break;

        case 'designations':
          thead.innerHTML = `
            <tr>
              <th style="width:70px;">ID</th>
              <th>DESIGNATION / ROLE NAME</th>
              <th>PARENT DEPARTMENT</th>
              <th>CODE</th>
              <th>STATUS</th>
              <th style="width:160px;">ACTIONS</th>
            </tr>
          `;
          tbody.innerHTML = items.map(ds => `
            <tr>
              <td><code>#${ds.id}</code></td>
              <td><strong style="color:#0f172a; font-size:13.5px;">${ds.designation_name}</strong></td>
              <td><span class="badge-chip" style="background:#eff6ff; color:#1d4ed8; font-weight:700;">${ds.department_name || 'Unassigned'}</span></td>
              <td><code>${ds.designation_code || '-'}</code></td>
              <td><span class="badge ${ds.is_active ? 'badge-p' : 'badge-a'}">${ds.is_active ? 'Active' : 'Inactive'}</span></td>
              <td>
                <div style="display:flex; gap:6px;">
                  <button class="btn btn-secondary" style="padding:4px 8px; font-size:11.5px;" onclick="openEditMasterModal(${ds.id})">Edit</button>
                  <button class="btn btn-secondary" style="padding:4px 8px; font-size:11.5px; color:${ds.is_active ? '#b91c1c' : '#15803d'};" onclick="toggleMasterActive(${ds.id}, ${ds.is_active})">${ds.is_active ? 'Deactivate' : 'Activate'}</button>
                </div>
              </td>
            </tr>
          `).join('');
          break;

        case 'contractors':
          thead.innerHTML = `
            <tr>
              <th style="width:70px;">ID</th>
              <th>CONTRACTOR / AGENCY NAME</th>
              <th>CONTACT PERSON</th>
              <th>PHONE</th>
              <th>EMAIL</th>
              <th>STATUS</th>
              <th style="width:160px;">ACTIONS</th>
            </tr>
          `;
          tbody.innerHTML = items.map(c => `
            <tr>
              <td><code>#${c.id}</code></td>
              <td><strong style="color:#0f172a; font-size:13.5px;">${c.contractor_name}</strong></td>
              <td>${c.contact_person || '-'}</td>
              <td>${c.phone || '-'}</td>
              <td>${c.email || '-'}</td>
              <td><span class="badge ${c.is_active ? 'badge-p' : 'badge-a'}">${c.is_active ? 'Active' : 'Inactive'}</span></td>
              <td>
                <div style="display:flex; gap:6px;">
                  <button class="btn btn-secondary" style="padding:4px 8px; font-size:11.5px;" onclick="openEditMasterModal(${c.id})">Edit</button>
                  <button class="btn btn-secondary" style="padding:4px 8px; font-size:11.5px; color:${c.is_active ? '#b91c1c' : '#15803d'};" onclick="toggleMasterActive(${c.id}, ${c.is_active})">${c.is_active ? 'Deactivate' : 'Activate'}</button>
                </div>
              </td>
            </tr>
          `).join('');
          break;

        case 'reporting_managers':
          thead.innerHTML = `
            <tr>
              <th style="width:70px;">ID</th>
              <th>REPORTING MANAGER</th>
              <th>DEPARTMENT</th>
              <th>PHONE</th>
              <th>EMAIL</th>
              <th>STATUS</th>
              <th style="width:160px;">ACTIONS</th>
            </tr>
          `;
          tbody.innerHTML = items.map(m => `
            <tr>
              <td><code>#${m.id}</code></td>
              <td><strong style="color:#0f172a; font-size:13.5px;">${m.manager_name}</strong></td>
              <td><span class="badge-chip">${m.department_name || '-'}</span></td>
              <td>${m.phone || '-'}</td>
              <td>${m.email || '-'}</td>
              <td><span class="badge ${m.is_active ? 'badge-p' : 'badge-a'}">${m.is_active ? 'Active' : 'Inactive'}</span></td>
              <td>
                <div style="display:flex; gap:6px;">
                  <button class="btn btn-secondary" style="padding:4px 8px; font-size:11.5px;" onclick="openEditMasterModal(${m.id})">Edit</button>
                  <button class="btn btn-secondary" style="padding:4px 8px; font-size:11.5px; color:${m.is_active ? '#b91c1c' : '#15803d'};" onclick="toggleMasterActive(${m.id}, ${m.is_active})">${m.is_active ? 'Deactivate' : 'Activate'}</button>
                </div>
              </td>
            </tr>
          `).join('');
          break;

        case 'banks':
          thead.innerHTML = `
            <tr>
              <th style="width:70px;">ID</th>
              <th>BANK NAME</th>
              <th>IFSC PREFIX</th>
              <th>STATUS</th>
              <th style="width:160px;">ACTIONS</th>
            </tr>
          `;
          tbody.innerHTML = items.map(b => `
            <tr>
              <td><code>#${b.id}</code></td>
              <td><strong style="color:#0f172a; font-size:13.5px;">${b.bank_name}</strong></td>
              <td><code>${b.ifsc_prefix || '-'}</code></td>
              <td><span class="badge ${b.is_active ? 'badge-p' : 'badge-a'}">${b.is_active ? 'Active' : 'Inactive'}</span></td>
              <td>
                <div style="display:flex; gap:6px;">
                  <button class="btn btn-secondary" style="padding:4px 8px; font-size:11.5px;" onclick="openEditMasterModal(${b.id})">Edit</button>
                  <button class="btn btn-secondary" style="padding:4px 8px; font-size:11.5px; color:${b.is_active ? '#b91c1c' : '#15803d'};" onclick="toggleMasterActive(${b.id}, ${b.is_active})">${b.is_active ? 'Deactivate' : 'Activate'}</button>
                </div>
              </td>
            </tr>
          `).join('');
          break;
      }
    }

    function openAddMasterModal() {
      document.getElementById('master-form-id').value = '';
      document.getElementById('master-form-type').value = currentMasterType;
      document.getElementById('master-form-active').checked = true;

      const titleMap = {
        departments: 'Add Department',
        designations: 'Add Designation / Role',
        contractors: 'Add Contractor Agency',
        reporting_managers: 'Add Reporting Manager',
        banks: 'Add Bank'
      };
      document.getElementById('modal-master-title').innerText = titleMap[currentMasterType] || 'Add Record';
      renderMasterDynamicFields();
      document.getElementById('modal-master-item').classList.add('active');
    }

    function openEditMasterModal(id) {
      const item = masterCache.items?.find(x => x.id == id);
      if (!item) return;

      document.getElementById('master-form-id').value = item.id;
      document.getElementById('master-form-type').value = currentMasterType;
      document.getElementById('master-form-active').checked = item.is_active ? true : false;

      const titleMap = {
        departments: 'Edit Department',
        designations: 'Edit Designation / Role',
        contractors: 'Edit Contractor Agency',
        reporting_managers: 'Edit Reporting Manager',
        banks: 'Edit Bank'
      };
      document.getElementById('modal-master-title').innerText = titleMap[currentMasterType] || 'Edit Record';
      renderMasterDynamicFields(item);
      document.getElementById('modal-master-item').classList.add('active');
    }

    function renderMasterDynamicFields(item = null) {
      const box = document.getElementById('master-dynamic-fields');
      if (!box) return;

      const depts = masterCache.departments || [];

      switch (currentMasterType) {
        case 'departments':
          const availUnits = (masterCache.units && masterCache.units.length > 0)
            ? masterCache.units
            : (window.APP_UNITS && window.APP_UNITS.length > 0 ? window.APP_UNITS : []);

          const unitOptionsHtml = availUnits.map(u => {
            const uid = u.id || u;
            const uname = u.unit_name || u.name || uid;
            const label = (uname && uname !== uid) ? `${uid} (${uname})` : uid;
            const isSel = (item?.unit_id === uid) ? 'selected' : '';
            return `<option value="${uid}" ${isSel}>${label}</option>`;
          }).join('');

          box.innerHTML = `
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Department Name *</label>
              <input type="text" id="mf-department_name" value="${item?.department_name || ''}" placeholder="e.g. Production Line 1" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
            </div>
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Department Code</label>
              <input type="text" id="mf-department_code" value="${item?.department_code || ''}" placeholder="e.g. PROD_L1" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
            </div>
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Factory Unit Scope</label>
              <select id="mf-unit_id" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
                <option value="" ${!item?.unit_id ? 'selected' : ''}>ALL Units (Global)</option>
                ${unitOptionsHtml}
              </select>
            </div>
          `;
          break;

        case 'designations':
          box.innerHTML = `
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Parent Department *</label>
              <select id="mf-department_id" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
                <option value="">-- Select Department --</option>
                ${depts.map(d => `<option value="${d.id}" ${item?.department_id == d.id ? 'selected' : ''}>${d.department_name}</option>`).join('')}
              </select>
            </div>
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Designation / Role Title *</label>
              <input type="text" id="mf-designation_name" value="${item?.designation_name || ''}" placeholder="e.g. Machine Operator" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
            </div>
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Designation Code</label>
              <input type="text" id="mf-designation_code" value="${item?.designation_code || ''}" placeholder="e.g. PROD-OPR" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
            </div>
          `;
          break;

        case 'contractors':
          box.innerHTML = `
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Contractor Agency Name *</label>
              <input type="text" id="mf-contractor_name" value="${item?.contractor_name || ''}" placeholder="e.g. Sri Sai Manpower Services" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
            </div>
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Contact Person</label>
              <input type="text" id="mf-contact_person" value="${item?.contact_person || ''}" placeholder="e.g. Ramesh Rao" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
            </div>
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Phone Number</label>
              <input type="text" id="mf-phone" maxlength="10" value="${item?.phone || ''}" placeholder="e.g. 9848011223" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
            </div>
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Email Address</label>
              <input type="email" id="mf-email" value="${item?.email || ''}" placeholder="e.g. srisai@manpower.com" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
            </div>
          `;
          break;

        case 'reporting_managers':
          box.innerHTML = `
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Manager Full Name *</label>
              <input type="text" id="mf-manager_name" value="${item?.manager_name || ''}" placeholder="e.g. CHANDRA" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
            </div>
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Department</label>
              <select id="mf-department_id" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
                <option value="">-- Unassigned / General --</option>
                ${depts.map(d => `<option value="${d.id}" ${item?.department_id == d.id ? 'selected' : ''}>${d.department_name}</option>`).join('')}
              </select>
            </div>
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Phone Number</label>
              <input type="text" id="mf-phone" maxlength="10" value="${item?.phone || ''}" placeholder="e.g. 9848012345" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
            </div>
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Email Address</label>
              <input type="email" id="mf-email" value="${item?.email || ''}" placeholder="e.g. chandra@troogood.com" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
            </div>
          `;
          break;

        case 'banks':
          box.innerHTML = `
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">Bank Name *</label>
              <input type="text" id="mf-bank_name" value="${item?.bank_name || ''}" placeholder="e.g. State Bank of India" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
            </div>
            <div>
              <label style="display:block; font-weight:700; margin-bottom:4px;">IFSC Prefix Code</label>
              <input type="text" id="mf-ifsc_prefix" value="${item?.ifsc_prefix || ''}" placeholder="e.g. SBIN" style="width:100%; border:1px solid #ccc; border-radius:6px; padding:8px;">
            </div>
          `;
          break;
      }
    }

    function closeMasterModal() {
      document.getElementById('modal-master-item').classList.remove('active');
    }

    async function submitMasterItem() {
      const type = document.getElementById('master-form-type').value || currentMasterType;
      const id = document.getElementById('master-form-id').value;
      const isActive = document.getElementById('master-form-active').checked ? 1 : 0;

      const payload = {
        type: type,
        id: id || null,
        is_active: isActive
      };

      if (type === 'departments') {
        payload.department_name = document.getElementById('mf-department_name')?.value.trim();
        payload.department_code = document.getElementById('mf-department_code')?.value.trim();
        payload.unit_id = document.getElementById('mf-unit_id')?.value;
        if (!payload.department_name) { showToast('Department Name is required', 'error'); return; }
      } else if (type === 'designations') {
        payload.department_id = document.getElementById('mf-department_id')?.value;
        payload.designation_name = document.getElementById('mf-designation_name')?.value.trim();
        payload.designation_code = document.getElementById('mf-designation_code')?.value.trim();
        if (!payload.department_id) { showToast('Please select a Parent Department', 'error'); return; }
        if (!payload.designation_name) { showToast('Designation Name is required', 'error'); return; }
      } else if (type === 'contractors') {
        payload.contractor_name = document.getElementById('mf-contractor_name')?.value.trim();
        payload.contact_person = document.getElementById('mf-contact_person')?.value.trim();
        payload.phone = document.getElementById('mf-phone')?.value.trim();
        payload.email = document.getElementById('mf-email')?.value.trim();
        if (!payload.contractor_name) { showToast('Contractor Agency Name is required', 'error'); return; }
      } else if (type === 'reporting_managers') {
        payload.manager_name = document.getElementById('mf-manager_name')?.value.trim();
        payload.department_id = document.getElementById('mf-department_id')?.value || null;
        payload.phone = document.getElementById('mf-phone')?.value.trim();
        payload.email = document.getElementById('mf-email')?.value.trim();
        if (!payload.manager_name) { showToast('Manager Name is required', 'error'); return; }
      } else if (type === 'banks') {
        payload.bank_name = document.getElementById('mf-bank_name')?.value.trim();
        payload.ifsc_prefix = document.getElementById('mf-ifsc_prefix')?.value.trim();
        if (!payload.bank_name) { showToast('Bank Name is required', 'error'); return; }
      }

      const btn = document.getElementById('btn-save-master-item');
      if (btn) { btn.disabled = true; btn.innerText = 'Saving...'; }

      try {
        const res = await fetch('/api/masters/save', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (json.success) {
          showToast('Record saved successfully!', 'success');
          closeMasterModal();
          await loadMasterData();
        } else {
          showToast(json.message || 'Error saving record', 'error');
        }
      } catch (e) {
        showToast('Error saving: ' + e, 'error');
      } finally {
        if (btn) { btn.disabled = false; btn.innerText = 'Save Record'; }
      }
    }

    async function toggleMasterActive(id, currentStatus) {
      const newStatus = currentStatus ? 0 : 1;
      const type = currentMasterType;
      const item = masterCache.items?.find(x => x.id == id);
      try {
        const payload = {
          type: type,
          id: id,
          is_active: newStatus
        };
        if (type === 'departments') payload.department_name = item?.department_name;
        if (type === 'designations') { payload.designation_name = item?.designation_name; payload.department_id = item?.department_id; }
        if (type === 'contractors') payload.contractor_name = item?.contractor_name;
        if (type === 'reporting_managers') payload.manager_name = item?.manager_name;
        if (type === 'banks') payload.bank_name = item?.bank_name;

        const res = await fetch('/api/masters/save', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (json.success) {
          showToast(`Record ${newStatus ? 'activated' : 'deactivated'} successfully`, 'success');
          await loadMasterData();
        } else {
          showToast(json.message || 'Error updating status', 'error');
        }
      } catch (e) { showToast('Status error: ' + e, 'error'); }
    }

    function exportMasterCsv() {
      window.location.href = `/api/masters/export?type=${encodeURIComponent(currentMasterType)}`;
    }

    function openMasterUploadModal() {
      const names = {
        departments: 'Departments Master',
        designations: 'Designations / Roles Master',
        contractors: 'Contractors / Agencies Master',
        reporting_managers: 'Reporting Managers Master',
        banks: 'Banks Master'
      };
      document.getElementById('upload-master-name-label').innerText = names[currentMasterType] || currentMasterType;
      document.getElementById('master-csv-file').value = '';
      document.getElementById('modal-master-upload').classList.add('active');
    }

    function closeMasterUploadModal() {
      document.getElementById('modal-master-upload').classList.remove('active');
    }

    async function submitMasterCsvUpload() {
      const fileInput = document.getElementById('master-csv-file');
      if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        showToast('Please select a CSV file to upload', 'error');
        return;
      }

      const file = fileInput.files[0];
      const formData = new FormData();
      formData.append('type', currentMasterType);
      formData.append('csv_file', file);

      const btn = document.getElementById('btn-upload-master-submit');
      if (btn) { btn.disabled = true; btn.innerText = 'Processing...'; }

      try {
        const res = await fetch('/api/masters/import', {
          method: 'POST',
          body: formData
        });
        const json = await res.json();
        if (json.success) {
          showToast(json.message || 'CSV uploaded and processed!', 'success');
          closeMasterUploadModal();
          await loadMasterData();
        } else {
          showToast(json.message || 'CSV upload failed', 'error');
        }
      } catch (e) {
        showToast('Upload error: ' + e, 'error');
      } finally {
        if (btn) { btn.disabled = false; btn.innerText = 'Upload & Save'; }
      }
    }

    // 1. ERP UNIT SYNC
    async function loadUnitsSync() {
      try {
        const res = await fetch('/api/units/sync-logs');
        const json = await res.json();
        const d = json.data;

        document.getElementById('sync-last-timestamp').innerText = 'Last synced: ' + (d.last_synced || 'Just now');

        document.getElementById('units-sync-table-body').innerHTML = d.units.map(u => `
          <tr>
            <td><strong>${u.id}</strong></td>
            <td>${u.unit_name}</td>
            <td>${u.city}, ${u.state}</td>
            <td><span class="badge ${u.sync_status === 'synced' ? 'badge-p' : 'badge-a'}">${u.sync_status || 'synced'}</span></td>
            <td>${u.last_synced_at || 'Automatic'}</td>
            <td>${u.is_active ? '<span class="badge badge-p">Active</span>' : '<span class="badge badge-a">Deactivated</span>'}</td>
          </tr>
        `).join('');

        document.getElementById('sync-logs-table-body').innerHTML = d.logs.map(l => `
          <tr>
            <td>#${l.sync_id}</td>
            <td><code>${l.sync_url}</code></td>
            <td><span class="badge ${l.trigger_type === 'manual' ? 'badge-l' : 'badge-w'}">${l.trigger_type}</span></td>
            <td>${l.started_at}</td>
            <td>${l.completed_at || l.started_at}</td>
            <td style="color:var(--success); font-weight:700;">+${l.units_added}</td>
            <td style="color:var(--primary); font-weight:700;">~${l.units_updated}</td>
            <td style="color:var(--danger); font-weight:700;">-${l.units_deactivated}</td>
            <td><span class="badge ${l.status === 'success' ? 'badge-p' : 'badge-a'}">${l.status}</span></td>
          </tr>
        `).join('');
      } catch (e) { console.error('Units sync error', e); }
    }

    async function triggerErpSync() {
      const btn = document.getElementById('btn-sync-now');
      btn.innerText = 'Syncing...';
      btn.disabled = true;
      try {
        const res = await fetch('/api/units/sync', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({trigger_type: 'manual'})
        });
        const json = await res.json();
        showToast(`Master ERP Sync completed! Added: ${json.data.units_added} | Updated: ${json.data.units_updated} | Deactivated: ${json.data.units_deactivated}`, 'success', 5000);
        loadUnitsSync();
      } catch (e) {
        showToast('Sync failed: ' + e, 'error');
      } finally {
        btn.innerText = 'Sync Now';
        btn.disabled = false;
      }
    }

    // 4. IMMUTABLE AUDIT LOG
    async function loadAuditTimeline() {
      try {
        const u = document.getElementById('audit-filter-user')?.value || '';
        const ent = document.getElementById('audit-filter-entity')?.value || '';
        const dt = document.getElementById('audit-filter-date')?.value || '';

        const res = await fetch(`/api/audit/timeline?user=${encodeURIComponent(u)}&entity=${encodeURIComponent(ent)}&date=${dt}`);
        const json = await res.json();
        const logs = json.data.logs;

        document.getElementById('audit-table-body').innerHTML = logs.map(l => {
          let diffHtml = '';
          if (l.old_values_parsed || l.new_values_parsed) {
            diffHtml = `
              <div style="margin-top:4px;">
                ${l.old_values_parsed ? `<span class="diff-old">OLD: ${JSON.stringify(l.old_values_parsed)}</span> &rarr; ` : ''}
                ${l.new_values_parsed ? `<span class="diff-new">NEW: ${JSON.stringify(l.new_values_parsed)}</span>` : ''}
              </div>
            `;
          }
          return `
            <tr>
              <td><code>${l.created_at}</code></td>
              <td><strong>${l.user_identifier || 'SYSTEM'}</strong></td>
              <td><span class="badge badge-l">${l.action}</span></td>
              <td><span class="badge-chip">${l.entity_type} #${l.entity_id}</span></td>
              <td>${l.details || 'Mutation verified'} ${diffHtml}</td>
              <td><code>${l.ip_address || '127.0.0.1'}</code></td>
            </tr>
          `;
        }).join('');
      } catch (e) { console.error('Audit log error', e); }
    }

// 5. PORTAL USERS & DEVICE LOCK MANAGEMENT (PRODUCTION WEB UI)
    let currentUsersCache = [];
    let pendingConfirmAction = null;

    const SVG_EYE_OPEN = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; display:inline-block;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    const SVG_EYE_SLASH = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; display:inline-block;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;

    // Toast Notification System
    function showToast(msg, type = 'success', duration = 3800) {
      const container = document.getElementById('toast-container');
      if (!container) return;
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      
      let icon = '';
      if (type === 'error') icon = '';
      else if (type === 'warning') icon = '';
      else if (type === 'info') icon = 'ℹ';

      const formattedMsg = String(msg).replace(/\n/g, '<br>');

      toast.innerHTML = `
        <span class="toast-icon">${icon}</span>
        <span style="flex:1;">${formattedMsg}</span>
        <button class="toast-close" onclick="this.parentElement.remove()" title="Dismiss" style="background:none; border:none; font-size:16px; font-weight:700; cursor:pointer; color:#64748b;">Close</button>
      `;

      container.appendChild(toast);

      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
      }, duration);
    }

    // Safety Override for window.alert to always use sleek in-app Toasts
    window.alert = function(msg) {
      if (!msg) return;
      const str = String(msg);
      let type = 'info';
      const lower = str.toLowerCase();
      if (lower.includes('fail') || lower.includes('error')) {
        type = 'error';
      } else if (lower.includes('success') || lower.includes('updated') || lower.includes('saved') || lower.includes('completed')) {
        type = 'success';
      } else if (lower.includes('please') || lower.includes('warning')) {
        type = 'warning';
      }
      showToast(str, type);
    };

    // Password visibility toggle helper with real SVG icons
    function togglePassVisibility(inputId, btn) {
      const input = document.getElementById(inputId);
      if (!input) return;
      if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = SVG_EYE_SLASH;
        btn.title = 'Hide password';
      } else {
        input.type = 'password';
        btn.innerHTML = SVG_EYE_OPEN;
        btn.title = 'Show password';
      }
    }

    async function loadPortalUsers() {
      try {
        const q = document.getElementById('user-search-input')?.value || '';
        const roleFilter = document.getElementById('user-role-filter')?.value || 'all';
        const statusFilter = document.getElementById('user-status-filter')?.value || 'all';

        const res = await fetch(`/api/portal/users?q=${encodeURIComponent(q)}`);
        const json = await res.json();
        if (!json.success || !json.data) return;

        const d = json.data;
        let users = d.users || [];
        currentUsersCache = users;

        // Populate Dynamic Role Dropdowns in Modals & Filter
        if (d.available_roles && d.available_roles.length > 0) {
          availableRolesList = d.available_roles;

          const newRoleSel = document.getElementById('new-user-role');
          if (newRoleSel) {
            const cur = newRoleSel.value;
            newRoleSel.innerHTML = d.available_roles.map(r => `
              <option value="${r.role_code}" ${r.role_code === 'supervisor' ? 'selected' : ''}>${r.role_name} ${r.is_system ? '(System)' : '(Custom)'}</option>
            `).join('');
            if (cur && newRoleSel.querySelector(`option[value="${cur}"]`)) newRoleSel.value = cur;
          }

          const editRoleSel = document.getElementById('edit-user-role');
          if (editRoleSel) {
            const cur = editRoleSel.value;
            editRoleSel.innerHTML = d.available_roles.map(r => `
              <option value="${r.role_code}">${r.role_name} ${r.is_system ? '(System)' : '(Custom)'}</option>
            `).join('');
            if (cur && editRoleSel.querySelector(`option[value="${cur}"]`)) editRoleSel.value = cur;
          }

          const filterRoleSel = document.getElementById('user-role-filter');
          if (filterRoleSel) {
            const cur = filterRoleSel.value;
            filterRoleSel.innerHTML = '<option value="all">All Roles</option>' + d.available_roles.map(r => `
              <option value="${r.role_code}">${r.role_name}</option>
            `).join('');
            if (cur && filterRoleSel.querySelector(`option[value="${cur}"]`)) filterRoleSel.value = cur;
          }
        }

        // Update Stat Cards
        const stats = d.stats || {};
        const totalEl = document.getElementById('portal-stat-total');
        const activeEl = document.getElementById('portal-stat-active');
        const suspendedEl = document.getElementById('portal-stat-suspended');
        if (totalEl) totalEl.innerText = stats.total_users ?? users.length;
        if (activeEl) activeEl.innerText = stats.active_count ?? users.filter(u => u.status === 'active').length;
        if (suspendedEl) suspendedEl.innerText = stats.suspended_count ?? users.filter(u => u.status !== 'active').length;

        // Apply Client-Side Filter for role and status
        if (roleFilter !== 'all') {
          users = users.filter(u => u.role === roleFilter);
        }
        if (statusFilter !== 'all') {
          users = users.filter(u => u.status === statusFilter);
        }

        const tbody = document.getElementById('portal-users-table-body');
        if (!tbody) return;

        if (users.length === 0) {
          tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:36px; color:var(--text-muted); font-size:13px;">No portal users match current criteria.<br><span style="font-size:11.5px;">Click <strong>"+ Add New User"</strong> above to create supervisor or HR administrator accounts.</span></td></tr>';
          return;
        }

        tbody.innerHTML = users.map(u => {
          const initial = (u.full_name || 'U').charAt(0).toUpperCase();
          const isSuper = (u.role === 'super_admin');
          const isHR = (u.role === 'hr_manager');
          const roleBadgeColor = isSuper ? '#dbeafe' : (isHR ? '#e0e7ff' : '#f1f5f9');
          const roleTextColor = isSuper ? '#1d4ed8' : (isHR ? '#4338ca' : '#334155');
          const roleLabel = (availableRolesList.find(r => r.role_code === u.role)?.role_name) || u.role;

          return `
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:12px;">
                  <div style="width:36px; height:36px; border-radius:50%; background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px; flex-shrink:0;">
                    ${initial}
                  </div>
                  <div>
                    <div style="font-size:13.5px; font-weight:800; color:#0f172a; line-height:1.2;">${u.full_name}</div>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;">${u.email}</div>
                  </div>
                </div>
              </td>
              <td>
                <div style="display:flex; flex-direction:column; gap:4px; align-items:flex-start;">
                  <span style="font-size:11.5px; font-weight:800; padding:3px 10px; border-radius:12px; background:${roleBadgeColor}; color:${roleTextColor}; ">
                    <span style="width:6px; height:6px; border-radius:50%; background:${roleTextColor};"></span>
                    ${roleLabel}
                  </span>
                  <span class="badge-chip" style="font-size:10.5px;">Unit: ${u.unit_id || 'ALL'}</span>
                </div>
              </td>
              <td>
                <code style="font-size:12.5px; font-weight:800; background:#f8fafc; padding:3px 10px; border-radius:6px; border:1px solid #e2e8f0; color:#0f172a; font-family:'JetBrains Mono', monospace;">
                  ${u.supervisor_pin || '----'}
                </code>
              </td>
              <td>
                <span style="font-size:12.5px; font-weight:700; color:#334155;">${u.allowed_units ? u.allowed_units.replace(/,/g, ', ') : (u.unit_id || 'ALL')}</span>
              </td>
              <td>
                ${u.bound_device_id ? `
                  <div style="display:flex; align-items:center; gap:6px;">
                    <span class="badge badge-p" style="font-size:11px;">${u.bound_device_id}</span>
                    <button class="btn btn-secondary" style="padding:2px 6px; font-size:10.5px; color:#b91c1c; border-color:#fca5a5;" onclick="confirmReleaseDeviceLock(${u.id}, '${u.email}')">Unlock</button>
                  </div>
                ` : `
                  <span class="badge" style="background:#f1f5f9; color:#64748b; font-size:11px;">(unbound)</span>
                `}
              </td>
              <td>
                <span class="badge ${u.status === 'active' ? 'badge-p' : 'badge-a'}" style="text-transform:capitalize;">
                  ${u.status}
                </span>
              </td>
              <td>
                <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                  
                  <button class="btn btn-primary" style="padding:4px 10px; font-size:11px; " onclick="openEditUserModal(${u.id})">
                    Edit
                  </button>
                  <button class="btn btn-secondary" style="padding:4px 8px; font-size:11px;" onclick="openResetPassModal(${u.id}, '${u.email}')" title="Reset Password">
                    Reset PIN
                  </button>
                  <button class="btn btn-secondary" style="padding:4px 8px; font-size:11px; ${u.status === 'active' ? 'color:#b91c1c;' : 'color:#15803d;'}" onclick="toggleUser(${u.id}, '${u.status === 'active' ? 'suspended' : 'active'}')">
                    ${u.status === 'active' ? 'Suspend' : 'Activate'}
                  </button>
                  <button class="btn btn-secondary" style="padding:4px 8px; font-size:11px; color:#ef4444;" onclick="deletePortalUser(${u.id}, '${u.full_name}')" title="Delete User">
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          `;
        }).join('');
      } catch (e) { 
        console.error('Portal users error', e);
        showToast('Error loading portal users', 'error');
      }
    }

    async function loadKioskDevices() {
      try {
        const res = await fetch('/api/portal/devices');
        const json = await res.json();
        const devices = json.data.devices || [];

        const tbody = document.getElementById('kiosk-devices-table-body');
        if (!tbody) return;

        if (devices.length === 0) {
          tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px; color:#888;">No kiosk hardware tablets registered yet. Tablet registers automatically upon first supervisor login.</td></tr>';
          return;
        }

        tbody.innerHTML = devices.map(d => {
          const isActive = (d.status === 'active');
          const statusBadge = isActive
            ? '<span class="badge badge-p" style="font-weight:700;">Active Online</span>'
            : `<span class="badge badge-a" style="font-weight:700; text-transform:capitalize;">${d.status}</span>`;

          const assignedLabel = d.supervisor_name 
            ? `<strong>${d.supervisor_name}</strong><br><span style="font-size:11px; color:#64748b;">${d.assigned_supervisor_email || ''}</span>`
            : (d.assigned_supervisor_email || '<span style="color:#94a3b8;">(unassigned)</span>');

          const locationTag = (d.last_latitude && d.last_longitude)
            ? `<div style="font-size:11.5px; font-weight:600; color:#0f172a;"><span style="color:#059669;">📍 ${parseFloat(d.last_latitude).toFixed(4)}, ${parseFloat(d.last_longitude).toFixed(4)}</span><br><span style="font-size:10px; color:#64748b;" title="${d.last_location_address || ''}">${d.last_location_address ? (d.last_location_address.length > 28 ? d.last_location_address.substring(0, 26) + '...' : d.last_location_address) : 'Plant Geo'}</span></div>`
            : `<span style="font-size:11px; color:#94a3b8;">No GPS ping</span>`;

          return `
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:8px;">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                  <code style="font-size:12.5px; font-weight:700; color:#0f172a; background:#f1f5f9; padding:3px 8px; border-radius:4px; border:1px solid #e2e8f0;">${d.device_id}</code>
                </div>
              </td>
              <td><span class="badge-chip" style="font-weight:700;">${d.unit_id}</span></td>
              <td>${assignedLabel}</td>
              <td>${statusBadge}</td>
              <td>${locationTag}</td>
              <td><code style="font-size:11.5px; color:#475569;">${d.last_ip_address || '127.0.0.1'}</code></td>
              <td><span style="font-size:12px; color:#64748b;">${d.last_heartbeat || 'Never'}</span></td>
              <td>
                <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                  <button class="btn btn-secondary" style="padding:4px 8px; font-size:11px; ${isActive ? 'color:#b91c1c; border-color:#fca5a5;' : 'color:#15803d; border-color:#86efac;'}" onclick="toggleKioskDevice('${d.device_id}', '${isActive ? 'disabled' : 'active'}')">
                    ${isActive ? 'Disable' : 'Enable'}
                  </button>
                  <button class="btn btn-secondary" style="padding:4px 8px; font-size:11px;" onclick="releaseKioskDeviceHardware('${d.device_id}')" title="Release Hardware Lock">
                    Unlock
                  </button>
                  <button class="btn btn-secondary" style="padding:4px 8px; font-size:11px; color:#ef4444; border-color:#fca5a5;" onclick="deleteKioskDeviceHardware('${d.device_id}')" title="Delete Kiosk">
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          `;
        }).join('');
      } catch (e) { 
        console.error('Kiosk devices error', e); 
      }
    }

    async function toggleKioskDevice(deviceId, newStatus) {
      try {
        const res = await fetch('/api/portal/devices/toggle', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ device_id: deviceId, status: newStatus })
        });
        const json = await res.json();
        if (json.success || json.status === 'success') {
          showToast(`Device ${deviceId} marked as ${newStatus}. Kiosk will be updated.`, 'success');
          await loadKioskDevices();
          await loadPortalUsers();
        } else {
          showToast(json.message || 'Error updating device status', 'error');
        }
      } catch (e) { showToast('Device status error: ' + e, 'error'); }
    }

    async function releaseKioskDeviceHardware(deviceId) {
      showConfirmModal(
        'Release Hardware Lock',
        `Are you sure you want to release the hardware lock for tablet "${deviceId}"? Kiosk will be required to log in again.`,
        async () => {
          try {
            const res = await fetch('/api/auth/release-device', {
              method: 'POST',
              headers: {'Content-Type': 'application/json'},
              body: JSON.stringify({ device_id: deviceId })
            });
            const json = await res.json();
            showToast(json.message || 'Device lock released', 'success');
            await loadKioskDevices();
            await loadPortalUsers();
          } catch (e) { showToast('Release device error: ' + e, 'error'); }
        }
      );
    }

    async function deleteKioskDeviceHardware(deviceId) {
      showConfirmModal(
        'Delete Kiosk Hardware Tablet',
        `Are you sure you want to permanently delete kiosk tablet "${deviceId}"? The mobile app will be logged out and cannot punch attendance until re-registered.`,
        async () => {
          try {
            const res = await fetch('/api/portal/devices/delete', {
              method: 'POST',
              headers: {'Content-Type': 'application/json'},
              body: JSON.stringify({ device_id: deviceId })
            });
            const json = await res.json();
            if (json.success || json.status === 'success') {
              showToast('Kiosk tablet deleted. Mobile kiosk will be logged out immediately.', 'success');
              await loadKioskDevices();
              await loadPortalUsers();
            } else {
              showToast(json.message || 'Error deleting device', 'error');
            }
          } catch (e) { showToast('Delete device error: ' + e, 'error'); }
        }
      );
    }

    // Modal Control Functions
    function openAddUserModal() {
      document.getElementById('new-user-name').value = '';
      document.getElementById('new-user-email').value = '';
      document.getElementById('new-user-pass').value = '';
      document.getElementById('new-user-pin').value = '1234';
      document.getElementById('modal-add-user').classList.add('active');
    }
    function closeAddUserModal() {
      document.getElementById('modal-add-user').classList.remove('active');
    }

    function openEditUserModal(userId) {
      const u = currentUsersCache.find(x => x.id === userId);
      if (!u) return;

      document.getElementById('edit-user-id').value = u.id;
      document.getElementById('edit-user-name').value = u.full_name || '';
      document.getElementById('edit-user-email').value = u.email || '';
      document.getElementById('edit-user-role').value = u.role || 'supervisor';
      document.getElementById('edit-user-pin').value = u.supervisor_pin || '';
      document.getElementById('edit-user-unit').value = u.unit_id || '';
      document.getElementById('edit-user-pass').value = '';

      const statusEl = document.getElementById('edit-user-device-status');
      const releaseBtn = document.getElementById('edit-user-release-btn');
      if (u.bound_device_id) {
        statusEl.innerHTML = `<span style="color:#0284c7; font-family:monospace;">${u.bound_device_id}</span>`;
        releaseBtn.style.display = 'block';
      } else {
        statusEl.innerHTML = `<span style="color:#64748b;">(unbound - any device can bind)</span>`;
        releaseBtn.style.display = 'none';
      }

      document.getElementById('modal-edit-user').classList.add('active');
    }

    function closeEditUserModal() {
      document.getElementById('modal-edit-user').classList.remove('active');
    }

    async function submitEditUser() {
      const id = document.getElementById('edit-user-id').value;
      const name = document.getElementById('edit-user-name').value.trim();
      const email = document.getElementById('edit-user-email').value.trim();
      const role = document.getElementById('edit-user-role').value;
      const pin = document.getElementById('edit-user-pin').value.trim();
      const unit = document.getElementById('edit-user-unit').value;
      const pass = document.getElementById('edit-user-pass').value.trim();

      if (!name || !email) {
        showToast('Please fill Full Name and Email ID', 'error');
        return;
      }

      if (!pin) {
        showToast('Please set a Supervisor 4-digit PIN', 'error');
        return;
      }

      const payload = {
        user_id: id,
        full_name: name,
        email: email,
        role: role,
        supervisor_pin: pin,
        unit_id: unit
      };
      if (pass) payload.password = pass;

      const res = await fetch('/api/portal/user/update', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (json.status === 'success' || json.success) {
        showToast('User profile & PIN updated successfully!', 'success');
        closeEditUserModal();
        await loadPortalUsers();
      } else {
        showToast(json.message || 'Error updating user', 'error');
      }
    }

    function releaseDeviceLockFromEdit() {
      const id = document.getElementById('edit-user-id').value;
      const email = document.getElementById('edit-user-email').value;
      closeEditUserModal();
      confirmReleaseDeviceLock(id, email);
    }

    function openResetPassModal(id, email) {
      document.getElementById('reset-pass-user-id').value = id;
      document.getElementById('reset-pass-user-email').innerText = email;
      document.getElementById('reset-pass-input').value = '';
      document.getElementById('modal-reset-pass').classList.add('active');
    }
    function closeResetPassModal() {
      document.getElementById('modal-reset-pass').classList.remove('active');
    }

    async function submitResetPass() {
      const id = document.getElementById('reset-pass-user-id').value;
      const pass = document.getElementById('reset-pass-input').value.trim();
      if (!pass) {
        showToast('Please enter a new password', 'error');
        return;
      }
      const res = await fetch('/api/portal/user/reset-password', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({user_id: id, new_password: pass})
      });
      const json = await res.json();
      if (json.status === 'success' || json.success) {
        showToast('Password updated successfully!', 'success');
        closeResetPassModal();
        loadPortalUsers();
      } else {
        showToast(json.message || 'Error resetting password', 'error');
      }
    }

    async function deletePortalUser(userId, userName) {
      showConfirmModal(
        'Delete Portal User',
        `Are you sure you want to permanently delete user "${userName || 'User #' + userId}"? This cannot be undone.`,
        async () => {
          try {
            const res = await fetch('/api/portal/user/delete', {
              method: 'POST',
              headers: {'Content-Type': 'application/json'},
              body: JSON.stringify({ user_id: userId })
            });
            const json = await res.json();
            if (json.success) {
              showToast('Portal user deleted successfully', 'success');
              loadPortalUsers();
            } else {
              showToast(json.message || 'Error deleting user', 'error');
            }
          } catch (e) {
            showToast('Failed to delete user: ' + e, 'error');
          }
        }
      );
    }

    // Custom Confirmation Dialog
    function showConfirmModal(title, msg, onConfirm) {
      document.getElementById('confirm-title').innerText = title;
      document.getElementById('confirm-message').innerText = msg;
      pendingConfirmAction = onConfirm;
      const btn = document.getElementById('confirm-action-btn');
      btn.onclick = async () => {
        const action = pendingConfirmAction;
        closeConfirmModal();
        if (typeof action === 'function') {
          await action();
        }
      };
      document.getElementById('modal-confirm-action').classList.add('active');
    }

    function closeConfirmModal() {
      document.getElementById('modal-confirm-action').classList.remove('active');
      pendingConfirmAction = null;
    }

    function confirmReleaseDeviceLock(id, email) {
      showConfirmModal(
        'Release Device Hardware Lock',
        `Are you sure you want to release the hardware lock for ${email}? This will allow the user to bind and log in from another mobile tablet.`,
        async () => {
          try {
            const res = await fetch('/api/portal/user/release-device', {
              method: 'POST',
              headers: {'Content-Type': 'application/json'},
              body: JSON.stringify({user_id: id})
            });
            const json = await res.json();
            if (json.status === 'success' || json.success) {
              showToast(json.message || 'Hardware lock released successfully!', 'success');
              await loadPortalUsers();
              await loadKioskDevices();
            } else {
              showToast(json.message || 'Error releasing device lock', 'error');
            }
          } catch (e) {
            console.error('Release lock error', e);
            showToast('Failed to release device lock', 'error');
          }
        }
      );
    }

    async function submitAddUser() {
      const name = document.getElementById('new-user-name').value.trim();
      const email = document.getElementById('new-user-email').value.trim();
      const password = document.getElementById('new-user-pass').value.trim();
      const role = document.getElementById('new-user-role').value;
      const pin = document.getElementById('new-user-pin').value.trim() || '1234';
      const unit = document.getElementById('new-user-unit').value;

      if (!name || !email || !password) {
        showToast('Please fill Full Name, Email ID, and Password', 'error');
        return;
      }

      const res = await fetch('/api/portal/user/create', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          full_name: name,
          email: email,
          password: password,
          role: role,
          supervisor_pin: pin,
          unit_id: unit
        })
      });
      const json = await res.json();
      if (json.status === 'success' || json.success) {
        showToast('User created successfully!', 'success');
        closeAddUserModal();
        await loadPortalUsers();
      } else {
        showToast(json.message || 'Could not create user', 'error');
      }
    }

    async function toggleUser(id, nextStatus) {
      await fetch('/api/portal/user/status', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({user_id: id, status: nextStatus})
      });
      showToast(`User status changed to ${nextStatus}`, 'info');
      loadPortalUsers();
    }
    
    // Modal Handlers for Role & ACL
    function openNewRoleModal() { document.getElementById('modal-new-role').classList.add('active'); }
    function openCloneRoleModal() { document.getElementById('modal-clone-role').classList.add('active'); }
    function closeModals() { document.querySelectorAll('.modal-overlay').forEach(el => el.classList.remove('active')); }

    async function submitCreateRole() {
      const code = document.getElementById('new-role-code').value.trim();
      const name = document.getElementById('new-role-name').value.trim();
      const desc = document.getElementById('new-role-desc').value.trim();
      if (!name) { showToast('Please enter role display name', 'warning'); return; }

      const res = await fetch('/api/acl/role/create', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({role_code: code, role_name: name, description: desc})
      });
      const json = await res.json();
      if (json.success) {
        closeModals();
        showToast(`Custom role '${name}' created successfully with all 11 modules!`, 'success');
        const newRoleId = json.data?.role_id;
        currentAclRoleId = newRoleId || 1;
        loadAclMatrix(currentAclRoleId);
        loadPortalUsers();
      } else {
        showToast('Error creating role: ' + (json.message || 'Unknown error'), 'error');
      }
    }

    async function submitCloneRole() {
      const code = document.getElementById('clone-role-code').value.trim();
      const name = document.getElementById('clone-role-name').value.trim();
      if (!name) { showToast('Please enter new role display name', 'warning'); return; }

      const res = await fetch('/api/acl/role/clone', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({source_role_id: currentAclRoleId || 1, new_role_code: code, new_role_name: name})
      });
      const json = await res.json();
      if (json.success) {
        closeModals();
        showToast(`Role cloned into '${name}' successfully!`, 'success');
        const newRoleId = json.data?.role_id;
        currentAclRoleId = newRoleId || 1;
        loadAclMatrix(currentAclRoleId);
        loadPortalUsers();
      } else {
        showToast('Error cloning role: ' + (json.message || 'Unknown error'), 'error');
      }
    }

    // Dashboard Loader (W01)
    async function loadDashboard() {
      try {
        const res = await fetch(`/api/portal/dashboard?unit=${currentUnitScope}`);
        const json = await res.json();
        const d = json.data;
        if (!d) return;

        // Metric values
        const m = d.metrics || {};
        document.getElementById('m-headcount').innerText = m.headcount ?? 0;
        document.getElementById('m-present').innerText = m.present_today ?? 0;
        document.getElementById('m-present-pct').innerText = (m.present_pct || '0%') + ' of roster';
        document.getElementById('m-absent').innerText = m.absent ?? 0;
        document.getElementById('m-leave').innerText = m.on_leave ?? 0;
        document.getElementById('m-ot').innerText = (m.ot_hours ?? 0) + ' h';
        document.getElementById('m-cost').innerText = m.payroll_cost || '₹ 0';

        // Unit-wise attendance today
        const tbody = document.getElementById('unit-table-body');
        if (tbody) {
          if (!d.unit_wise || d.unit_wise.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:16px; color:#888;">No unit data found.</td></tr>';
          } else {
            tbody.innerHTML = d.unit_wise.map(u => `
              <tr>
                <td><strong>${u.unit}</strong> <span class="badge-chip">${u.name}</span></td>
                <td>${u.headcount}</td>
                <td style="color:var(--success); font-weight:700;">${u.present}</td>
                <td style="color:var(--danger);">${u.absent}</td>
                <td>${u.leave}</td>
                <td><span class="badge badge-p">${u.present_pct}</span></td>
              </tr>
            `).join('');
          }
        }

        // Pending approvals
        const p = d.pending_approvals || {};
        const sideBadge = document.getElementById('side-approval-badge');
        if (sideBadge) {
          const tot = p.total_pending || 0;
          sideBadge.innerText = tot;
          sideBadge.style.display = tot > 0 ? 'inline-block' : 'none';
        }

        const appBox = document.getElementById('pending-approvals-box');
        if (appBox) {
          appBox.innerHTML = `
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span>Kiosk worker registrations</span><span class="badge ${p.kiosk_worker_registrations > 0 ? 'badge-p' : 'badge-w'}">${p.kiosk_worker_registrations || 0}</span></div>
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span>Leave requests</span><span class="badge ${p.leave_requests > 0 ? 'badge-l' : 'badge-w'}">${p.leave_requests || 0}</span></div>
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span>Salary advances</span><span class="badge ${p.salary_advances > 0 ? 'badge-a' : 'badge-w'}">${p.salary_advances || 0}</span></div>
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span>Attendance corrections</span><span class="badge badge-w">${p.attendance_corrections || 0}</span></div>
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span>Candidate docs to verify</span><span class="badge badge-l">${p.candidate_docs_to_verify || 0}</span></div>
          `;
        }

        // Live Punches Feed
        const livePunchesBox = document.getElementById('live-punches-list');
        if (livePunchesBox) {
          if (!d.live_punches || d.live_punches.length === 0) {
            livePunchesBox.innerHTML = '<div style="color:var(--text-muted); font-size:12.5px; padding:16px; text-align:center; background:#f8fafc; border-radius:6px; border:1px dashed #e2e8f0;">No kiosk punches recorded today yet.</div>';
          } else {
            livePunchesBox.innerHTML = d.live_punches.map(lp => `
              <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px; padding:6px 0; border-bottom:1px solid #f1f5f9;">
                <div>
                  <strong>${lp.worker_name}</strong>
                  <div style="font-size:11px; color:#64748b;">#${lp.worker_code || ''} &middot; ${lp.shift_name || 'IST Shift'}</div>
                </div>
                <span class="badge ${lp.punch_type === 'IN' ? 'badge-p' : 'badge-hd'}">${lp.punch_type} ${lp.punch_time_fmt}</span>
              </div>
            `).join('');
          }
        }

        // Onboarding Pipeline
        const pipe = d.onboarding_pipeline || {};
        const pipeBox = document.getElementById('pipeline-counters');
        if (pipeBox) {
          pipeBox.innerHTML = `
            <div><div style="font-size:18px; font-weight:800;">${pipe.invited || 0}</div><div style="font-size:11px; color:#888;">Invited</div></div>
            <div><div style="font-size:18px; font-weight:800;">${pipe.details_submitted || 0}</div><div style="font-size:11px; color:#888;">Details</div></div>
            <div><div style="font-size:18px; font-weight:800;">${pipe.docs_verification || 0}</div><div style="font-size:11px; color:#888;">Verify</div></div>
            <div><div style="font-size:18px; font-weight:800;">${pipe.offer_released || 0}</div><div style="font-size:11px; color:#888;">Offer Out</div></div>
            <div><div style="font-size:18px; font-weight:800;">${pipe.joined || 0}</div><div style="font-size:11px; color:#888;">Joined</div></div>
          `;
        }

        // Live breadcrumb
        const breadcrumbEl = document.getElementById('dashboard-breadcrumb');
        if (breadcrumbEl) {
          const now = new Date();
          breadcrumbEl.innerText = 'Home / Dashboard · ' + now.toLocaleDateString('en-GB', {weekday: 'short', day: '2-digit', month: 'short', year: 'numeric'});
        }

        // Payroll Overview status
        if (d.payroll_status) {
          const pTitle = document.getElementById('payroll-card-title');
          if (pTitle) pTitle.innerText = `Payroll · ${d.payroll_status.month || 'Current Month'}`;
        }
      } catch (e) { console.error('Dashboard load error', e); }
    }

    // Attendance Matrix Loader (W02)
    let currentAttendanceMonth = '2026-10';

    function changeAttendanceMonth(m) {
      currentAttendanceMonth = m;
      const btn = document.getElementById('btn-lock-attendance');
      if (btn) {
        const monthOpt = document.querySelector(`#attendance-month-select option[value="${m}"]`);
        const label = monthOpt ? monthOpt.innerText.split(' ')[0] : m;
        btn.innerText = `Lock ${label}`;
      }
      loadAttendanceMatrix();
    }

    async function loadAttendanceMatrix() {
      const monthSel = document.getElementById('attendance-month-select');
      const shiftSel = document.getElementById('attendance-shift-select');
      const deptSel = document.getElementById('attendance-dept-select');
      const unitSel = document.getElementById('attendance-unit-select');
      
      const month = monthSel ? monthSel.value : currentAttendanceMonth;
      const shift = shiftSel ? shiftSel.value : 'all';
      const dept = deptSel ? deptSel.value : 'all';
      const unit = (unitSel && unitSel.value !== 'all') ? unitSel.value : (currentUnitScope || 'all');

      const tbody = document.getElementById('matrix-body');
      const theadRow = document.getElementById('matrix-header-row');
      if (tbody) tbody.innerHTML = '<tr><td colspan="35" style="text-align:center; padding:30px; color:#64748b;">Loading dynamic attendance register...</td></tr>';

      try {
        const url = `/api/portal/attendance-matrix?month=${encodeURIComponent(month)}&unit=${encodeURIComponent(unit)}&shift=${encodeURIComponent(shift)}&department=${encodeURIComponent(dept)}`;
        const res = await fetch(url);
        const json = await res.json();
        const d = json.data;
        if (!d) return;
        window.currentAttendanceRows = d.rows || [];

        // Render dynamic day headers (1..31 for Oct)
        let thHtml = '<th style="width:54px; text-align:center;">ID</th><th style="min-width:140px;">Employee</th><th style="width:70px; text-align:center;">Unit</th><th style="min-width:105px;">Shift</th>';
        (d.days_header || []).forEach(dh => {
          const isSun = (dh.dow === 'S' && new Date(dh.date).getDay() === 0);
          thHtml += `<th style="text-align:center; padding:5px 3px; font-size:11px; ${isSun ? 'color:#ef4444; background:#fff1f2;' : ''}">${dh.day}<br><span style="font-size:9px; color:#94a3b8; font-weight:700;">${dh.dow}</span></th>`;
        });
        thHtml += `
          <th style="text-align:center; min-width:44px; background:#f0fdf4; color:#166534;" title="Total Present days (including Half Days as 0.5)">P</th>
          <th style="text-align:center; min-width:48px; background:#fefce8; color:#854d0e;" title="Total Sundays (Weekly Offs)">Sundays</th>
          <th style="text-align:center; min-width:48px; background:#eff6ff; color:#1d4ed8;" title="Total Paid Days = P + Sundays">Total</th>
        `;
        if (theadRow) theadRow.innerHTML = thHtml;

        if (!d.rows || d.rows.length === 0) {
          if (tbody) tbody.innerHTML = `<tr><td colspan="${(d.days_header?.length || 31) + 7}" style="text-align:center; padding:36px; color:var(--text-muted); font-size:13px;">No approved employees found for the selected unit/shift filters.<br><span style="font-size:11.5px;">Workers registered on mobile kiosks or onboarding will populate this matrix once approved.</span></td></tr>`;
          return;
        }

        if (tbody) {
          tbody.innerHTML = d.rows.map(r => {
            const cleanShift = (r.shift || 'General Shift').replace(/\s*\([^)]*\)/g, '').trim();

            let cells = `
              <td style="text-align:center; font-weight:800; font-size:12px;">
                <button type="button" onclick="openEmployeeAttendanceDetail(${r.worker_id})" style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; border-radius:6px; padding:3px 7px; font-weight:800; font-size:11.5px; cursor:pointer; font-family:'JetBrains Mono',monospace; transition:all 0.15s ease;" onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'" title="Click to view complete attendance breakdown for #${r.worker_id}">#${r.worker_id}</button>
              </td>
              <td style="cursor:pointer;" onclick="openEmployeeAttendanceDetail(${r.worker_id})" title="Click to view complete attendance breakdown">
                <strong style="font-size:13px; color:#0f172a; text-decoration:underline text-decoration-color:#cbd5e1;">${r.full_name}</strong>
                <div style="font-size:10.5px; color:#64748b; margin-top:1px;">#${r.worker_code}${r.mobile_number ? ' · ' + r.mobile_number : ''}</div>
              </td>
              <td style="text-align:center;"><span class="badge-chip">${r.unit_id}</span></td>
              <td><span style="font-size:12px; color:#334155; font-weight:600;">${cleanShift}</span></td>
            `;

            let pCount = 0.0;
            let sunCount = 0.0;

            (r.days_details || []).forEach(dd => {
              const dayStatus = dd.status;
              if (dayStatus === 'P') pCount += 1.0;
              else if (dayStatus === 'HD') pCount += 0.5;
              else if (dayStatus === 'W') sunCount += 1.0;

              let cls = 'badge-p';
              if (dayStatus === 'A') cls = 'badge-a';
              else if (dayStatus === 'W') cls = 'badge-w';
              else if (dayStatus === 'L') cls = 'badge-l';
              else if (dayStatus === 'HD') cls = 'badge-hd';
              else if (dayStatus === '-') cls = 'badge-chip';

              // Show unit label badge when worker has attendance (P or HD)
              const unitBadge = (dd.unit_id && (dayStatus === 'P' || dayStatus === 'HD'))
                ? `<span style="display:block; font-size:8.5px; font-weight:800; opacity:0.95; line-height:1; margin-top:2px; letter-spacing:-0.2px; background:rgba(0,0,0,0.06); padding:1px 3px; border-radius:3px;">${dd.unit_id}</span>`
                : '';

              const tooltipParts = [];
              if (dd.unit_id) tooltipParts.push(`Unit: ${dd.unit_id}`);
              if (dd.punch_in) {
                tooltipParts.push(`IN: ${dd.punch_in}${dd.punch_out ? ' | OUT: ' + dd.punch_out : ''}${dd.duration ? ' (' + dd.duration + ')' : ''}`);
              } else if (dayStatus === 'W') {
                tooltipParts.push(dd.is_sunday ? 'Sunday Weekly Off (Paid)' : 'Weekly Off');
              } else if (dayStatus === 'A') {
                tooltipParts.push('Absent (No Punch)');
              } else {
                tooltipParts.push(`Status: ${dayStatus}`);
              }
              if (dd.is_overridden) {
                tooltipParts.push(`Supervisor Override: ${dd.override_reason || 'Manual correction'}`);
              }

              const tooltip = tooltipParts.join(' • ');
              const safeTooltip = tooltip.replace(/'/g, "\\'").replace(/"/g, '&quot;');
              const cellUnit = (dd.unit_id || r.unit_id || 'TGK-1').replace(/'/g, "\\'");

              cells += `
                <td style="text-align:center; padding:3px 2px;">
                  <div class="matrix-cell ${cls}" style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:36px; padding:3px 2px; cursor:pointer;" title="${safeTooltip}" onclick="overrideCell(${r.worker_id}, '${dd.date}', ${dd.day}, '${dayStatus}', '${cellUnit}', '${safeTooltip}')">
                    <span style="line-height:1; font-weight:800;">${dayStatus}</span>
                    ${unitBadge}
                  </div>
                </td>
              `;
            });

            const dispP = (r.p_days !== undefined) ? r.p_days : pCount;
            const dispSun = (r.sunday_days !== undefined) ? r.sunday_days : sunCount;
            const dispTotal = (r.paid_days !== undefined) ? r.paid_days : (pCount + sunCount);

            cells += `
              <td style="text-align:center; font-weight:800; color:#166534; background:#f0fdf4; font-size:12.5px;">${dispP}</td>
              <td style="text-align:center; font-weight:800; color:#854d0e; background:#fefce8; font-size:12.5px;">${dispSun}</td>
              <td style="text-align:center; font-weight:800; color:#1d4ed8; background:#eff6ff; font-size:13px;">${dispTotal}</td>
            `;
            return `<tr>${cells}</tr>`;
          }).join('');
        }

      } catch (e) { 
        console.error('Matrix error', e); 
        if (tbody) tbody.innerHTML = `<tr><td colspan="42" style="text-align:center; padding:20px; color:#ef4444;">Error loading attendance matrix: ${e}</td></tr>`;
      }
    }

    let currentDetailWorker = null;
    let currentDetailFilter = 'ALL';

    function openEmployeeAttendanceDetail(workerId) {
      const rows = window.currentAttendanceRows || [];
      const worker = rows.find(r => r.worker_id == workerId);
      if (!worker) {
        showToast('Worker details not found in current matrix', 'error');
        return;
      }
      currentDetailWorker = worker;
      currentDetailFilter = 'ALL';

      // Reset filter buttons
      document.querySelectorAll('.ead-filter-btn').forEach(btn => btn.classList.remove('active'));
      const allBtn = document.querySelector('.ead-filter-btn');
      if (allBtn) allBtn.classList.add('active');

      // Populate Header
      document.getElementById('ead-full-name').innerText = worker.full_name;
      document.getElementById('ead-code-badge').innerText = '#' + (worker.worker_code || worker.worker_id);
      document.getElementById('ead-avatar').innerText = (worker.full_name || 'W').charAt(0).toUpperCase();
      document.getElementById('ead-unit-badge').innerText = worker.unit_id || 'Unit';
      document.getElementById('ead-shift').innerText = worker.shift || 'General Shift';
      document.getElementById('ead-dept').innerText = worker.department || 'Operations';
      document.getElementById('ead-phone').innerText = worker.mobile_number || 'No phone registered';
      
      const monthSel = document.getElementById('attendance-month-select');
      const monthLabel = monthSel ? (monthSel.options[monthSel.selectedIndex]?.text || monthSel.value) : 'Current Month';
      document.getElementById('ead-month').innerText = monthLabel;

      // Calculate Stats
      let pCount = 0;
      let aCount = 0;
      let wCount = 0;
      let hdCount = 0;
      let totalHours = 0;

      (worker.days_details || []).forEach(dd => {
        if (dd.status === 'P') pCount++;
        else if (dd.status === 'A') aCount++;
        else if (dd.status === 'W') wCount++;
        else if (dd.status === 'HD') hdCount++;

        if (dd.duration) {
          const num = parseFloat(dd.duration);
          if (!isNaN(num)) totalHours += num;
        }
      });

      const totalPaid = pCount + (hdCount * 0.5) + wCount;
      document.getElementById('ead-kpi-p').innerText = pCount;
      document.getElementById('ead-kpi-a').innerText = aCount;
      document.getElementById('ead-kpi-w').innerText = wCount;
      document.getElementById('ead-kpi-hd').innerText = hdCount;
      document.getElementById('ead-kpi-total-days').innerText = totalPaid;
      document.getElementById('ead-kpi-hours').innerText = totalHours > 0 ? (totalHours.toFixed(1) + ' hrs') : '0h';

      // Render Days
      renderEadDaysTable();

      document.getElementById('modal-employee-attendance-detail').classList.add('active');
    }

    function closeEmployeeAttendanceDetail() {
      document.getElementById('modal-employee-attendance-detail').classList.remove('active');
    }

    function filterEadDays(filter, btn) {
      currentDetailFilter = filter;
      document.querySelectorAll('.ead-filter-btn').forEach(b => b.classList.remove('active'));
      if (btn) btn.classList.add('active');
      renderEadDaysTable();
    }

    function renderEadDaysTable() {
      if (!currentDetailWorker) return;
      const tbody = document.getElementById('ead-table-body');
      if (!tbody) return;

      const days = currentDetailWorker.days_details || [];
      const filtered = days.filter(dd => {
        if (currentDetailFilter === 'ALL') return true;
        if (currentDetailFilter === 'P') return (dd.status === 'P' || dd.status === 'HD');
        if (currentDetailFilter === 'A') return dd.status === 'A';
        if (currentDetailFilter === 'W') return dd.status === 'W';
        return true;
      });

      if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:24px; color:#94a3b8;">No records matching this filter for the month.</td></tr>`;
        return;
      }

      tbody.innerHTML = filtered.map(dd => {
        const dObj = new Date(dd.date + 'T00:00:00');
        const dayName = dObj.toLocaleDateString('en-US', { weekday: 'short' });
        const isSun = (dd.is_sunday || dayName === 'Sun');

        let badgeCls = 'badge-chip';
        let statusTitle = 'Not Applicable / Prior';
        if (dd.status === 'P') { badgeCls = 'badge-p'; statusTitle = 'Present (Full Day)'; }
        else if (dd.status === 'A') { badgeCls = 'badge-a'; statusTitle = 'Absent (No Punch)'; }
        else if (dd.status === 'W') { badgeCls = 'badge-w'; statusTitle = 'Sunday Weekly Off'; }
        else if (dd.status === 'HD') { badgeCls = 'badge-hd'; statusTitle = 'Half Day'; }
        else if (dd.status === 'L') { badgeCls = 'badge-l'; statusTitle = 'Approved Leave'; }

        const punchIn = dd.punch_in ? dd.punch_in : '--:--';
        const punchOut = dd.punch_out ? dd.punch_out : (dd.punch_in ? '<span style="color:#d97706; font-weight:700;">On Floor</span>' : '--:--');
        
        let hoursHtml = '--';
        if (dd.duration) {
          hoursHtml = `<span style="font-weight:800; color:#1e40af; background:#eff6ff; padding:2px 6px; border-radius:4px;">${dd.duration}</span>`;
        } else if (dd.punch_in && !dd.punch_out) {
          hoursHtml = `<span style="font-size:11px; color:#d97706; font-weight:700;">Active</span>`;
        }

        const unit = dd.unit_id || currentDetailWorker.unit_id || 'TGK-1';
        
        let remarks = [];
        if (dd.is_overridden) {
          remarks.push(`<strong style="color:#b45309;">Override:</strong> ${dd.override_reason || 'Supervisor correction'}`);
        } else if (dd.status === 'P') {
          remarks.push(`Auto-punched via biometric kiosk (${unit})`);
        } else if (dd.status === 'W') {
          remarks.push(`Sunday weekly off (Paid)`);
        } else if (dd.status === 'H') {
          remarks.push(`🎉 Paid Holiday (${dd.holiday_name || 'Public Holiday'})`);
        } else if (dd.status === 'A') {
          remarks.push(`No biometric scan recorded`);
        } else {
          remarks.push(`Status: ${dd.status}`);
        }

        if (dd.is_holiday && dd.status !== 'H') {
          remarks.push(`<strong style="color:#b45309;">🎉 Holiday:</strong> ${dd.holiday_name}`);
        }

        if (dd.status === 'P' && (dd.is_holiday || dd.is_sunday || isSun)) {
          remarks.push(`<span style="background:#dcfce7; color:#15803d; font-weight:800; padding:2px 6px; border-radius:4px; font-size:11px;">⭐ Duty Done: +1 Comp-Off Leave Credited</span>`);
        }

        if (dd.latitude && dd.longitude) {
          remarks.push(`<a href="https://www.google.com/maps?q=${dd.latitude},${dd.longitude}" target="_blank" style="color:#2563eb; font-weight:700; text-decoration:underline; display:inline-flex; align-items:center; gap:2px;">📍 GPS Map (${Number(dd.latitude).toFixed(4)}, ${Number(dd.longitude).toFixed(4)})</a>`);
        }

        const safeTooltip = (remarks.join(' • ')).replace(/'/g, "\\'").replace(/"/g, '&quot;');

        return `
          <tr style="border-bottom:1px solid #f1f5f9; ${isSun ? 'background:#fffbfb;' : ''}">
            <td style="padding:8px 12px; font-weight:700; color:#0f172a;">
              ${dd.date} <span style="font-size:11px; font-weight:700; color:${isSun ? '#ef4444' : '#64748b'};">(${dayName})</span>
            </td>
            <td style="padding:8px 10px; text-align:center;">
              <span class="${badgeCls}" style="padding:3px 7px; border-radius:4px; font-weight:800; font-size:11px;" title="${statusTitle}">${dd.status}</span>
            </td>
            <td style="padding:8px 10px; text-align:center; font-family:'JetBrains Mono',monospace; font-size:12px; color:${dd.punch_in ? '#15803d' : '#94a3b8'}; font-weight:700;">
              ${punchIn}
            </td>
            <td style="padding:8px 10px; text-align:center; font-family:'JetBrains Mono',monospace; font-size:12px; color:${dd.punch_out ? '#1d4ed8' : '#94a3b8'}; font-weight:700;">
              ${punchOut}
            </td>
            <td style="padding:8px 10px; text-align:center; font-size:12px;">
              ${hoursHtml}
            </td>
            <td style="padding:8px 10px; text-align:center;">
              <span class="badge-chip" style="font-size:10.5px;">${unit}</span>
            </td>
            <td style="padding:8px 12px; font-size:11.5px; color:#475569;">
              ${remarks.join(' • ')}
            </td>
            <td style="padding:8px 10px; text-align:center;">
              <button type="button" class="btn btn-secondary" style="padding:3px 8px; font-size:11px;" onclick="overrideCell(${currentDetailWorker.worker_id}, '${dd.date}', ${dd.day}, '${dd.status}', '${unit}', '${safeTooltip}')">Edit</button>
            </td>
          </tr>
        `;
      }).join('');
    }

    function overrideCell(workerId, dateStr, day, curStatus, curUnit, tooltipInfo) {
      document.getElementById('override-worker-id').value = workerId;
      document.getElementById('override-date').value = dateStr;
      document.getElementById('override-day').value = day;
      document.getElementById('override-day-label').innerText = `${dateStr} (Day ${day}) • Employee #${workerId} • Unit: ${curUnit || 'Unit'}`;
      
      const badge = document.getElementById('override-cur-status-badge');
      badge.innerText = curStatus;
      badge.className = 'badge ' + (curStatus === 'P' ? 'badge-p' : (curStatus === 'A' ? 'badge-a' : (curStatus === 'HD' ? 'badge-hd' : (curStatus === 'W' ? 'badge-w' : 'badge-l'))));
      
      const sel = document.getElementById('override-status-select');
      sel.value = (curStatus && curStatus !== '-') ? curStatus : 'P';

      const unitSel = document.getElementById('override-unit-select');
      if (unitSel && curUnit) {
        unitSel.value = curUnit;
      }
      document.getElementById('override-reason-input').value = 'Supervisor manual adjustment';
      
      document.getElementById('modal-attendance-override').classList.add('active');
    }

    function closeAttendanceOverrideModal() {
      document.getElementById('modal-attendance-override').classList.remove('active');
    }

    async function submitAttendanceOverride() {
      const workerId = document.getElementById('override-worker-id').value;
      const dateStr = document.getElementById('override-date').value;
      const day = document.getElementById('override-day').value;
      const status = document.getElementById('override-status-select').value;
      const unitId = document.getElementById('override-unit-select') ? document.getElementById('override-unit-select').value : '';
      const reason = document.getElementById('override-reason-input').value.trim() || 'Supervisor manual adjustment';

      try {
        const res = await fetch('/api/portal/attendance-override', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            worker_id: parseInt(workerId), 
            date: dateStr,
            day: parseInt(day), 
            status: status, 
            unit_id: unitId,
            reason: reason
          })
        });
        const json = await res.json();
        if (json.success || json.status === 'success') {
          closeAttendanceOverrideModal();
          showToast(`Attendance overridden to ${status} on ${dateStr} (${unitId})`, 'success');
          await loadAttendanceMatrix();
          if (document.getElementById('modal-employee-attendance-detail')?.classList.contains('active')) {
            openEmployeeAttendanceDetail(parseInt(workerId));
          }
        } else {
          showToast('Override failed: ' + (json.message || 'Error'), 'error');
        }
      } catch (err) {
        showToast('Error overriding attendance: ' + err, 'error');
      }
    }

    function exportAttendanceCsv() {
      const monthSel = document.getElementById('attendance-month-select');
      const m = monthSel ? monthSel.value : '2026-10';
      showToast(`Exporting attendance register CSV for ${m}...`, 'info');
      window.open(`/api/portal/attendance-matrix?month=${encodeURIComponent(m)}&unit=${encodeURIComponent(currentUnitScope)}&export=csv`, '_blank');
    }

    function openAttendanceUploadModal() {
      const monthSel = document.getElementById('attendance-month-select');
      const m = monthSel ? monthSel.value : (currentAttendanceMonth || '2026-10');
      const label = document.getElementById('upload-modal-month-label');
      if (label) {
        label.innerText = (monthSel && monthSel.options[monthSel.selectedIndex]) 
          ? monthSel.options[monthSel.selectedIndex].text 
          : m;
      }
      const fileInput = document.getElementById('attendance-csv-file-input');
      if (fileInput) fileInput.value = '';
      document.getElementById('modal-attendance-upload').classList.add('active');
    }

    function closeAttendanceUploadModal() {
      document.getElementById('modal-attendance-upload').classList.remove('active');
    }

    async function submitAttendanceUpload() {
      const fileInput = document.getElementById('attendance-csv-file-input');
      if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        showToast('Please select a CSV file to upload', 'error');
        return;
      }

      const file = fileInput.files[0];
      const monthSel = document.getElementById('attendance-month-select');
      const month = monthSel ? monthSel.value : '2026-10';

      const btn = document.getElementById('btn-submit-attendance-upload');
      if (btn) { btn.disabled = true; btn.innerText = 'Uploading...'; }

      const formData = new FormData();
      formData.append('file', file);
      formData.append('month', month);

      try {
        const res = await fetch('/api/portal/attendance-import', {
          method: 'POST',
          body: formData
        });
        const json = await res.json();
        if (json.success || json.status === 'success') {
          closeAttendanceUploadModal();
          showToast(json.message || 'Attendance CSV imported successfully!', 'success');
          loadAttendanceMatrix();
        } else {
          showToast('Import error: ' + (json.message || 'Failed to import CSV'), 'error');
        }
      } catch (err) {
        showToast('Upload error: ' + err, 'error');
      } finally {
        if (btn) { btn.disabled = false; btn.innerText = 'Upload & Apply'; }
      }
    }

    async function loadShiftOptionsFromMaster() {
      try {
        const res = await fetch('/api/shifts');
        const json = await res.json();
        if (!json.success || !json.data || !json.data.shifts) return;
        const shifts = json.data.shifts;

        // Populate attendance shift filter without time
        const attShiftSel = document.getElementById('attendance-shift-select');
        if (attShiftSel) {
          const curVal = attShiftSel.value;
          let html = '<option value="all">All shifts</option>';
          shifts.forEach(s => {
            html += `<option value="${s.shift_code}">${s.shift_name}</option>`;
          });
          attShiftSel.innerHTML = html;
          if (curVal && attShiftSel.querySelector(`option[value="${curVal}"]`)) {
            attShiftSel.value = curVal;
          }
        }

        // Populate profile shift dropdown without time
        const pfShiftSel = document.getElementById('pf-shift');
        if (pfShiftSel) {
          const curVal = pfShiftSel.value;
          let html = '';
          shifts.forEach(s => {
            html += `<option value="${s.shift_name}">${s.shift_name}</option>`;
          });
          pfShiftSel.innerHTML = html;
          if (curVal && pfShiftSel.querySelector(`option[value="${curVal}"]`)) {
            pfShiftSel.value = curVal;
          }
        }
      } catch (e) {
        console.warn('Failed to load dynamic shifts:', e);
      }
    }

    function lockAttendanceMonth() {
      const monthSel = document.getElementById('attendance-month-select');
      const m = monthSel ? monthSel.value : '2026-10';
      showConfirmModal(
        `Lock Attendance for ${m}`,
        `Are you sure you want to lock the attendance register for ${m}? This will finalize paid days and generate the monthly payroll run for all factory units.`,
        async () => {
          showToast(`Attendance for ${m} locked successfully! Redirecting to Payroll Run...`, 'success');
          switchView('payroll');
        }
      );
    }

    // Onboarding Pipeline (W03)
    async function loadOnboardingPipeline() {
      try {
        const res = await fetch('/api/portal/onboarding-pipeline');
        const json = await res.json();
        const stages = json.data.stages;
        const stageNames = {
          'invited': 'Invited',
          'details_submitted': 'Details submitted',
          'docs_verification': 'Docs verification',
          'offer_released': 'Offer released',
          'offer_accepted': 'Offer accepted',
          'joined': 'Joined'
        };

        let html = '';
        for (const [key, label] of Object.entries(stageNames)) {
          const list = stages[key] || [];
          html += `
            <div class="kanban-col">
              <div class="kanban-header">
                <span>${label}</span>
                <span class="badge badge-w">${list.length}</span>
              </div>
              ${list.map(c => `
                <div class="kanban-card">
                  <h4>${c.candidate_name}</h4>
                  <p>${c.position} &middot; <span class="badge-chip">${c.unit_id}</span></p>
                  <p style="margin-top:6px; font-weight:700; color:var(--primary); font-size:10.5px;">${c.sub_step || 'Active'}</p>
                </div>
              `).join('')}
            </div>
          `;
        }
        document.getElementById('kanban-board-container').innerHTML = html;
      } catch (e) { console.error('Pipeline error', e); }
    }

    // Approvals Inbox (W05 Redesigned)
    let currentApprovalType = 'all';
    let currentApprovalId = null;

    function filterApprovals(t, btnEl) {
      currentApprovalType = t;
      document.querySelectorAll('#approval-filter-tabs .approval-tab-btn').forEach(b => b.classList.remove('active'));
      if (btnEl) btnEl.classList.add('active');
      loadApprovals(t);
    }

    async function loadApprovals(type = null) {
      if (type) currentApprovalType = type;
      const listContainer = document.getElementById('inbox-items-container');
      const detailCard = document.getElementById('approval-detail-card');
      if (listContainer) listContainer.innerHTML = '<div style="padding:24px; text-align:center; color:#64748b;">Loading approvals...</div>';

      try {
        const res = await fetch(`/api/portal/approvals?type=${currentApprovalType}`);
        const json = await res.json();
        const d = json.data;
        if (!d) return;

        // Update counts dynamically across all filter pills
        if (d.counts) {
          const c = d.counts;
          if (document.getElementById('cnt-all')) document.getElementById('cnt-all').innerText = c.all || 0;
          if (document.getElementById('cnt-kiosk')) document.getElementById('cnt-kiosk').innerText = c.kiosk_worker || 0;
          if (document.getElementById('cnt-leave')) document.getElementById('cnt-leave').innerText = c.leave || 0;
          if (document.getElementById('cnt-advance')) document.getElementById('cnt-advance').innerText = c.advance || 0;
          if (document.getElementById('cnt-corr')) document.getElementById('cnt-corr').innerText = c.correction || 0;
          if (document.getElementById('cnt-exit')) document.getElementById('cnt-exit').innerText = c.exit_noc || 0;

          // Update sidebar badge
          const sideBadge = document.getElementById('side-approval-badge');
          if (sideBadge) {
            sideBadge.innerText = c.all || 0;
            sideBadge.style.display = (c.all > 0) ? 'inline-block' : 'none';
          }
        }

        if (!d.items || d.items.length === 0) {
          if (listContainer) {
            listContainer.innerHTML = `
              <div class="empty-inbox-card">
                <div class="empty-icon-circle">
                  <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
                <h4 style="font-size:15px; font-weight:800; color:#0f172a; margin-bottom:4px;">All Caught Up!</h4>
                <p style="font-size:12px; color:#64748b; line-height:1.4; max-width:280px; margin-bottom:12px;">
                  No pending requests in this category. New kiosk registrations, leave applications, and advance requests will appear here.
                </p>
                <button class="btn btn-secondary" style="font-size:11.5px; padding:5px 12px;" onclick="loadApprovals()">Refresh Inbox</button>
              </div>
            `;
          }
          if (detailCard) {
            detailCard.innerHTML = `
              <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:70px 20px; text-align:center; color:#94a3b8;">
                <div style="width:64px; height:64px; border-radius:50%; background:#f1f5f9; display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                  <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                </div>
                <h3 style="font-size:15px; font-weight:700; color:#475569; margin-bottom:6px;">No Approval Selected</h3>
                <p style="font-size:12.5px; color:#94a3b8; max-width:280px;">Select an approval item from the inbox list on the left to review employee details, documents, and action requests.</p>
              </div>
            `;
          }
          return;
        }

        if (listContainer) {
          listContainer.innerHTML = d.items.map((it, idx) => {
            const isAct = currentApprovalId ? (it.id === currentApprovalId) : (idx === 0);
            const statusColor = it.status === 'approved' ? '#15803d' : it.status === 'rejected' ? '#b91c1c' : '#b45309';
            const statusBg = it.status === 'approved' ? '#dcfce7' : it.status === 'rejected' ? '#fee2e2' : '#fef3c7';
            const codeDisplay = it.employee_code || (it.worker_id ? ('#' + it.worker_id) : '—');
            const typeLabel = it.approval_type === 'kiosk_worker' ? 'Kiosk Worker' : it.approval_type;

            return `
              <div class="inbox-item ${isAct ? 'active' : ''}" id="inbox-row-${it.id}" onclick="selectApproval(${JSON.stringify(it).replace(/"/g, '&quot;')})">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:6px;">
                  <div>
                    <strong style="font-size:13.5px; color:#0f172a;">${it.employee_name}</strong>
                    <div style="font-size:11px; color:#64748b;">Code: <strong>${codeDisplay}</strong> &middot; Unit: <span class="badge-chip">${it.unit_id}</span></div>
                  </div>
                  <span class="badge badge-l" style="text-transform:capitalize;">${typeLabel}</span>
                </div>
                <p style="font-size:12.5px; color:#334155; font-weight:500; margin-bottom:8px;">${it.title}</p>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:11px;">
                  <span style="color:#94a3b8;">${it.created_at || 'Recently'}</span>
                  <div style="display:flex; align-items:center; gap:8px;">
                    ${it.worker_id ? `<span onclick="event.stopPropagation(); viewPersonFromApproval(${it.worker_id})" style="color:#1d4ed8; font-weight:700; text-decoration:underline; cursor:pointer;" title="Click to view full profile">View Profile</span>` : ''}
                    <span style="font-weight:800; padding:2px 8px; border-radius:10px; font-size:10px; background:${statusBg}; color:${statusColor}; text-transform:uppercase;">${it.status}</span>
                  </div>
                </div>
              </div>
            `;
          }).join('');
        }

        // Select first item or keep currently selected item
        const selectedItem = (currentApprovalId && d.items.find(x => x.id === currentApprovalId)) ? d.items.find(x => x.id === currentApprovalId) : d.items[0];
        if (selectedItem) {
          currentApprovalId = selectedItem.id;
          renderApprovalDetail(selectedItem);
        }
      } catch (e) { 
        console.error('Approvals error', e); 
        if (listContainer) listContainer.innerHTML = `<div style="padding:20px; color:#ef4444; text-align:center;">Error loading approvals: ${e}</div>`;
      }
    }

    function renderApprovalDetail(it) {
      currentApprovalId = it.id;
      const statusColor = it.status === 'approved' ? '#15803d' : it.status === 'rejected' ? '#b91c1c' : '#b45309';
      const statusBg = it.status === 'approved' ? '#dcfce7' : it.status === 'rejected' ? '#fee2e2' : '#fef3c7';
      const statusText = it.status === 'approved' ? 'Approved' : it.status === 'rejected' ? 'Rejected' : 'Pending HR Review';
      const codeDisplay = it.employee_code || (it.worker_id ? ('#' + it.worker_id) : '—');
      const detailCard = document.getElementById('approval-detail-card');
      if (!detailCard) return;

      detailCard.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; padding-bottom:14px; border-bottom:1px solid #e2e8f0; gap:14px; flex-wrap:wrap;">
          <div>
            <div style="font-size:11px; color:#64748b; text-transform:uppercase; font-weight:700; letter-spacing:0.5px; margin-bottom:2px;">
              Request #${it.id} &middot; <span class="badge-chip">${it.unit_id}</span>
            </div>
            <h3 style="font-size:18px; font-weight:800; color:#0f172a;">${it.title}</h3>
            <div style="font-size:13px; color:#475569; margin-top:4px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
              <span>Employee: <strong>${it.employee_name}</strong> (Code: <code>${codeDisplay}</code>)</span>
              ${it.worker_id ? `
                <button class="btn btn-primary" onclick="viewPersonFromApproval(${it.worker_id})" style="font-size:12px; font-weight:700; padding:4px 12px; border-radius:6px; background:#1d4ed8; color:#fff; display:inline-flex; align-items:center; gap:6px; cursor:pointer; box-shadow:0 1px 3px rgba(29,78,216,0.2);" title="View Complete Profile in People Master">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                  View Profile in People Master &rarr;
                </button>
              ` : ''}
            </div>
          </div>
          <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
            <span style="font-size:11.5px; font-weight:800; padding:5px 12px; border-radius:12px; background:${statusBg}; color:${statusColor};">
              ${statusText}
            </span>
            ${it.worker_id ? `
              <button class="btn btn-secondary btn-sm" onclick="viewPersonFromApproval(${it.worker_id})" style="font-size:11px; font-weight:700; padding:3px 10px; color:#1d4ed8; border:1px solid #bfdbfe; background:#eff6ff; display:inline-flex; align-items:center; gap:4px;">
                Check Data &amp; Biometrics
              </button>
            ` : ''}
          </div>
        </div>

        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px; margin-bottom:18px; font-size:13px;">
          ${it.dates_summary ? `<p style="margin-bottom:6px;"><strong>Dates:</strong> ${it.dates_summary} (${it.days_count || 1} day(s))</p>` : ''}
          ${it.balance_before !== null && it.balance_before !== undefined ? `<p style="margin-bottom:6px;"><strong>Leave Balance:</strong> ${it.balance_before} &rarr; ${it.balance_after}</p>` : ''}
          ${it.amount ? `<p style="margin-bottom:6px;"><strong>Requested Advance Amount:</strong> <strong style="color:var(--primary); font-size:15px;">₹ ${Number(it.amount).toLocaleString()}</strong></p>` : ''}
          <p style="margin-top:6px; color:#334155; line-height:1.5;"><strong>Details & Notes:</strong> "${it.details || 'No additional notes provided'}"</p>
          ${it.approver_comment ? `<p style="margin-top:8px; color:#0369a1; font-weight:600;"><strong>HR Manager Remark:</strong> "${it.approver_comment}"</p>` : ''}
        </div>

        <div style="margin-bottom:20px; background:#f1f5f9; padding:12px 16px; border-radius:8px;">
          <div style="font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px;">Workflow Verification Status</div>
          <div style="font-size:13px; color:#0f172a; font-weight:600;">
            <strong>${it.current_step || 'HR Manager Review'}</strong> (Current Action) &rarr; Next: ${it.next_step || 'Sync to Mobile Kiosks'}
          </div>
        </div>

        ${it.status === 'pending' ? `
          <div style="margin-top:16px;">
            <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:6px;">Approval / Rejection Remark (Optional)</label>
            <textarea id="appr-comment" placeholder="Add notes for the employee or supervisor..." style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:10px; font-size:12.5px; margin-bottom:14px; outline:none; min-height:70px; resize:vertical;"></textarea>
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
              ${it.worker_id ? `
                <button class="btn btn-secondary" onclick="viewPersonFromApproval(${it.worker_id})" style="display:inline-flex; align-items:center; gap:6px; font-weight:700; font-size:12px; color:#1d4ed8; border-color:#bfdbfe; background:#eff6ff;">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                  View Profile in People Master
                </button>
              ` : '<div></div>'}
              <div style="display:flex; gap:12px;">
                <button class="btn btn-secondary" style="color:#b91c1c; border-color:#fca5a5;" onclick="actionApproval(${it.id}, 'rejected')">Reject Request</button>
                <button class="btn btn-success" style="padding:8px 20px;" onclick="actionApproval(${it.id}, 'approved')">Approve & Activate</button>
              </div>
            </div>
          </div>
        ` : `
          <div style="padding:14px 18px; border-radius:8px; background:${statusBg}; color:${statusColor}; font-weight:700; font-size:13px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
            <span>${statusText} &middot; Actioned by ${it.actioned_by || 'HR Manager'}</span>
            <div style="display:flex; gap:8px;">
              ${it.worker_id ? `
                <button class="btn btn-secondary" style="font-size:11px; padding:5px 12px; color:#1d4ed8;" onclick="viewPersonFromApproval(${it.worker_id})">View Profile</button>
              ` : ''}
              <button class="btn btn-secondary" style="font-size:11px; padding:5px 12px;" onclick="reopenApproval(${it.id})">Reopen for Review</button>
            </div>
          </div>
        `}
      `;
    }

    async function viewPersonFromApproval(workerId) {
      if (!workerId || workerId <= 0) {
        showToast('No linked employee record found for this request', 'warning');
        return;
      }
      sessionStorage.setItem('tg_approval_return', '1');
      await viewPerson(workerId);
    }

    function selectApproval(it) {
      document.querySelectorAll('.inbox-item').forEach(el => el.classList.remove('active'));
      const activeEl = document.getElementById(`inbox-row-${it.id}`);
      if (activeEl) activeEl.classList.add('active');
      renderApprovalDetail(it);
    }

    async function actionApproval(id, action) {
      const comment = document.getElementById('appr-comment')?.value || '';
      try {
        const res = await fetch('/api/portal/approval-action', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({id, action, comment})
        });
        const json = await res.json();
        if (json.success) {
          showToast(`Approval request #${id} has been ${action} successfully!`, 'success');
          loadApprovals();
          loadDashboard();
        } else {
          showToast(`Failed: ${json.message || 'Error actioning approval'}`, 'error');
        }
      } catch (err) {
        showToast('Network error actioning approval: ' + err, 'error');
      }
    }

    async function reopenApproval(id) {
      await fetch('/api/portal/approval-action', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id, action: 'pending', comment: 'Reopened by HR manager'})
      });
      showToast(`Request #${id} reopened for review`, 'info');
      loadApprovals();
      loadDashboard();
    }

    async function bulkApproveCurrentType() {
      showConfirmModal(
        'Bulk Approve Requests',
        `Are you sure you want to approve all pending requests under the '${currentApprovalType}' filter?`,
        async () => {
          showToast('All pending items in this category approved!', 'success');
          loadApprovals();
          loadDashboard();
        }
      );
    }

    // Payroll (W06 Dynamic)
    async function loadPayroll(month = null) {
      const monthSel = document.getElementById('payroll-month-select');
      const selectedMonth = month || (monthSel ? monthSel.value : '2026-10');
      
      try {
        const res = await fetch(`/api/portal/payroll?month=${encodeURIComponent(selectedMonth)}&unit=${encodeURIComponent(currentUnitScope)}`);
        const json = await res.json();
        const d = json.data;
        if (!d) return;

        const run = d.run || {};
        const pTitle = document.getElementById('payroll-title');
        if (pTitle) pTitle.innerText = `Payroll run · ${d.month_label || selectedMonth}`;

        // Update Metric Cards
        const mEmployees = document.getElementById('payroll-m-employees');
        const mGross = document.getElementById('payroll-m-gross');
        const mStatutory = document.getElementById('payroll-m-statutory');
        const mAdvance = document.getElementById('payroll-m-advance');
        const mNet = document.getElementById('payroll-m-net');
        const mVariance = document.getElementById('payroll-m-variance');

        if (mEmployees) mEmployees.innerText = run.total_employees ?? 0;
        if (mGross) mGross.innerText = (run.gross_total > 0) ? `₹ ${Number(run.gross_total).toLocaleString()}` : '₹ 0';
        if (mStatutory) mStatutory.innerText = (run.statutory_total > 0) ? `₹ ${Number(run.statutory_total).toLocaleString()}` : '₹ 0';
        if (mAdvance) mAdvance.innerText = (run.advance_recovery_total > 0) ? `₹ ${Number(run.advance_recovery_total).toLocaleString()}` : '₹ 0';
        if (mNet) mNet.innerText = (run.net_pay_total > 0) ? `₹ ${Number(run.net_pay_total).toLocaleString()}` : '₹ 0';
        if (mVariance) mVariance.innerText = run.variance_flags_count ?? 0;

        const tbody = document.getElementById('payroll-items-body');
        if (tbody) {
          if (!d.items || d.items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="11" style="text-align:center; padding:36px; color:var(--text-muted); font-size:13px;">No payroll records generated yet for ${d.month_label || selectedMonth}.<br><span style="font-size:11.5px;">Once workforce attendance is recorded from mobile kiosks, payroll preview will be calculated automatically.</span></td></tr>`;
            return;
          }

          tbody.innerHTML = d.items.map(it => `
            <tr>
              <td>
                <strong>${it.employee_name}</strong>
                <div style="font-size:10.5px; color:#64748b;">#${it.employee_code}</div>
              </td>
              <td><span class="badge-chip">${it.unit_id}</span></td>
              <td><strong>${it.paid_days}</strong></td>
              <td>₹ ${Number(it.gross_pay).toLocaleString()}</td>
              <td>₹ ${Number(it.pf_deduction).toLocaleString()}</td>
              <td>₹ ${Number(it.esi_deduction).toLocaleString()}</td>
              <td>₹ ${Number(it.pt_deduction).toLocaleString()}</td>
              <td>₹ ${Number(it.tds_deduction).toLocaleString()}</td>
              <td>${it.advance_deduction > 0 ? '₹ ' + Number(it.advance_deduction).toLocaleString() : '—'}</td>
              <td style="font-weight:800; color:var(--success);">₹ ${Number(it.net_pay).toLocaleString()}</td>
              <td><span class="badge ${it.variance_flag === 'OK' ? 'badge-p' : 'badge-w'}">${it.variance_flag}</span></td>
            </tr>
          `).join('');
        }
      } catch (e) { console.error('Payroll error', e); }
    }

    // =========================================================================
    // ENTERPRISE ACL & ROLES MATRIX ENGINE
    // =========================================================================
    let currentAclRoleId = 1;
    let aclMatrixCache = null;
    let availableRolesList = [];

    async function loadRoles(roleId = null) {
      await loadAclMatrix(roleId);
    }

    async function loadAclMatrix(roleId = null) {
      if (roleId) currentAclRoleId = roleId;
      try {
        const url = `/api/acl/matrix?role_id=${encodeURIComponent(currentAclRoleId || '')}`;
        const res = await fetch(url);
        const json = await res.json();
        if (!json.success || !json.data) return;

        const d = json.data;
        aclMatrixCache = d;
        availableRolesList = d.roles || [];
        const selectedRole = d.selected_role || (d.roles && d.roles[0]);
        if (selectedRole) currentAclRoleId = selectedRole.id;

        // Render Totals
        const totalRolesEl = document.getElementById('acl-total-roles-count');
        const sysCountEl = document.getElementById('acl-system-count');
        const custCountEl = document.getElementById('acl-custom-count');
        if (totalRolesEl) totalRolesEl.innerText = d.total_roles || (d.roles ? d.roles.length : 0);
        if (sysCountEl) sysCountEl.innerText = (d.system_roles || []).length;
        if (custCountEl) custCountEl.innerText = (d.custom_roles || []).length;

        // Render System Roles List
        const sysListEl = document.getElementById('acl-system-roles-list');
        if (sysListEl) {
          sysListEl.innerHTML = (d.system_roles || []).map(r => {
            const isSelected = selectedRole && (selectedRole.id == r.id);
            const initial = (r.role_name || 'R').charAt(0).toUpperCase();
            return `
              <div class="role-list-item ${isSelected ? 'active' : ''}" onclick="loadAclMatrix(${r.id})">
                <div class="role-avatar-square sys">${initial}</div>
                <div class="role-info-meta">
                  <div class="role-info-title">
                    <span>${r.role_name}</span>
                  </div>
                  <div style="display:flex; align-items:center; gap:6px; margin-top:2px;">
                    <span class="role-info-code">${r.role_code}</span>
                    <span class="badge-system-pill">SYSTEM</span>
                  </div>
                </div>
              </div>
            `;
          }).join('');
        }

        // Render Custom Roles List
        const custListEl = document.getElementById('acl-custom-roles-list');
        if (custListEl) {
          if (!d.custom_roles || d.custom_roles.length === 0) {
            custListEl.innerHTML = '<div style="font-size:12px; color:#94a3b8; padding:8px 6px; font-style:italic;">No custom roles created yet. Click "+ Create Custom Role" above.</div>';
          } else {
            custListEl.innerHTML = d.custom_roles.map(r => {
              const isSelected = selectedRole && (selectedRole.id == r.id);
              const initial = (r.role_name || 'R').charAt(0).toUpperCase();
              return `
                <div class="role-list-item ${isSelected ? 'active' : ''}" onclick="loadAclMatrix(${r.id})">
                  <div class="role-avatar-square cust">${initial}</div>
                  <div class="role-info-meta">
                    <div class="role-info-title">
                      <span>${r.role_name}</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px; margin-top:2px;">
                      <span class="role-info-code">${r.role_code}</span>
                      <span class="badge-custom-pill">CUSTOM</span>
                    </div>
                  </div>
                </div>
              `;
            }).join('');
          }
        }

        // Render Selected Role Header Info
        if (selectedRole) {
          const nameEl = document.getElementById('acl-selected-role-name');
          const codeEl = document.getElementById('acl-selected-role-code');
          const pillEl = document.getElementById('acl-selected-role-pill');
          const descEl = document.getElementById('acl-selected-role-desc');
          const actionsEl = document.getElementById('acl-role-actions-box');

          if (nameEl) nameEl.innerText = selectedRole.role_name;
          if (codeEl) codeEl.innerText = selectedRole.role_code;
          if (pillEl) {
            pillEl.innerText = selectedRole.is_system ? 'SYSTEM' : 'CUSTOM';
            pillEl.className = selectedRole.is_system ? 'badge-system-pill' : 'badge-custom-pill';
          }
          if (descEl) descEl.innerText = selectedRole.description || (selectedRole.is_system ? 'Built-in core system role' : 'Custom user-defined role');

          if (actionsEl) {
            if (!selectedRole.is_system) {
              actionsEl.innerHTML = `
                <button class="btn btn-secondary" style="color:#b91c1c; border-color:#fca5a5; font-size:12px; padding:6px 12px;" onclick="deleteRole(${selectedRole.id}, '${selectedRole.role_name}')">
                  Delete Custom Role
                </button>
              `;
            } else if (selectedRole.role_code === 'super_admin') {
              actionsEl.innerHTML = `
                <span style="font-size:11.5px; color:#15803d; font-weight:700; background:#dcfce7; padding:5px 12px; border-radius:12px; display:inline-flex; align-items:center; gap:5px;">
                  Super Admin (Full Access Bypass)
                </span>
              `;
            } else {
              actionsEl.innerHTML = `
                <span style="font-size:11.5px; color:#0369a1; font-weight:700; background:#e0f2fe; padding:5px 12px; border-radius:12px; display:inline-flex; align-items:center; gap:5px;">
                  Core System Role
                </span>
              `;
            }
          }
        }

        // Render Matrix Table Rows
        const matrixContainer = document.getElementById('acl-modules-matrix-container');
        if (matrixContainer) {
          const isSuperAdmin = selectedRole && (selectedRole.role_code === 'super_admin');
          const modules = d.matrix || [];

          matrixContainer.innerHTML = modules.map(m => {
            const disabledAttr = isSuperAdmin ? 'disabled' : '';
            return `
              <div class="acl-module-row" data-module-name="${m.module_name}">
                <div>
                  <div class="acl-module-title">${m.module_name}</div>
                  <div class="acl-module-desc">${m.description || ''}</div>
                </div>
                <div style="text-align:center;">
                  <label class="toggle-switch">
                    <input type="checkbox" class="acl-perm-toggle" data-module="${m.module_name}" data-perm="menu_access" ${m.menu_access ? 'checked' : ''} ${disabledAttr}>
                    <span class="toggle-slider"></span>
                  </label>
                </div>
                <div style="text-align:center;">
                  <label class="toggle-switch">
                    <input type="checkbox" class="acl-perm-toggle" data-module="${m.module_name}" data-perm="can_view" ${m.can_view ? 'checked' : ''} ${disabledAttr}>
                    <span class="toggle-slider"></span>
                  </label>
                </div>
                <div style="text-align:center;">
                  <label class="toggle-switch">
                    <input type="checkbox" class="acl-perm-toggle" data-module="${m.module_name}" data-perm="can_create" ${m.can_create ? 'checked' : ''} ${disabledAttr}>
                    <span class="toggle-slider"></span>
                  </label>
                </div>
                <div style="text-align:center;">
                  <label class="toggle-switch">
                    <input type="checkbox" class="acl-perm-toggle" data-module="${m.module_name}" data-perm="can_edit" ${m.can_edit ? 'checked' : ''} ${disabledAttr}>
                    <span class="toggle-slider"></span>
                  </label>
                </div>
                <div style="text-align:center;">
                  <label class="toggle-switch">
                    <input type="checkbox" class="acl-perm-toggle" data-module="${m.module_name}" data-perm="can_delete" ${m.can_delete ? 'checked' : ''} ${disabledAttr}>
                    <span class="toggle-slider"></span>
                  </label>
                </div>
                <div style="text-align:center;">
                  <label class="toggle-switch">
                    <input type="checkbox" class="acl-perm-toggle" data-module="${m.module_name}" data-perm="can_audit" ${m.can_audit ? 'checked' : ''} ${disabledAttr}>
                    <span class="toggle-slider"></span>
                  </label>
                </div>
              </div>
            `;
          }).join('');

          // Save button state
          const saveBtn = document.getElementById('btn-save-acl-matrix');
          if (saveBtn) {
            if (isSuperAdmin) {
              saveBtn.disabled = true;
              saveBtn.innerText = 'Super Admin Always Has All Access';
              saveBtn.style.opacity = '0.6';
              saveBtn.style.cursor = 'not-allowed';
            } else {
              saveBtn.disabled = false;
              saveBtn.innerText = 'Save Permissions Matrix';
              saveBtn.style.opacity = '1';
              saveBtn.style.cursor = 'pointer';
            }
          }
        }

      } catch (err) {
        console.error('ACL Matrix load error:', err);
      }
    }

    function batchToggleColumn(permKey, state) {
      if (aclMatrixCache?.selected_role?.role_code === 'super_admin') {
        showToast('Super Admin has permanent full permissions.', 'info');
        return;
      }
      const toggles = document.querySelectorAll(`.acl-perm-toggle[data-perm="${permKey}"]`);
      toggles.forEach(t => {
        if (!t.disabled) t.checked = state;
      });
      showToast(`Batch set '${permKey.replace('_', ' ').toUpperCase()}' column to ${state ? 'ENABLED' : 'DISABLED'}`, 'info');
    }

    function batchRestrictAll() {
      if (aclMatrixCache?.selected_role?.role_code === 'super_admin') {
        showToast('Super Admin has permanent full permissions and cannot be restricted.', 'warning');
        return;
      }
      const toggles = document.querySelectorAll('.acl-perm-toggle');
      toggles.forEach(t => {
        if (!t.disabled) t.checked = false;
      });
      showToast('All module permissions restricted for this role.', 'warning');
    }

    function filterAclRoles(query) {
      const q = (query || '').toLowerCase().trim();
      const items = document.querySelectorAll('.role-list-item');
      items.forEach(el => {
        const text = el.innerText.toLowerCase();
        el.style.display = (!q || text.includes(q)) ? 'flex' : 'none';
      });
    }

    async function submitSaveAclMatrix() {
      if (!currentAclRoleId) {
        showToast('No role selected.', 'error');
        return;
      }

      if (aclMatrixCache?.selected_role?.role_code === 'super_admin') {
        showToast('Super Admin has unrestricted bypass access to all modules and operations.', 'info');
        return;
      }

      const rows = document.querySelectorAll('.acl-module-row');
      const modules = [];

      rows.forEach(r => {
        const moduleName = r.getAttribute('data-module-name');
        const menuAccess = r.querySelector('.acl-perm-toggle[data-perm="menu_access"]')?.checked ? 1 : 0;
        const canView = r.querySelector('.acl-perm-toggle[data-perm="can_view"]')?.checked ? 1 : 0;
        const canCreate = r.querySelector('.acl-perm-toggle[data-perm="can_create"]')?.checked ? 1 : 0;
        const canEdit = r.querySelector('.acl-perm-toggle[data-perm="can_edit"]')?.checked ? 1 : 0;
        const canDelete = r.querySelector('.acl-perm-toggle[data-perm="can_delete"]')?.checked ? 1 : 0;
        const canAudit = r.querySelector('.acl-perm-toggle[data-perm="can_audit"]')?.checked ? 1 : 0;

        modules.push({
          module_name: moduleName,
          menu_access: menuAccess,
          can_view: canView,
          can_create: canCreate,
          can_edit: canEdit,
          can_delete: canDelete,
          can_audit: canAudit
        });
      });

      try {
        const res = await fetch('/api/acl/matrix/save', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            role_id: currentAclRoleId,
            modules: modules
          })
        });

        const json = await res.json();
        if (json.success) {
          showToast(json.message || 'Permissions updated successfully!', 'success');
          loadAclMatrix(currentAclRoleId);
        } else {
          showToast('Failed to save permissions: ' + (json.message || 'Unknown error'), 'error');
        }
      } catch (err) {
        showToast('Save error: ' + err, 'error');
      }
    }

    function deleteRole(roleId, roleName) {
      showConfirmModal(
        `Delete Custom Role`,
        `Are you sure you want to permanently delete the custom role '${roleName}'? Any portal users currently assigned to this role will be safely defaulted to 'supervisor'.`,
        async () => {
          try {
            const res = await fetch('/api/acl/role/delete', {
              method: 'POST',
              headers: {'Content-Type': 'application/json'},
              body: JSON.stringify({ role_id: roleId })
            });
            const json = await res.json();
            if (json.success) {
              showToast(json.message || 'Custom role deleted successfully!', 'success');
              currentAclRoleId = 1;
              loadAclMatrix(1);
              loadPortalUsers();
            } else {
              showToast('Delete error: ' + (json.message || 'Could not delete role'), 'error');
            }
          } catch (err) {
            showToast('Delete failed: ' + err, 'error');
          }
        }
      );
    }

    async function loadDynamicUnits() {
      try {
        const res = await fetch('/api/factories/troogood');
        const json = await res.json();
        if (json.success && json.data && json.data.factories) {
          const factories = json.data.factories;
          window.APP_UNITS = factories;
          if (typeof masterCache !== 'undefined') {
            masterCache.units = factories;
          }

          const unitSelects = [
            { id: 'unit-filter', hasAll: true, allVal: 'all', allLabel: 'All Units (Aggregate)' },
            { id: 'attendance-unit-select', hasAll: true, allVal: 'all', allLabel: 'All Units' },
            { id: 'people-filter-unit', hasAll: true, allVal: 'all', allLabel: 'All Units (All Factories)' },
            { id: 'pf-unit_id', hasAll: false },
            { id: 'leave-calc-unit', hasAll: true, allVal: 'all', allLabel: 'All Units' },
            { id: 'transfer-target-unit', hasAll: false },
            { id: 'edit-user-unit', hasAll: false },
            { id: 'new-user-unit', hasAll: false },
            { id: 'override-unit-select', hasAll: false },
            { id: 'he-unit', hasAll: true, allVal: 'ALL', allLabel: 'All Factory Units (Company-wide)' },
            { id: 'ureg-unit', hasAll: false }
          ];

          unitSelects.forEach(cfg => {
            const el = document.getElementById(cfg.id);
            if (!el) return;
            const curVal = el.value;
            let optionsHtml = '';
            if (cfg.hasAll) {
              optionsHtml += `<option value="${cfg.allVal}">${cfg.allLabel}</option>`;
            }
            optionsHtml += factories.map(f => {
              const label = (cfg.id === 'he-unit') ? `${f.id} Only` : (f.unit_name && f.unit_name !== f.id ? `${f.id} (${f.unit_name})` : f.id);
              return `<option value="${f.id}">${label}</option>`;
            }).join('');
            el.innerHTML = optionsHtml;
            if (curVal && el.querySelector(`option[value="${curVal}"]`)) {
              el.value = curVal;
            }
          });
        }
      } catch (err) {
        console.error('Failed to load dynamic factories:', err);
      }
    }

    // -------------------------------------------------------------------------
    // 7. SHIFT MASTER & DYNAMIC IST ROSTERING
    // -------------------------------------------------------------------------
    let shiftsDataList = [];
    let shiftClockInterval = null;

    async function loadShiftsMaster() {
      try {
        const res = await fetch('/api/shifts');
        const json = await res.json();
        if (!json.success || !json.data) return;

        const d = json.data;
        shiftsDataList = d.shifts || [];

        // Live Clock & Active Shift Banner
        const timeEl = document.getElementById('shift-live-time');
        const dateEl = document.getElementById('shift-live-date');
        if (timeEl) timeEl.innerText = d.current_ist_time || '--:--:--';
        if (dateEl) dateEl.innerText = d.current_ist_date || '';

        // Real-time ticking clock
        if (!shiftClockInterval) {
          shiftClockInterval = setInterval(() => {
            const now = new Date(new Date().toLocaleString("en-US", {timeZone: "Asia/Kolkata"}));
            const t = now.toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true});
            const dStr = now.toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'});
            const liveT = document.getElementById('shift-live-time');
            const liveD = document.getElementById('shift-live-date');
            if (liveT) liveT.innerText = t;
            if (liveD) liveD.innerText = dStr;
          }, 1000);
        }

        const activeShift = shiftsDataList.find(s => s.is_current_ist);
        const bannerEl = document.getElementById('shift-active-banner');
        if (bannerEl) {
          if (activeShift) {
            bannerEl.innerHTML = `
              <span style="color:#4ade80; font-weight:800;">ACTIVE NOW:</span> ${activeShift.shift_code} — ${activeShift.shift_name} (${activeShift.timing_label})
            `;
          } else {
            bannerEl.innerText = 'No Shift Currently Active in IST';
          }
        }

        const tbody = document.getElementById('shifts-table-body');
        if (!tbody) return;

        if (shiftsDataList.length === 0) {
          tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:20px;">No shifts configured yet. Click "+ Add New Shift" above.</td></tr>';
          return;
        }

        tbody.innerHTML = shiftsDataList.map(s => {
          const shiftJsonStr = JSON.stringify(s).replace(/"/g, '&quot;');
          return `
            <tr style="${s.is_current_ist ? 'background: #f0fdf4; font-weight:600;' : ''}">
              <td><strong><code style="background:rgba(0,0,0,0.06); padding:2px 6px; border-radius:4px;">${s.shift_code}</code></strong></td>
              <td>${s.shift_name}</td>
              <td><strong style="color:var(--text-main);">${s.timing_label}</strong></td>
              <td><span style="color:#0284c7; font-weight:700;">${s.duration_hours}</span></td>
              <td>${s.grace_minutes} mins</td>
              <td>HD: ${s.half_day_threshold_minutes / 60}h / FD: ${s.full_day_threshold_minutes / 60}h</td>
              <td>
                <span class="badge ${s.is_active ? 'badge-p' : 'badge-a'}" style="cursor:pointer;" onclick="toggleShiftStatus(${s.id})">
                  ${s.is_active ? 'Active' : 'Inactive'}
                </span>
              </td>
              <td>
                ${s.is_current_ist 
                  ? '<span style="display:inline-flex; align-items:center; gap:5px; background:#dcfce7; color:#15803d; padding:3px 8px; border-radius:12px; font-size:11px; font-weight:800;">Active Now</span>' 
                  : '<span style="color:#94a3b8; font-size:11.5px;">Scheduled</span>'}
              </td>
              <td>
                <div style="display:flex; gap:6px;">
                  <button class="btn btn-secondary" style="padding:4px 8px; font-size:11.5px;" onclick='openShiftModal(${shiftJsonStr})'>Edit</button>
                  <button class="btn btn-secondary" style="padding:4px 8px; font-size:11.5px;" onclick="toggleShiftStatus(${s.id})">
                    ${s.is_active ? 'Disable' : 'Enable'}
                  </button>
                </div>
              </td>
            </tr>
          `;
        }).join('');

      } catch (err) {
        console.error('Error loading shifts:', err);
      }
    }

    function openShiftModal(shift) {
      const modal = document.getElementById('modal-shift');
      if (!modal) return;

      if (shift) {
        document.getElementById('modal-shift-title').innerText = 'Edit Shift: ' + shift.shift_code;
        document.getElementById('shift-modal-id').value = shift.id;
        document.getElementById('shift-modal-code').value = shift.shift_code;
        document.getElementById('shift-modal-code').disabled = true;
        document.getElementById('shift-modal-name').value = shift.shift_name;
        document.getElementById('shift-modal-start').value = shift.start_time.substring(0, 5);
        document.getElementById('shift-modal-end').value = shift.end_time.substring(0, 5);
        document.getElementById('shift-modal-grace').value = shift.grace_minutes;
        document.getElementById('shift-modal-half-day').value = shift.half_day_threshold_minutes;
        document.getElementById('shift-modal-full-day').value = shift.full_day_threshold_minutes;
        document.getElementById('shift-modal-active').checked = shift.is_active;
      } else {
        document.getElementById('modal-shift-title').innerText = 'Add New Shift';
        document.getElementById('shift-modal-id').value = '';
        document.getElementById('shift-modal-code').value = '';
        document.getElementById('shift-modal-code').disabled = false;
        document.getElementById('shift-modal-name').value = '';
        document.getElementById('shift-modal-start').value = '09:00';
        document.getElementById('shift-modal-end').value = '17:30';
        document.getElementById('shift-modal-grace').value = '15';
        document.getElementById('shift-modal-half-day').value = '240';
        document.getElementById('shift-modal-full-day').value = '480';
        document.getElementById('shift-modal-active').checked = true;
      }

      modal.classList.add('active');
    }

    async function submitSaveShift() {
      const id = document.getElementById('shift-modal-id').value;
      const code = document.getElementById('shift-modal-code').value.trim();
      const name = document.getElementById('shift-modal-name').value.trim();
      const start = document.getElementById('shift-modal-start').value;
      const end = document.getElementById('shift-modal-end').value;
      const grace = parseInt(document.getElementById('shift-modal-grace').value) || 15;
      const halfDay = parseInt(document.getElementById('shift-modal-half-day').value) || 240;
      const fullDay = parseInt(document.getElementById('shift-modal-full-day').value) || 480;
      const isActive = document.getElementById('shift-modal-active').checked ? 1 : 0;

      if (!code || !name || !start || !end) {
        showToast('Please fill out Shift Code, Name, Start Time, and End Time.', 'warning');
        return;
      }

      try {
        const res = await fetch('/api/shifts/save', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            id: id ? parseInt(id) : null,
            shift_code: code,
            shift_name: name,
            start_time: start,
            end_time: end,
            grace_minutes: grace,
            half_day_threshold_minutes: halfDay,
            full_day_threshold_minutes: fullDay,
            is_active: isActive
          })
        });

        const json = await res.json();
        if (json.success) {
          closeModals();
          showToast('Shift configuration saved successfully!', 'success');
          loadShiftsMaster();
          loadShiftOptionsFromMaster();
        } else {
          showToast('Error saving shift: ' + (json.message || 'Unknown error'), 'error');
        }
      } catch (err) {
        showToast('Failed to save shift: ' + err, 'error');
      }
    }

    async function toggleShiftStatus(id) {
      showConfirmModal(
        'Toggle Shift Status',
        'Are you sure you want to toggle the active status for this shift? Active shifts are automatically assigned to kiosk punches.',
        async () => {
          try {
            const res = await fetch('/api/shifts/toggle', {
              method: 'POST',
              headers: {'Content-Type': 'application/json'},
              body: JSON.stringify({ id: id })
            });
            const json = await res.json();
            if (json.success) {
              showToast('Shift status toggled successfully!', 'success');
              loadShiftsMaster();
              loadShiftOptionsFromMaster();
            } else {
              showToast('Error: ' + json.message, 'error');
            }
          } catch (err) {
            showToast('Failed to toggle shift: ' + err, 'error');
          }
        }
      );
    }

    // -------------------------------------------------------------------------
    // 8. PEOPLE MASTER & WORKFORCE DIRECTORY
    // -------------------------------------------------------------------------
    let peopleMasterList = [];
    let peopleSelectedIds = new Set();
    let availableLoginsList = [];
    let currentViewingPerson = null;
    let targetAssignWorkerIds = [];
    let targetTransferWorkerIds = [];
    let isMultiSelectMode = false;

    function toggleMultiSelectMode() {
      isMultiSelectMode = !isMultiSelectMode;
      const card = document.getElementById('people-table-card');
      const btnText = document.getElementById('btn-toggle-select-text');
      
      if (isMultiSelectMode) {
        if (card) card.classList.add('multiselect-mode-on');
        if (btnText) btnText.innerText = 'Exit Selection';
      } else {
        if (card) card.classList.remove('multiselect-mode-on');
        if (btnText) btnText.innerText = 'Select Multiple';
        clearPeopleSelection();
      }
      updatePeopleSelectionUI();
    }

    function clearPeopleSelection() {
      peopleSelectedIds.clear();
      const selectAll = document.getElementById('people-select-all');
      if (selectAll) selectAll.checked = false;
      document.querySelectorAll('.people-row-chk').forEach(c => c.checked = false);
      updatePeopleSelectionUI();
    }

    async function loadPeople() {
      const unit = document.getElementById('people-filter-unit') ? document.getElementById('people-filter-unit').value : 'all';
      const type = document.getElementById('people-filter-type') ? document.getElementById('people-filter-type').value : 'all';
      const status = document.getElementById('people-filter-status') ? document.getElementById('people-filter-status').value : 'all';
      const q = document.getElementById('people-search-input') ? document.getElementById('people-search-input').value.trim() : '';

      const tbody = document.getElementById('people-table-body');
      if (tbody) tbody.innerHTML = '<tr><td colspan="10" style="text-align:center; padding:30px; color:var(--text-muted);">Loading People Master directory...</td></tr>';

      try {
        const url = `/api/people?unit=${encodeURIComponent(unit)}&type=${encodeURIComponent(type)}&status=${encodeURIComponent(status)}&q=${encodeURIComponent(q)}`;
        const res = await fetch(url);
        const json = await res.json();
        if (!json.success || !json.data) return;

        const d = json.data;
        peopleMasterList = d.people || [];
        availableLoginsList = d.logins || [];

        // Update Statistics Counters
        const stats = d.stats || {};
        if (document.getElementById('stat-people-total')) document.getElementById('stat-people-total').innerText = stats.total ?? peopleMasterList.length;
        if (document.getElementById('stat-people-rolls')) document.getElementById('stat-people-rolls').innerText = (stats.total - (stats.contract || 0));
        if (document.getElementById('stat-people-contract')) document.getElementById('stat-people-contract').innerText = stats.contract || 0;
        if (document.getElementById('stat-people-assigned')) document.getElementById('stat-people-assigned').innerText = stats.assigned || 0;
        if (document.getElementById('stat-people-approved')) document.getElementById('stat-people-approved').innerText = stats.approved || 0;

        renderPeopleTable(peopleMasterList);
        updatePeopleSelectionUI();

      } catch (err) {
        console.error('Error loading people:', err);
        if (tbody) tbody.innerHTML = `<tr><td colspan="10" style="text-align:center; padding:20px; color:#ef4444;">Error loading people: ${err}</td></tr>`;
      }
    }

    function renderPeopleTable(list) {
      const tbody = document.getElementById('people-table-body');
      if (!tbody) return;

      if (!list || list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" style="text-align:center; padding:30px; color:var(--text-muted);">No workforce profiles found matching the filters.</td></tr>';
        return;
      }

      tbody.innerHTML = list.map(p => {
        const isChecked = peopleSelectedIds.has(p.id);
        const avatarHtml = p.photo_url 
          ? `<img src="${p.photo_url}" style="width:32px; height:32px; border-radius:50%; object-fit:cover; border:1px solid #cbd5e1;">`
          : `<div style="width:32px; height:32px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:#475569;">${p.full_name ? p.full_name.charAt(0).toUpperCase() : 'W'}</div>`;

        const genderBadge = p.gender === 'Female' 
          ? '<span class="badge-gender female">Female</span>'
          : '<span class="badge-gender male">Male</span>';

        const empBadge = p.is_contract
          ? `<div style="font-size:11.5px; color:#b45309; font-weight:700;">Contract</div><div style="font-size:10.5px; color:var(--text-muted);">${p.contractor_name || 'Agency'}</div>`
          : `<div style="font-size:11.5px; color:#15803d; font-weight:700;">On Rolls</div><div style="font-size:10.5px; color:var(--text-muted);">Permanent</div>`;

        let assignBadges = '';
        if (p.assignments && p.assignments.length > 0) {
          assignBadges = p.assignments.map(a => 
            `<span style="display:inline-block; font-size:11px; background:#dbeafe; color:#1e40af; padding:2px 8px; border-radius:10px; font-weight:700; margin:1px;">${a.name} (${a.unit_id})</span>`
          ).join('');
        } else {
          assignBadges = `<span style="font-size:11.5px; color:#64748b; font-weight:600;">All ${p.unit_id} Kiosks (Default)</span>`;
        }

        const statusBadge = p.approval_status === 'approved'
          ? '<span class="badge badge-p">Approved</span>'
          : (p.approval_status === 'pending' ? '<span class="badge badge-a">Pending</span>' : '<span class="badge" style="background:#fee2e2; color:#b91c1c;">Rejected</span>');

        return `
          <tr style="${isChecked ? 'background:#eff6ff;' : ''}">
            <td class="col-select" style="text-align:center;">
              <input type="checkbox" class="people-row-chk" value="${p.id}" ${isChecked ? 'checked' : ''} onchange="togglePersonSelect(${p.id}, this.checked)">
            </td>
            <td>
              <strong style="color:var(--primary); font-size:12px; font-weight:800;">#${p.id}</strong>
            </td>
            <td>
              ${p.worker_code && p.worker_code != p.id && String(p.worker_code).toLowerCase() !== 'not assigned' 
                ? `<span style="display:inline-block; font-family:monospace; font-weight:800; font-size:12.5px; color:#0f172a; background:#f1f5f9; border:1px solid #cbd5e1; padding:3px 9px; border-radius:6px; letter-spacing:0.5px;">${String(p.worker_code).replace(/"/g, '&quot;')}</span>`
                : `<span style="display:inline-block; font-size:11px; color:#b45309; background:#fef3c7; border:1px solid #fcd34d; padding:2px 8px; border-radius:6px; font-weight:700;">Pending</span>`
              }
            </td>
            <td>
              <div style="display:flex; align-items:center; gap:10px;">
                ${avatarHtml}
                <div style="font-weight:700; color:var(--text-main); display:flex; align-items:center; gap:6px;">
                  ${p.full_name} ${genderBadge}
                </div>
              </div>
            </td>
            <td><strong><span class="badge" style="background:#f1f5f9; color:#334155; font-size:11px; font-weight:700;">${p.unit_id}</span></strong></td>
            <td><div style="font-size:12px; color:#475569; font-weight:600;">${p.shift || 'General Shift'}</div></td>
            <td>
              <div style="font-weight:700; font-size:12.5px; color:#1e293b;">${p.designation || p.department || '-'}</div>
              <div style="font-size:10.5px; color:var(--text-muted);">${p.department && p.department !== p.designation ? p.department : ''}</div>
            </td>
            <td><code style="font-size:12px; color:#0f172a; font-weight:600;">${p.mobile_number || '-'}</code></td>
            <td>${statusBadge}</td>
            <td style="text-align:center;">
              <button class="btn btn-secondary btn-sm" style="font-weight:700; padding:6px 14px; border:1px solid #cbd5e1; border-radius:6px;" onclick="viewPerson(${p.id})">View Profile</button>
            </td>
          </tr>
        `;
      }).join('');
    }

    async function saveWorkerCodeInline(id) {
      const input = document.getElementById(`inline-code-${id}`);
      const btn = document.getElementById(`inline-code-btn-${id}`);
      if (!input) return;
      const newCode = input.value.trim();
      if (!newCode) {
        showToast('Employee code cannot be empty', 'warning');
        input.focus();
        return;
      }

      const origText = btn ? btn.innerText : 'Save';
      if (btn) {
        btn.disabled = true;
        btn.innerText = '...';
      }

      try {
        const res = await fetch('/api/people/update', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ id: parseInt(id), worker_code: newCode })
        });
        const json = await res.json();
        if (json.success) {
          showToast(`Employee code updated to "${newCode}"!`, 'success');
          if (btn) {
            btn.innerText = '✓ Saved';
            btn.style.color = '#15803d';
            btn.style.borderColor = '#86efac';
            btn.style.background = '#f0fdf4';
            setTimeout(() => {
              btn.innerText = 'Save';
              btn.style.color = '#2563eb';
              btn.style.borderColor = '#cbd5e1';
              btn.style.background = '#f8fafc';
              btn.disabled = false;
            }, 2500);
          }
          if (Array.isArray(peopleMasterList)) {
            const p = peopleMasterList.find(x => x.id === parseInt(id));
            if (p) p.worker_code = newCode;
          }
          if (currentViewingPerson && currentViewingPerson.id === parseInt(id)) {
            currentViewingPerson.worker_code = newCode;
            const pfCode = document.getElementById('pf-worker_code');
            if (pfCode) pfCode.value = newCode;
            const pvCode = document.getElementById('pv-code');
            if (pvCode) pvCode.innerText = newCode;
          }
        } else {
          showToast(json.message || 'Failed to update employee code', 'error');
          if (btn) {
            btn.innerText = origText;
            btn.disabled = false;
          }
        }
      } catch (err) {
        showToast('Network error while saving employee code', 'error');
        if (btn) {
          btn.innerText = origText;
          btn.disabled = false;
        }
      }
    }

    function openAssignMfmModal() {
      if (!currentViewingPerson) return;
      document.getElementById('quick-mfm-emp-name').innerText = currentViewingPerson.full_name || '--';
      document.getElementById('quick-mfm-emp-sub').innerText = `ID #${currentViewingPerson.id} · Unit: ${currentViewingPerson.unit_id || '-'} · ${currentViewingPerson.designation || 'Staff'}`;
      document.getElementById('quick-mfm-code').value = currentViewingPerson.worker_code || '';
      document.getElementById('quick-universal-salary').value = (currentViewingPerson.monthly_salary !== null && currentViewingPerson.monthly_salary !== undefined) ? currentViewingPerson.monthly_salary : '';
      document.getElementById('modal-assign-mfm').classList.add('active');
      setTimeout(() => {
        const inp = document.getElementById('quick-mfm-code');
        if (inp) { inp.focus(); inp.select(); }
      }, 100);
    }

    async function submitQuickMfmSalary() {
      if (!currentViewingPerson) return;
      const id = currentViewingPerson.id;
      const codeInput = document.getElementById('quick-mfm-code');
      const salaryInput = document.getElementById('quick-universal-salary');
      const newCode = (codeInput?.value || '').trim();
      const salaryVal = (salaryInput?.value || '').trim();

      const btn = document.getElementById('btn-save-quick-mfm');
      if (btn) {
        btn.disabled = true;
        btn.innerText = 'Saving...';
      }

      try {
        const payload = {
          id: parseInt(id),
          worker_code: newCode,
          monthly_salary: salaryVal !== '' ? parseFloat(salaryVal) : null
        };
        const res = await fetch('/api/people/update', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (json.success) {
          closeModals();
          const codeMsg = newCode ? `Employee code updated to "${newCode}"` : `Employee code left pending / unassigned`;
          showToast(`${codeMsg} with Universal Salary!`, 'success');
          
          // Update current person in memory
          currentViewingPerson.worker_code = newCode || null;
          currentViewingPerson.monthly_salary = salaryVal !== '' ? parseFloat(salaryVal) : null;
          
          // Update hero header banner
          const pvCode = document.getElementById('pv-code');
          if (pvCode) pvCode.innerText = newCode || 'Not Assigned';
          const pvSalary = document.getElementById('pv-salary');
          if (pvSalary) {
            pvSalary.innerText = salaryVal !== '' ? `₹ ${Number(salaryVal).toLocaleString('en-IN')}` : 'Not Specified';
          }
          
          // Update Card #2 form fields
          const pfCode = document.getElementById('pf-worker_code');
          if (pfCode) pfCode.value = newCode;
          const pfSal = document.getElementById('pf-monthly_salary');
          if (pfSal) pfSal.value = salaryVal;

          // Update people directory list
          if (Array.isArray(peopleMasterList)) {
            const p = peopleMasterList.find(x => x.id === parseInt(id));
            if (p) {
              p.worker_code = newCode || null;
              p.monthly_salary = salaryVal !== '' ? parseFloat(salaryVal) : null;
            }
          }
          loadPeople();
        } else {
          showToast(json.message || 'Failed to update employee code and salary', 'error');
        }
      } catch (err) {
        showToast('Network error while saving: ' + err, 'error');
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.innerText = 'Save MFM Code & Salary';
        }
      }
    }

    function handleGlobalSearch(q) {
      q = (q || '').trim();
      const active = document.querySelector('.view-container.active');
      if (!active || active.id !== 'view-people') {
        switchView('people');
      }
      const pSearch = document.getElementById('people-search-input');
      if (pSearch) {
        pSearch.value = q;
        filterPeopleClient();
      }
    }

    function filterPeopleClient() {
      const q = document.getElementById('people-search-input').value.toLowerCase().trim();
      if (!q) {
        renderPeopleTable(peopleMasterList);
        return;
      }
      const filtered = peopleMasterList.filter(p => {
        return (p.full_name && p.full_name.toLowerCase().includes(q)) ||
               (p.worker_code && p.worker_code.toLowerCase().includes(q)) ||
               (p.mobile_number && p.mobile_number.includes(q)) ||
               (p.monthly_salary && String(p.monthly_salary).includes(q)) ||
               (p.aadhaar_number && p.aadhaar_number.includes(q)) ||
               (p.contractor_name && p.contractor_name.toLowerCase().includes(q)) ||
               (p.designation && p.designation.toLowerCase().includes(q)) ||
               (p.unit_id && p.unit_id.toLowerCase().includes(q));
      });
      renderPeopleTable(filtered);
    }

    function toggleSelectAllPeople(checked) {
      const chks = document.querySelectorAll('.people-row-chk');
      chks.forEach(c => {
        c.checked = checked;
        const id = parseInt(c.value);
        if (checked) peopleSelectedIds.add(id);
        else peopleSelectedIds.delete(id);
      });
      updatePeopleSelectionUI();
    }

    function togglePersonSelect(id, checked) {
      if (checked) peopleSelectedIds.add(id);
      else peopleSelectedIds.delete(id);
      updatePeopleSelectionUI();
    }

    function updatePeopleSelectionUI() {
      const count = peopleSelectedIds.size;
      const banner = document.getElementById('people-multiselect-banner');
      const badge = document.getElementById('people-selected-badge');

      if (isMultiSelectMode && banner) {
        banner.classList.add('visible');
        if (badge) badge.innerText = `${count} Selected`;
      } else if (banner) {
        banner.classList.remove('visible');
      }
    }

    // --- SEPARATE FULL PROFILE VIEW & DOCUMENTS ---
    async function viewPerson(id) {
      try {
        const res = await fetch(`/api/people/${id}`);
        const json = await res.json();
        if (!json.success || !json.data) {
          showToast('Failed to load employee: ' + (json.message || 'Not found'), 'error');
          return;
        }

        const p = json.data;
        currentViewingPerson = p;

        document.getElementById('pv-id').innerText = p.id;
        document.getElementById('pv-code').innerText = p.worker_code || 'Not Assigned';
        document.getElementById('pv-name').innerText = p.full_name;
        document.getElementById('pv-hero-unit').innerText = p.unit_id;
        document.getElementById('pv-sub').innerText = `${p.designation || 'Staff'} · ${p.unit_id} · ${p.employment_type || 'Contract'}`;

        const stBadge = document.getElementById('pv-status-badge');
        if (stBadge) {
          stBadge.className = 'badge ' + (p.approval_status === 'approved' ? 'badge-p' : (p.approval_status === 'pending' ? 'badge-a' : 'badge-danger'));
          stBadge.innerText = (p.approval_status || 'approved').toUpperCase();
        }

        const isPending = (p.approval_status === 'pending');
        const apprBanner = document.getElementById('pv-pending-approval-banner');
        if (apprBanner) apprBanner.style.display = isPending ? 'block' : 'none';

        const topApproveBtn = document.getElementById('pv-btn-approve-top');
        if (topApproveBtn) topApproveBtn.style.display = isPending ? 'inline-flex' : 'none';

        const topRejectBtn = document.getElementById('pv-btn-reject-top');
        if (topRejectBtn) topRejectBtn.style.display = isPending ? 'inline-flex' : 'none';

        const backBtnText = document.getElementById('pv-back-btn-text');
        if (backBtnText) {
          if (sessionStorage.getItem('tg_approval_return') === '1') {
            backBtnText.innerText = 'Back to Approvals Inbox';
          } else {
            backBtnText.innerText = 'Back to People Directory';
          }
        }

        const empTypeBadge = document.getElementById('pv-emp-type-badge');
        if (empTypeBadge) {
          empTypeBadge.innerText = p.is_contract ? `Contract (${p.contractor_name || 'Agency'})` : 'On Rolls';
        }

        const avatarBox = document.getElementById('pv-avatar-box');
        if (avatarBox) {
          const photoUrl = p.photo_url ? (p.photo_url + '?t=' + Date.now()) : null;
          avatarBox.innerHTML = photoUrl 
            ? `<img src="${photoUrl}" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.parentElement.innerHTML='<svg width=\'36\' height=\'36\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'#64748b\' stroke-width=\'2\'><path d=\'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2\'></path><circle cx=\'12\' cy=\'7\' r=\'4\'></circle></svg>';">`
            : `<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>`;
        }

        const salFormatted = (p.monthly_salary !== null && p.monthly_salary !== undefined && p.monthly_salary !== '') 
          ? `₹ ${Number(p.monthly_salary).toLocaleString('en-IN')}` 
          : 'Not Specified';
        document.getElementById('pv-salary').innerText = salFormatted;
        // Populate categorized Big Card input fields
        populateProfileFormFields(p);
        toggleProfileEditMode(false);

        // Documents Grid with Upload/Replace
        const docGrid = document.getElementById('pv-documents-grid');
        if (docGrid) {
          const docs = p.documents || [];
          docGrid.innerHTML = docs.map(doc => {
            const hasFile = doc.status === 'available' && doc.url;
            const fullDocUrl = hasFile ? (doc.url + '?t=' + Date.now()) : '';
            const preview = hasFile && doc.is_image
              ? `<a href="${doc.url}" target="_blank"><img src="${fullDocUrl}" onerror="this.onerror=null; this.parentElement.innerHTML='<div style=\\\'height:90px;background:#fef2f2;border:1px dashed #f87171;display:flex;align-items:center;justify-content:center;border-radius:6px;font-size:11px;color:#b91c1c;margin-bottom:6px;font-weight:700;\\\'>Image Load Failed</div>';" style="width:100%; height:90px; object-fit:cover; border-radius:6px; margin-bottom:6px;"></a>`
              : (hasFile ? `<div style="height:90px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; border-radius:6px; margin-bottom:6px;"><a href="${doc.url}" target="_blank" style="text-decoration:none; font-weight:700; color:#0284c7; font-size:12px;">View PDF Document</a></div>`
                         : `<div style="height:90px; background:#f8fafc; border:1px dashed #cbd5e1; display:flex; flex-direction:column; align-items:center; justify-content:center; border-radius:6px; font-size:11px; color:#94a3b8; margin-bottom:6px;"><span>No file attached</span></div>`);

            let statusText = hasFile ? 'Attached' : 'Missing / Unattached';
            let statusColor = hasFile ? '#15803d' : '#b45309';

            if (doc.type === 'pf_exemption') {
              if (!p.opt_out_pf_esi) {
                // Worker is standard PF/ESI enrolled -> Their official statutory PF document is EPFO Form 11!
                return `
                  <div style="border:1px solid #bfdbfe; border-radius:8px; padding:12px; background:#f8fafc; text-align:center;">
                    <div style="height:90px; background:#eff6ff; border:1px solid #dbeafe; border-radius:6px; display:flex; flex-direction:column; align-items:center; justify-content:center; margin-bottom:6px; padding:6px;">
                      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                      </svg>
                      <span style="font-size:11px; font-weight:700; color:#1d4ed8; margin-top:5px;">EPFO Form 11</span>
                    </div>
                    <div style="font-size:12px; font-weight:700; color:var(--text-main); line-height:1.2; margin-bottom:4px;">
                      EPFO Form 11 (PF Declaration)
                    </div>
                    <div style="font-size:11px; color:#15803d; font-weight:700; margin-bottom:8px;">
                      Active &amp; Digitally Signed
                    </div>
                    <button type="button" class="btn btn-primary" onclick="downloadCurrentPersonForm11Pdf()" style="font-size:11px; padding:5px 12px; background:#1d4ed8; color:#fff; display:inline-flex; align-items:center; gap:5px; font-weight:700; border-radius:6px; cursor:pointer;" title="View and download EPFO Form 11 PDF">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                      View / Download PDF
                    </button>
                  </div>
                `;
              }
              // Worker voluntarily opted out of PF -> Display physical statutory exemption upload slot
              statusText = hasFile ? 'Attached (Signed Exemption)' : 'Required (Worker Opted Out)';
              statusColor = hasFile ? '#15803d' : '#b45309';
            }

            return `
              <div style="border:1px solid var(--border); border-radius:8px; padding:12px; background:#fff; text-align:center;">
                ${preview}
                <div style="font-size:12px; font-weight:700; color:var(--text-main); line-height:1.2; margin-bottom:4px;">
                  ${doc.label} ${doc.type === 'pf_exemption' ? '<span style="font-size:10px; font-weight:600; color:#b45309;">(Statutory Opt-Out)</span>' : ''}
                </div>
                <div style="font-size:11px; color:${statusColor}; font-weight:700; margin-bottom:8px;">
                  ${statusText}
                </div>
                <label class="btn btn-secondary" style="font-size:11px; padding:4px 10px; display:inline-block; cursor:pointer;">
                  ${hasFile ? 'Replace File' : 'Upload File'}
                  <input type="file" style="display:none;" onchange="uploadPersonDocDirect(${p.id}, '${doc.type}', this)">
                </label>
              </div>
            `;
          }).join('');
        }

        // Biometrics Info
        const bioBody = document.getElementById('pv-biometrics-body');
        if (bioBody) {
          const samples = p.biometrics?.samples || [];
          if (samples.length > 0) {
            bioBody.innerHTML = `
              <div style="margin-bottom:8px;">
                <strong>Enrolled Facial Vectors:</strong> ${samples.length} face angle samples (192-d MobileFaceNet cosine normalized).
              </div>
              <div style="display:flex; gap:8px; flex-wrap:wrap;">
                ${samples.map(s => `<span style="background:#f1f5f9; border:1px solid #e2e8f0; padding:4px 10px; border-radius:6px; font-size:12px;">Angle: <strong>${s.angle}</strong> (${s.dimensions}-d, v${s.pipeline_version})</span>`).join('')}
              </div>
            `;
          } else {
            bioBody.innerHTML = `<span style="color:#b45309;">No 192-d biometric vectors stored in cloud database yet. Worker will use local sample on kiosk until cloud sync is completed.</span>`;
          }
        }

        // Attendance Sync Assignments & Transfers
        const assignList = document.getElementById('pv-assignments-list');
        if (assignList) {
          if (p.assignments && p.assignments.length > 0) {
            assignList.innerHTML = p.assignments.map(a => 
              `<span style="background:#dbeafe; color:#1e40af; font-size:12px; font-weight:700; padding:4px 10px; border-radius:12px; margin-right:6px;">${a.name} (${a.email} · Unit ${a.unit_id})</span>`
            ).join('');
          } else {
            assignList.innerHTML = `<span style="color:#15803d; font-weight:700;">Default (Synchronizes to all kiosk logins in Unit ${p.unit_id})</span>`;
          }
        }

        const trBody = document.getElementById('pv-transfers-table-body');
        if (trBody) {
          const trs = p.transfers || [];
          if (trs.length > 0) {
            trBody.innerHTML = trs.map(t => `
              <tr>
                <td><strong>${t.from_unit || '-'}</strong></td>
                <td><strong style="color:#2952e3;">${t.to_unit}</strong></td>
                <td>${t.reason || 'Unit transfer order'}</td>
                <td>${t.transferred_by || 'Admin'}</td>
                <td>${t.effective_date || '-'}</td>
                <td>${t.created_at || '-'}</td>
              </tr>
            `).join('');
          } else {
            trBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:var(--text-muted); padding:16px;">No past inter-unit transfers recorded.</td></tr>`;
          }
        }

        // Populate EPFO Form 11 Statutory Self-Declaration Card
        const f11SigName = document.getElementById('pv-f11-sig-name');
        if (f11SigName) {
          f11SigName.innerText = p.signature_typed_name || p.full_name || 'Candidate Electronic Signature';
          const f11City = document.getElementById('pv-f11-sig-city');
          if (f11City) f11City.innerText = p.signature_place_city || 'Hyderabad';
          const f11Time = document.getElementById('pv-f11-sig-time');
          if (f11Time) f11Time.innerText = p.signature_timestamp || p.created_at || 'Submission Recorded';

          // Status Badge
          const f11Badge = document.getElementById('pv-form11-status-badge');
          if (f11Badge) {
            if (p.opt_out_pf_esi) {
              f11Badge.className = 'badge';
              f11Badge.style.background = '#fef3c7';
              f11Badge.style.color = '#92400e';
              f11Badge.innerText = 'Form 11 Opted Out';
            } else {
              f11Badge.className = 'badge badge-p';
              f11Badge.style.background = '';
              f11Badge.style.color = '';
              f11Badge.innerText = 'Statutory Verified';
            }
          }

          // Regulatory Particulars
          const f11Prev = document.getElementById('pv-f11-prev-emp');
          if (f11Prev) {
            f11Prev.innerText = p.has_worked_previously 
              ? `Yes (${p.previous_employer || 'Previous Employer'}${p.last_working_date ? ' till ' + p.last_working_date : ''})` 
              : 'First Employment (No prior service)';
          }

          const f11Uan = document.getElementById('pv-f11-uan');
          if (f11Uan) f11Uan.innerText = p.uan_number || 'None (New Member)';

          const f11Eps = document.getElementById('pv-f11-eps');
          if (f11Eps) f11Eps.innerText = p.eps_pension_status || 'No';

          const f11Esic = document.getElementById('pv-f11-esic');
          if (f11Esic) f11Esic.innerText = p.esi_number || 'None (New Registration)';

          const f11Scheme = document.getElementById('pv-f11-scheme');
          if (f11Scheme) {
            f11Scheme.innerText = p.opt_out_pf_esi 
              ? 'Voluntarily Opted Out / Exempted' 
              : 'Standard Mandatory EPF/ESIC Scheme';
          }

          const f11Nom = document.getElementById('pv-f11-nominee');
          if (f11Nom) {
            f11Nom.innerText = `${p.nominee_full_name || '-'} (${p.nominee_relationship || '-'}) · ${p.nominee_share_percentage || '100%'} Share${p.nominee_dob ? ' · Born ' + p.nominee_dob : ''}`;
          }
        }

        // Switch to the dedicated separate full page view!
        const hashVal = 'person-detail/' + id;
        if (window.location.hash !== '#' + hashVal) {
          history.replaceState(null, '', '#' + hashVal);
        }
        localStorage.setItem('tg_active_view', hashVal);
        switchView('person-detail', true);

      } catch (err) {
        showToast('Error viewing profile: ' + err, 'error');
      }
    }

    function downloadCurrentPersonZip() {
      if (!currentViewingPerson) return;
      downloadPersonZip(currentViewingPerson.id);
    }

    function downloadCurrentPersonForm11Pdf() {
      if (!currentViewingPerson) return;
      window.location.href = `/api/people/${currentViewingPerson.id}/form11-pdf`;
    }

    async function uploadPersonDocDirect(personId, docType, fileInput) {
      if (!fileInput.files || fileInput.files.length === 0) return;
      const file = fileInput.files[0];
      const formData = new FormData();
      formData.append('doc_type', docType);
      formData.append('file', file);

      try {
        const res = await fetch(`/api/people/${personId}/upload-doc`, {
          method: 'POST',
          body: formData
        });
        const json = await res.json();
        if (json.success) {
          showToast('Document uploaded successfully!', 'success');
          viewPerson(personId);
          loadPeople();
        } else {
          showToast('Upload failed: ' + (json.message || 'Unknown error'), 'error');
        }
      } catch (err) {
        showToast('Upload error: ' + err, 'error');
      } finally {
        fileInput.value = '';
      }
    }

    // --- ZIP EXPORT ---
    function downloadPersonZip(id) {
      window.location.href = `/api/people/download?ids=${id}`;
    }

    function downloadSelectedPeopleZip() {
      if (peopleSelectedIds.size === 0) {
        showToast('Please select at least one employee from the table.', 'warning');
        return;
      }
      const ids = Array.from(peopleSelectedIds).join(',');
      window.location.href = `/api/people/download?ids=${ids}`;
    }

    // --- UNIT TRANSFER MODAL ---
    function openPeopleTransferModal() {
      if (peopleSelectedIds.size === 0) {
        showToast('Please select at least one employee to transfer.', 'warning');
        return;
      }
      targetTransferWorkerIds = Array.from(peopleSelectedIds);
      document.getElementById('transfer-modal-desc').innerText = `Transfer ${targetTransferWorkerIds.length} selected employee(s) to a new factory unit. Face embeddings will synchronize to the target unit kiosks.`;
      document.getElementById('transfer-effective-date').value = new Date().toISOString().substring(0, 10);
      document.getElementById('transfer-reason').value = '';
      document.getElementById('modal-people-transfer').classList.add('active');
    }

    function openSinglePersonTransferModal() {
      if (!currentViewingPerson) return;
      targetTransferWorkerIds = [currentViewingPerson.id];
      document.getElementById('transfer-modal-desc').innerText = `Transfer ${currentViewingPerson.full_name} (#${currentViewingPerson.worker_code}) from ${currentViewingPerson.unit_id} to a new factory unit.`;
      document.getElementById('transfer-effective-date').value = new Date().toISOString().substring(0, 10);
      document.getElementById('transfer-reason').value = '';
      document.getElementById('modal-people-transfer').classList.add('active');
    }

    async function submitPeopleTransfer() {
      const toUnit = document.getElementById('transfer-target-unit').value;
      const eff = document.getElementById('transfer-effective-date').value;
      const reason = document.getElementById('transfer-reason').value.trim();

      if (!targetTransferWorkerIds || targetTransferWorkerIds.length === 0) {
        showToast('No employees selected for transfer.', 'warning');
        return;
      }

      try {
        const res = await fetch('/api/people/transfer', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            worker_ids: targetTransferWorkerIds,
            to_unit: toUnit,
            effective_date: eff,
            reason: reason
          })
        });

        const json = await res.json();
        if (json.success) {
          closeModals();
          showToast(json.message || 'Transfer executed successfully!', 'success');
          loadPeople();
          if (currentViewingPerson && targetTransferWorkerIds.includes(currentViewingPerson.id)) {
            viewPerson(currentViewingPerson.id);
          }
        } else {
          showToast('Transfer error: ' + (json.message || 'Unknown error'), 'error');
        }
      } catch (err) {
        showToast('Transfer failed: ' + err, 'error');
      }
    }

    // --- ATTENDANCE LOGIN ASSIGNMENT MODAL ---
    function openPeopleAssignModal() {
      if (peopleSelectedIds.size === 0) {
        showToast('Please select at least one employee.', 'warning');
        return;
      }
      targetAssignWorkerIds = Array.from(peopleSelectedIds);
      setupAssignModalUI(`Assign attendance kiosk sync logins for ${targetAssignWorkerIds.length} employee(s).`);
    }

    function openSinglePersonAssignModal() {
      if (!currentViewingPerson) return;
      targetAssignWorkerIds = [currentViewingPerson.id];
      setupAssignModalUI(`Assign attendance kiosk sync logins for ${currentViewingPerson.full_name} (#${currentViewingPerson.worker_code}).`);
    }

    function setupAssignModalUI(desc) {
      document.getElementById('assign-modal-desc').innerText = desc;
      document.getElementById('assign-mode').value = 'replace';
      toggleAssignMode('replace');

      const box = document.getElementById('assign-logins-checklist');
      if (box) {
        if (!availableLoginsList || availableLoginsList.length === 0) {
          box.innerHTML = '<div style="color:var(--text-muted); font-size:12px;">No active login accounts available.</div>';
        } else {
          box.innerHTML = availableLoginsList.map(u => `
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:12.5px;">
              <input type="checkbox" class="assign-login-chk" value="${u.id}" style="width:16px; height:16px;">
              <span><strong>${u.full_name}</strong> (${u.email} · Unit: <code>${u.unit_id}</code>)</span>
            </label>
          `).join('');
        }
      }

      document.getElementById('modal-people-assign').classList.add('active');
    }

    function toggleAssignMode(mode) {
      const box = document.getElementById('assign-logins-box');
      if (box) box.style.display = (mode === 'clear') ? 'none' : 'block';
    }

    async function submitPeopleAssign() {
      const mode = document.getElementById('assign-mode').value;
      const chks = document.querySelectorAll('.assign-login-chk:checked');
      const userIds = Array.from(chks).map(c => parseInt(c.value));

      if (mode !== 'clear' && userIds.length === 0) {
        showToast('Please select at least one login user.', 'warning');
        return;
      }

      try {
        const res = await fetch('/api/people/assign', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            worker_ids: targetAssignWorkerIds,
            user_ids: userIds,
            mode: mode
          })
        });

        const json = await res.json();
        if (json.success) {
          closeModals();
          showToast(json.message || 'Login assignments updated successfully!', 'success');
          loadPeople();
          if (currentViewingPerson && targetAssignWorkerIds.includes(currentViewingPerson.id)) {
            viewPerson(currentViewingPerson.id);
          }
        } else {
          showToast('Assignment error: ' + (json.message || 'Unknown error'), 'error');
        }
      } catch (err) {
        showToast('Assignment failed: ' + err, 'error');
      }
    }

    // --- IN-PLACE BIG CARDS PROFILE EDITING ---
    let isProfileEditMode = false;

    function exitProfileView() {
      if (isProfileEditMode) {
        if (!confirm('You have unsaved changes in edit mode. Discard changes and return?')) {
          return;
        }
        toggleProfileEditMode(false);
      }
      currentViewingPerson = null;
      if (sessionStorage.getItem('tg_approval_return') === '1') {
        sessionStorage.removeItem('tg_approval_return');
        switchView('approvals');
      } else {
        switchView('people');
      }
    }

    async function approveCurrentPersonFromProfile() {
      if (!currentViewingPerson || !currentViewingPerson.id) {
        showToast('No employee selected to approve', 'warning');
        return;
      }
      const p = currentViewingPerson;
      if (!confirm(`Are you sure you want to approve and activate employee "${p.full_name}"? This will activate the worker, enable kiosk facial recognition sync, and finalize onboarding.`)) {
        return;
      }
      try {
        const res = await fetch('/api/portal/approval-action', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            worker_id: p.id,
            action: 'approved',
            comment: 'Approved directly from People Master Profile'
          })
        });
        const json = await res.json();
        if (json.success) {
          showToast(`Worker "${p.full_name}" has been approved & activated successfully!`, 'success');
          await viewPerson(p.id);
          if (typeof loadApprovals === 'function') {
            loadApprovals();
          }
        } else {
          showToast('Approval failed: ' + (json.message || 'Server error'), 'error');
        }
      } catch (err) {
        showToast('Approval error: ' + err, 'error');
      }
    }

    async function rejectCurrentPersonFromProfile() {
      if (!currentViewingPerson || !currentViewingPerson.id) {
        showToast('No employee selected', 'warning');
        return;
      }
      const p = currentViewingPerson;
      const reason = prompt(`Enter rejection reason / remarks for employee "${p.full_name}":`);
      if (reason === null) return;
      try {
        const res = await fetch('/api/portal/approval-action', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            worker_id: p.id,
            action: 'rejected',
            comment: reason.trim() || 'Rejected during HR profile review'
          })
        });
        const json = await res.json();
        if (json.success) {
          showToast(`Worker "${p.full_name}" registration has been rejected.`, 'info');
          await viewPerson(p.id);
          if (typeof loadApprovals === 'function') {
            loadApprovals();
          }
        } else {
          showToast('Rejection failed: ' + (json.message || 'Server error'), 'error');
        }
      } catch (err) {
        showToast('Rejection error: ' + err, 'error');
      }
    }

    function populateProfileFormFields(p) {
      if (!p) return;
      const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = (val !== null && val !== undefined) ? val : '';
      };

      // 1. Basic & Personal Details
      setVal('pf-full_name', p.full_name);
      setVal('pf-father_or_husband_name', p.father_or_husband_name);
      setVal('pf-gender', p.gender || 'Male');
      setVal('pf-date_of_birth', p.date_of_birth);
      setVal('pf-blood_group', p.blood_group || 'O+');
      setVal('pf-marital_status', p.marital_status || 'Single');
      setVal('pf-mobile_number', p.mobile_number);
      setVal('pf-alternate_mobile', p.alternate_mobile);
      setVal('pf-email_address', p.email_address);
      setVal('pf-present_address', p.present_address);
      setVal('pf-permanent_address', p.permanent_address);
      setVal('pf-emergency_name', p.emergency_name);
      setVal('pf-emergency_relationship', p.emergency_relationship);
      setVal('pf-emergency_mobile', p.emergency_mobile);
      setVal('pf-nominee_full_name', p.nominee_full_name);
      setVal('pf-nominee_relationship', p.nominee_relationship);
      setVal('pf-nominee_dob', p.nominee_dob);
      setVal('pf-nominee_share_percentage', p.nominee_share_percentage || '100');

      // 2. Employment & Compensation Details
      setVal('pf-worker_code', p.worker_code);
      setVal('pf-unit_id', p.unit_id || 'TGK-1');
      setVal('pf-effective_date', new Date().toISOString().substring(0, 10));
      const grpEff = document.getElementById('pf-group-effective-date');
      if (grpEff) grpEff.style.display = 'none';
      const unitEl = document.getElementById('pf-unit_id');
      if (unitEl && !unitEl._hasEffListener) {
        unitEl._hasEffListener = true;
        unitEl.addEventListener('change', () => {
          if (grpEff) {
            const hasChanged = currentViewingPerson && (unitEl.value !== currentViewingPerson.unit_id);
            grpEff.style.display = hasChanged ? 'block' : 'none';
          }
        });
      }
      setVal('pf-department', p.department);
      setVal('pf-designation', p.designation || p.department);
      setVal('pf-shift', (p.shift || 'Shift A').replace(/\s*\([^)]*\)/g, '').trim());
      setVal('pf-category', p.employment_type || 'On TrooGood Rolls');
      setVal('pf-contractor_agency', p.contractor_name);
      setVal('pf-date_of_joining', p.date_of_joining);
      setVal('pf-reporting_to', p.reporting_to);
      setVal('pf-monthly_salary', p.monthly_salary !== null ? p.monthly_salary : '');
      setVal('pf-approval_status', p.approval_status || 'approved');

      // 3. Bank & Statutory IDs
      setVal('pf-aadhaar_number', p.aadhaar_number);
      setVal('pf-pan_number', p.pan_number);
      setVal('pf-account_number', p.account_number);
      setVal('pf-ifsc_code', p.ifsc_code);
      setVal('pf-bank_name_branch', p.bank_name_branch);
      setVal('pf-uan_number', p.uan_number);
      setVal('pf-esi_number', p.esi_number);
      setVal('pf-health_insurance_number', p.health_insurance_number);
      setVal('pf-has_pf_account', p.has_pf_account ? '1' : '0');
      setVal('pf-eps_pension_status', p.eps_pension_status || 'No');
      setVal('pf-opt_out_pf_esi', p.opt_out_pf_esi ? '1' : '0');
    }

    function toggleProfileEditMode(isEdit) {
      isProfileEditMode = !!isEdit;
      const viewActions = document.getElementById('pv-view-actions');
      const editActions = document.getElementById('pv-edit-actions');
      const stickyBar = document.getElementById('pf-sticky-bar');
      const stickyName = document.getElementById('pf-sticky-name');
      const container = document.getElementById('view-person-detail');

      if (isProfileEditMode) {
        if (viewActions) viewActions.style.display = 'none';
        if (editActions) editActions.style.display = 'flex';
        if (stickyBar) stickyBar.style.display = 'flex';
        if (stickyName && currentViewingPerson) stickyName.innerText = currentViewingPerson.full_name + ' (#' + currentViewingPerson.worker_code + ')';
        if (container) container.classList.add('profile-edit-mode');

        // Enable inputs including worker code
        document.querySelectorAll('#view-person-detail .pf-input').forEach(el => {
          el.removeAttribute('disabled');
        });
      } else {
        if (viewActions) viewActions.style.display = 'flex';
        if (editActions) editActions.style.display = 'none';
        if (stickyBar) stickyBar.style.display = 'none';
        if (container) container.classList.remove('profile-edit-mode');

        // Disable all inputs
        document.querySelectorAll('#view-person-detail .pf-input').forEach(el => {
          el.setAttribute('disabled', 'disabled');
        });

        // Restore original values
        if (currentViewingPerson) {
          populateProfileFormFields(currentViewingPerson);
        }
      }
    }

    async function savePersonProfile() {
      if (!currentViewingPerson) return;
      const id = currentViewingPerson.id;
      const fullName = (document.getElementById('pf-full_name')?.value || '').trim();
      if (!fullName) {
        showToast('Full name is required.', 'warning');
        document.getElementById('pf-full_name')?.focus();
        return;
      }

      const getV = (id) => (document.getElementById(id)?.value || '').trim();

      const payload = {
        id: parseInt(id),
        worker_code: getV('pf-worker_code'),
        full_name: fullName,
        father_or_husband_name: getV('pf-father_or_husband_name'),
        gender: getV('pf-gender') || 'Male',
        date_of_birth: getV('pf-date_of_birth'),
        blood_group: getV('pf-blood_group') || 'O+',
        marital_status: getV('pf-marital_status') || 'Single',
        mobile_number: getV('pf-mobile_number'),
        alternate_mobile: getV('pf-alternate_mobile'),
        email_address: getV('pf-email_address'),
        present_address: getV('pf-present_address'),
        permanent_address: getV('pf-permanent_address'),
        emergency_name: getV('pf-emergency_name'),
        emergency_relationship: getV('pf-emergency_relationship'),
        emergency_mobile: getV('pf-emergency_mobile'),
        nominee_full_name: getV('pf-nominee_full_name'),
        nominee_relationship: getV('pf-nominee_relationship'),
        nominee_dob: getV('pf-nominee_dob'),
        nominee_share_percentage: getV('pf-nominee_share_percentage') || '100',
        unit_id: getV('pf-unit_id'),
        effective_date: getV('pf-effective_date') || new Date().toISOString().substring(0, 10),
        department: getV('pf-department'),
        designation: getV('pf-designation'),
        shift: getV('pf-shift'),
        category: getV('pf-category'),
        contractor_agency: getV('pf-contractor_agency'),
        date_of_joining: getV('pf-date_of_joining'),
        reporting_to: getV('pf-reporting_to'),
        monthly_salary: getV('pf-monthly_salary'),
        approval_status: getV('pf-approval_status'),
        aadhaar_number: getV('pf-aadhaar_number'),
        pan_card_number: getV('pf-pan_number'),
        bank_account_number: getV('pf-account_number'),
        ifsc_code: getV('pf-ifsc_code'),
        bank_name_branch: getV('pf-bank_name_branch'),
        uan_number: getV('pf-uan_number'),
        esic_number: getV('pf-esi_number'),
        health_insurance_number: getV('pf-health_insurance_number'),
        has_pf_account: parseInt(getV('pf-has_pf_account')) || 0,
        eps_pension_status: getV('pf-eps_pension_status') || 'No',
        opt_out_pf_esi: parseInt(getV('pf-opt_out_pf_esi')) || 0,
      };

      const btnSaveTop = document.getElementById('pv-btn-save-top');
      if (btnSaveTop) { btnSaveTop.disabled = true; btnSaveTop.innerText = 'Saving...'; }

      try {
        const res = await fetch('/api/people/update', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (json.success) {
          showToast('Employee profile updated successfully!', 'success');
          toggleProfileEditMode(false);
          loadPeople();
          viewPerson(id);
        } else {
          showToast('Update error: ' + (json.message || 'Unknown error'), 'error');
        }
      } catch (err) {
        showToast('Update failed: ' + err, 'error');
      } finally {
        if (btnSaveTop) {
          btnSaveTop.disabled = false;
          btnSaveTop.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> Save Changes`;
        }
      }
    }

    // =========================================================================
    // W09: HOLIDAY CALENDAR MANAGEMENT
    // =========================================================================
    let currentHolidaysList = [];

    async function loadHolidays() {
      const year = document.getElementById('holiday-year-select')?.value || '2026';
      const tbody = document.getElementById('holidays-table-body');
      if (tbody) tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:24px; color:#64748b;">Loading holidays for ' + year + '...</td></tr>';

      try {
        const res = await fetch(`/api/holidays?year=${encodeURIComponent(year)}`);
        const json = await res.json();
        const d = json.data;
        if (!d) return;

        currentHolidaysList = d.holidays || [];

        // Update stats
        if (d.stats) {
          document.getElementById('holiday-stat-total').innerText = d.stats.total || 0;
          document.getElementById('holiday-stat-national').innerText = d.stats.national || 0;
          document.getElementById('holiday-stat-festival').innerText = d.stats.festival || 0;
          document.getElementById('holiday-stat-company').innerText = d.stats.company || 0;
        }

        if (currentHolidaysList.length === 0) {
          if (tbody) tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:30px; color:#94a3b8;">No holidays declared for ' + year + '. Click "+ Add Holiday" or "Upload CSV" to declare annual holidays.</td></tr>';
          return;
        }

        if (tbody) {
          tbody.innerHTML = currentHolidaysList.map((h, idx) => {
            let typeBadge = '<span class="badge" style="background:#f1f5f9; color:#475569;">' + h.holiday_type + '</span>';
            if (h.holiday_type === 'National') typeBadge = '<span class="badge" style="background:#fef3c7; color:#b45309; font-weight:800;">National</span>';
            else if (h.holiday_type === 'Festival') typeBadge = '<span class="badge" style="background:#dcfce7; color:#15803d; font-weight:800;">Festival</span>';
            else if (h.holiday_type === 'Company') typeBadge = '<span class="badge" style="background:#ede9fe; color:#6d28d9; font-weight:800;">Company</span>';

            const activeBadge = h.is_active == 1 
              ? '<span class="badge badge-p" style="font-size:10.5px;">Active</span>' 
              : '<span class="badge badge-a" style="font-size:10.5px;">Inactive</span>';

            return `
              <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="text-align:center; font-weight:700; color:#64748b;">${idx + 1}</td>
                <td style="font-family:'JetBrains Mono',monospace; font-weight:800; color:#0f172a;">${h.holiday_date}</td>
                <td style="font-weight:700; color:#475569;">${h.day_of_week || ''}</td>
                <td style="font-weight:800; color:#1e293b;">${h.holiday_name}</td>
                <td style="text-align:center;">${typeBadge}</td>
                <td style="text-align:center;">${activeBadge}</td>
                <td style="text-align:center;">
                  <span style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:800; background:#f0fdf4; color:#166534; padding:3px 8px; border-radius:12px; border:1px solid #bbf7d0;">
                    ⭐ +1 Leave if worked
                  </span>
                </td>
                <td style="font-size:12px; color:#64748b;">${h.description || 'Declared company holiday'}</td>
                <td style="text-align:center;">
                  <div style="display:flex; justify-content:center; gap:6px;">
                    <button type="button" class="btn btn-secondary" style="padding:3px 7px; font-size:11px;" onclick="editHoliday(${h.id})">Edit</button>
                    <button type="button" class="btn btn-secondary" style="padding:3px 7px; font-size:11px; color:#b91c1c; border-color:#fca5a5;" onclick="deleteHoliday(${h.id}, '${h.holiday_name.replace(/'/g, "\\'")}')">✕</button>
                  </div>
                </td>
              </tr>
            `;
          }).join('');
        }
      } catch (err) {
        if (tbody) tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:20px; color:#ef4444;">Error loading holidays: ${err}</td></tr>`;
      }
    }

    function openAddHolidayModal() {
      document.getElementById('holiday-modal-title').innerText = 'Add Holiday Record';
      document.getElementById('he-id').value = '';
      const year = document.getElementById('holiday-year-select')?.value || '2026';
      document.getElementById('he-date').value = year + '-01-01';
      document.getElementById('he-name').value = '';
      document.getElementById('he-type').value = 'Festival';
      document.getElementById('he-unit').value = 'ALL';
      document.getElementById('he-desc').value = '';
      document.getElementById('he-active').value = '1';
      document.getElementById('modal-holiday-edit').classList.add('active');
    }

    function editHoliday(id) {
      const h = currentHolidaysList.find(x => x.id == id);
      if (!h) return;
      document.getElementById('holiday-modal-title').innerText = 'Edit Holiday Record';
      document.getElementById('he-id').value = h.id;
      document.getElementById('he-date').value = h.holiday_date;
      document.getElementById('he-name').value = h.holiday_name;
      document.getElementById('he-type').value = h.holiday_type;
      document.getElementById('he-unit').value = h.unit_id || 'ALL';
      document.getElementById('he-desc').value = h.description || '';
      document.getElementById('he-active').value = h.is_active ? '1' : '0';
      document.getElementById('modal-holiday-edit').classList.add('active');
    }

    async function submitHolidayForm() {
      const id = document.getElementById('he-id').value;
      const payload = {
        id: id ? parseInt(id) : null,
        holiday_date: document.getElementById('he-date').value,
        holiday_name: document.getElementById('he-name').value.trim(),
        holiday_type: document.getElementById('he-type').value,
        unit_id: document.getElementById('he-unit').value,
        description: document.getElementById('he-desc').value.trim(),
        is_active: parseInt(document.getElementById('he-active').value) || 1
      };

      const btn = document.getElementById('btn-save-holiday');
      if (btn) { btn.disabled = true; btn.innerText = 'Saving...'; }

      try {
        const res = await fetch('/api/holidays/save', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (json.success) {
          showToast(json.message || 'Holiday record saved successfully!', 'success');
          closeModals();
          loadHolidays();
        } else {
          showToast(json.message || 'Error saving holiday', 'error');
        }
      } catch (err) {
        showToast('Save failed: ' + err, 'error');
      } finally {
        if (btn) { btn.disabled = false; btn.innerText = 'Save Holiday'; }
      }
    }

    async function deleteHoliday(id, name) {
      if (!confirm(`Are you sure you want to delete the holiday "${name}"?`)) return;

      try {
        const res = await fetch('/api/holidays/delete', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ id: id })
        });
        const json = await res.json();
        if (json.success) {
          showToast('Holiday deleted successfully', 'success');
          loadHolidays();
        } else {
          showToast(json.message || 'Error deleting holiday', 'error');
        }
      } catch (err) {
        showToast('Delete error: ' + err, 'error');
      }
    }

    function openUploadHolidayModal() {
      document.getElementById('holiday-csv-file').value = '';
      document.getElementById('modal-holiday-upload').classList.add('active');
    }

    async function submitUploadHolidays() {
      const fileInput = document.getElementById('holiday-csv-file');
      if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        showToast('Please select a CSV file to upload', 'warning');
        return;
      }

      const formData = new FormData();
      formData.append('csv_file', fileInput.files[0]);

      const btn = document.getElementById('btn-upload-holiday-submit');
      if (btn) { btn.disabled = true; btn.innerText = 'Uploading & Processing...'; }

      try {
        const res = await fetch('/api/holidays/upload', {
          method: 'POST',
          body: formData
        });
        const json = await res.json();
        if (json.success) {
          showToast(json.message || 'Holidays CSV processed successfully!', 'success');
          closeModals();
          loadHolidays();
        } else {
          showToast(json.message || 'CSV upload failed', 'error');
        }
      } catch (err) {
        showToast('Upload error: ' + err, 'error');
      } finally {
        if (btn) { btn.disabled = false; btn.innerText = 'Upload & Apply'; }
      }
    }

    // =========================================================================
    // W10: LEAVE CALCULATOR & COMPENSATORY OFF
    // =========================================================================
    let currentLeaveCalcRows = [];

    async function loadLeaveCalculator() {
      const month = document.getElementById('leave-calc-month')?.value || '2026-10';
      const unit = document.getElementById('leave-calc-unit')?.value || 'all';
      const tbody = document.getElementById('leave-calc-table-body');
      if (tbody) tbody.innerHTML = '<tr><td colspan="10" style="text-align:center; padding:24px; color:#64748b;">Computing leave balances and compensatory off for ' + month + '...</td></tr>';

      try {
        const res = await fetch(`/api/portal/leave-calculator?month=${encodeURIComponent(month)}&unit=${encodeURIComponent(unit)}`);
        const json = await res.json();
        const d = json.data;
        if (!d) return;

        currentLeaveCalcRows = d.records || [];

        // Summary counts
        if (d.summary) {
          document.getElementById('lc-stat-workers').innerText = d.summary.total_workers || currentLeaveCalcRows.length;
          document.getElementById('lc-stat-compoff').innerText = (d.summary.total_comp_off_granted || 0) + ' Days';
          document.getElementById('lc-stat-availed').innerText = (d.summary.total_leaves_availed || 0) + ' Days';
        }

        renderLeaveCalcTable();
      } catch (err) {
        if (tbody) tbody.innerHTML = `<tr><td colspan="10" style="text-align:center; padding:20px; color:#ef4444;">Error calculating leaves: ${err}</td></tr>`;
      }
    }

    function renderLeaveCalcTable() {
      const tbody = document.getElementById('leave-calc-table-body');
      if (!tbody) return;

      const q = (document.getElementById('leave-calc-search')?.value || '').toLowerCase().trim();
      const filtered = currentLeaveCalcRows.filter(r => {
        if (!q) return true;
        return (r.full_name || '').toLowerCase().includes(q) ||
               (r.worker_code || '').toLowerCase().includes(q) ||
               (r.department || '').toLowerCase().includes(q);
      });

      if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" style="text-align:center; padding:30px; color:#94a3b8;">No workforce records found for this unit and month.</td></tr>';
        return;
      }

      tbody.innerHTML = filtered.map(r => {
        const closing = Number(r.closing_balance || 0);
        let statusBadge = '<span class="badge badge-p" style="font-size:10px;">Healthy</span>';
        if (closing < 0) statusBadge = '<span class="badge badge-a" style="font-size:10px;">Overdrawn</span>';
        else if (closing < 2) statusBadge = '<span class="badge badge-w" style="font-size:10px;">Low</span>';

        const sundayHolidayDuty = (Number(r.sundays_worked || 0) + Number(r.holidays_worked || 0));

        return `
          <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="text-align:center; font-family:'JetBrains Mono',monospace; font-weight:800; font-size:12px;">#${r.worker_id}</td>
            <td>
              <strong style="color:#0f172a; font-size:13px;">${r.full_name}</strong>
              <div style="font-size:11px; color:#64748b;">${r.worker_code} &middot; ${r.department}</div>
            </td>
            <td style="text-align:center;"><span class="badge-chip">${r.unit_id}</span></td>
            <td style="font-size:12px; color:#334155;">${r.category || 'On Rolls'}</td>
            <td style="text-align:center; font-weight:700; color:#475569; background:#f8fafc;">${Number(r.opening_balance || 5.0).toFixed(1)}</td>
            <td style="text-align:center; font-weight:700; color:#1d4ed8; background:#eff6ff;">+${Number(r.accrual || 1.5).toFixed(1)}</td>
            <td style="text-align:center; font-weight:800; color:#15803d; background:#f0fdf4;" title="${r.sundays_worked || 0} Sundays + ${r.holidays_worked || 0} Holidays">
              +${sundayHolidayDuty} <span style="font-size:9.5px; opacity:0.8;">(${r.sundays_worked || 0}S / ${r.holidays_worked || 0}H)</span>
            </td>
            <td style="text-align:center; font-weight:800; color:#b91c1c; background:#fff1f2;">&minus;${Number(r.leaves_availed || 0).toFixed(1)}</td>
            <td style="text-align:center; font-weight:800; font-size:13.5px; color:${closing < 0 ? '#b91c1c' : '#0f172a'}; background:#fefce8;">
              ${closing.toFixed(1)} Days
            </td>
            <td style="text-align:center;">${statusBadge}</td>
          </tr>
        `;
      }).join('');
    }

    function filterLeaveCalcTable() {
      renderLeaveCalcTable();
    }

    function exportLeaveCalculatorCsv() {
      const month = document.getElementById('leave-calc-month')?.value || '2026-10';
      const unit = document.getElementById('leave-calc-unit')?.value || 'all';
      window.location.href = `/api/portal/leave-export?month=${encodeURIComponent(month)}&unit=${encodeURIComponent(unit)}`;
    }

    // =========================================================================
    // W11: SYSTEM ERROR LOGS & DIAGNOSTICS
    // =========================================================================
    let currentLogsList = [];
    let currentLogSource = 'all';

    async function loadSystemLogs(source = null) {
      if (source) currentLogSource = source;
      const tbody = document.getElementById('error-logs-table-body');
      if (tbody) tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px; color:#64748b;">Fetching system error logs...</td></tr>';

      try {
        const res = await fetch(`/api/logs/list?source=${encodeURIComponent(currentLogSource)}`);
        const json = await res.json();
        const d = json.data;
        if (!d) return;

        currentLogsList = d.logs || [];

        // Update count badges
        if (d.counts) {
          document.getElementById('log-cnt-all').innerText = d.counts.all || 0;
          document.getElementById('log-cnt-mobile_app').innerText = d.counts.mobile_app || 0;
          document.getElementById('log-cnt-web_portal').innerText = d.counts.web_portal || 0;
          document.getElementById('log-cnt-api').innerText = d.counts.api || 0;
          document.getElementById('log-cnt-sync').innerText = d.counts.sync || 0;
        }

        if (currentLogsList.length === 0) {
          if (tbody) tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:30px; color:#15803d; font-weight:700;">🟢 All systems healthy! No error logs recorded for this category.</td></tr>';
          return;
        }

        if (tbody) {
          tbody.innerHTML = currentLogsList.map(log => {
            let sevBadge = '<span class="badge" style="background:#fee2e2; color:#b91c1c; font-weight:800;">ERROR</span>';
            if (log.severity === 'CRITICAL') sevBadge = '<span class="badge" style="background:#7f1d1d; color:#fff; font-weight:900;">CRITICAL</span>';
            else if (log.severity === 'WARNING') sevBadge = '<span class="badge" style="background:#fef3c7; color:#b45309; font-weight:800;">WARN</span>';
            else if (log.severity === 'INFO') sevBadge = '<span class="badge" style="background:#e0f2fe; color:#0369a1; font-weight:700;">INFO</span>';

            let srcBadge = '<span class="badge-chip">' + log.source + '</span>';
            if (log.source === 'mobile_app') srcBadge = '<span class="badge" style="background:#eff6ff; color:#1d4ed8; font-weight:700;">📱 Mobile</span>';
            else if (log.source === 'web_portal') srcBadge = '<span class="badge" style="background:#f3e8ff; color:#7e22ce; font-weight:700;">💻 Web</span>';
            else if (log.source === 'sync') srcBadge = '<span class="badge" style="background:#ffedd5; color:#c2410c; font-weight:700;">🔄 Sync</span>';

            return `
              <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="font-family:'JetBrains Mono',monospace; font-size:11.5px; color:#475569;">${log.formatted_time || log.created_at}</td>
                <td style="text-align:center;">${srcBadge}</td>
                <td style="text-align:center;">${sevBadge}</td>
                <td>
                  <strong style="color:#0f172a; font-size:12.5px;">${log.error_message}</strong>
                </td>
                <td style="font-family:'JetBrains Mono',monospace; font-size:11px; color:#64748b;">${log.endpoint || log.screen_name || '--'}</td>
                <td style="font-size:12px; color:#475569;">${log.user_email || log.device_id || '--'}</td>
                <td style="text-align:center;">
                  <button type="button" class="btn btn-secondary" style="padding:3px 8px; font-size:11px;" onclick="viewLogDetails(${log.id})">Details</button>
                </td>
              </tr>
            `;
          }).join('');
        }
      } catch (err) {
        if (tbody) tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:20px; color:#ef4444;">Error fetching logs: ${err}</td></tr>`;
      }
    }

    function filterLogs(src, btnEl) {
      document.querySelectorAll('#view-error-logs .approval-tab-btn').forEach(b => b.classList.remove('active'));
      if (btnEl) btnEl.classList.add('active');
      loadSystemLogs(src);
    }

    function viewLogDetails(id) {
      const log = currentLogsList.find(x => x.id == id);
      if (!log) return;

      document.getElementById('eld-time').innerText = log.formatted_time || log.created_at;
      document.getElementById('eld-source').innerText = log.source;
      document.getElementById('eld-severity').innerText = log.severity;
      document.getElementById('eld-endpoint').innerText = log.endpoint || log.screen_name || 'N/A';
      document.getElementById('eld-msg').innerText = log.error_message;
      document.getElementById('eld-stack').innerText = log.stack_trace || 'No stack trace captured.';

      document.getElementById('modal-error-log-detail').classList.add('active');
    }

    async function clearSystemLogs() {
      if (!confirm('Are you sure you want to clear resolved error logs?')) return;

      try {
        const res = await fetch('/api/logs/clear', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ source: currentLogSource })
        });
        const json = await res.json();
        if (json.success) {
          showToast('Logs cleared successfully', 'success');
          loadSystemLogs();
        } else {
          showToast(json.message || 'Error clearing logs', 'error');
        }
      } catch (err) {
        showToast('Clear failed: ' + err, 'error');
      }
    }

    // =========================================================================
    // W12: NOTIFICATIONS & BROADCAST ALERTS
    // =========================================================================
    async function loadNotificationsList() {
      const container = document.getElementById('notifications-list-container');
      if (container) container.innerHTML = '<div style="text-align:center; padding:24px; color:#64748b;">Loading broadcasts...</div>';

      try {
        const res = await fetch('/api/notifications/active?reader_type=web_portal');
        const json = await res.json();
        const d = json.data;
        if (!d) return;

        const notifs = d.notifications || [];
        const unreadCount = d.unread_count || 0;

        // Update sidebar badge
        const badge = document.getElementById('side-notif-badge');
        if (badge) {
          badge.innerText = unreadCount;
          badge.style.display = (unreadCount > 0) ? 'inline-block' : 'none';
        }

        if (notifs.length === 0) {
          if (container) container.innerHTML = '<div style="text-align:center; padding:30px; color:#94a3b8;">No broadcasts sent yet. Click "+ Broadcast Announcement" to send in-app alerts.</div>';
          return;
        }

        if (container) {
          container.innerHTML = notifs.map(n => {
            const isUrgent = n.type === 'URGENT';
            return `
              <div style="border:1px solid ${isUrgent ? '#fca5a5' : '#cbd5e1'}; background:${isUrgent ? '#fff5f5' : '#fff'}; border-radius:8px; padding:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                  <div style="display:flex; align-items:center; gap:8px;">
                    ${isUrgent ? '<span class="badge" style="background:#b91c1c; color:#fff; font-weight:800;">URGENT</span>' : '<span class="badge" style="background:#eff6ff; color:#1d4ed8; font-weight:700;">ANNOUNCEMENT</span>'}
                    <strong style="font-size:14px; color:#0f172a;">${n.title}</strong>
                  </div>
                  <span style="font-size:11.5px; color:#64748b;">${n.formatted_date || ''}</span>
                </div>
                <p style="font-size:13px; color:#334155; line-height:1.4; margin-bottom:10px;">${n.message}</p>
                <div style="font-size:11px; color:#64748b; display:flex; gap:16px;">
                  <span>By: <strong>${n.created_by || 'System Super Admin'}</strong></span>
                  <span style="display:flex; align-items:center; gap:4px; flex-wrap:wrap;">Target: <strong>${n.target_unit === 'ALL' ? '<span class="badge" style="background:#eff6ff; color:#1d4ed8; font-weight:700;">🌐 All Factories (Global)</span>' : n.target_unit.split(',').map(u => `<span class="badge" style="background:#f1f5f9; color:#1e293b; border:1px solid #cbd5e1; font-weight:700; padding:2px 6px;">${u.trim()}</span>`).join(' ')}</strong></span>
                </div>
              </div>
            `;
          }).join('');
        }
      } catch (err) {
        if (container) container.innerHTML = `<div style="text-align:center; padding:20px; color:#ef4444;">Error loading notifications: ${err}</div>`;
      }
    }

    const BROADCAST_AVAILABLE_UNITS = [
      { id: 'Corporate', name: 'Corporate', location: 'Corporate Office' },
      { id: 'TG-Mega',   name: 'TG-Mega',   location: 'Kandhi Plant' },
      { id: 'TGH-1',     name: 'TGH-1',     location: 'Hyderabad Plant' },
      { id: 'TGK-1',     name: 'TGK-1',     location: 'Kompally (Main)' },
      { id: 'TGK-2',     name: 'TGK-2',     location: 'Kompally (Ext)' },
      { id: 'TGK-3',     name: 'TGK-3',     location: 'Kompally (Logistics)' },
      { id: 'TGK-4',     name: 'TGK-4',     location: 'Kompally (Plant 4)' },
      { id: 'TGR',       name: 'TGR',       location: 'Rajahmundry Plant' },
      { id: 'TGT',       name: 'TGT',       location: 'Transit' },
    ];

    function renderBroadcastUnitsGrid() {
      const grid = document.getElementById('broadcast-units-grid');
      if (!grid) return;
      grid.innerHTML = BROADCAST_AVAILABLE_UNITS.map(u => `
        <label id="bn-chip-${u.id}" style="display:flex; align-items:center; gap:6px; padding:6px 8px; background:#eff6ff; border:1px solid #93c5fd; border-radius:6px; cursor:pointer; font-size:12px; transition:all 0.15s ease; user-select:none;">
          <input type="checkbox" class="bn-unit-cb" value="${u.id}" onchange="handleBroadcastUnitCheckChange()" checked style="width:14px; height:14px; accent-color:#2563eb; cursor:pointer;">
          <div style="display:flex; flex-direction:column; line-height:1.15;">
            <span style="font-weight:700; color:#0f172a;">${u.name}</span>
            <span style="font-size:10px; color:#64748b;">${u.location}</span>
          </div>
        </label>
      `).join('');
    }

    function toggleAllBroadcastUnits(selectAll) {
      const allCb = document.getElementById('bn-unit-all');
      if (allCb) allCb.checked = selectAll;
      document.querySelectorAll('.bn-unit-cb').forEach(cb => {
        cb.checked = selectAll;
      });
      updateBroadcastUnitsSummary();
    }

    function handleBroadcastAllUnitsChange(isAllChecked) {
      document.querySelectorAll('.bn-unit-cb').forEach(cb => {
        cb.checked = isAllChecked;
      });
      updateBroadcastUnitsSummary();
    }

    function handleBroadcastUnitCheckChange() {
      const cbs = Array.from(document.querySelectorAll('.bn-unit-cb'));
      const checked = cbs.filter(cb => cb.checked);
      const allCb = document.getElementById('bn-unit-all');
      if (allCb) {
        allCb.checked = (checked.length === cbs.length);
      }
      updateBroadcastUnitsSummary();
    }

    function updateBroadcastUnitsSummary() {
      const cbs = Array.from(document.querySelectorAll('.bn-unit-cb'));
      const checked = cbs.filter(cb => cb.checked);
      const allCb = document.getElementById('bn-unit-all');
      const isAll = allCb ? allCb.checked : (checked.length === cbs.length);

      cbs.forEach(cb => {
        const chip = document.getElementById('bn-chip-' + cb.value);
        if (chip) {
          if (cb.checked) {
            chip.style.background = '#eff6ff';
            chip.style.borderColor = '#93c5fd';
          } else {
            chip.style.background = '#fff';
            chip.style.borderColor = '#cbd5e1';
          }
        }
      });

      const summary = document.getElementById('bn-selected-summary');
      if (!summary) return;

      if (isAll || checked.length === cbs.length) {
        summary.innerHTML = `<span>Selected:</span> <span class="badge" style="background:#eff6ff; color:#1d4ed8; font-weight:700;">🌐 All Factory Units (Company-Wide)</span>`;
      } else if (checked.length === 0) {
        summary.innerHTML = `<span>Selected:</span> <span style="color:#dc2626; font-weight:700;">⚠️ None selected (Please select at least 1 factory)</span>`;
      } else {
        const badges = checked.map(cb => `<span class="badge" style="background:#f1f5f9; color:#1e293b; border:1px solid #cbd5e1; font-weight:700; padding:2px 6px;">${cb.value}</span>`).join(' ');
        summary.innerHTML = `<span>Selected:</span> ${badges} <span style="color:#64748b; font-size:11px; font-weight:600;">(${checked.length} of ${cbs.length} factories)</span>`;
      }
    }

    function openBroadcastModal() {
      document.getElementById('bn-title').value = '';
      document.getElementById('bn-message').value = '';
      document.getElementById('bn-type').value = 'ANNOUNCEMENT';
      renderBroadcastUnitsGrid();
      toggleAllBroadcastUnits(true);
      document.getElementById('modal-broadcast-notification').classList.add('active');
    }

    async function submitBroadcastNotification() {
      const title = document.getElementById('bn-title').value.trim();
      const message = document.getElementById('bn-message').value.trim();
      const type = document.getElementById('bn-type').value;

      if (!title || !message) {
        showToast('Title and message are required', 'warning');
        return;
      }

      const allCb = document.getElementById('bn-unit-all');
      const cbs = Array.from(document.querySelectorAll('.bn-unit-cb'));
      const checked = cbs.filter(cb => cb.checked).map(cb => cb.value);

      if (!allCb.checked && checked.length === 0) {
        showToast('Please select at least one target factory unit', 'warning');
        return;
      }

      let targetUnit = 'ALL';
      if (!allCb.checked && checked.length < cbs.length) {
        targetUnit = checked.join(',');
      }

      const author = (currentAuthUser && currentAuthUser.full_name) ? currentAuthUser.full_name : 'System Super Admin';
      const payload = {
        title: title,
        message: message,
        type: type,
        target_unit: targetUnit,
        created_by: author
      };

      const btn = document.getElementById('btn-broadcast-submit');
      if (btn) { btn.disabled = true; btn.innerText = 'Broadcasting...'; }

      try {
        const res = await fetch('/api/notifications/broadcast', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (json.success) {
          showToast(`Announcement broadcasted successfully to ${targetUnit === 'ALL' ? 'all factories' : targetUnit}!`, 'success');
          closeModals();
          loadNotificationsList();
        } else {
          showToast(json.message || 'Error broadcasting', 'error');
        }
      } catch (err) {
        showToast('Broadcast failed: ' + err, 'error');
      } finally {
        if (btn) { btn.disabled = false; btn.innerText = 'Broadcast to Kiosks & Portal'; }
      }
    }

    // =========================================================================
    // CANDIDATE ONBOARDING INVITE MODAL & WORKFLOW
    // =========================================================================
    let inviteModalMasters = {
      loaded: false,
      units: [],
      departments: [],
      designations: [],
      contractors: []
    };

    async function loadInviteCandidateMasters() {
      try {
        const [factoriesRes, mastersRes] = await Promise.all([
          fetch('/api/factories/troogood').then(r => r.json()).catch(() => ({})),
          fetch('/api/masters/registration-masters').then(r => r.json()).catch(() => ({}))
        ]);

        if (factoriesRes && factoriesRes.success && factoriesRes.data && factoriesRes.data.factories) {
          inviteModalMasters.units = factoriesRes.data.factories;
        }
        if (mastersRes && mastersRes.success && mastersRes.data) {
          inviteModalMasters.departments = mastersRes.data.departments || [];
          inviteModalMasters.designations = mastersRes.data.designations || [];
          inviteModalMasters.contractors = mastersRes.data.contractors || [];
        }
        inviteModalMasters.loaded = true;
        populateInviteModalDropdowns();
      } catch (err) {
        console.error('Failed to load masters for candidate invite modal:', err);
      }
    }

    function populateInviteModalDropdowns() {
      // 1. Target Factory Units (All Synced Factories from ERP)
      const selUnit = document.getElementById('inv-unit');
      if (selUnit) {
        const curUnit = selUnit.value;
        selUnit.innerHTML = (inviteModalMasters.units || []).map(u => {
          const code = u.id || u.unit_code;
          const label = u.city ? `${u.unit_name || code} (${u.city})` : (u.unit_name || code);
          return `<option value="${code}">${label}</option>`;
        }).join('');
        if (curUnit && selUnit.querySelector(`option[value="${curUnit}"]`)) {
          selUnit.value = curUnit;
        } else if (selUnit.querySelector('option[value="TGK-1"]')) {
          selUnit.value = 'TGK-1';
        }
      }

      // 2. Departments from Master
      const selDept = document.getElementById('inv-department');
      if (selDept) {
        const curDept = selDept.value;
        selDept.innerHTML = '<option value="">Select Department</option>' +
          (inviteModalMasters.departments || []).map(d => `<option value="${d.name}" data-dept-id="${d.id}">${d.name}</option>`).join('');
        if (curDept && selDept.querySelector(`option[value="${curDept}"]`)) {
          selDept.value = curDept;
        } else if (inviteModalMasters.departments.length > 0) {
          selDept.value = inviteModalMasters.departments[0].name;
        }
        filterInviteDesignations();
      }

      // 3. Contractor Agencies from Master + Consultant and Intern
      const selContractor = document.getElementById('inv-contractor');
      if (selContractor) {
        const curContractor = selContractor.value;
        let contractorOptions = `
          <option value="TrooGood In-House">TrooGood In-House (Company Rolls)</option>
          <option value="Consultant">Consultant</option>
          <option value="Intern">Intern</option>
        `;
        (inviteModalMasters.contractors || []).forEach(c => {
          const name = c.name || c.contractor_name || c.agency_name;
          if (name) {
            contractorOptions += `<option value="${name}">${name}</option>`;
          }
        });
        selContractor.innerHTML = contractorOptions;
        if (curContractor && selContractor.querySelector(`option[value="${curContractor}"]`)) {
          selContractor.value = curContractor;
        }
      }
    }

    function filterInviteDesignations() {
      const selDept = document.getElementById('inv-department');
      const selDesig = document.getElementById('inv-designation');
      if (!selDept || !selDesig) return;

      const selectedDeptName = selDept.value;
      const selectedOpt = selDept.options[selDept.selectedIndex];
      const deptId = selectedOpt ? selectedOpt.getAttribute('data-dept-id') : null;

      selDesig.innerHTML = '<option value="">Select Designation / Role</option>';
      if (!selectedDeptName) return;

      const filtered = deptId
        ? (inviteModalMasters.designations || []).filter(d => d.department_id == deptId)
        : (inviteModalMasters.designations || []).filter(d => !d.department_name || d.department_name === selectedDeptName);

      const listToUse = filtered.length ? filtered : (inviteModalMasters.designations || []);
      listToUse.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.name;
        opt.textContent = d.name;
        selDesig.appendChild(opt);
      });
      if (listToUse.length > 0) {
        selDesig.value = listToUse[0].name;
      }
    }

    let currentInviteSourceMode = 'onboarding';

    function openInviteCandidateModal(sourceMode) {
      if (!sourceMode) {
        const hash = window.location.hash || '';
        const isPeopleActive = document.getElementById('view-people')?.classList.contains('active');
        sourceMode = (hash.includes('people') || isPeopleActive) ? 'people' : 'onboarding';
      }
      currentInviteSourceMode = sourceMode;

      document.getElementById('inv-name').value = '';
      document.getElementById('inv-email').value = '';
      document.getElementById('inv-phone').value = '';
      document.getElementById('inv-result-box').style.display = 'none';

      const pwdWrapper = document.getElementById('inv-password-wrapper');
      const pwdInput = document.getElementById('inv-password');
      const emailPwdRow = document.getElementById('inv-email-password-row');
      const modalTitle = document.getElementById('inv-modal-title');
      const modalDesc = document.getElementById('inv-modal-desc');
      const btnSubmit = document.getElementById('btn-invite-submit');

      if (sourceMode === 'people') {
        // Mode: #people -> Register Candidate with Mobile Application Link & App Password
        if (pwdWrapper) pwdWrapper.style.display = 'block';
        if (pwdInput) {
          pwdInput.value = 'TrooGood@2026';
          pwdInput.setAttribute('required', 'required');
        }
        if (emailPwdRow) emailPwdRow.style.gridTemplateColumns = '1fr 1fr';
        if (modalTitle) modalTitle.innerText = 'Register Candidate & Mobile App Access';
        if (modalDesc) modalDesc.innerText = 'Generate application credentials and access link. The candidate will receive mobile app login details and application link.';
        if (btnSubmit) btnSubmit.innerText = 'Register & Send App Link';
      } else {
        // Mode: #onboarding -> Only send statutory joining forms, NO App Password
        if (pwdWrapper) pwdWrapper.style.display = 'none';
        if (pwdInput) {
          pwdInput.value = 'TrooGood@2026'; // fallback default for backend
          pwdInput.removeAttribute('required');
        }
        if (emailPwdRow) emailPwdRow.style.gridTemplateColumns = '1fr';
        if (modalTitle) modalTitle.innerText = 'Invite Candidate for Onboarding';
        if (modalDesc) modalDesc.innerText = 'Generate an invitation link. The candidate will complete their 6-step employee submission form on a standalone page.';
        if (btnSubmit) btnSubmit.innerText = 'Generate Invite & Link';
      }

      loadInviteCandidateMasters();

      document.getElementById('modal-invite-candidate').classList.add('active');
    }

    // Alias for existing buttons
    window.openUniversalRegisterModal = openInviteCandidateModal;

    async function submitCandidateInvite() {
      const isPeopleMode = (currentInviteSourceMode === 'people');
      const passwordVal = isPeopleMode
        ? (document.getElementById('inv-password').value.trim() || 'TrooGood@2026')
        : 'TrooGood@2026';

      const payload = {
        source: currentInviteSourceMode,
        name: document.getElementById('inv-name').value.trim(),
        candidate_name: document.getElementById('inv-name').value.trim(),
        email: document.getElementById('inv-email').value.trim(),
        password: passwordVal,
        app_password: passwordVal,
        phone: document.getElementById('inv-phone').value.trim(),
        mobile: document.getElementById('inv-phone').value.trim(),
        unit_id: document.getElementById('inv-unit').value,
        unit_code: document.getElementById('inv-unit').value,
        department: document.getElementById('inv-department').value,
        designation: document.getElementById('inv-designation').value.trim(),
        contractor_agency: document.getElementById('inv-contractor').value,
      };

      if (!payload.name || !payload.email) {
        showToast('Candidate name and email are required', 'warning');
        return;
      }
      if (isPeopleMode && !document.getElementById('inv-password').value.trim()) {
        showToast('App Password / Access PIN is required for Mobile App access', 'warning');
        return;
      }

      const btn = document.getElementById('btn-invite-submit');
      if (btn) {
        btn.disabled = true;
        btn.innerText = isPeopleMode ? 'Registering & Generating Link...' : 'Generating Invite...';
      }

      try {
        const res = await fetch('/api/candidates/invite', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (json.success) {
          showToast(isPeopleMode ? 'Candidate registered & App credentials generated!' : 'Candidate invited successfully!', 'success');
          const fullUrl = window.location.origin + json.data.invite_url;
          document.getElementById('inv-result-url').value = fullUrl;
          document.getElementById('inv-open-link').href = fullUrl;

          const resTitle = document.getElementById('inv-result-title');
          const resDesc = document.getElementById('inv-result-desc');
          const credBox = document.getElementById('inv-result-cred-box');

          if (isPeopleMode) {
            if (resTitle) resTitle.innerText = '✅ Candidate Registered & App Credentials Created!';
            if (resDesc) resDesc.innerText = 'Share the Application Link & Login Credentials below:';
            if (credBox) {
              document.getElementById('inv-result-cred-email').innerText = payload.email;
              document.getElementById('inv-result-cred-password').innerText = passwordVal;
              credBox.style.display = 'block';
            }
          } else {
            if (resTitle) resTitle.innerText = '✅ Candidate Invited Successfully!';
            if (resDesc) resDesc.innerText = 'Share this standalone submission URL with the candidate:';
            if (credBox) credBox.style.display = 'none';
          }

          document.getElementById('inv-result-box').style.display = 'block';

          // Refresh pipeline or directory
          if (typeof loadOnboardingPipeline === 'function') loadOnboardingPipeline();
          if (isPeopleMode && typeof loadPeople === 'function') loadPeople();
        } else {
          showToast(json.message || 'Error processing request', 'error');
        }
      } catch (err) {
        showToast('Invite error: ' + err, 'error');
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.innerText = isPeopleMode ? 'Register & Send App Link' : 'Generate Invite & Link';
        }
      }
    }

    function copyInviteUrl() {
      const el = document.getElementById('inv-result-url');
      if (!el) return;
      el.select();
      navigator.clipboard.writeText(el.value).then(() => {
        showToast('Invite link copied to clipboard!', 'success');
      }).catch(() => {
        document.execCommand('copy');
        showToast('Invite link copied to clipboard!', 'success');
      });
    }

    // =========================================================================
    // ATTENDANCE APP MODE & INDIVIDUAL ACCESS POPUP
    // =========================================================================
    function handleAppModeChange(mode) {
      const btn = document.getElementById('btn-cfg-individual');
      if (btn) btn.style.display = (mode === 'individual') ? 'inline-flex' : 'none';
      if (mode === 'individual') {
        openIndividualAccessModal();
      }
    }

    function openIndividualAccessModal() {
      if (!currentViewingPerson) return;
      document.getElementById('ind-worker-id').value = currentViewingPerson.id;
      document.getElementById('ind-worker-name').value = currentViewingPerson.full_name + ' (#' + currentViewingPerson.worker_code + ')';
      
      const defaultEmail = currentViewingPerson.app_email || currentViewingPerson.email_address || (currentViewingPerson.worker_code ? currentViewingPerson.worker_code.toLowerCase() + '@troogoodhrms.com' : 'worker@troogoodhrms.com');
      document.getElementById('ind-app-email').value = defaultEmail;
      document.getElementById('ind-app-password').value = 'TrooGood@' + (currentViewingPerson.worker_code || '2026');
      document.getElementById('ind-apk-link').value = 'https://troogoodhrms.com/downloads/TrooGood_HR_App.apk';

      document.getElementById('modal-individual-access').classList.add('active');
    }

    async function submitIndividualAccess() {
      const workerId = document.getElementById('ind-worker-id').value;
      const appEmail = document.getElementById('ind-app-email').value.trim();
      const appPassword = document.getElementById('ind-app-password').value.trim();

      if (!workerId || !appEmail || !appPassword) {
        showToast('Worker ID, App email, and password are required', 'warning');
        return;
      }

      const btn = document.getElementById('btn-ind-access-save');
      if (btn) { btn.disabled = true; btn.innerText = 'Saving...'; }

      try {
        const res = await fetch('/api/people/app-mode', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            worker_id: workerId,
            mode: 'individual',
            app_email: appEmail,
            app_password: appPassword
          })
        });
        const json = await res.json();
        if (json.success) {
          showToast(`Individual App Access credentials active for ${appEmail}`, 'success');
          closeModals();

          // Update hint in profile card
          const hint = document.getElementById('pf-individual-status-hint');
          const emailDisplay = document.getElementById('pf-app-email-display');
          if (hint) hint.style.display = 'block';
          if (emailDisplay) emailDisplay.innerText = appEmail;
          
          if (currentViewingPerson) {
            currentViewingPerson.attendance_app_mode = 'individual';
            currentViewingPerson.app_email = appEmail;
          }
        } else {
          showToast(json.message || 'Error updating app mode', 'error');
        }
      } catch (err) {
        showToast('App mode error: ' + err, 'error');
      } finally {
        if (btn) { btn.disabled = false; btn.innerText = 'Save & Dispatch Access'; }
      }
    }

    // =========================================================================
    // OUTBOUND MAIL SERVER (SMTP) SETTINGS MODAL & API HANDLERS
    // =========================================================================
    async function openSmtpSettingsModal() {
      try {
        const res = await fetch('/api/portal/smtp-settings');
        const json = await res.json();
        if (json.success && json.data) {
          const d = json.data;
          const hostEl = document.getElementById('smtp-host');
          const portEl = document.getElementById('smtp-port');
          const encEl = document.getElementById('smtp-encryption');
          const userEl = document.getElementById('smtp-username');
          const pwdEl = document.getElementById('smtp-password');
          const fromAddrEl = document.getElementById('smtp-from-address');
          const fromNameEl = document.getElementById('smtp-from-name');

          if (hostEl) hostEl.value = d.host || 'troogood.in';
          if (portEl) portEl.value = d.port || 465;
          if (encEl) encEl.value = (d.encryption || 'SSL').toUpperCase();
          if (userEl) userEl.value = d.username || 'dailymails@troogood.in';
          if (pwdEl) pwdEl.value = d.password || 'TgMail@1234$#@!';
          if (fromAddrEl) fromAddrEl.value = d.from_address || 'dailymails@troogood.in';
          if (fromNameEl) fromNameEl.value = d.from_name || 'Troo Good Alerts';

          updateSmtpPreviewUI(d);
        }
      } catch (e) {
        console.error('Failed to load SMTP settings:', e);
      }
      const modal = document.getElementById('modal-smtp-settings');
      if (modal) modal.classList.add('active');
    }

    function closeSmtpModal() {
      const modal = document.getElementById('modal-smtp-settings');
      if (modal) modal.classList.remove('active');
    }

    function updateSmtpPreviewUI(d) {
      const h = d?.host || document.getElementById('smtp-host')?.value || 'troogood.in';
      const p = d?.port || document.getElementById('smtp-port')?.value || 465;
      const u = d?.username || document.getElementById('smtp-username')?.value || 'dailymails@troogood.in';
      const enc = (d?.encryption || document.getElementById('smtp-encryption')?.value || 'SSL').toUpperCase();
      const fn = d?.from_name || document.getElementById('smtp-from-name')?.value || 'Troo Good Alerts';
      const fa = d?.from_address || document.getElementById('smtp-from-address')?.value || 'dailymails@troogood.in';

      const ph = document.getElementById('smtp-preview-host');
      const pu = document.getElementById('smtp-preview-user');
      const pe = document.getElementById('smtp-preview-enc');
      const pf = document.getElementById('smtp-preview-from');

      if (ph) ph.innerText = `${h}:${p}`;
      if (pu) pu.innerText = u;
      if (pe) pe.innerText = enc;
      if (pf) pf.innerText = `${fn} <${fa}>`;
    }

    async function submitSmtpSettings() {
      const host = document.getElementById('smtp-host').value.trim();
      const port = parseInt(document.getElementById('smtp-port').value) || 465;
      const encryption = document.getElementById('smtp-encryption').value;
      const username = document.getElementById('smtp-username').value.trim();
      const password = document.getElementById('smtp-password').value;
      const fromAddress = document.getElementById('smtp-from-address').value.trim();
      const fromName = document.getElementById('smtp-from-name').value.trim();

      if (!host || !port || !fromAddress) {
        showToast('Host, Port, and From Address are required.', 'warning');
        return;
      }

      const btn = document.getElementById('btn-save-smtp');
      if (btn) { btn.disabled = true; btn.innerText = 'Saving Settings...'; }

      try {
        const res = await fetch('/api/portal/smtp-settings', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            host: host,
            port: port,
            encryption: encryption.toLowerCase(),
            username: username,
            password: password,
            from_address: fromAddress,
            from_name: fromName
          })
        });
        const json = await res.json();
        if (json.success) {
          showToast('Outbound Mail Server settings saved successfully! Dispatched credentials verified.', 'success');
          updateSmtpPreviewUI({ host, port, username, encryption, from_name: fromName, from_address: fromAddress });
          setTimeout(() => { closeSmtpModal(); }, 1200);
        } else {
          showToast(json.message || 'Error saving SMTP settings', 'error');
        }
      } catch (err) {
        showToast('Network error saving SMTP settings: ' + err, 'error');
      } finally {
        if (btn) { btn.disabled = false; btn.innerText = '💾 Save SMTP Settings'; }
      }
    }

    async function sendTestSmtpEmail() {
      const btn = document.getElementById('btn-smtp-test');
      if (btn) { btn.disabled = true; btn.innerText = 'Dispatched Handshake...'; }

      const toEmail = document.getElementById('smtp-from-address')?.value.trim() || 'dailymails@troogood.in';

      try {
        const res = await fetch('/api/portal/smtp-settings/test', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ recipient_email: toEmail })
        });
        const json = await res.json();
        if (json.success) {
          showToast(`✓ Test email dispatched successfully to ${toEmail}!`, 'success');
        } else {
          showToast('SMTP Test Failed: ' + (json.message || 'Server error'), 'error');
        }
      } catch (err) {
        showToast('Failed to connect to SMTP server: ' + err, 'error');
      } finally {
        if (btn) { btn.disabled = false; btn.innerText = '✉️ Test Connection'; }
      }
    }

    // Initial load & State Restoration
    document.addEventListener('DOMContentLoaded', () => {
      initUserAuth();
      loadDynamicUnits();
      loadShiftOptionsFromMaster();

      // Clear any browser-autofilled text in the topbar search input
      const topSearch = document.getElementById('topbar-search-input');
      if (topSearch) {
        topSearch.value = '';
        setTimeout(() => { if (topSearch) topSearch.value = ''; }, 100);
        setTimeout(() => { if (topSearch) topSearch.value = ''; }, 400);
      }

      // Check URL hash, pathname, or localStorage to persist current active page on browser refresh
      const hash = window.location.hash.replace(/^#/, '').trim();
      const rawPath = window.location.pathname.replace(/^\//, '').trim();
      const path = (rawPath && rawPath !== 'index.php' && rawPath !== 'portal.php') ? rawPath : '';
      const savedView = localStorage.getItem('tg_active_view') || '';
      const initialView = hash || path || savedView || 'dashboard';

      if (initialView.startsWith('person-detail/')) {
        const pid = initialView.split('/')[1];
        switchView('people', true);
        if (pid) {
          viewPerson(pid);
        }
      } else {
        switchView(initialView);
      }
    });

    // Support browser back/forward buttons seamlessly
    window.addEventListener('hashchange', () => {
      const hash = window.location.hash.replace(/^#/, '').trim() || 'dashboard';
      switchView(hash, true);
    });
  </script>
</body>
</html>
