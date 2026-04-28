# Yard Fund | Student Scholarship Portal #
This project is a web-based database application designed to streamline the scholarship application and donor transaction process. It facilitates a three-way ecosystem between Students seeking financial assistance, Donors looking to contribute, and Admins who verify and manage requests.
## Project Overview 
The system allows students to register and submit academic claims for tuition assistance. These claims enter a review queue for administrators. Once approved, students are featured on a public browsing page where donors can contribute funds directly to their verified goals.
## Core Features
- **Role-Based Authentication:** Secure login for Admins, Donors, and Students.
- **Claim Management:** Admin dashboard to approve or deny pending student funding requests.
- **Real-Time Fundraising:** Dynamic progress bars showing the percentage of tuition goals met.
- **Transaction Ledger:** Detailed logging of every donation to ensure transparency.
- **Profile Updates:** Approved students can request updates to their academic standing (GPA, Major, Urgency) for further review.
## File Directory
| File | Description |
| :--- | :--- |
| `db_connection.php` | The core configuration file for establishing a connection to the MySQL database. |
| `index.php` | The main landing and login page; redirects users to their specific dashboard based on their role. |
| `register.php` | Handles account creation for both Donors and Students, including academic data collection for applicants. |
| `admin_dashboard.php` | Management interface for administrators to review, approve, or deny student claims and profile updates. |
| `student_dashboard.php` | Personal portal for students to track their fundraising progress, view donor history, and request info updates. |
| `browse_students.php` | A directory for donors to view verified student profiles, academic stats, and urgency levels. |
| `donate.php` | The transaction processing page where donors confirm their contribution amounts. |

## Technical Stack
- **Frontend**: HTML5, CSS3, and Bootstrap 5 for a responsive, modern UI.
- **Backend**: PHP for server-side logic and session handling.
- **Database**: MySQL (via MariaDB) using the mysqli extension for data persistence.

## Database Schema (SQL)
```sql
CREATE DATABASE IF NOT EXISTS myproject;
USE myproject;

-- 1. Admins Table
CREATE TABLE admins (
    a_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    a_name VARCHAR(100) NOT NULL,
    a_email VARCHAR(100) NOT NULL UNIQUE
);

-- 2. Donors Table
CREATE TABLE donors (
    d_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    d_name VARCHAR(100) NOT NULL,
    d_email VARCHAR(100) NOT NULL UNIQUE,
    d_total_donations DECIMAL(10,2) DEFAULT 0.00,
    d_donation_count INT(11) DEFAULT 0
);

-- 3. Approved Claims (Students)
CREATE TABLE approved_claims (
    s_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    s_name VARCHAR(100) NOT NULL,
    s_email VARCHAR(100) NOT NULL UNIQUE,
    s_major VARCHAR(100),
    s_classification VARCHAR(50),
    s_gpa DECIMAL(3,2),
    s_tuition DECIMAL(10,2),
    s_amount_received DECIMAL(10,2) DEFAULT 0.00,
    s_urgency VARCHAR(50)
);

-- 4. Users Table (Master Auth)
CREATE TABLE users (
    u_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    u_email VARCHAR(100) NOT NULL UNIQUE,
    u_password VARCHAR(255) NOT NULL,
    u_role ENUM('admin', 'donor', 'student') NOT NULL,
    s_id INT(11) DEFAULT NULL,
    d_id INT(11) DEFAULT NULL,
    a_id INT(11) DEFAULT NULL,
    FOREIGN KEY (s_id) REFERENCES approved_claims(s_id) ON DELETE SET NULL,
    FOREIGN KEY (d_id) REFERENCES donors(d_id) ON DELETE SET NULL,
    FOREIGN KEY (a_id) REFERENCES admins(a_id) ON DELETE SET NULL
);

-- 5. Pending Claims
CREATE TABLE pending_claims (
    c_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    s_id INT(11) DEFAULT 0,
    c_name VARCHAR(100),
    c_email VARCHAR(100),
    c_major VARCHAR(100),
    c_classification VARCHAR(50),
    c_gpa DECIMAL(3,2),
    c_tuition DECIMAL(10,2),
    c_urgency VARCHAR(50),
    c_status ENUM('Pending', 'Approved', 'Denied') DEFAULT 'Pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. Denied Claims
CREATE TABLE denied_claims (
    de_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    c_id INT(11),
    de_name VARCHAR(100),
    de_email VARCHAR(100),
    de_major VARCHAR(100),
    de_classification VARCHAR(50),
    de_gpa DECIMAL(3,2),
    de_tuition DECIMAL(10,2),
    de_urgency VARCHAR(50),
    denied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    s_id INT(11) DEFAULT NULL
);

-- 7. Transactions Table
CREATE TABLE transactions (
    t_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    d_id INT(11) NOT NULL,
    s_id INT(11) NOT NULL,
    t_amount DECIMAL(10,2) NOT NULL,
    t_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (d_id) REFERENCES donors(d_id),
    FOREIGN KEY (s_id) REFERENCES approved_claims(s_id)
);
