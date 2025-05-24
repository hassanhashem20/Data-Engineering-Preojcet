-- Complete Library Management System Setup
-- This file contains all necessary SQL commands to set up the enhanced library system
-- Run this file in MySQL to create the complete database structure

-- Disable foreign key checks temporarily to avoid dependency issues
SET FOREIGN_KEY_CHECKS = 0;

-- STEP 1: Create lookup/reference tables first (no dependencies)

-- Book Categories/Genres
CREATE TABLE IF NOT EXISTS Book_Categories (
    CategoryID INT AUTO_INCREMENT PRIMARY KEY,
    CategoryName VARCHAR(100) NOT NULL UNIQUE,
    Description TEXT,
    CreatedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE if not exists Borrowings (
    BorrowingID INT AUTO_INCREMENT PRIMARY KEY,
    ISBN VARCHAR(20) NOT NULL,
    UserID INT NOT NULL,
    BorrowDate DATE NOT NULL,
    ReturnDate DATE,
    FOREIGN KEY (ISBN) REFERENCES Book(ISBN),
    FOREIGN KEY (UserID) REFERENCES Users(UserID)
);

CREATE TABLE IF NOT EXISTS Reservations (
    ReservationID INT AUTO_INCREMENT PRIMARY KEY,
    ISBN VARCHAR(20) NOT NULL,
    UserID INT NOT NULL,
    ReservationDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Notified TINYINT(1) NOT NULL DEFAULT 0,
    Status ENUM('active','fulfilled','cancelled') NOT NULL DEFAULT 'active',
    FOREIGN KEY (ISBN) REFERENCES Book(ISBN),
    FOREIGN KEY (UserID) REFERENCES Users(UserID)
); 

-- Book Status tracking
CREATE TABLE IF NOT EXISTS Book_Status (
    StatusID INT AUTO_INCREMENT PRIMARY KEY,
    StatusName VARCHAR(50) NOT NULL UNIQUE,
    Description VARCHAR(200)
);

-- Insert default statuses
INSERT IGNORE INTO Book_Status (StatusName, Description) VALUES 
('Available', 'Book is available for borrowing'),
('Borrowed', 'Book is currently borrowed'),
('Reserved', 'Book is reserved for a member'),
('Under Maintenance', 'Book is being repaired or maintained'),
('Lost', 'Book has been reported lost'),
('Damaged', 'Book is damaged and needs repair');

-- Insert default book categories
INSERT IGNORE INTO Book_Categories (CategoryName, Description) VALUES 
('Fiction', 'Fictional literature including novels and short stories'),
('Non-Fiction', 'Factual books including biographies, history, science'),
('Science Fiction', 'Speculative fiction dealing with futuristic concepts'),
('Fantasy', 'Fiction involving magical or supernatural elements'),
('Mystery', 'Fiction dealing with the solution of a crime or puzzle'),
('Romance', 'Fiction dealing with love relationships'),
('Biography', 'Life stories of real people'),
('History', 'Books about past events and civilizations'),
('Science', 'Books about scientific subjects and discoveries'),
('Technology', 'Books about computers, engineering, and technical subjects'),
('Arts', 'Books about visual arts, music, and creative expression'),
('Philosophy', 'Books dealing with fundamental questions about existence'),
('Religion', 'Books about religious beliefs and practices'),
('Self Help', 'Books aimed at self-improvement and personal development'),
('Children', 'Books specifically written for children'),
('Young Adult', 'Books targeted at teenage readers'),
('Reference', 'Dictionaries, encyclopedias, and other reference materials'),
('Textbooks', 'Educational books for academic study'),
('Poetry', 'Collections of poems and poetic works'),
('Drama', 'Plays and theatrical works');

-- User Authentication system
CREATE TABLE IF NOT EXISTS Users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Role ENUM('Admin', 'Librarian', 'Assistant') DEFAULT 'Assistant',
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    IsActive BOOLEAN DEFAULT TRUE,
    CreatedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    LastLogin TIMESTAMP NULL
);

-- Create a default admin user (password: admin123 - should be changed!)
INSERT IGNORE INTO Users (Username, Password, Email, Role, FirstName, LastName) VALUES 
('admin', '$2a$12$AR2QlW.AwW0/xD5xQJOqQufJtBoldbK9V8LLmCgPYDmZS4QOgTVo2', 'admin@library.com', 'Admin', 'System', 'Administrator');

-- Staff Management
CREATE TABLE IF NOT EXISTS Staff (
    StaffID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    EmployeeID VARCHAR(20) UNIQUE,
    Department VARCHAR(100),
    Position VARCHAR(100),
    HireDate DATE,
    Salary DECIMAL(10,2),
    Phone VARCHAR(20),
    Address TEXT,
    EmergencyContact VARCHAR(200),
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE
);

-- System Settings
CREATE TABLE IF NOT EXISTS System_Settings (
    SettingID INT AUTO_INCREMENT PRIMARY KEY,
    SettingKey VARCHAR(100) NOT NULL UNIQUE,
    SettingValue TEXT NOT NULL,
    Description VARCHAR(200),
    Category VARCHAR(50) DEFAULT 'General',
    LastModified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ModifiedBy INT,
    FOREIGN KEY (ModifiedBy) REFERENCES Users(UserID) ON DELETE SET NULL
);

-- Insert default system settings
INSERT IGNORE INTO System_Settings (SettingKey, SettingValue, Description, Category) VALUES
('max_borrow_days', '14', 'Maximum number of days a book can be borrowed', 'Circulation'),
('max_renewals', '2', 'Maximum number of times a book can be renewed', 'Circulation'),
('fine_per_day', '1.00', 'Fine amount per day for overdue books', 'Fines'),
('max_books_per_member', '5', 'Maximum number of books a member can borrow', 'Circulation'),
('reservation_expiry_days', '3', 'Number of days a reservation remains active', 'Reservations'),
('notification_due_days', '3', 'Days before due date to send reminder', 'Notifications'),
('library_name', 'Central Library', 'Name of the library', 'General'),
('library_email', 'library@example.com', 'Library contact email', 'General'),
('library_phone', '+1234567890', 'Library contact phone', 'General'),
('auto_notifications', '1', 'Enable automatic notifications', 'Notifications');

-- STEP 2: Create or enhance core tables

-- Book table (if it doesn't exist)
CREATE TABLE IF NOT EXISTS Book (
    ISBN VARCHAR(20) PRIMARY KEY,
    Title VARCHAR(255) NOT NULL,
    Author VARCHAR(255) NOT NULL,
    Publisher VARCHAR(255),
    PublicationYear INT,
    CategoryID INT,
    StatusID INT DEFAULT 1,
    CoverImage VARCHAR(500),
    Summary TEXT,
    Pages INT,
    Weight DECIMAL(5,2),
    Dimensions VARCHAR(50),
    AverageRating DECIMAL(3,2) DEFAULT 0.00,
    TotalReviews INT DEFAULT 0,
    PopularityScore INT DEFAULT 0,
    LastBorrowDate DATE,
    TotalBorrows INT DEFAULT 0,
    IsDigitalAvailable BOOLEAN DEFAULT FALSE,
    CreatedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Member table (if it doesn't exist)
CREATE TABLE IF NOT EXISTS Member (
    MemberID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Email VARCHAR(100),
    Phone VARCHAR(20),
    DateOfBirth DATE,
    Address TEXT,
    EmergencyContact VARCHAR(200),
    MemberSince DATE DEFAULT (CURRENT_DATE),
    LastActivity TIMESTAMP,
    TotalBooksRead INT DEFAULT 0,
    CurrentFines DECIMAL(10,2) DEFAULT 0.00,
    MaxBooksAllowed INT DEFAULT 5,
    PhotoPath VARCHAR(500),
    Notes TEXT,
    IsActive BOOLEAN DEFAULT TRUE,
    CreatedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Borrowing table (if it doesn't exist)
CREATE TABLE IF NOT EXISTS Borrowing (
    BorrowID INT AUTO_INCREMENT PRIMARY KEY,
    ISBN VARCHAR(20) NOT NULL,
    MemberID INT NOT NULL,
    BorrowDate DATE NOT NULL,
    DueDate DATE NOT NULL,
    ReturnDate DATE,
    ActualReturnDate DATE,
    Status ENUM('Active', 'Returned', 'Overdue', 'Lost') DEFAULT 'Active',
    RenewalCount INT DEFAULT 0,
    LastRenewalDate DATE,
    ConditionOnReturn VARCHAR(200),
    ProcessedBy INT,
    Notes TEXT,
    CreatedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- STEP 3: Create enhancement tables

-- Book Copies
CREATE TABLE IF NOT EXISTS Book_Copies (
    CopyID INT AUTO_INCREMENT PRIMARY KEY,
    ISBN VARCHAR(20) NOT NULL,
    CopyNumber INT NOT NULL,
    StatusID INT DEFAULT 1,
    AcquisitionDate DATE,
    Condition_Notes TEXT,
    Location VARCHAR(100),
    UNIQUE KEY unique_copy (ISBN, CopyNumber)
);

-- Book Reservations
CREATE TABLE IF NOT EXISTS Book_Reservations (
    ReservationID INT AUTO_INCREMENT PRIMARY KEY,
    ISBN VARCHAR(20) NOT NULL,
    MemberID INT NOT NULL,
    ReservationDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ExpiryDate DATE,
    Status ENUM('Active', 'Fulfilled', 'Cancelled', 'Expired') DEFAULT 'Active',
    Priority INT DEFAULT 1
);

-- Book Reviews
CREATE TABLE IF NOT EXISTS Book_Reviews (
    ReviewID INT AUTO_INCREMENT PRIMARY KEY,
    ISBN VARCHAR(20) NOT NULL,
    MemberID INT NOT NULL,
    Rating INT CHECK (Rating >= 1 AND Rating <= 5),
    Review TEXT,
    ReviewDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    IsApproved BOOLEAN DEFAULT FALSE,
    UNIQUE KEY unique_member_book_review (ISBN, MemberID)
);

-- Waiting Lists
CREATE TABLE IF NOT EXISTS Waiting_Lists (
    WaitingID INT AUTO_INCREMENT PRIMARY KEY,
    ISBN VARCHAR(20) NOT NULL,
    MemberID INT NOT NULL,
    DateAdded TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Position INT,
    Status ENUM('Waiting', 'Notified', 'Fulfilled', 'Cancelled') DEFAULT 'Waiting'
);

-- Member Notifications
CREATE TABLE IF NOT EXISTS Member_Notifications (
    NotificationID INT AUTO_INCREMENT PRIMARY KEY,
    MemberID INT NOT NULL,
    Type ENUM('Due_Reminder', 'Overdue_Notice', 'Reserve_Available', 'Fine_Notice', 'General') NOT NULL,
    Title VARCHAR(200) NOT NULL,
    Message TEXT NOT NULL,
    CreatedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    SentDate TIMESTAMP NULL,
    ReadDate TIMESTAMP NULL,
    Status ENUM('Pending', 'Sent', 'Read', 'Failed') DEFAULT 'Pending'
);

-- Fines table
CREATE TABLE IF NOT EXISTS Fines (
    FineID INT AUTO_INCREMENT PRIMARY KEY,
    MemberID INT NOT NULL,
    BorrowID INT NOT NULL,
    Amount DECIMAL(10,2) NOT NULL,
    Reason VARCHAR(200) NOT NULL,
    IssueDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    DueDate DATE NOT NULL,
    Status ENUM('Pending', 'Paid', 'Waived', 'Overdue') DEFAULT 'Pending'
);

-- Fine Payments
CREATE TABLE IF NOT EXISTS Fine_Payments (
    PaymentID INT AUTO_INCREMENT PRIMARY KEY,
    FineID INT NOT NULL,
    AmountPaid DECIMAL(10,2) NOT NULL,
    PaymentDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PaymentMethod ENUM('Cash', 'Card', 'Bank_Transfer', 'Online') DEFAULT 'Cash',
    TransactionReference VARCHAR(100),
    ReceivedBy VARCHAR(100)
);

-- Member Reading Lists
CREATE TABLE IF NOT EXISTS Member_Reading_Lists (
    ListID INT AUTO_INCREMENT PRIMARY KEY,
    MemberID INT NOT NULL,
    ListName VARCHAR(100) NOT NULL,
    Description TEXT,
    IsPublic BOOLEAN DEFAULT FALSE,
    CreatedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reading List Items
CREATE TABLE IF NOT EXISTS Reading_List_Items (
    ItemID INT AUTO_INCREMENT PRIMARY KEY,
    ListID INT NOT NULL,
    ISBN VARCHAR(20) NOT NULL,
    DateAdded TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Priority INT DEFAULT 1,
    Notes TEXT,
    UNIQUE KEY unique_list_book (ListID, ISBN)
);

-- Book Renewals
CREATE TABLE IF NOT EXISTS Book_Renewals (
    RenewalID INT AUTO_INCREMENT PRIMARY KEY,
    BorrowID INT NOT NULL,
    RenewalDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    NewDueDate DATE NOT NULL,
    RenewalCount INT DEFAULT 1,
    RenewedBy VARCHAR(100)
);

-- Events
CREATE TABLE IF NOT EXISTS Events (
    EventID INT AUTO_INCREMENT PRIMARY KEY,
    Title VARCHAR(200) NOT NULL,
    Description TEXT,
    EventType ENUM('Workshop', 'Reading_Club', 'Author_Visit', 'Exhibition', 'Training', 'Other') DEFAULT 'Other',
    StartDate DATETIME NOT NULL,
    EndDate DATETIME NOT NULL,
    Location VARCHAR(100),
    MaxParticipants INT,
    CurrentParticipants INT DEFAULT 0,
    OrganizerID INT,
    Status ENUM('Planned', 'Active', 'Completed', 'Cancelled') DEFAULT 'Planned',
    RegistrationRequired BOOLEAN DEFAULT FALSE,
    Fee DECIMAL(10,2) DEFAULT 0.00,
    CreatedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (OrganizerID) REFERENCES Staff(StaffID)
);

-- Event Registrations
CREATE TABLE IF NOT EXISTS Event_Registrations (
    RegistrationID INT AUTO_INCREMENT PRIMARY KEY,
    EventID INT NOT NULL,
    MemberID INT NOT NULL,
    RegistrationDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Status ENUM('Registered', 'Attended', 'No_Show', 'Cancelled') DEFAULT 'Registered',
    PaymentStatus ENUM('Pending', 'Paid', 'Refunded') DEFAULT 'Pending',
    UNIQUE KEY unique_member_event (EventID, MemberID)
);

-- Digital Resources
CREATE TABLE IF NOT EXISTS Digital_Resources (
    ResourceID INT AUTO_INCREMENT PRIMARY KEY,
    Title VARCHAR(200) NOT NULL,
    Type ENUM('E-Book', 'Audio_Book', 'Video', 'Document', 'Database', 'Other') NOT NULL,
    URL VARCHAR(500),
    FileLocation VARCHAR(500),
    AccessType ENUM('Free', 'Members_Only', 'Premium') DEFAULT 'Members_Only',
    Description TEXT,
    FileSize BIGINT,
    Format VARCHAR(50),
    Duration INT,
    UploadDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    DownloadCount INT DEFAULT 0,
    IsActive BOOLEAN DEFAULT TRUE
);

-- Digital Access Log
CREATE TABLE IF NOT EXISTS Digital_Access_Log (
    AccessID INT AUTO_INCREMENT PRIMARY KEY,
    ResourceID INT NOT NULL,
    MemberID INT,
    AccessDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    AccessType ENUM('View', 'Download', 'Stream') NOT NULL,
    IPAddress VARCHAR(45),
    UserAgent TEXT
);

-- Book Recommendations
CREATE TABLE IF NOT EXISTS Book_Recommendations (
    RecommendationID INT AUTO_INCREMENT PRIMARY KEY,
    ISBN VARCHAR(20) NOT NULL,
    RecommendedISBN VARCHAR(20) NOT NULL,
    RecommendationType ENUM('Similar_Genre', 'Same_Author', 'Popular_With_Readers', 'Staff_Pick', 'AI_Generated') NOT NULL,
    Confidence DECIMAL(3,2) DEFAULT 0.50,
    CreatedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CreatedBy INT,
    UNIQUE KEY unique_recommendation (ISBN, RecommendedISBN)
);

-- Book Transfers
CREATE TABLE IF NOT EXISTS Book_Transfers (
    TransferID INT AUTO_INCREMENT PRIMARY KEY,
    CopyID INT NOT NULL,
    FromLocation VARCHAR(100) NOT NULL,
    ToLocation VARCHAR(100) NOT NULL,
    TransferDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    RequestedBy INT,
    ApprovedBy INT,
    Status ENUM('Requested', 'In_Transit', 'Completed', 'Cancelled') DEFAULT 'Requested',
    Reason VARCHAR(200),
    CompletedDate TIMESTAMP NULL
);

-- STEP 4: Add foreign key constraints

-- Book table foreign keys
ALTER TABLE Book ADD FOREIGN KEY (CategoryID) REFERENCES Book_Categories(CategoryID) ON DELETE SET NULL;
ALTER TABLE Book ADD FOREIGN KEY (StatusID) REFERENCES Book_Status(StatusID);

-- Book_Copies foreign keys
ALTER TABLE Book_Copies ADD FOREIGN KEY (ISBN) REFERENCES Book(ISBN) ON DELETE CASCADE;
ALTER TABLE Book_Copies ADD FOREIGN KEY (StatusID) REFERENCES Book_Status(StatusID);

-- Book_Reservations foreign keys
ALTER TABLE Book_Reservations ADD FOREIGN KEY (ISBN) REFERENCES Book(ISBN) ON DELETE CASCADE;
ALTER TABLE Book_Reservations ADD FOREIGN KEY (MemberID) REFERENCES Member(MemberID) ON DELETE CASCADE;

-- Book_Reviews foreign keys
ALTER TABLE Book_Reviews ADD FOREIGN KEY (ISBN) REFERENCES Book(ISBN) ON DELETE CASCADE;
ALTER TABLE Book_Reviews ADD FOREIGN KEY (MemberID) REFERENCES Member(MemberID) ON DELETE CASCADE;

-- Waiting_Lists foreign keys
ALTER TABLE Waiting_Lists ADD FOREIGN KEY (ISBN) REFERENCES Book(ISBN) ON DELETE CASCADE;
ALTER TABLE Waiting_Lists ADD FOREIGN KEY (MemberID) REFERENCES Member(MemberID) ON DELETE CASCADE;

-- Member_Notifications foreign keys
ALTER TABLE Member_Notifications ADD FOREIGN KEY (MemberID) REFERENCES Member(MemberID) ON DELETE CASCADE;

-- Fines foreign keys
ALTER TABLE Fines ADD FOREIGN KEY (MemberID) REFERENCES Member(MemberID) ON DELETE CASCADE;
ALTER TABLE Fines ADD FOREIGN KEY (BorrowID) REFERENCES Borrowing(BorrowID) ON DELETE CASCADE;

-- Fine_Payments foreign keys
ALTER TABLE Fine_Payments ADD FOREIGN KEY (FineID) REFERENCES Fines(FineID) ON DELETE CASCADE;

-- Member_Reading_Lists foreign keys
ALTER TABLE Member_Reading_Lists ADD FOREIGN KEY (MemberID) REFERENCES Member(MemberID) ON DELETE CASCADE;

-- Reading_List_Items foreign keys
ALTER TABLE Reading_List_Items ADD FOREIGN KEY (ListID) REFERENCES Member_Reading_Lists(ListID) ON DELETE CASCADE;
ALTER TABLE Reading_List_Items ADD FOREIGN KEY (ISBN) REFERENCES Book(ISBN) ON DELETE CASCADE;

-- Book_Renewals foreign keys
ALTER TABLE Book_Renewals ADD FOREIGN KEY (BorrowID) REFERENCES Borrowing(BorrowID) ON DELETE CASCADE;

-- Event_Registrations foreign keys
ALTER TABLE Event_Registrations ADD FOREIGN KEY (EventID) REFERENCES Events(EventID) ON DELETE CASCADE;
ALTER TABLE Event_Registrations ADD FOREIGN KEY (MemberID) REFERENCES Member(MemberID) ON DELETE CASCADE;

-- Digital_Access_Log foreign keys
ALTER TABLE Digital_Access_Log ADD FOREIGN KEY (ResourceID) REFERENCES Digital_Resources(ResourceID) ON DELETE CASCADE;
ALTER TABLE Digital_Access_Log ADD FOREIGN KEY (MemberID) REFERENCES Member(MemberID) ON DELETE SET NULL;

-- Book_Recommendations foreign keys
ALTER TABLE Book_Recommendations ADD FOREIGN KEY (ISBN) REFERENCES Book(ISBN) ON DELETE CASCADE;
ALTER TABLE Book_Recommendations ADD FOREIGN KEY (RecommendedISBN) REFERENCES Book(ISBN) ON DELETE CASCADE;
ALTER TABLE Book_Recommendations ADD FOREIGN KEY (CreatedBy) REFERENCES Staff(StaffID) ON DELETE SET NULL;

-- Book_Transfers foreign keys
ALTER TABLE Book_Transfers ADD FOREIGN KEY (CopyID) REFERENCES Book_Copies(CopyID) ON DELETE CASCADE;
ALTER TABLE Book_Transfers ADD FOREIGN KEY (RequestedBy) REFERENCES Staff(StaffID) ON DELETE SET NULL;
ALTER TABLE Book_Transfers ADD FOREIGN KEY (ApprovedBy) REFERENCES Staff(StaffID) ON DELETE SET NULL;

-- STEP 5: Create indexes for better performance
-- Create indexes (MySQL will ignore if they already exist)
CREATE INDEX idx_book_category ON Book(CategoryID);
CREATE INDEX idx_book_status ON Book(StatusID);
CREATE INDEX idx_book_title ON Book(Title);
CREATE INDEX idx_member_email ON Member(Email);
CREATE INDEX idx_member_isactive ON Member(IsActive);
CREATE INDEX idx_member_membersince ON Member(MemberSince);
CREATE INDEX idx_borrowing_status ON Borrowing(Status);
CREATE INDEX idx_borrowing_due_date ON Borrowing(DueDate);
CREATE INDEX idx_notifications_member ON Member_Notifications(MemberID);
CREATE INDEX idx_notifications_status ON Member_Notifications(Status);
CREATE INDEX idx_reservations_status ON Book_Reservations(Status);
CREATE INDEX idx_reviews_book ON Book_Reviews(ISBN);
CREATE INDEX idx_reviews_rating ON Book_Reviews(Rating);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Success message
SELECT 'Enhanced library schema created successfully!' as Status; 


