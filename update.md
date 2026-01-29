# 📋 Production Readiness & Feature Revision To-Do List
**Tech Stack:** Laravel + Filament v3  
**Realtime:** Laravel Echo + Pusher / Reverb  
**PDF Generation:** PHP command-based (Blade-driven)  
**Importing:** Maatwebsite Excel  
**Roles:** Admin, Municipal Health Officer (MHO), Barangay Health Worker (BHW), Resident (Patient)

---

## 1️⃣ Email Sending – Production Readiness Check

### Objective
Ensure all email-related features are fully functional, reliable, and production-ready using SMTP.

### Tasks
- Verify `.env` SMTP configuration:
  - `MAIL_MAILER=smtp`
  - `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`
  - `MAIL_ENCRYPTION=tls`
  - `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`
- Confirm Google SMTP credentials are production (not sandbox/test).
- Verify **email queueing**:
  - Emails for login, registration, and verification must be queued.
  - Ensure `queue:work` is running in production.
- Check `failed_jobs` table exists and is monitored.
- Test scenarios:
  - User registration email
  - Login OTP / verification email
  - Password reset email
- Confirm emails are **NOT used** for messaging notifications.

### Acceptance Criteria
- Emails are sent asynchronously via queue.
- No blocking email logic in controllers/resources.
- Failed emails are logged and retryable.

---

## 2️⃣ Real-Time Messaging & Notification Hardening

### Objective
Ensure messaging and in-app notifications are stable, real-time, and production-safe.

### Tasks
- Review database schema:
  - Messages are stored persistently.
  - Sender, receiver, role-based targeting is correct.
- Verify broadcasting:
  - Events implement `ShouldBroadcast`.
  - Channels are properly authorized per role.
- Test real-time behavior:
  - Admin ↔ MHO
  - MHO ↔ BHW
  - BHW ↔ Resident
- Validate notification logic:
  - In-app notifications only (no email).
  - Notification count updates in real-time.
- Check for anomalies:
  - Duplicate messages
  - Missing broadcasts
  - Race conditions
- Confirm Pusher / Reverb credentials:
  - Production keys
  - Secure WebSocket connections

### Acceptance Criteria
- Messages appear instantly without refresh.
- Notifications are accurate and role-aware.
- No console or server broadcast errors.

---

## 3️⃣ Patient Resource → Rename to Residents Resource

### Objective
Align terminology and fix data integrity issues.

### Tasks
- Rename:
  - Resource: `PatientResource` → `ResidentResource`
  - Routes, permissions, navigation labels
- Replace **Barangay column** in table:
  - Remove barangay text column
  - Add **Purok select column**
- Fix age handling:
  - Ensure `age` column is stored as **INTEGER**
  - Remove float/double casting
  - Validate age input as integer only
- Baby records:
  - Ensure newborns/infants store age correctly as integer

### Acceptance Criteria
- Resource naming is consistent across UI and backend.
- Age is never stored as double.
- Purok selection works correctly.

---

## 4️⃣ Category Resource – Remove Description Field

### Objective
Simplify category data structure.

### Tasks
- Remove `description` field from:
  - Filament form
  - Table display
- (Optional) Remove column from database if unused.
- Update validation rules accordingly.

### Acceptance Criteria
- Category form no longer shows description.
- No errors on create/update.

---

## 5️⃣ Barangay Defaulting & Locking (Residents Form)

### Objective
Ensure barangay consistency based on logged-in user role.

### Logic
- If current user has `barangay_id`:
  - Auto-select that barangay in the resident form.
  - Disable the barangay dropdown.
- If user has no barangay:
  - Dropdown remains editable.

### Tasks
- Fetch `auth()->user()->barangay_id`.
- Apply default value in Filament form.
- Conditionally disable field for BHW users.
- Ensure Admin & MHO can still select barangay freely.

### Acceptance Criteria
- BHW users cannot change barangay.
- Data integrity is enforced automatically.

---

## 6️⃣ Residents Bulk Import – Multi-file & Phone Normalization

### Objective
Improve flexibility and robustness of bulk imports.

### Tasks
- Enable **multi-file upload**:
  - Accept multiple XLSX and CSV files.
- Phone number normalization:
  - Accept formats:
    - `+63XXXXXXXXXX`
    - `09XXXXXXXXX`
    - `9XXXXXXXXX`
  - If starts with `9`, prepend `0`.
  - Store as **string**, not integer.
- Validation:
  - Only accept Philippine numbers.
  - Invalid numbers saved as string (no hard fail).
- Import behavior:
  - Continue importing even if some rows have conflicts.
  - Log conflicted rows for review.

### Acceptance Criteria
- Multiple files import successfully.
- Phone numbers are normalized and saved correctly.
- Import never stops due to single-row errors.

---

## 7️⃣ Consultation Resource – Label Updates

### Objective
Improve UI clarity.

### Tasks
- Change table label:
  - `"Referred"` → `"View Details"`
- Update action:
  - `"Download Referral PDF"` → icon-only button
  - PDF still includes logo, but **no text label**.

### Acceptance Criteria
- Labels are clear and minimal.
- Download action works correctly.

---

## 8️⃣ Fix Error: `Call to a member function getName() on null`

### Location
Occurs when clicking **Create Referral**.

### Tasks
- Identify nullable relationships:
  - Doctor
  - Patient/Resident
  - Barangay
- Add null-safe checks:
  - Use `optional()` or null guards.
- Ensure required relationships are enforced before referral creation.
- Add validation or fallback values.

### Acceptance Criteria
- No fatal errors when creating referral.
- Clear validation messages if data is missing.

---

## 9️⃣ Referral PDF – Missing Textarea Content

### Objective
Ensure all form data appears in the generated PDF.

### Tasks
- Inspect Blade PDF template.
- Ensure fields included:
  - Notes
  - Remarks
  - Other textarea-based inputs
- Confirm data passed to view is complete.
- Test with long multi-line inputs.

### Acceptance Criteria
- All textarea fields render properly in PDF.
- No missing content.

---

## 🔟 Clinical Referral – Merge Date & Time

### Objective
Improve form UX and data consistency.

### Tasks
- Replace separate Date and Time fields.
- Use single **DateTime picker**.
- Update:
  - Database handling
  - Validation rules
  - PDF display formatting

### Acceptance Criteria
- One unified DateTime input.
- Correct storage and display everywhere.

---

## 1️⃣1️⃣ Analytics – Add Demographic Filters

### Objective
Enhance data filtering.

### Filters to Add
- Male
- Female
- Children (age-based)

### Tasks
- Extend existing dropdown filter.
- Children definition:
  - Use integer age column.
- Update queries accordingly.
- Ensure filters can be combined with existing ones.

### Acceptance Criteria
- Filters work correctly.
- Analytics update instantly.

---

## 1️⃣2️⃣ Program Calendar – Rename Label

### Objective
Improve naming clarity.

### Tasks
- Change label:
  - `"Program Calendar"` → `"Health Program Calendar"`
- Update navigation, headers, and breadcrumbs.

### Acceptance Criteria
- Label updated everywhere consistently.

---

## 1️⃣3️⃣ Fix Empty “View Program” Modal

### Objective
Display correct program information.

### Fields to Display
- Program Name
- Date Range
- Barangay
- Description
- Coordinator

### Tasks
- Verify modal data binding.
- Ensure correct program record is passed.
- Update modal UI layout if needed.

### Acceptance Criteria
- Modal shows complete program details.
- No empty or undefined fields.

---

## ✅ Final Notes
- Prioritize production blockers first:
  1. Email
  2. Messaging
  3. Referral errors
- Add logs where silent failures are possible.
- All changes must be role-aware and data-safe.

---

**End of Document**