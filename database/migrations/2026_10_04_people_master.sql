-- =============================================================================
-- People Master: salary / insurance / PF exemption fields,
-- login (attendance) assignments and unit transfer history.
-- Safe to re-run: uses IF NOT EXISTS everywhere (MariaDB 10.x).
-- =============================================================================

ALTER TABLE workers
    ADD COLUMN IF NOT EXISTS designation VARCHAR(150) NULL AFTER department,
    ADD COLUMN IF NOT EXISTS monthly_salary DECIMAL(12,2) NULL AFTER designation,
    ADD COLUMN IF NOT EXISTS pf_exemption_url VARCHAR(255) NULL AFTER cheque_passbook_url;

ALTER TABLE universal_registrations
    ADD COLUMN IF NOT EXISTS health_insurance_number VARCHAR(60) NULL AFTER esic_number,
    ADD COLUMN IF NOT EXISTS monthly_salary DECIMAL(12,2) NULL AFTER designation_title,
    ADD COLUMN IF NOT EXISTS pf_exemption_url VARCHAR(255) NULL AFTER opt_out_pf_esi;

-- Which kiosk logins (users) a worker's face vectors sync to.
-- If a worker has NO active assignment, he syncs to every login of his unit (default).
CREATE TABLE IF NOT EXISTS worker_login_assignments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    user_id INT NOT NULL,
    assigned_by VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_worker_user (worker_id, user_id),
    KEY idx_wla_user (user_id),
    KEY idx_wla_worker (worker_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Immutable history of unit transfers.
CREATE TABLE IF NOT EXISTS worker_transfers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    from_unit VARCHAR(50) NULL,
    to_unit VARCHAR(50) NOT NULL,
    reason VARCHAR(255) NULL,
    transferred_by VARCHAR(100) NULL,
    effective_date DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_wt_worker (worker_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
