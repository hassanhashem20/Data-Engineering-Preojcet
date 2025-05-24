<?php
require_once 'config.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    sendResponse(200, ['message' => 'Preflight request successful']);
    exit;
}

// Verify API key for all endpoints except registration and login
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST']) || 
    !in_array($_SERVER['REQUEST_URI'], ['/LibraryMS/api/users.php/login', '/LibraryMS/api/users.php/register'])) {
    verifyApiKey();
}

// Get request body
$data = getRequestBody();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        // Handle registration and login
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'register':
                    // Validate required fields
                    $requiredFields = ['username', 'email', 'password', 'first_name', 'last_name'];
                    validateRequiredFields($data, $requiredFields);
                    
                    // Sanitize input
                    $username = sanitizeInput($data['username']);
                    $email = sanitizeInput($data['email']);
                    $password = $data['password']; // Don't sanitize password
                    $firstName = sanitizeInput($data['first_name']);
                    $lastName = sanitizeInput($data['last_name']);
                    
                    // Check if username or email already exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $email]);
                    if ($stmt->rowCount() > 0) {
                        sendResponse(400, ['error' => 'Username or email already exists']);
                    }
                    
                    // Hash password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert new user
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, email, password, first_name, last_name, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    
                    try {
                        $stmt->execute([$username, $email, $hashedPassword, $firstName, $lastName]);
                        sendResponse(201, [
                            'message' => 'User registered successfully',
                            'user_id' => $pdo->lastInsertId()
                        ]);
                    } catch (PDOException $e) {
                        sendResponse(500, ['error' => 'Failed to register user']);
                    }
                    break;
                    
                case 'login':
                    // Validate required fields
                    $requiredFields = ['username', 'password'];
                    validateRequiredFields($data, $requiredFields);
                    
                    // Sanitize input
                    $username = sanitizeInput($data['username']);
                    $password = $data['password']; // Don't sanitize password
                    
                    // Get user
                    $stmt = $pdo->prepare("
                        SELECT id, username, email, password, first_name, last_name, role
                        FROM users 
                        WHERE username = ?
                    ");
                    $stmt->execute([$username]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$user || !password_verify($password, $user['password'])) {
                        sendResponse(401, ['error' => 'Invalid username or password']);
                    }
                    
                    // Generate JWT token
                    $token = generateJWT([
                        'user_id' => $user['id'],
                        'username' => $user['username'],
                        'role' => $user['role']
                    ]);
                    
                    // Remove password from response
                    unset($user['password']);
                    
                    sendResponse(200, [
                        'message' => 'Login successful',
                        'token' => $token,
                        'user' => $user
                    ]);
                    break;
                    
                default:
                    sendResponse(400, ['error' => 'Invalid action']);
            }
        } else {
            sendResponse(400, ['error' => 'Action parameter required']);
        }
        break;
        
    case 'GET':
        // Get user profile
        if (isset($_GET['id'])) {
            $userId = (int)$_GET['id'];
            
            $stmt = $pdo->prepare("
                SELECT id, username, email, first_name, last_name, role, created_at
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                sendResponse(404, ['error' => 'User not found']);
            }
            
            sendResponse(200, $user);
        } else {
            sendResponse(400, ['error' => 'User ID required']);
        }
        break;
        
    case 'PUT':
        // Update user profile
        if (isset($_GET['id'])) {
            $userId = (int)$_GET['id'];
            
            // Validate required fields
            $requiredFields = ['first_name', 'last_name', 'email'];
            validateRequiredFields($data, $requiredFields);
            
            // Sanitize input
            $firstName = sanitizeInput($data['first_name']);
            $lastName = sanitizeInput($data['last_name']);
            $email = sanitizeInput($data['email']);
            
            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->rowCount() > 0) {
                sendResponse(400, ['error' => 'Email already taken']);
            }
            
            // Update user
            $stmt = $pdo->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, email = ?
                WHERE id = ?
            ");
            
            try {
                $stmt->execute([$firstName, $lastName, $email, $userId]);
                sendResponse(200, ['message' => 'User updated successfully']);
            } catch (PDOException $e) {
                sendResponse(500, ['error' => 'Failed to update user']);
            }
        } else {
            sendResponse(400, ['error' => 'User ID required']);
        }
        break;
        
    case 'DELETE':
        // Delete user
        if (isset($_GET['id'])) {
            $userId = (int)$_GET['id'];
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            
            try {
                $stmt->execute([$userId]);
                if ($stmt->rowCount() === 0) {
                    sendResponse(404, ['error' => 'User not found']);
                }
                sendResponse(200, ['message' => 'User deleted successfully']);
            } catch (PDOException $e) {
                sendResponse(500, ['error' => 'Failed to delete user']);
            }
        } else {
            sendResponse(400, ['error' => 'User ID required']);
        }
        break;
        
    default:
        sendResponse(405, ['error' => 'Method not allowed']);
} 