CREATE DATABASE employee_portal;
USE employee_portal;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    company VARCHAR(100) NOT NULL,
    location VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    salary DECIMAL(10,2) DEFAULT NULL,
    type ENUM('Full-time','Part-time','Contract','Internship') NOT NULL,
    posted_date DATE NOT NULL
);

CREATE TABLE visitor_diaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE employee_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    employee_id VARCHAR(50) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    dob DATE NOT NULL,
    doj DATE NOT NULL,
    completion_date DATE NOT NULL,
    father_name VARCHAR(100) NOT NULL,
    mother_name VARCHAR(100) NOT NULL,
    parents_phone VARCHAR(20) NOT NULL,
    college_name VARCHAR(255) NOT NULL,
    hod_name VARCHAR(100) NOT NULL,
    hod_phone VARCHAR(20) NOT NULL,
    guide_name VARCHAR(100) NOT NULL,
    guide_phone VARCHAR(20) NOT NULL,
    placement_officer VARCHAR(100) NOT NULL,
    placement_officer_phone VARCHAR(20) NOT NULL,
    referred_from VARCHAR(255) NOT NULL,
    reference_number VARCHAR(100) NOT NULL,
    resume_path VARCHAR(255) NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    certificates_path VARCHAR(255) NOT NULL,
    ppt_path VARCHAR(255) NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);