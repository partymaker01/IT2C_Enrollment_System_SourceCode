A web-based student enrollment system built using PHP, MySQL, and Bootstrap. This system allows students to register, log in, and submit enrollment forms while admins can manage, approve, reject, and view student applications.

## 🚀 Features

### Student Functions
- 🔐 Register & Login
- 📄 Submit Enrollment Form
- 👁️ View Submitted Enrollment
- 📤 Upload Requirements
- 📥 View Approval Status

### Admin Functions
- 👤 Manage Student Accounts
- 📋 View Submitted Enrollments
- ✅ Approve, ❌ Reject, or 🕘 Mark Enrollments as Missing
- 📊 Dashboard Overview

## 🧱 Tech Stack
- **Frontend**: HTML, CSS, Bootstrap 5.3.6
- **Backend**: PHP
- **Database**: MySQL
- **Email Services**: PHPMailer (for password resets and email verification)

## 🗂️ Project Structure

📁 IT2C_Enrollment_System
├── config/ # Database and config files
├── css/ # Custom styles
├── js/ # JavaScript functions
├── includes/ # Header, footer, reusable components
├── student/ # Student dashboard and forms
├── admin/ # Admin dashboard and management pages
├── login.php # Login page
├── register.php # Registration page
├── verify_email.php # Email verification handler
├── reset_password/ # Password reset logic
└── database.sql # Database schema

## ⚙️ Installation Steps

1. **Clone the repository / Extract the ZIP**
   ```bash
   git clone https://github.com/yourusername/IT2C_Enrollment_System.git

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'enrollment_db';

Visit http://localhost:8000/ in your browser

Admin: Account

Email: admin@example.com

Password: password

Student:

You need to register your account

