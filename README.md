# TimeTrack Pro - Comprehensive Workforce Management System

A full-featured time tracking, project management, and team collaboration platform built with PHP and MySQL. TimeTrack Pro helps businesses manage employee attendance, track project progress, coordinate teams, and generate comprehensive reports.

![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?style=flat&logo=tailwind-css&logoColor=white)
![License](https://img.shields.io/badge/License-Proprietary-red.svg)

---

## 📋 Table of Contents

- [Features](#-features)
- [System Architecture](#-system-architecture)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Database Setup](#-database-setup)
- [Configuration](#-configuration)
- [User Roles](#-user-roles)
- [Core Modules](#-core-modules)
- [API Documentation](#-api-documentation)
- [Screenshots](#-screenshots)
- [Security](#-security)
- [Contributing](#-contributing)
- [License](#-license)

---

## 🌟 Features

### Time & Attendance Management

- **Clock In/Out System**: Barcode-based attendance tracking
- **Real-time Status Monitoring**: Track who's currently clocked in
- **Attendance History**: Comprehensive reports of all clock events
- **Flexible Permissions**: Configure who can clock themselves or others
- **Time Window Controls**: Set specific time ranges for clock-in/out operations

### Project Management

- **Multi-Phase Projects**: Break down projects into manageable phases
- **Task Management**: Create, assign, and track tasks with deadlines
- **Team Assignment**: Link projects to specific teams
- **Client Management**: Track client information and project deliverables
- **Budget Tracking**: Monitor estimated vs actual hours
- **Progress Visualization**: Real-time project completion percentages
- **Empty State Handling**: Professional UI when no projects exist

### Team Collaboration

- **Team Creation**: Organize staff into functional teams
- **Team Leaders**: Assign dedicated leaders with special permissions
- **Multi-Member Selection**: Add multiple team members simultaneously via checkbox interface
- **Team Dashboard**: View team statistics and active projects
- **Member Management**: Add/remove members with role tracking
- **Empty State Handling**: Guided UI for creating first team

### Staff Management

- **User Directory**: Comprehensive staff database with search and filters
- **Role-Based Access**: Admin, Team Leader, and Team Member roles
- **Barcode Integration**: Unique barcode assignment for each user
- **Permission Controls**: Granular permissions for clocking and reporting
- **Bulk Operations**: Select and manage multiple users
- **User Status**: Active/inactive user management
- **Empty State Handling**: Onboarding UI for first staff member

### Reporting & Analytics

- **Daily Activity Reports**: Plan and report on daily activities
- **Task Progress Reports**: Track task completion and time spent
- **Team Performance**: Analyze team productivity metrics
- **Export Functionality**: Download reports in various formats
- **Status Tracking**: Monitor plan submission, report submission, clock-out status

### Teacher/School Features

- **Timetable Management**: Create and manage class schedules
- **Activity Tracking**: Log teaching activities and class sessions
- **Fulfillment Tracking**: Mark scheduled activities as completed
- **Class Management**: Track rooms, subjects, and grade levels

### UI/UX Enhancements

- **Toast Notifications**: Non-intrusive success/error messages with animations
- **Modal Confirmations**: User-friendly confirmation dialogs
- **Empty States**: Professional, appealing displays when data is empty
- **Dark Mode Support**: Full dark theme compatibility
- **Responsive Design**: Mobile-first approach with Tailwind CSS
- **Material Icons**: Modern icon set throughout the interface
- **Component Architecture**: Reusable header and sidebar components

---

## 🏗 System Architecture

```
clocking/
├── admin/                  # Business/Admin Dashboard
│   ├── dashboard.php       # Main admin overview
│   ├── users.php          # Staff directory management
│   ├── teams.php          # Team management
│   ├── team_details.php   # Individual team view
│   ├── projects.php       # Project listing
│   ├── project_details.php # Project overview
│   ├── project_phases.php # Phase management
│   ├── tasks.php          # Task management
│   ├── scanner.php        # Barcode scanning interface
│   ├── timetable.php      # Schedule management
│   ├── settings.php       # Business settings
│   ├── header.php         # Reusable header component
│   └── sidebar.php        # Reusable navigation sidebar
│
├── user/                  # Employee/User Dashboard
│   ├── dashboard.php      # User home page
│   ├── clock-others.php   # Clock others (authorized users)
│   ├── projects.php       # Assigned projects view
│   ├── tasks.php          # Task list
│   ├── history.php        # Attendance history
│   └── settings.php       # User account settings
│
├── api/                   # RESTful API Endpoints
│   ├── clock.php          # Clock in/out operations
│   ├── reports.php        # Report submissions
│   ├── users.php          # User data retrieval
│   └── check-status.php   # Status checking
│
├── lib/
│   └── constant.php       # Database config & constants
│
├── db_schemas/
│   └── Clocking.sql       # Database schema
│
├── index.php              # Login page
├── register.php           # User registration
└── logout.php             # Session termination
```

---

## 💻 Requirements

### Server Requirements

- **PHP**: 8.0 or higher
  - `mysqli` extension enabled
  - `password_hash()` support
- **MySQL**: 8.0 or higher
- **Web Server**: Apache/Nginx with mod_rewrite
- **Timezone**: Server configured for Africa/Lagos (configurable)

### Browser Requirements

- Modern browsers with JavaScript enabled
- Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

### Development Environment

- XAMPP/WAMP/MAMP (recommended for local development)
- Composer (optional, for future dependency management)

---

## 🚀 Installation

### 1. Clone or Download the Repository

```bash
git clone https://github.com/Tolushawlar/clocking.git
cd clocking
```

### 2. Configure Web Server

#### For XAMPP (Windows/Mac):

- Move the `clocking` folder to `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (Mac)
- Access via: `http://localhost/clocking`

#### For Ubuntu/Linux:

- Move to `/var/www/html/clocking`
- Set permissions:

```bash
sudo chown -R www-data:www-data /var/www/html/clocking
sudo chmod -R 755 /var/www/html/clocking
```

### 3. Database Setup

#### Create Database:

```sql
CREATE DATABASE Clocking CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

#### Import Schema:

```bash
# Via MySQL command line
mysql -u root -p Clocking < db_schemas/Clocking.sql

# Or via phpMyAdmin
# 1. Navigate to phpMyAdmin
# 2. Select 'Clocking' database
# 3. Click 'Import' tab
# 4. Choose 'Clocking.sql' file
# 5. Click 'Go'
```

### 4. Configure Database Connection

Edit `lib/constant.php`:

```php
<?php
// Database Configuration
define("DB_SERVER", "localhost");
define("DB_USER", "your_username");      // Change this
define("DB_PASS", "your_password");      // Change this
define("DB_NAME", "Clocking");

// Timezone (adjust as needed)
date_default_timezone_set('Africa/Lagos');
```

### 5. Set Permissions (Linux/Mac)

```bash
# Make storage directories writable
chmod -R 775 storage/
chmod -R 775 uploads/

# Secure config file
chmod 600 lib/constant.php
```

---

## 🗄 Database Setup

### Core Tables

#### `business`

- Stores organization/company information
- Manages clocking and reporting settings
- Controls time windows for operations

#### `users`

- Employee/staff records
- Authentication credentials (hashed passwords)
- Role assignments (admin, team_leader, team_member)
- Barcode associations
- Permission flags

#### `teams`

- Team organizational structure
- Team leader assignments
- Team descriptions and metadata

#### `team_members`

- Many-to-many relationship between teams and users
- Tracks who added each member
- Timestamps for audit trail

#### `projects`

- Project master records
- Client information
- Status tracking (planning, active, on_hold, completed, cancelled)
- Budget and timeline management
- Team associations

#### `project_phases`

- Project subdivision into phases
- Phase ordering and dependencies
- Hour estimation per phase
- Phase status tracking

#### `tasks`

- Granular task management
- Assignment to specific users
- Priority levels (low, medium, high, urgent)
- Due dates and estimated hours
- Phase associations

#### `reports`

- Daily attendance records
- Clock in/out timestamps
- Daily plans and reports
- Status tracking workflow

#### `timetables` & `timetable_slots`

- Class/activity scheduling
- Recurring schedule management
- Fulfillment tracking

### Sample Data

The database includes sample data for testing:

- 3 businesses
- 5 users with various roles
- 2 teams with members
- 8 projects across different statuses
- 7 project phases
- 6 tasks

---

## ⚙️ Configuration

### Time Windows

Configure when users can clock in/out in the `business` table:

```sql
UPDATE business SET
  clock_in_start = '08:00:00',
  clock_in_end = '10:00:00',
  clock_out_start = '17:00:00',
  clock_out_end = '19:00:00'
WHERE id = 1;
```

### Feature Toggles

Enable/disable clocking and reporting:

```sql
UPDATE business SET
  clocking_enabled = 1,
  reporting_enabled = 1
WHERE id = 1;
```

### Default Credentials

After importing the database, use these credentials to log in:

**Business Admin:**

- Email: `admin@business.com`
- Password: `password`

**User/Staff:**

- Email: `sola@gmail.com`
- Password: `password`
- Barcode: `12345`

⚠️ **Change default passwords immediately in production!**

---

## 👥 User Roles

### Business Admin

**Access Level:** Full system control

**Capabilities:**

- Manage all users and teams
- Create and assign projects
- Configure business settings
- View all reports and analytics
- Manage time windows
- Enable/disable features

**Dashboard:** `admin/dashboard.php`

---

### Team Leader

**Access Level:** Team-specific management

**Capabilities:**

- View team members and projects
- Assign tasks to team members
- Track team progress
- Submit reports for team
- Cannot modify business settings
- Cannot create/delete teams

**Dashboard:** `user/dashboard.php` (with enhanced permissions)

---

### Team Member

**Access Level:** Individual contributor

**Capabilities:**

- Clock in/out via barcode
- Submit daily plans and reports
- View assigned tasks and projects
- Update task progress
- View personal attendance history
- Cannot manage other users

**Dashboard:** `user/dashboard.php`

---

### Special Permissions

#### `can_clock_others`

- Allows user to clock in/out other employees
- Useful for receptionists or HR personnel
- Access to `user/clock-others.php`

#### `is_active`

- Controls whether user can log in
- Inactive users retain historical data
- Can be toggled in admin interface

---

## 🔧 Core Modules

### 1. Authentication System

**Files:** `index.php`, `logout.php`, `register.php`

**Features:**

- Dual login (Business Admin / User)
- Password hashing with `password_hash()`
- Session management
- Role-based redirects
- Automatic logout on inactivity

**Login Flow:**

```
User Input → Validate Credentials → Check User Type
                                    ├─ Business → admin/dashboard.php
                                    └─ User → user/dashboard.php
```

---

### 2. Clock In/Out Module

**Files:** `admin/scanner.php`, `user/dashboard.php`, `api/clock.php`

**Features:**

- Barcode scanning
- Time window validation
- Duplicate prevention
- Real-time status updates
- Toast notifications

**Workflow:**

```
Scan Barcode → Verify User → Check Time Window → Validate Status → Insert Record → Show Confirmation
```

---

### 3. Project Management

**Files:** `admin/projects.php`, `admin/project_details.php`, `admin/project_phases.php`

**Features:**

- Multi-phase project structure
- Team assignment
- Task breakdown
- Progress tracking
- Budget vs actual hours
- Client management
- Empty state handling

**Project Lifecycle:**

```
Planning → Active → [On Hold] → Completed/Cancelled
```

---

### 4. Team Management

**Files:** `admin/teams.php`, `admin/team_details.php`

**Features:**

- Team creation with leaders
- Multi-member selection (checkbox interface)
- Member role tracking
- Project assignments
- Team statistics
- Empty state onboarding

**Team Structure:**

```
Team
├─ Team Leader (1)
├─ Team Members (many)
└─ Projects (many)
```

---

### 5. Task Management

**Files:** `admin/add_task.php`, `admin/task_details.php`, `user/tasks.php`

**Features:**

- Phase-based organization
- User assignment
- Priority levels
- Due date tracking
- Deliverables
- Progress reporting

**Task States:**

```
Pending → In Progress → Completed/Cancelled
```

---

### 6. Reporting System

**Files:** `user/dashboard.php`, `admin/task_reporting.php`

**Features:**

- Daily plan submission
- Activity reporting
- Report editing
- Status workflow
- Export capabilities

**Report Workflow:**

```
Clocked In → Plan Submitted → Report Submitted → Clocked Out
```

---

### 7. Staff Directory

**Files:** `admin/users.php`, `admin/user.php`

**Features:**

- Comprehensive user listing
- Search and filtering
- Bulk selection
- Role management
- Status toggle (active/inactive)
- Barcode assignment
- Delete with cascade (removes reports)
- Toast notifications for actions
- Empty state for new businesses

---

### 8. Timetable Module

**Files:** `admin/timetable.php`, `admin/fulfillment.php`, `teacher_timetable.php`

**Features:**

- Recurring schedules
- Class/activity management
- Fulfillment tracking
- Teacher assignments

---

## 🔌 API Documentation

### Base URL

```
http://localhost/clocking/api/
```

### Endpoints

#### Clock Operations

**POST** `/api/clock.php`

Clock in or out a user.

**Parameters:**

```json
{
  "action": "clock_in",
  "barcode": "12345",
  "user_id": 3
}
```

**Response:**

```json
{
  "success": true,
  "message": "Successfully clocked in",
  "timestamp": "2026-01-21 08:30:00"
}
```

---

#### Report Submission

**POST** `/api/reports.php`

Submit daily plan or report.

**Parameters:**

```json
{
  "action": "submit_plan",
  "user_id": 3,
  "plan": "Complete project documentation"
}
```

---

#### User Status

**GET** `/api/check-status.php?user_id=3`

Check current clock status.

**Response:**

```json
{
  "clocked_in": true,
  "plan_submitted": true,
  "report_submitted": false,
  "clock_in_time": "08:30:00"
}
```

---

## 🎨 UI Components

### Toast Notifications

**Location:** Used throughout admin and user interfaces

**Features:**

- Slide-in animation
- Auto-dismiss after 5 seconds
- Manual close option
- Success/error styling
- Icon indicators

**Usage:**

```javascript
showToast("User created successfully", "success");
showToast("Error: Invalid barcode", "error");
```

---

### Empty States

Professional, engaging displays when no data exists:

- **Teams:** "No Teams Yet" with create team CTA
- **Projects:** "No Projects Yet" with create project CTA
- **Users:** "No Staff Members Yet" with add staff CTA

**Design Elements:**

- Gradient icon circles
- Clear messaging
- Descriptive text
- Prominent call-to-action buttons
- Consistent with overall design system

---

### Modal Dialogs

**Delete Confirmation:**

- User name display
- Warning icon
- Clear action buttons
- Backdrop click to close

**Create/Edit Forms:**

- Inline validation
- Field focusing
- Cancel/submit actions

---

### Component Architecture

**Reusable Components:**

- `admin/header.php` - Top navigation with user info, time, dark mode toggle
- `admin/sidebar.php` - Navigation menu with active page highlighting
- `user/sidebar.php` - User-specific navigation

---

## 🔒 Security

### Implemented Security Measures

#### 1. Password Security

- `password_hash()` with bcrypt algorithm
- `password_verify()` for authentication
- No plaintext password storage

#### 2. SQL Injection Prevention

- Prepared statements throughout
- Parameter binding with `bind_param()`
- Input sanitization with `trim()`

#### 3. Session Management

- Session-based authentication
- Session regeneration on login
- Automatic session timeout
- Role-based access control

#### 4. XSS Prevention

- `htmlspecialchars()` on all user input display
- `ENT_QUOTES` flag for comprehensive escaping

#### 5. CSRF Protection

- Form token validation (recommended to implement)
- Same-origin policy

#### 6. Database Security

- Foreign key constraints
- Cascade deletes for data integrity
- Unique constraints on critical fields
- Index optimization

### Security Recommendations

#### Production Checklist

- [ ] Change default database credentials
- [ ] Update default user passwords
- [ ] Enable HTTPS (SSL/TLS)
- [ ] Implement rate limiting
- [ ] Add CSRF tokens to forms
- [ ] Enable error logging (not display)
- [ ] Set secure session cookies
- [ ] Implement IP whitelisting for admin
- [ ] Regular database backups
- [ ] Keep PHP and MySQL updated

#### `.htaccess` Configuration (Apache)

```apache
# Prevent directory listing
Options -Indexes

# Protect config files
<Files "constant.php">
    Order Allow,Deny
    Deny from all
</Files>

# Force HTTPS (production)
# RewriteEngine On
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 📸 Screenshots

### Admin Dashboard

- Real-time attendance overview
- Today's clock-ins
- Quick stats (users, teams, projects)
- Recent activity feed

### User Dashboard

- Personal clock status
- Plan/report submission
- Assigned tasks
- Upcoming deadlines

### Project Management

- Project grid with status badges
- Progress visualization
- Team assignments
- Phase breakdown

### Team Management

- Team cards with member counts
- Leader identification
- Project associations
- Add member modal with checkboxes

---

## 🧪 Testing

### Test Accounts

**Business 1 (Sample Business):**

- Admin: `admin@business.com` / `password`
- User: `john@example.com` / `password` (Barcode: BC001)

**Business 2 (Livepetal):**

- Admin: `livepetal@gmail.com` / `password`
- User: `sola@gmail.com` / `password` (Barcode: 12345)

### Test Scenarios

#### Clock In/Out

1. Login as user
2. Scan barcode or enter manually
3. Verify time window restrictions
4. Check duplicate prevention
5. Confirm clock-out only after clock-in

#### Project Workflow

1. Create project as admin
2. Add phases
3. Create tasks
4. Assign to team members
5. Track progress updates
6. Mark tasks complete
7. Close project

#### Team Management

1. Create team with leader
2. Add multiple members (checkbox selection)
3. Assign project to team
4. View team dashboard
5. Remove members
6. Update team leader

---

## 🛠 Troubleshooting

### Common Issues

#### "Database connection failed"

**Solution:**

- Verify MySQL is running
- Check credentials in `lib/constant.php`
- Ensure database exists
- Test connection: `mysql -u username -p`

#### "Session start failed"

**Solution:**

- Check session directory permissions
- Verify `session.save_path` in `php.ini`
- Ensure disk space available

#### "Barcode not recognized"

**Solution:**

- Verify barcode exists in `users` table
- Check for trailing spaces
- Ensure barcode is unique

#### "Clock in blocked"

**Solution:**

- Check time window settings in `business` table
- Verify `clocking_enabled = 1`
- Ensure user has `can_clock = 1`
- Check for existing clock-in today

#### "Toast notifications not showing"

**Solution:**

- Check browser console for JavaScript errors
- Verify `toast-container` div exists
- Clear browser cache

---

## 🚧 Future Enhancements

### Planned Features

- [ ] Email notifications for reports
- [ ] Push notifications for mobile
- [ ] Advanced analytics dashboard
- [ ] Export to PDF/Excel
- [ ] API rate limiting
- [ ] Two-factor authentication
- [ ] Mobile app (React Native)
- [ ] Geolocation-based clock-in
- [ ] Biometric integration
- [ ] Slack/Teams integration
- [ ] Calendar synchronization
- [ ] Document attachment to tasks
- [ ] Task commenting system
- [ ] Gantt chart view
- [ ] Resource allocation view

### Technical Improvements

- [ ] Migrate to PDO from mysqli
- [ ] Implement Composer for dependencies
- [ ] Add unit tests (PHPUnit)
- [ ] Set up CI/CD pipeline
- [ ] Docker containerization
- [ ] Redis for session storage
- [ ] WebSocket for real-time updates
- [ ] GraphQL API option
- [ ] Multi-language support (i18n)

---

## 🤝 Contributing

**Note:** This is proprietary commercial software. Contributions are accepted only from authorized developers under a signed Contributor License Agreement (CLA).

### For Authorized Contributors

1. **Clone the Repository**

   ```bash
   git clone https://github.com/Tolushawlar/clocking.git
   cd clocking
   git checkout -b feature/your-feature-name
   ```

2. **Make Changes**
   - Follow existing code style
   - Comment complex logic
   - Test thoroughly
   - Sign commits with your verified identity

3. **Commit Changes**

   ```bash
   git add .
   git commit -s -m "Add feature: your feature description"
   ```

4. **Push and Create PR**
   ```bash
   git push origin feature/your-feature-name
   ```
   Then create a Pull Request for internal review

### Becoming a Contributor

Interested in contributing? Contact us at: contributors@timetrackpro.com

### Coding Standards

- Use PSR-12 coding style
- Comment all functions
- Use meaningful variable names
- Sanitize all inputs
- Use prepared statements
- Handle errors gracefully

### Reporting Bugs

Please include:

- PHP and MySQL versions
- Browser and OS
- Steps to reproduce
- Expected vs actual behavior
- Error messages/screenshots

---

## 📄 License

This is proprietary commercial software. All rights reserved.

```
PROPRIETARY SOFTWARE LICENSE

Copyright (c) 2026 LivePetal. All Rights Reserved.

This software and associated documentation files (the "Software") are the
proprietary and confidential property of LivePetal.

LICENSE GRANT:
Subject to the terms of your license agreement and payment of applicable fees,
you are granted a non-exclusive, non-transferable license to use this Software
solely for your internal business operations.

RESTRICTIONS:
You may NOT:
- Copy, modify, or create derivative works of the Software
- Distribute, sublicense, rent, lease, or lend the Software
- Reverse engineer, decompile, or disassemble the Software
- Remove or alter any proprietary notices or labels
- Use the Software for any unlawful purpose
- Transfer your license without prior written consent

OWNERSHIP:
LivePetal retains all right, title, and interest in and to the Software,
including all intellectual property rights.

WARRANTY DISCLAIMER:
THE SOFTWARE IS PROVIDED "AS IS" WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE, OR NON-INFRINGEMENT.

LIMITATION OF LIABILITY:
IN NO EVENT SHALL TOLUSHAWLAR BE LIABLE FOR ANY SPECIAL, INCIDENTAL, INDIRECT,
OR CONSEQUENTIAL DAMAGES WHATSOEVER ARISING OUT OF THE USE OR INABILITY TO USE
THE SOFTWARE.

TERMINATION:
This license is effective until terminated. Your rights under this license will
terminate automatically without notice if you fail to comply with any term.

For licensing inquiries, please contact: license@timetrackpro.com
```

---

## 📞 Support

### For Licensed Customers

- **Documentation Portal:** [https://docs.timetrackpro.com](https://docs.timetrackpro.com)
- **Support Tickets:** [https://support.timetrackpro.com](https://support.timetrackpro.com)
- **Email Support:** support@timetrackpro.com
- **Phone Support:** +1 (555) 123-4567 (Business hours: Mon-Fri, 9AM-5PM WAT)

### Enterprise Support

- **Priority Support:** Available for Enterprise license holders
- **Dedicated Account Manager:** For Enterprise+ plans
- **Custom Development:** Contact sales@timetrackpro.com

### Sales Inquiries

- **Email:** sales@timetrackpro.com
- **Website:** [https://www.timetrackpro.com](https://www.timetrackpro.com)
- **Request Demo:** [Schedule a demo](https://www.timetrackpro.com/demo)

---

## 🙏 Acknowledgments

- **TailwindCSS** - Utility-first CSS framework
- **Material Symbols** - Icon set by Google
- **Inter Font** - Typography by Rasmus Andersson
- **PHP Community** - For excellent documentation
- **MySQL** - Reliable database management

---

## 📊 Project Stats

- **Lines of Code:** ~15,000+
- **Database Tables:** 16
- **Admin Pages:** 25+
- **User Pages:** 10+
- **API Endpoints:** 4
- **Supported Languages:** English
- **Active Development:** Yes

---

## 🗺 Roadmap

### Q1 2026

- [ ] Mobile responsive improvements
- [ ] PDF report generation
- [ ] Email notification system

### Q2 2026

- [ ] Mobile app (React Native)
- [ ] Advanced analytics
- [ ] API v2 with authentication

### Q3 2026

- [ ] Multi-tenant support
- [ ] Custom branding
- [ ] Integration marketplace

### Q4 2026

- [ ] AI-powered insights
- [ ] Predictive analytics
- [ ] Automated scheduling

---

**Built with ❤️ for efficient workforce management**

---

_Last Updated: January 21, 2026_
_Version: 1.0.0_
_Repository: [https://github.com/Tolushawlar/clocking](https://github.com/Tolushawlar/clocking)_
