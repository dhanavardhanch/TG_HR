# Wireframe to Backend Implementation Workflow

## Process
When wireframes are placed into `E:\TG_HR_Mobile\TG_Laravel\screens\`:

1. **Screen Inspection**:
   - Analyze visual UI elements, input forms, data tables, metrics cards, action buttons, and modal dialogs.
2. **Entity & Schema Mapping**:
   - Ensure all displayed fields exist in `Troogood_HR_APP` schema.
   - If new entities are required, generate a clean migration script with `created_at`, `updated_at`, `deleted_at`.
3. **Controller & Business Logic**:
   - Implement API endpoints matching the screen's interactive actions (e.g., submit punch, approve leave, export report).
4. **Validation & Security**:
   - Sanitization, CSRF/bearer token authentication, and forensic logging to `audit_logs`.
