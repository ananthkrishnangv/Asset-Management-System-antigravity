# CSIR-SERC Asset Management System - Walkthrough Guide

## Quick Start

### Accessing the System

1. **Open Browser**: Navigate to `https://ir.serc.res.in/public/`
2. **Login**: Enter your AMS ID and password
3. **Dashboard**: View statistics and quick actions

---

## User Guide

### 1. Dashboard

The dashboard provides an overview of:

- **Statistics Cards**: Total DIR items, PIR items, pending transfers, pending returns
- **Quick Actions** (Supervisors): Add DIR/PIR items, export reports, generate QR codes
- **Charts**: Category distribution, monthly trends, condition breakdown
- **Recent Activity**: Latest system actions
- **Pending Approvals**: Transfer requests awaiting your action

### 2. DIR Inventory

**View DIR Items:**
1. Click "DIR Details" in sidebar
2. Use search box to filter items
3. Click column headers to sort
4. Use export buttons (CSV, PDF, Print)

**Add New DIR Item:**
1. Click "Add New" button
2. Fill in basic information (description, quantity, category)
3. Select department and current holder
4. Enter purchase details (PO number, date, amount)
5. Upload scanned document and asset photo
6. Set warranty/AMC expiry dates
7. Click "Save Asset"

### 3. PIR Inventory

Same functionality as DIR, but for personal-issue items assigned to specific employees.

### 4. Transfer Requests

**Initiate Transfer:**
1. Go to item details
2. Click "Request Transfer"
3. Select new holder/department
4. Add remarks
5. Submit for approval

**Approve Transfer (Supervisors):**
1. View pending transfers in dashboard or Transfers page
2. Review request details
3. Approve or reject with comments

### 5. Stores Returns

**Request Return:**
1. Go to item details
2. Click "Return to Stores"
3. Select reason (non-serviceable, surplus, etc.)
4. Submit for approval

### 6. Reports

**View Analytics:**
- Department-wise distribution
- Category breakdown
- Condition status
- Monthly trends
- Expiring warranties/AMCs

**Export Data:**
- Click "Export DIR" or "Export PIR" for CSV
- Click "Print Report" for PDF

### 7. Activity Logs (Admin)

View complete audit trail:
1. Go to Admin → Activity Logs
2. Filter by user, action type, or date range
3. Export logs if needed

---

## Admin Guide

### 1. User Management

**Add User:**
1. Go to Admin → Users
2. Click "Add User"
3. Enter AMS ID, name, email
4. Select role and department
5. Set password or leave blank for default

**Import Users (CSV):**
1. Prepare CSV with columns: AMS_ID, Name, Email, Password, Role, Department
2. Click "Import CSV"
3. Upload file

**Delete Users:**
1. Select users with checkboxes
2. Click "Delete Selected"
3. Confirm deletion

### 2. System Settings

**General Configuration:**
- Organization name and address
- Maintenance mode toggle
- Items per page setting

**Email (SMTP):**
- Enable SMTP
- Configure host, port, credentials
- Test with "Send Test Email"

**WhatsApp Integration:**
- Enable WhatsApp notifications
- Enter Phone Number ID and Access Token
- Select notification triggers

**Backup Configuration:**
- Enable automatic backups
- Set backup time
- Configure retention period
- Create manual backup

**Alert Settings:**
- Warranty alert threshold (days)
- AMC alert threshold (days)

### 3. Department Management

- Add/edit departments
- Set building and floor
- Assign HoD

---

## Common Tasks

### Find an Asset
1. Go to DIR or PIR listing
2. Use the search box
3. Filter by category, department, or condition

### Check Warranty Status
1. Go to Reports page
2. View "Expiring Warranties" section
3. Or check item details page

### Generate QR Codes
1. Go to Settings → Quick Actions
2. Click "Generate All DIR QR Codes" or "Generate All PIR QR Codes"

### Create Backup
1. Go to Settings
2. Click "Create Manual Backup Now"
3. Download backup file from Backups page

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Can't login | Check AMS ID spelling, reset password if needed |
| 500 Error | Clear browser cache, contact admin |
| File upload fails | Check file size (max 10MB) and type |
| Charts not loading | Refresh page, check internet connection |

---

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `/` | Focus search box (in listings) |
| `Esc` | Close modal dialogs |
| `Enter` | Submit forms |

---

## Mobile Access

The system is responsive and works on:
- Desktop browsers (Chrome, Firefox, Edge, Safari)
- Tablets (iPad, Android)
- Mobile phones (limited functionality)

For best experience, use a desktop or tablet in landscape mode.

---

## Getting Help

- **Technical Issues**: Contact ICT Division
- **Training**: Request demo session from admin
- **Feedback**: Email suggestions to ict@serc.res.in
