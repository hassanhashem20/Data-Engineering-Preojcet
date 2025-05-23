# 📚 Library Management System

This is a simple Library Management System (LMS) created as part of the Data Engineering course. It allows users to manage books, members, borrowing, and staff using a web application connected to a database.

---

## 🎯 Project Scope

The system will help libraries:

- Add and organize books, authors, genres, and publishers
- Register members and manage their borrowing
- Borrow and return books with due dates
- Calculate fines for late returns
- Assign roles to staff
- Manage book clubs and member participation

---

## 🚀 Features

- **Book Management**  
  Store and organize book metadata and link to authors, publishers, genres, shelves, and vendors.

- **Membership Management**  
  Handle member registrations, preferred genres, and membership types with feedback support.

- **Borrowing & Fines**  
  Enable book borrowing/returning, fine calculation, and payment tracking.

- **Branch & Shelf Management**  
  Allocate books across multiple branches, rooms, and shelves with capacity planning.

- **Staff & Role Control**  
  Assign roles and manage staff permissions and branch assignments.

- **Vendor Management**  
  Maintain vendor profiles, contracts, and supplied resources.

- **Book Club Integration**  
  Create and manage book clubs, member participation, and scheduled activities.

---

## 🛠️ Planned Tools

- **Backend**:  PHP
- **Frontend**: HTML, CSS, JavaScript (with Razor Pages or Blazor)
- **Database**: MySQL
- **IDE**: Visual Studio
- **Data Format**: JSON for sending/receiving data
- **Diagrams**: Draw.io or Lucidchart
- **Docs**: Word, PDF for reports and diagrams

---

## 💡 Design Decisions

- We are using a **relational database** with separate tables for each part (books, members, staff, etc.)
- The system will follow **MVC or Razor Page structure** in .NET
- **Entity Framework Core** will be used to connect to the database and handle data
- We will use **join tables** for many-to-many relationships like books and genres
- Basic **web forms** will allow users to perform create, read, update, and delete (CRUD) actions

---

## 👥 Team Members

- Hassan Hashem – 120210068  
- Mina Ramsis – 120210169  
- Karim Walid Fathy – 120210220  
- Marwan Sobih – 120200146

---

## 📌 Note

This project is part of the Data Engineering course and shows how to build a small web application using .NET with a connected database.
