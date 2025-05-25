<?php
require_once 'config.php';

// Validate API key for all requests except OPTIONS
if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    validateApiKey();
}

// Get database connection
$conn = getDbConnection();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get all books or specific book
        $isbn = isset($_GET['isbn']) ? sanitizeInput($_GET['isbn']) : null;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;

        if ($isbn) {
            // Get specific book
            $stmt = $conn->prepare("
                SELECT b.*, c.CategoryName as category_name, s.StatusName as status_name 
                FROM Book b 
                LEFT JOIN Book_Categories c ON b.CategoryID = c.CategoryID 
                LEFT JOIN Book_Status s ON b.StatusID = s.StatusID 
                WHERE b.ISBN = ?
            ");
            $stmt->bind_param("s", $isbn);
        } else {
            // Get all books with pagination
            $stmt = $conn->prepare("
                SELECT b.*, c.CategoryName as category_name, s.StatusName as status_name 
                FROM Book b 
                LEFT JOIN Book_Categories c ON b.CategoryID = c.CategoryID 
                LEFT JOIN Book_Status s ON b.StatusID = s.StatusID 
                LIMIT ? OFFSET ?
            ");
            $stmt->bind_param("ii", $limit, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $books = $result->fetch_all(MYSQLI_ASSOC);

        if ($isbn && empty($books)) {
            sendResponse(['error' => 'Book not found'], 404);
        }

        sendResponse($books);
        break;

    case 'POST':
        // Create new book
        $data = getRequestBody();
        validateRequiredFields($data, ['isbn', 'title', 'author']);

        $stmt = $conn->prepare("
            INSERT INTO Book (
                ISBN, Title, Author, Publisher, PublicationYear, 
                CategoryID, StatusID, Summary, Pages, Weight, Dimensions
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $category_id = isset($data['category_id']) ? $data['category_id'] : null;
        $status_id = isset($data['status_id']) ? $data['status_id'] : 1; // Default to Available
        $publication_year = isset($data['publication_year']) ? $data['publication_year'] : null;
        $publisher = isset($data['publisher']) ? $data['publisher'] : null;
        $summary = isset($data['summary']) ? $data['summary'] : null;
        $pages = isset($data['pages']) ? $data['pages'] : null;
        $weight = isset($data['weight']) ? $data['weight'] : null;
        $dimensions = isset($data['dimensions']) ? $data['dimensions'] : null;

        $stmt->bind_param(
            "ssssiiisids",
            $data['isbn'],
            $data['title'],
            $data['author'],
            $publisher,
            $publication_year,
            $category_id,
            $status_id,
            $summary,
            $pages,
            $weight,
            $dimensions
        );

        if ($stmt->execute()) {
            sendResponse([
                'message' => 'Book created successfully',
                'isbn' => $data['isbn']
            ], 201);
        } else {
            sendResponse(['error' => 'Failed to create book'], 500);
        }
        break;

    case 'PUT':
        // Update existing book
        $isbn = isset($_GET['isbn']) ? sanitizeInput($_GET['isbn']) : null;
        if (!$isbn) {
            sendResponse(['error' => 'ISBN is required'], 400);
        }

        $data = getRequestBody();
        $updates = [];
        $types = "";
        $values = [];

        $allowedFields = [
            'title', 'author', 'category_id', 'status_id', 
            'publication_year', 'publisher', 'summary', 
            'pages', 'weight', 'dimensions'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $types .= is_int($data[$field]) ? "i" : "s";
                $values[] = $data[$field];
            }
        }

        if (empty($updates)) {
            sendResponse(['error' => 'No fields to update'], 400);
        }

        $types .= "s"; // for ISBN
        $values[] = $isbn;

        $sql = "UPDATE Book SET " . implode(", ", $updates) . " WHERE ISBN = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(['message' => 'Book updated successfully']);
            } else {
                sendResponse(['error' => 'Book not found'], 404);
            }
        } else {
            sendResponse(['error' => 'Failed to update book'], 500);
        }
        break;

    case 'DELETE':
        // Delete book
        $isbn = isset($_GET['isbn']) ? sanitizeInput($_GET['isbn']) : null;
        if (!$isbn) {
            sendResponse(['error' => 'ISBN is required'], 400);
        }

        $stmt = $conn->prepare("DELETE FROM Book WHERE ISBN = ?");
        $stmt->bind_param("s", $isbn);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(['message' => 'Book deleted successfully']);
            } else {
                sendResponse(['error' => 'Book not found'], 404);
            }
        } else {
            sendResponse(['error' => 'Failed to delete book'], 500);
        }
        break;

    default:
        sendResponse(['error' => 'Method not allowed'], 405);
        break;
} 