# Daily 9-Hour Compliance Feature — Server Deployment Guide

## Overview

This feature adds a **Daily 9-Hour Compliance Report** to OrangeHRM under **Time → Attendance → Daily 9Hr Report**. It tracks whether employees completed 9 hours daily (based on punch-out time), excluding weekends.

---

## Files to Deploy

### New Files (copy to server)

| # | File Path | Description |
|---|-----------|-------------|
| 1 | `src/plugins/orangehrmAttendancePlugin/Api/EmployeeDailyHoursComplianceAPI.php` | REST API endpoint |
| 2 | `src/plugins/orangehrmAttendancePlugin/Api/Model/EmployeeDailyHoursComplianceListModel.php` | API response model |
| 3 | `src/plugins/orangehrmAttendancePlugin/Controller/DailyHoursComplianceController.php` | PHP page controller |
| 4 | `src/client/src/orangehrmAttendancePlugin/pages/DailyHoursCompliance.vue` | Vue frontend page |

### Modified Files (update on server)

| # | File Path | What Changed |
|---|-----------|-------------|
| 5 | `src/plugins/orangehrmAttendancePlugin/Dao/AttendanceDao.php` | Added `getEmployeeDailyHoursCompliance()` method at the end of the class |
| 6 | `src/plugins/orangehrmAttendancePlugin/config/routes.yaml` | Added API route + screen route at the end |
| 7 | `src/client/src/orangehrmAttendancePlugin/index.ts` | Added `DailyHoursCompliance` import + registration |

### Built Assets (copy to server)

| # | File Path | Description |
|---|-----------|-------------|
| 8 | `web/dist/` (entire folder) | Compiled frontend JS/CSS assets |

---

## Step-by-Step Deployment

### Step 1: Copy Files to Server

Copy all **new files** (items 1-4) and **modified files** (items 5-7) to the same paths on the server.

```bash
# Example using scp (replace SERVER_IP and SERVER_PATH)
scp src/plugins/orangehrmAttendancePlugin/Api/EmployeeDailyHoursComplianceAPI.php user@SERVER_IP:SERVER_PATH/src/plugins/orangehrmAttendancePlugin/Api/

scp src/plugins/orangehrmAttendancePlugin/Api/Model/EmployeeDailyHoursComplianceListModel.php user@SERVER_IP:SERVER_PATH/src/plugins/orangehrmAttendancePlugin/Api/Model/

scp src/plugins/orangehrmAttendancePlugin/Controller/DailyHoursComplianceController.php user@SERVER_IP:SERVER_PATH/src/plugins/orangehrmAttendancePlugin/Controller/

scp src/plugins/orangehrmAttendancePlugin/Dao/AttendanceDao.php user@SERVER_IP:SERVER_PATH/src/plugins/orangehrmAttendancePlugin/Dao/

scp src/plugins/orangehrmAttendancePlugin/config/routes.yaml user@SERVER_IP:SERVER_PATH/src/plugins/orangehrmAttendancePlugin/config/

scp src/client/src/orangehrmAttendancePlugin/index.ts user@SERVER_IP:SERVER_PATH/src/client/src/orangehrmAttendancePlugin/
```

### Step 2: Copy Built Frontend Assets

```bash
# Copy the entire web/dist folder
scp -r web/dist/ user@SERVER_IP:SERVER_PATH/web/
```

### Step 3: Regenerate Composer Autoload on Server

SSH into the server and run:

```bash
cd /path/to/orangehrm/src
composer dump-autoload --no-scripts
```

### Step 4: Clear Cache on Server

```bash
cd /path/to/orangehrm/src
rm -rf cache/doctrine_metadata/*
rm -rf cache/doctrine_queries/*
```

### Step 5: Run Database SQL

Connect to the server's MySQL database and execute these SQL statements:

```sql
-- 1. Register the API data group
INSERT INTO ohrm_data_group (id, name, description, can_read, can_create, can_update, can_delete)
VALUES (278, 'apiv2_attendance_employee_daily_hours_compliance', 'Daily Hours Compliance API', 1, 0, 0, 0);

-- 2. Register the API permission
INSERT INTO ohrm_api_permission (id, api_name, module_id, data_group_id)
VALUES (197, 'OrangeHRM\\Attendance\\Api\\EmployeeDailyHoursComplianceAPI', 6, 278);

-- 3. Grant Admin role read access to API (read + self_read)
INSERT INTO ohrm_user_role_data_group (id, user_role_id, data_group_id, can_read, can_create, can_update, can_delete, self)
VALUES (712, 1, 278, 1, 0, 0, 0, 0);

INSERT INTO ohrm_user_role_data_group (id, user_role_id, data_group_id, can_read, can_create, can_update, can_delete, self)
VALUES (713, 1, 278, 1, 0, 0, 0, 1);

-- 4. Grant Supervisor role read access to API
INSERT INTO ohrm_user_role_data_group (id, user_role_id, data_group_id, can_read, can_create, can_update, can_delete, self)
VALUES (714, 3, 278, 1, 0, 0, 0, 0);

INSERT INTO ohrm_user_role_data_group (id, user_role_id, data_group_id, can_read, can_create, can_update, can_delete, self)
VALUES (715, 3, 278, 1, 0, 0, 0, 1);

-- 5. Register the screen
INSERT INTO ohrm_screen (id, name, module_id, action_url, menu_configurator)
VALUES (183, 'Daily Hours Compliance', 6, 'dailyHoursCompliance', 'OrangeHRM\\Attendance\\Menu\\AttendanceMenuConfigurator');

-- 6. Add menu item under Attendance (parent_id=56)
INSERT INTO ohrm_menu_item (id, menu_title, screen_id, parent_id, level, order_hint, status)
VALUES (113, 'Daily 9Hr Report', 183, 56, 3, 500, 1);

-- 7. Grant Admin screen access
INSERT INTO ohrm_user_role_screen (id, user_role_id, screen_id, can_read, can_create, can_update, can_delete)
VALUES (262, 1, 183, 1, 0, 0, 0);

-- 8. Grant Supervisor screen access
INSERT INTO ohrm_user_role_screen (id, user_role_id, screen_id, can_read, can_create, can_update, can_delete)
VALUES (263, 3, 183, 1, 0, 0, 0);
```

> **IMPORTANT:** Before running the SQL, check if the IDs (278, 197, 712-715, 183, 113, 262-263) already exist on the server. If they conflict, use the next available ID:
> ```sql
> SELECT MAX(id) FROM ohrm_data_group;
> SELECT MAX(id) FROM ohrm_api_permission;
> SELECT MAX(id) FROM ohrm_user_role_data_group;
> SELECT MAX(id) FROM ohrm_screen;
> SELECT MAX(id) FROM ohrm_menu_item;
> SELECT MAX(id) FROM ohrm_user_role_screen;
> ```

### Step 6: Verify

1. Log in as Admin on the server
2. Go to **Time → Attendance → Daily 9Hr Report**
3. Select a month/year, click **View**
4. Confirm data appears with Present/Absent status

---

## Rollback (If Needed)

To undo the changes:

```sql
-- Remove DB records
DELETE FROM ohrm_user_role_screen WHERE screen_id = 183;
DELETE FROM ohrm_menu_item WHERE id = 113;
DELETE FROM ohrm_screen WHERE id = 183;
DELETE FROM ohrm_user_role_data_group WHERE data_group_id = 278;
DELETE FROM ohrm_api_permission WHERE data_group_id = 278;
DELETE FROM ohrm_data_group WHERE id = 278;
```

Then remove the 4 new files and restore the 3 modified files from backup.

---

## Summary

| Component             | Count            |
|-----------------------|------------------|
| New PHP files         | 3                |
| New Vue file          | 1                |
| Modified PHP files    | 1                |
| Modified config files | 2                |
| Built assets          | web/dist/ folder |
| DB inserts            | 8 SQL statements |
