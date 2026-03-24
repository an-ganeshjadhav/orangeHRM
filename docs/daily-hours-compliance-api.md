# Daily 9-Hour Compliance — Complete API Documentation

## Overview

A custom feature added to OrangeHRM that tracks whether employees completed **9 working hours per day** based on attendance punch-out time. Saturdays and Sundays are automatically excluded. Available as both a **REST API** and an **admin panel page** (Time → Attendance → Daily 9Hr Report).

---

## API Endpoint

| Property | Value |
|---|---|
| **Method** | `GET` |
| **Path** | `/api/v2/attendance/employees/daily-hours-compliance` |
| **Full URL** | `http://<your-domain>/orangehrm/web/index.php/api/v2/attendance/employees/daily-hours-compliance` |
| **Auth** | Session cookie (must be logged in) |

### Query Parameters

| Param | Type | Required | Default | Description |
|---|---|---|---|---|
| `fromDate` | `YYYY-MM-DD` | No | Today | Start of date range |
| `toDate` | `YYYY-MM-DD` | No | Today | End of date range |
| `empNumber` | `integer` | No | All accessible | Filter to specific employee |
| `limit` | `integer` | No | 50 | Number of records per page |
| `offset` | `integer` | No | 0 | Pagination offset |

### Access Control

| Role | Access |
|---|---|
| **Admin** | All employees |
| **Supervisor** | Direct subordinates only |
| **ESS** | Not permitted |

---

## All API Calls with Examples

### 1. Get Today's Compliance (Default)

**Use:** Quick check — did employees complete 9 hours today?

```
GET /api/v2/attendance/employees/daily-hours-compliance
```

```bash
curl -X GET "http://localhost/orangehrm/orangehrm/web/index.php/api/v2/attendance/employees/daily-hours-compliance" \
  --cookie "PHPSESSID=<your_session>"
```

**Response:**
```json
{
  "data": [
    {
      "empNumber": 2,
      "lastName": "Raghavan",
      "firstName": "Rajeev",
      "middleName": "Chanfran",
      "employeeId": "0002",
      "terminationId": null,
      "date": "2026-03-20",
      "duration": {
        "hours": 4,
        "minutes": 7,
        "label": "4.12"
      },
      "completed9Hours": false
    }
  ],
  "meta": { "total": 1 },
  "rels": []
}
```

---

### 2. Get Full Month Report

**Use:** Monthly attendance report showing Present/Absent for each working day.

```
GET /api/v2/attendance/employees/daily-hours-compliance?fromDate=2026-03-01&toDate=2026-03-31
```

```bash
curl -X GET "http://localhost/orangehrm/orangehrm/web/index.php/api/v2/attendance/employees/daily-hours-compliance?fromDate=2026-03-01&toDate=2026-03-31" \
  --cookie "PHPSESSID=<your_session>"
```

**Response:** Returns one row per employee per working day (Mon-Fri only) where they had punch records.

---

### 3. Get Specific Employee's Report

**Use:** Check one employee's compliance for a date range.

```
GET /api/v2/attendance/employees/daily-hours-compliance?empNumber=2&fromDate=2026-03-01&toDate=2026-03-31
```

```bash
curl -X GET "http://localhost/orangehrm/orangehrm/web/index.php/api/v2/attendance/employees/daily-hours-compliance?empNumber=2&fromDate=2026-03-01&toDate=2026-03-31" \
  --cookie "PHPSESSID=<your_session>"
```

---

### 4. Get Single Date Report

**Use:** Check all employees' compliance for a specific date.

```
GET /api/v2/attendance/employees/daily-hours-compliance?fromDate=2026-03-20&toDate=2026-03-20
```

```bash
curl -X GET "http://localhost/orangehrm/orangehrm/web/index.php/api/v2/attendance/employees/daily-hours-compliance?fromDate=2026-03-20&toDate=2026-03-20" \
  --cookie "PHPSESSID=<your_session>"
```

---

### 5. Paginated Request (Page 2)

**Use:** When there are many records, fetch page 2 (records 51-100).

```
GET /api/v2/attendance/employees/daily-hours-compliance?fromDate=2026-03-01&toDate=2026-03-31&limit=50&offset=50
```

```bash
curl -X GET "http://localhost/orangehrm/orangehrm/web/index.php/api/v2/attendance/employees/daily-hours-compliance?fromDate=2026-03-01&toDate=2026-03-31&limit=50&offset=50" \
  --cookie "PHPSESSID=<your_session>"
```

---

### 6. Get Specific Employee on a Single Date

**Use:** Check if one employee completed 9 hours on a particular day.

```
GET /api/v2/attendance/employees/daily-hours-compliance?empNumber=3&fromDate=2026-03-20&toDate=2026-03-20
```

```bash
curl -X GET "http://localhost/orangehrm/orangehrm/web/index.php/api/v2/attendance/employees/daily-hours-compliance?empNumber=3&fromDate=2026-03-20&toDate=2026-03-20" \
  --cookie "PHPSESSID=<your_session>"
```

**Response:**
```json
{
  "data": [
    {
      "empNumber": 3,
      "lastName": "Patil",
      "firstName": "Dilp",
      "middleName": "shantaram",
      "employeeId": "0003",
      "terminationId": null,
      "date": "2026-03-20",
      "duration": {
        "hours": 0,
        "minutes": 22,
        "label": "0.37"
      },
      "completed9Hours": false
    }
  ],
  "meta": { "total": 1 },
  "rels": []
}
```

---

## Response Fields Explained

| Field | Type | Description |
|---|---|---|
| `empNumber` | integer | Employee's internal ID |
| `firstName` | string | First name |
| `lastName` | string | Last name |
| `middleName` | string | Middle name |
| `employeeId` | string | Employee ID (e.g., "0002") |
| `terminationId` | integer/null | Null if active, has value if terminated |
| `date` | string (YYYY-MM-DD) | The working date |
| `duration.hours` | integer | Total hours worked that day |
| `duration.minutes` | integer | Remaining minutes after hours |
| `duration.label` | string | Decimal hours (e.g., "4.12" = 4 hours 7 minutes) |
| `completed9Hours` | boolean | `true` = Present (≥9 hrs), `false` = Absent (<9 hrs) |
| `meta.total` | integer | Total number of records |

---

## Business Rules

1. **Weekends excluded** — Saturday and Sunday records never appear
2. **Punch-out based** — Date is determined by punch-out time, not punch-in
3. **Only completed punches** — Open/incomplete punches (no punch-out) are ignored
4. **9-hour threshold** — 32,400 seconds (9 × 60 × 60)
5. **Multiple punches summed** — If employee punches in/out multiple times in a day, all durations are added
6. **No-punch days hidden** — Days with no attendance records don't appear (no row = no data)

---

## Frontend Page

**URL:** `http://<your-domain>/orangehrm/web/index.php/attendance/dailyHoursCompliance`

**Menu Path:** Time → Attendance → Daily 9Hr Report

**Filters:**
- Employee Name (optional autocomplete)
- Month (required, dropdown)
- Year (required, dropdown)
- Date (optional, date picker — overrides month/year for single-day view)

**Table Columns:** Employee Name | Month | Year | Total Hours | Status (9 Hrs)

**Status Values:** Present (≥9 hrs) or Absent (<9 hrs)

---

## Architecture Flow

```
GET /api/v2/attendance/employees/daily-hours-compliance
  │
  ▼
GenericRestController → EmployeeDailyHoursComplianceAPI.getAll()
  │
  ├── Validate params (fromDate, toDate, empNumber)
  ├── Get accessible employee IDs via UserRoleManager
  │
  ▼
AttendanceDao.getEmployeeDailyHoursCompliance()
  │
  ├── SQL: GROUP BY employee + DATE(punchOutUserTime)
  ├── SQL: SUM(TIME_DIFF(punchOut - punchIn))
  ├── SQL: WHERE punchOutUtcTime IS NOT NULL
  │
  ▼
Filter out Sat/Sun (PHP DateTime::format('N') >= 6)
  │
  ▼
EmployeeDailyHoursComplianceListModel.toArray()
  │
  ├── Convert seconds → hours/minutes
  ├── Add completed9Hours flag (>= 32400 sec)
  │
  ▼
JSON Response
```
