# 📔 Life Canvas - Personal Diary Application

A sophisticated diary application with advanced database features, glass morphism UI, and social networking capabilities.

---

## 🚀 Quick Start (3 Steps)

### 1️⃣ Import Database
- Open **phpMyAdmin** → **SQL** tab
- Copy & paste entire `schema.sql` file
- Click **Go** ✅
- Wait 10-20 seconds for completion

### 2️⃣ Login
- URL: `http://localhost/CSE311-Diary/Personal-Diary/`
- **Username:** `admin` | **Password:** `password123`

### 3️⃣ Explore Features
📊 Dashboard | 🌍 Feed | 🏷️ Categories | 📈 Analytics | 👥 Admin Panel

---

## 📁 Project Files
- **`schema.sql`** - Complete database (import this ONE file only)
- **`README.md`** - This comprehensive guide
- **`config/`** - Configuration files
- **`lib/`** - Backend logic (auth, utils, db)
- **`partials/`** - Reusable UI components
- **`assets/`** - CSS, JS, and images

---

## 👥 User Credentials

### All users share the same password: `password123`

**Admin Account:**
- Username: `admin`
- Email: admin@diary.com
- Role: Admin (Full system access, user management)

**Premium Accounts:**
- `john_doe` - Enhanced features, social sharing
- `jane_smith` - Enhanced features, social sharing

**Regular User Accounts:**
- `alice_wonder` - Basic diary features
- `bob_builder` - Basic diary features
- `charlie_brown` - Basic diary features
- `diana_prince` - Basic diary features
- `emma_watson` - Basic diary features

**Security Question (all users):** "What is your favorite color?"  
**Security Answer (all users):** "blue"

---

## ✨ Features

### Core Features
- ✅ Create, read, update, delete diary entries
- ✅ Rich text editor with mood tracking
- ✅ Category and tag organization
- ✅ Location and weather tracking
- ✅ Media uploads (images, audio)
- ✅ Music link integration (YouTube/Spotify)
- ✅ Privacy controls (private/public)

### Social Features
- 🌍 Public feed with community posts
- ❤️ Like/reaction system
- 👥 User profiles
- 🔗 Entry sharing

### Advanced Features
- 📊 Analytics dashboard with statistics
- 🔍 Advanced search and filters
- 📈 Mood trends visualization
- 🏆 Writing streaks tracking
- 🎨 Glass morphism UI with custom theme
- 🌓 Dark/Light theme toggle
- 📱 Responsive design

### Admin Features
- 👥 User management
- 🏷️ Category management
- 📊 System statistics
- 🔐 Role-based access control

---

## 🗄️ Database Architecture

### Tables (20 Total)
- `users` - User accounts with roles and preferences
- `roles` - User role definitions (Admin, Premium, User)
- `permissions` - Granular permission system
- `role_permissions` - Role-permission mappings
- `entries` - Diary entries with metadata
- `categories` - Entry categorization
- `tags` - Flexible tagging system
- `entry_tags` - Entry-tag junction table
- `media` - File uploads (images, audio)
- `shared_entries` - Entry sharing between users
- `comments` - Nested comments on entries
- `reactions` - Like/Love reactions
- `followers` - User follow system
- `entry_stats` - Aggregated entry statistics
- `user_stats` - User writing statistics
- `mood_history` - Time-series mood tracking
- `entry_versions` - Version control
- `audit_log` - Complete audit trail
- `user_sessions` - Session tracking

### Views (5 Total)
- `v_user_dashboard` - User dashboard data
- `v_entry_details` - Complete entry information
- `v_mood_trends` - Mood analysis over time
- `v_popular_tags` - Tag usage statistics
- `v_shared_entries` - Shared entry details

### Stored Procedures (6 Total)
- `sp_update_user_stats` - Recalculate user statistics
- `sp_calculate_streak` - Compute writing streaks
- `sp_get_mood_distribution` - Mood analytics
- `sp_get_writing_calendar` - Calendar heatmap data
- `sp_soft_delete_entry` - Soft delete entries
- `sp_share_entry` - Share entries with users

### Triggers (10 Total)
- Word count auto-calculation
- Version creation on updates
- Mood history tracking
- Stats updates on entry changes
- Tag usage counting
- Audit logging
- Session tracking

### Functions (3 Total)
- `fn_has_permission` - Check user permissions
- `fn_get_current_streak` - Get writing streak
- `fn_readability_score` - Calculate readability

### Indexes (30+)
- Optimized for complex queries
- Foreign key indexes
- Full-text search indexes
- Composite indexes for filters

---

## 🎨 UI/UX Features

### Design System
- **Font:** Macondo Swash Caps (display), Poppins (body)
- **Theme:** Glass morphism with frosted effects
- **Background:** Custom fern image with dark overlay
- **Colors:** Dynamic primary colors with opacity layers

### Visual Effects
- Backdrop blur effects (20-25px)
- Liquid animations and transitions
- Hover effects with glow
- Smooth scrolling
- Responsive grid layouts

### Components
- Glass navigation with dropdown menu
- Frosted input fields
- Liquid buttons with ripple effects
- Toast notifications
- Modal dialogs
- Card layouts with hover transforms

---

## 📊 Sample Data Included

- **8 Users** (1 admin, 2 premium, 5 regular)
- **18 Diary Entries** across all users
- **6 Categories** (Personal, Work, Travel, Goals, Health, Gratitude)
- **10 Tags** (travel, goals, fitness, gratitude, work, family, etc.)
- **5 Sample Images** (Lorem Picsum URLs)
- **Entry Statistics** (views, shares, reactions)
- **User Statistics** (streaks, word counts)

---

## 🎓 DBMS Concepts Demonstrated

- ✅ Database Normalization (3NF)
- ✅ Foreign Key Constraints
- ✅ Complex JOINs (INNER, LEFT, RIGHT)
- ✅ Subqueries and CTEs
- ✅ Triggers (BEFORE/AFTER)
- ✅ Stored Procedures
- ✅ Views for complex queries
- ✅ Indexes for optimization
- ✅ Transactions (ACID properties)
- ✅ Full-text Search
- ✅ JSON data types
- ✅ Aggregate functions
- ✅ Window functions
- ✅ Audit logging
- ✅ Soft deletes
- ✅ Version control

---

## 🛠️ Technology Stack

### Backend
- **PHP 8.1+** - Server-side logic
- **MySQL 8.0+** - Database
- **PDO** - Database abstraction
- **Sessions** - Authentication

### Frontend
- **HTML5** - Structure
- **Tailwind CSS** - Utility-first CSS
- **Custom CSS** - Glass morphism theme
- **JavaScript** - Interactivity
- **FontAwesome** - Icons
- **Google Fonts** - Typography

### Development Environment
- **XAMPP** - Local server (Apache + MySQL + PHP)
- **phpMyAdmin** - Database management
- **VS Code** - Code editor

---

## 📋 Installation Guide

### Prerequisites
- Windows OS
- XAMPP installed (Apache + MySQL)
- Web browser (Chrome, Firefox, Edge)

### Step-by-Step Setup

#### 1. Start XAMPP
```
1. Open XAMPP Control Panel
2. Click "Start" next to Apache
3. Click "Start" next to MySQL
4. Wait for both to turn green
```

#### 2. Import Database
```
1. Click "Admin" next to MySQL (opens phpMyAdmin)
2. Click "SQL" tab at the top
3. Open schema.sql in text editor
4. Copy ALL contents (Ctrl+A, Ctrl+C)
5. Paste into phpMyAdmin SQL box
6. Click "Go" button
7. Wait 10-20 seconds
8. Verify "diary_app" database appears in left sidebar
```

#### 3. Access Application
```
1. Open browser
2. Go to: http://localhost/CSE311-Diary/Personal-Diary/
3. Login with admin credentials
4. Explore features!
```

---

## 🧪 Testing & Demo

### For Professor Demonstration

**Best Demo Account:** `admin` / `password123`
- Shows all features
- Has admin panel access
- Can manage users and categories
- View analytics and statistics

**Alternative Demo:** `john_doe` / `password123`
- Shows premium user experience
- Has more sample entries
- Demonstrates social feed features

### Demo Flow
1. **Login as admin** - Show authentication
2. **Dashboard** - Display entry management with filters
3. **Create Entry** - Demonstrate form with categories, tags, media
4. **Edit Entry** - Show all fields are editable
5. **Feed** - Display public posts and like system
6. **Analytics** - Show statistics and trends
7. **Categories** - Manage categories and tags
8. **Admin Panel** - User management and system stats
9. **View Entry** - Show version history and details

### Test Different Roles
- Logout and login as different users
- Each user has 2-3 sample entries
- See how roles affect available features
- Test privacy controls (private vs public)

---

## 🐛 Troubleshooting

### Database Import Issues

**Problem:** "Table already exists" error  
**Solution:** Drop database first:
```sql
DROP DATABASE IF EXISTS diary_app;
```
Then import schema.sql again.

**Problem:** Import timeout  
**Solution:** Increase PHP execution time in php.ini:
```ini
max_execution_time = 300
```

### Login Issues

**Problem:** Can't login  
**Solution:** 
- Verify password is exactly: `password123` (case-sensitive)
- Clear browser cookies
- Check database was imported correctly

**Problem:** Admin panel not showing  
**Solution:** Login as `admin` user (user_id = 1)

### Display Issues

**Problem:** No entries showing  
**Solution:** 
- Check if you're logged in as the right user
- Each user has their own entries
- Try clearing filters

**Problem:** Styles not loading  
**Solution:**
- Hard refresh browser (Ctrl+F5)
- Check assets/css/theme.css exists
- Verify Apache is serving CSS files

### Database Connection

**Problem:** "Connection refused"  
**Solution:**
- Check MySQL is running in XAMPP
- Verify credentials in config/db.php
- Check port 3306 is not blocked

---

## ⚠️ Security Notes

**IMPORTANT - FOR DEMO ONLY:**
- ⚠️ Passwords are stored in PLAIN TEXT for easy testing
- ⚠️ Security answers are also in plain text
- ⚠️ This is NOT production-ready
- ⚠️ For production, implement password hashing (bcrypt)
- ⚠️ Add CSRF protection
- ⚠️ Implement rate limiting
- ⚠️ Use prepared statements (already done)
- ⚠️ Sanitize all user inputs (already done)

---

## 📈 Recent Updates

### Latest Features
✅ Glass morphism UI with liquid effects  
✅ Fern background with dark overlay  
✅ Apple-style frosted components  
✅ Social feed with public posts  
✅ Like/reaction system  
✅ Role-based navigation  
✅ Editable music links  
✅ Enhanced edit form (all fields editable)  
✅ Dropdown menu positioning fixed  
✅ No underlines on link hover  
✅ Macondo Swash Caps font for logo  

### Bug Fixes
✅ Profile dropdown now appears correctly  
✅ Music link field fully editable  
✅ Category, location, tags editable in edit form  
✅ Navbar overflow issues resolved  
✅ Theme consistency across all pages  

---

## 📞 Support & Documentation

### File Structure
```
Personal-Diary/
├── schema.sql              # Database import file
├── README.md              # This file
├── index.php              # Login page
├── dashboard.php          # User dashboard
├── create.php             # Create entry
├── edit.php               # Edit entry
├── view.php               # View entry details
├── feed.php               # Public feed
├── analytics.php          # Statistics
├── categories.php         # Category management
├── admin.php              # Admin panel
├── profile.php            # User profile
├── config/
│   ├── config.php         # App configuration
│   └── db.php             # Database connection
├── lib/
│   ├── auth.php           # Authentication
│   ├── utils.php          # Utility functions
│   └── db.php             # Database helpers
├── partials/
│   ├── head.php           # HTML head
│   ├── nav.php            # Navigation
│   └── footer.php         # Footer
├── assets/
│   ├── css/
│   │   └── theme.css      # Custom styles
│   ├── js/
│   │   └── app.js         # JavaScript
│   └── BG.jpg             # Background image
└── uploads/               # User uploads
    └── {user_id}/         # User folders
```

### Key Functions

**Authentication (lib/auth.php):**
- `is_logged_in()` - Check login status
- `require_login()` - Enforce authentication
- `current_user_id()` - Get current user ID
- `is_admin()` - Check admin role
- `login_user($username, $password)` - Login
- `register_user($data)` - Register new user
- `logout_user()` - Logout

**Utilities (lib/utils.php):**
- `e($string)` - Escape HTML
- `flash($message, $type)` - Flash messages
- `redirect($url)` - Redirect with exit
- `get_categories($pdo)` - Get all categories
- `get_privacy_levels()` - Get privacy options
- `human_datetime($timestamp)` - Format datetime
- `excerpt($text, $length)` - Truncate text
- `mood_emoji($mood)` - Get mood emoji

---

## 🎯 Learning Outcomes

After exploring this project, you will understand:

1. **Database Design**
   - How to design normalized databases
   - Relationship modeling (1:1, 1:N, N:M)
   - Junction tables for many-to-many

2. **SQL Queries**
   - Complex SELECT with JOINs
   - Subqueries and CTEs
   - Aggregate functions
   - Window functions

3. **Database Objects**
   - Creating and using views
   - Writing stored procedures
   - Implementing triggers
   - Using functions

4. **Backend Development**
   - PHP session management
   - PDO for database operations
   - Input validation and sanitization
   - Error handling

5. **Frontend Development**
   - Responsive design with Tailwind
   - Custom CSS animations
   - JavaScript interactivity
   - AJAX requests

6. **Security**
   - SQL injection prevention
   - XSS protection
   - Authentication & authorization
   - Role-based access control

---

## 📄 License

This project is created for educational purposes as part of a DBMS course project.

---

## 👨‍💻 Credits

**Project:** Life Canvas - Personal Diary  
**Course:** CSE311 - Database Management Systems  
**Purpose:** Academic demonstration of advanced DBMS concepts  
**Technologies:** PHP, MySQL, Tailwind CSS, JavaScript  
**UI Design:** Glass morphism with custom animations  

---

## 🎉 Conclusion

This Personal Diary application demonstrates a comprehensive understanding of:
- Database design and normalization
- Complex SQL queries and optimization
- Stored procedures, triggers, and views
- Full-stack web development
- Modern UI/UX design principles
- Security best practices

Perfect for DBMS course demonstration and learning advanced database concepts!

**Ready to explore? Import `schema.sql` and start your journey! 🚀**

