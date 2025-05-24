<?php
// Test configuration
$baseUrl = 'http://localhost/LibraryMS/api';
$apiKey = 'library-ms-api-key-2024'; // Make sure this matches your config.php

// Helper function to make API requests
function makeRequest($method, $endpoint, $data = null) {
    global $baseUrl, $apiKey;
    
    $url = $baseUrl . '/' . $endpoint;
    $headers = [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

// Test cases
function runTests() {
    echo "Starting API Tests...\n\n";
    
    // Test 1: Get Documentation
    echo "Test 1: Get Documentation\n";
    $result = makeRequest('GET', 'documentation.php');
    echo "Status: " . $result['status'] . "\n";
    echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 2: Create Book
    echo "Test 2: Create Book\n";
    $bookData = [
        'isbn' => '978-3-16-148410-0',
        'title' => 'Test Book',
        'author' => 'Test Author',
        'publisher' => 'Test Publisher',
        'publication_year' => 2024,
        'category_id' => 1,  // Fiction category
        'status_id' => 1,    // Available status
        'summary' => 'This is a test book',
        'pages' => 200,
        'weight' => 0.5,
        'dimensions' => '6x9 inches'
    ];
    $result = makeRequest('POST', 'books.php', $bookData);
    echo "Status: " . $result['status'] . "\n";
    echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 3: Get Book
    echo "Test 3: Get Book\n";
    $result = makeRequest('GET', 'books.php?isbn=978-3-16-148410-0');
    echo "Status: " . $result['status'] . "\n";
    echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 4: Update Book
    echo "Test 4: Update Book\n";
    $updateData = [
        'title' => 'Updated Test Book',
        'author' => 'Updated Test Author',
        'summary' => 'This is an updated test book'
    ];
    $result = makeRequest('PUT', 'books.php?isbn=978-3-16-148410-0', $updateData);
    echo "Status: " . $result['status'] . "\n";
    echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 5: Delete Book
    echo "Test 5: Delete Book\n";
    $result = makeRequest('DELETE', 'books.php?isbn=978-3-16-148410-0');
    echo "Status: " . $result['status'] . "\n";
    echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";
}

// Run the tests
runTests(); 