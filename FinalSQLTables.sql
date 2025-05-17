CREATE DATABASE LibraryMS;
USE LibraryMS;

CREATE TABLE Author
(
  AuthorID INT NOT NULL IDENTITY(1,1),
  FullName NVARCHAR(255) NOT NULL,
  DateOfBirth DATE NOT NULL,
  Address NVARCHAR(255),
  ContractDetails NVARCHAR(MAX),
  PRIMARY KEY (AuthorID)
);

CREATE TABLE Publisher
(
  PublisherID INT NOT NULL IDENTITY(1,1),
  Name NVARCHAR(255) NOT NULL,
  Address NVARCHAR(255),
  ContactInfo NVARCHAR(255),
  Publications_No INT,
  PRIMARY KEY (PublisherID)
);

CREATE TABLE Genre
(
  GenreID INT NOT NULL IDENTITY(1,1),
  Name NVARCHAR(100) NOT NULL,
  Description NVARCHAR(MAX),
  PRIMARY KEY (GenreID)
);

CREATE TABLE Vendor
(
  VendorID INT NOT NULL IDENTITY(1,1),
  Name NVARCHAR(255) NOT NULL,
  ContractDetails NVARCHAR(MAX),
  ProductsSupplied NVARCHAR(MAX),
  PRIMARY KEY (VendorID)
);

CREATE TABLE Branch
(
  BranchID INT NOT NULL IDENTITY(1,1),
  Location NVARCHAR(255) NOT NULL,
  ContactInfo NVARCHAR(255),
  PRIMARY KEY (BranchID)
);

CREATE TABLE Room_Category
(
  ID INT NOT NULL IDENTITY(1,1),
  Name NVARCHAR(100) NOT NULL,
  Capacity INT NOT NULL,
  FloorNumber INT,
  PRIMARY KEY (ID)
);

CREATE TABLE Membership
(
  MembershipID INT NOT NULL IDENTITY(1,1),
  MembershipType NVARCHAR(50) NOT NULL,
  FeesPaid DECIMAL(10,2),
  PRIMARY KEY (MembershipID)
);

CREATE TABLE Role
(
  RoleID INT NOT NULL IDENTITY(1,1),
  RoleName NVARCHAR(100) NOT NULL,
  Permissions NVARCHAR(MAX),
  PRIMARY KEY (RoleID)
);

CREATE TABLE BookClub
(
  ID INT NOT NULL IDENTITY(1,1),
  Name NVARCHAR(255) NOT NULL,
  Description NVARCHAR(MAX),
  ClubSchedule NVARCHAR(MAX),
  PRIMARY KEY (ID)
);

CREATE TABLE BranchRoom
(
  BranchID INT NOT NULL,
  RoomID INT NOT NULL,
  PRIMARY KEY (BranchID, RoomID),
  FOREIGN KEY (BranchID) REFERENCES Branch(BranchID),
  FOREIGN KEY (RoomID) REFERENCES Room_Category(ID)
);

CREATE TABLE BookClub_Activities
(
  ID INT NOT NULL,
  Activities NVARCHAR(MAX) NOT NULL,
  PRIMARY KEY (Activities, ID),
  FOREIGN KEY (ID) REFERENCES BookClub(ID)
);

CREATE TABLE Staff
(
  StaffID INT NOT NULL IDENTITY(1,1),
  FullName NVARCHAR(255) NOT NULL,
  DateOfBirth DATE NOT NULL,
  HireDate DATE NOT NULL,
  ContractDetails NVARCHAR(MAX),
  RoleID INT NOT NULL,
  BranchID INT NOT NULL,
  PRIMARY KEY (StaffID),
  FOREIGN KEY (RoleID) REFERENCES Role(RoleID),
  FOREIGN KEY (BranchID) REFERENCES Branch(BranchID)
);

CREATE TABLE Shelf
(
  ShelfID INT NOT NULL IDENTITY(1,1),
  ShelfNumber NVARCHAR(50) NOT NULL,
  Location NVARCHAR(255),
  Capacity INT,
  ID INT NOT NULL,
  PRIMARY KEY (ShelfID),
  FOREIGN KEY (ID) REFERENCES Room_Category(ID)
);

CREATE TABLE Member
(
  MemberID INT NOT NULL IDENTITY(1,1),
  FullName NVARCHAR(255) NOT NULL,
  PreferredGenres NVARCHAR(MAX),
  ContractDetails NVARCHAR(MAX),
  Status NVARCHAR(50),
  MembershipID INT NOT NULL,
  PRIMARY KEY (MemberID),
  FOREIGN KEY (MembershipID) REFERENCES Membership(MembershipID)
);

CREATE TABLE Borrowing
(
  BorrowID INT NOT NULL IDENTITY(1,1),
  MemberID INT NOT NULL,
  BorrowDate DATE NOT NULL,
  DueDate DATE NOT NULL,
  ReturnDate DATE,
  Status NVARCHAR(50),
  PRIMARY KEY (BorrowID),
  FOREIGN KEY (MemberID) REFERENCES Member(MemberID)
);

CREATE TABLE Payment
(
  PaymentID INT NOT NULL IDENTITY(1,1),
  Amount DECIMAL(10,2) NOT NULL,
  Date DATE NOT NULL,
  PaymentMethod NVARCHAR(50),
  BorrowID INT NOT NULL,
  PRIMARY KEY (PaymentID),
  FOREIGN KEY (BorrowID) REFERENCES Borrowing(BorrowID)
);

CREATE TABLE Fines
(
  FineID INT NOT NULL IDENTITY(1,1),
  MemberID INT NOT NULL,
  Amount DECIMAL(10,2) NOT NULL,
  Date DATE NOT NULL,
  Reason NVARCHAR(MAX),
  Status NVARCHAR(50),
  PRIMARY KEY (FineID),
  FOREIGN KEY (MemberID) REFERENCES Member(MemberID)
);

CREATE TABLE Feedback
(
  FeedbackID INT NOT NULL IDENTITY(1,1),
  MemberID INT NOT NULL,
  Date DATE NOT NULL,
  Message NVARCHAR(MAX) NOT NULL,
  Category NVARCHAR(100),
  PRIMARY KEY (FeedbackID),
  FOREIGN KEY (MemberID) REFERENCES Member(MemberID)
);

CREATE TABLE MemberBookClub
(
  MemberID INT NOT NULL,
  ClubID INT NOT NULL,
  PRIMARY KEY (MemberID, ClubID),
  FOREIGN KEY (MemberID) REFERENCES Member(MemberID),
  FOREIGN KEY (ClubID) REFERENCES BookClub(ID)
);

CREATE TABLE BOOK
(
  ISBN NVARCHAR(20) NOT NULL,
  Title NVARCHAR(255) NOT NULL,
  Language NVARCHAR(50) NOT NULL,
  PublicationYear INT NOT NULL,
  Edition NVARCHAR(50),
  AuthorID INT NOT NULL,
  PublisherID INT NOT NULL,
  VendorID INT NOT NULL,
  ShelfID INT NOT NULL,
  PRIMARY KEY (ISBN),
  FOREIGN KEY (AuthorID) REFERENCES Author(AuthorID),
  FOREIGN KEY (PublisherID) REFERENCES Publisher(PublisherID),
  FOREIGN KEY (VendorID) REFERENCES Vendor(VendorID),
  FOREIGN KEY (ShelfID) REFERENCES Shelf(ShelfID)
);

CREATE TABLE BookGenre
(
  GenreID INT NOT NULL,
  ISBN NVARCHAR(20) NOT NULL,
  PRIMARY KEY (GenreID, ISBN),
  FOREIGN KEY (GenreID) REFERENCES Genre(GenreID),
  FOREIGN KEY (ISBN) REFERENCES BOOK(ISBN)
);

CREATE TABLE BookBranch
(
  ISBN NVARCHAR(20) NOT NULL,
  BranchID INT NOT NULL,
  Amount INT,
  PRIMARY KEY (ISBN, BranchID),
  FOREIGN KEY (ISBN) REFERENCES BOOK(ISBN),
  FOREIGN KEY (BranchID) REFERENCES Branch(BranchID)
);

CREATE TABLE BookBorrow
(
  BorrowID INT NOT NULL,
  ISBN NVARCHAR(20) NOT NULL,
  Quantity INT,
  PRIMARY KEY (BorrowID, ISBN),
  FOREIGN KEY (BorrowID) REFERENCES Borrowing(BorrowID),
  FOREIGN KEY (ISBN) REFERENCES BOOK(ISBN)
);
