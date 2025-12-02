# Diary / Journal Web App

A modern, colorful diary/journal web app built with PHP (XAMPP), MySQL, and TailwindCSS. Features authentication, CRUD entries, and image/audio uploads.

## Tech
- Frontend: HTML + TailwindCSS (via CDN), colorful gradients, rounded cards, smooth animations
- Backend: PHP 8+ (works on XAMPP)
- Database: MySQL (XAMPP)

## Features
- User signup/login/logout (password hashing, sessions)
- Create, view, edit, delete diary entries
- Upload multiple images/audio per entry
- Responsive, modern UI with Tailwind components

## Setup (Windows + XAMPP)
1. Start Apache and MySQL in XAMPP Control Panel.
2. Create database and tables:
   - Open phpMyAdmin (http://localhost/phpmyadmin) → Import `schema.sql` from this project.
3. Copy the project folder under `C:/xampp/htdocs/`.
4. Visit the app:
   - `http://localhost/CSE311LBaseline/index.php` (or your base path)

