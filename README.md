# LAB 02 - Library Book Borrowing

## Project Description

This project is a library borrowing system developed using PHP, MySQL, HTML and CSS.

It allows students to submit requests to borrow library books.

## Technologies Used

- HTML
- CSS
- PHP
- MySQL
- XAMPP

## Database Setup

1. Open XAMPP.
2. Start Apache and MySQL.
3. Open phpMyAdmin.
4. Select the SQL tab.
5. Execute the database.sql file.

Database name: lab02_library

Table name: loans

## Project Location

C:\xampp\htdocs\LAB02_Library_Borrowing

## How to Run

Open your browser and visit:

http://localhost/LAB02_Library_Borrowing/

## Validation Rules

- Student ID is required.
- ISBN must be a valid ISBN-10 or ISBN-13.
- Book title is required.
- Borrow date is required and cannot be in the future.
- Due date must be after borrow date.
- Loan duration must be between 1 and 30 days.
- Library rules must be accepted.
- A student cannot have two active loans for the same book.

## Database Security

The project uses PDO prepared statements to insert records safely.