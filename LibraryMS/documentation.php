<?php
require_once 'config.php';

// Get project documentation
$project_docs = [
    'title' => 'Library Management System Documentation',
    'description' => 'A comprehensive web-based library management system for managing books, members, and borrowings.',
    'version' => '1.0.0',
    'features' => [
        'Book Management' => [
            'Add, edit, and delete books',
            'Search books by title, author, or ISBN',
            'View book details including availability',
            'Manage book categories and status'
        ],
        'Member Management' => [
            'Register and manage library members',
            'Track member borrowing history',
            'Manage member accounts and permissions',
            'View member statistics'
        ],
        'Borrowing System' => [
            'Process book loans and returns',
            'Track due dates and late returns',
            'Handle book reservations',
            'Generate borrowing reports'
        ],
        'API Integration' => [
            'RESTful API for external integration',
            'Secure authentication system',
            'Comprehensive endpoint documentation',
            'JSON response format'
        ]
    ],
    'system_requirements' => [
        'Server' => [
            'PHP 7.4 or higher',
            'MySQL 5.7 or higher',
            'Apache/Nginx web server',
            'mod_rewrite enabled'
        ],
        'Client' => [
            'Modern web browser',
            'JavaScript enabled',
            'Internet connection'
        ]
    ],
    'installation' => [
        'steps' => [
            'Clone or download the repository',
            'Import the database schema from SQL_for_DB.sql',
            'Configure database connection in config.php',
            'Set up web server to point to the project directory',
            'Ensure proper permissions on upload directories'
        ],
        'configuration' => [
            'Database settings in config.php',
            'API key configuration',
            'File upload settings',
            'Session configuration'
        ]
    ],
    'api_documentation' => [
        'base_url' => 'http://localhost/LibraryMS/api',
        'authentication' => [
            'type' => 'API Key',
            'header' => 'api_key: library-ms-api-key-2024',
            'description' => 'All API requests require authentication using an API key.'
        ],
        'endpoints' => [
            'books' => [
                'base_path' => '/books.php',
                'methods' => [
                    'GET' => [
                        'description' => 'Get all books or specific book',
                        'parameters' => [
                            'isbn' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'ISBN of the book to retrieve'
                            ],
                            'page' => [
                                'type' => 'integer',
                                'required' => false,
                                'default' => 1,
                                'description' => 'Page number for pagination'
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'required' => false,
                                'default' => 10,
                                'description' => 'Number of items per page'
                            ]
                        ]
                    ],
                    'POST' => [
                        'description' => 'Create a new book',
                        'body' => [
                            'required' => [
                                'isbn' => 'string',
                                'title' => 'string',
                                'author' => 'string'
                            ],
                            'optional' => [
                                'category_id' => 'integer',
                                'status_id' => 'integer',
                                'publication_year' => 'integer',
                                'publisher' => 'string',
                                'summary' => 'string',
                                'pages' => 'integer',
                                'weight' => 'decimal',
                                'dimensions' => 'string'
                            ]
                        ]
                    ],
                    'PUT' => [
                        'description' => 'Update an existing book',
                        'parameters' => [
                            'isbn' => [
                                'type' => 'string',
                                'required' => true,
                                'description' => 'ISBN of the book to update'
                            ]
                        ],
                        'body' => [
                            'optional' => [
                                'title' => 'string',
                                'author' => 'string',
                                'category_id' => 'integer',
                                'status_id' => 'integer',
                                'publication_year' => 'integer',
                                'publisher' => 'string',
                                'summary' => 'string',
                                'pages' => 'integer',
                                'weight' => 'decimal',
                                'dimensions' => 'string'
                            ]
                        ]
                    ],
                    'DELETE' => [
                        'description' => 'Delete a book',
                        'parameters' => [
                            'isbn' => [
                                'type' => 'string',
                                'required' => true,
                                'description' => 'ISBN of the book to delete'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'status_codes' => [
            '200' => 'Success',
            '201' => 'Created',
            '400' => 'Bad Request',
            '401' => 'Unauthorized',
            '404' => 'Not Found',
            '405' => 'Method Not Allowed',
            '500' => 'Internal Server Error'
        ]
    ],
    'database_schema' => [
        'tables' => [
            'Book' => 'Stores book information including ISBN, title, author, etc.',
            'Book_Categories' => 'Manages book categories and classifications',
            'Book_Status' => 'Tracks book availability status',
            'Users' => 'Stores user/member information and authentication details',
            'Borrowings' => 'Records book loans and returns',
            'Reservations' => 'Manages book reservations'
        ]
    ],
    'security' => [
        'authentication' => [
            'User authentication using secure password hashing',
            'Session management',
            'Role-based access control'
        ],
        'api_security' => [
            'API key authentication',
            'Request validation',
            'Input sanitization'
        ],
        'data_protection' => [
            'SQL injection prevention',
            'XSS protection',
            'CSRF protection'
        ]
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentation - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="custom.css" rel="stylesheet">
    <style>
        .feature-card {
            margin-bottom: 1.5rem;
            background: rgba(255,255,255,0.95);
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: 0 8px 32px rgba(36, 50, 94, 0.10);
        }
        .method-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            margin-right: 0.5rem;
        }
        .method-get { 
            background: linear-gradient(90deg, #28a745 0%, #34c759 100%);
            color: white;
        }
        .method-post { 
            background: linear-gradient(90deg, #007bff 0%, #4f8cff 100%);
            color: white;
        }
        .method-put { 
            background: linear-gradient(90deg, #ffc107 0%, #ffd60a 100%);
            color: #222;
        }
        .method-delete { 
            background: linear-gradient(90deg, #dc3545 0%, #ff4d4d 100%);
            color: white;
        }
        .code-block {
            background: rgba(255,255,255,0.95);
            padding: 1.5rem;
            border-radius: 12px;
            margin: 1rem 0;
            box-shadow: 0 4px 16px rgba(36, 50, 94, 0.08);
            border: 1px solid #e3e8f0;
        }
        .code-block code {
            color: #4f8cff;
            font-weight: 600;
        }
        .parameter-table {
            font-size: 0.95rem;
            background: rgba(255,255,255,0.95);
            border-radius: 12px;
            overflow: hidden;
        }
        .parameter-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #4f8cff;
        }
        .parameter-table td {
            vertical-align: middle;
        }
        .parameter-table code {
            color: #4f8cff;
            font-weight: 600;
            background: rgba(79, 140, 255, 0.08);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
        }
        .list-group-item {
            border: none;
            padding: 0.75rem 1.25rem;
            background: transparent;
            color: #222;
            font-weight: 500;
            transition: all 0.2s;
        }
        .list-group-item:hover, .list-group-item:focus {
            background: rgba(79, 140, 255, 0.08);
            color: #4f8cff;
        }
        .list-group-item.active {
            background: rgba(79, 140, 255, 0.12);
            color: #4f8cff;
            font-weight: 600;
        }
        .card-header {
            background: rgba(255,255,255,0.95);
            border-bottom: 1px solid #e3e8f0;
            padding: 1.25rem;
        }
        .card-header h2, .card-header h5 {
            color: #4f8cff;
            font-weight: 600;
            margin: 0;
        }
        .card-body {
            padding: 1.5rem;
        }
        .card-title {
            color: #4f8cff;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .lead {
            color: #666;
            font-weight: 500;
        }
        .feature-list {
            list-style: none;
            padding-left: 0;
        }
        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e3e8f0;
        }
        .feature-list li:last-child {
            border-bottom: none;
        }
        .feature-list li:before {
            content: "•";
            color: #4f8cff;
            font-weight: bold;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Library Management System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="books.php">Books</a></li>
                    <li class="nav-item"><a class="nav-link" href="members.php">Members</a></li>
                    <li class="nav-item"><a class="nav-link" href="borrowings.php">Borrowings</a></li>
                    <li class="nav-item"><a class="nav-link active" href="documentation.php">Documentation</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5>Documentation Sections</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="#overview" class="list-group-item list-group-item-action">Overview</a>
                        <a href="#features" class="list-group-item list-group-item-action">Features</a>
                        <a href="#requirements" class="list-group-item list-group-item-action">System Requirements</a>
                        <a href="#installation" class="list-group-item list-group-item-action">Installation</a>
                        <a href="#api" class="list-group-item list-group-item-action">API Documentation</a>
                        <a href="#database" class="list-group-item list-group-item-action">Database Schema</a>
                        <a href="#security" class="list-group-item list-group-item-action">Security</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <!-- Overview Section -->
                <div id="overview" class="card mb-4">
                    <div class="card-body">
                        <h1 class="card-title"><?php echo htmlspecialchars($project_docs['title']); ?></h1>
                        <p class="lead"><?php echo htmlspecialchars($project_docs['description']); ?></p>
                        <p><strong>Version:</strong> <?php echo htmlspecialchars($project_docs['version']); ?></p>
                    </div>
                </div>

                <!-- Features Section -->
                <div id="features" class="card mb-4">
                    <div class="card-header">
                        <h2 class="h4">Features</h2>
                    </div>
                    <div class="card-body">
                        <?php foreach ($project_docs['features'] as $category => $features): ?>
                            <div class="feature-card">
                                <h3 class="h5 mb-3"><?php echo htmlspecialchars($category); ?></h3>
                                <ul class="feature-list">
                                    <?php foreach ($features as $feature): ?>
                                        <li><?php echo htmlspecialchars($feature); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- System Requirements Section -->
                <div id="requirements" class="card mb-4">
                    <div class="card-header">
                        <h2 class="h4">System Requirements</h2>
                    </div>
                    <div class="card-body">
                        <?php foreach ($project_docs['system_requirements'] as $category => $requirements): ?>
                            <div class="feature-card">
                                <h3 class="h5 mb-3"><?php echo htmlspecialchars($category); ?></h3>
                                <ul class="feature-list">
                                    <?php foreach ($requirements as $requirement): ?>
                                        <li><?php echo htmlspecialchars($requirement); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Installation Section -->
                <div id="installation" class="card mb-4">
                    <div class="card-header">
                        <h2 class="h4">Installation</h2>
                    </div>
                    <div class="card-body">
                        <div class="feature-card">
                            <h3 class="h5 mb-3">Installation Steps</h3>
                            <ol class="feature-list">
                                <?php foreach ($project_docs['installation']['steps'] as $step): ?>
                                    <li><?php echo htmlspecialchars($step); ?></li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                        <div class="feature-card">
                            <h3 class="h5 mb-3">Configuration</h3>
                            <ul class="feature-list">
                                <?php foreach ($project_docs['installation']['configuration'] as $config): ?>
                                    <li><?php echo htmlspecialchars($config); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- API Documentation Section -->
                <div id="api" class="card mb-4">
                    <div class="card-header">
                        <h2 class="h4">API Documentation</h2>
                    </div>
                    <div class="card-body">
                        <p><strong>Base URL:</strong> <code><?php echo htmlspecialchars($project_docs['api_documentation']['base_url']); ?></code></p>
                        
                        <div class="feature-card">
                            <h3 class="h5">Authentication</h3>
                            <p><?php echo htmlspecialchars($project_docs['api_documentation']['authentication']['description']); ?></p>
                            <div class="code-block">
                                <code><?php echo htmlspecialchars($project_docs['api_documentation']['authentication']['header']); ?></code>
                            </div>
                        </div>

                        <?php foreach ($project_docs['api_documentation']['endpoints'] as $endpoint => $details): ?>
                            <div class="feature-card">
                                <h3 class="h5"><?php echo ucfirst($endpoint); ?> Endpoint</h3>
                                <p>Base Path: <code><?php echo htmlspecialchars($details['base_path']); ?></code></p>
                                
                                <?php foreach ($details['methods'] as $method => $method_details): ?>
                                    <div class="endpoint-card">
                                        <h4 class="h6">
                                            <span class="badge method-badge method-<?php echo strtolower($method); ?>">
                                                <?php echo $method; ?>
                                            </span>
                                            <?php echo htmlspecialchars($method_details['description']); ?>
                                        </h4>
                                        
                                        <?php if (isset($method_details['parameters'])): ?>
                                            <h5 class="h6 mt-3">Parameters:</h5>
                                            <table class="table table-sm parameter-table">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Type</th>
                                                        <th>Required</th>
                                                        <th>Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($method_details['parameters'] as $param => $info): ?>
                                                        <tr>
                                                            <td><code><?php echo htmlspecialchars($param); ?></code></td>
                                                            <td><?php echo htmlspecialchars($info['type']); ?></td>
                                                            <td><?php echo $info['required'] ? 'Yes' : 'No'; ?></td>
                                                            <td><?php echo htmlspecialchars($info['description']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>

                                        <?php if (isset($method_details['body'])): ?>
                                            <h5 class="h6 mt-3">Request Body:</h5>
                                            <?php if (isset($method_details['body']['required'])): ?>
                                                <h6 class="h6">Required Fields:</h6>
                                                <ul>
                                                    <?php foreach ($method_details['body']['required'] as $field => $type): ?>
                                                        <li><code><?php echo htmlspecialchars($field); ?></code> (<?php echo htmlspecialchars($type); ?>)</li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($method_details['body']['optional'])): ?>
                                                <h6 class="h6">Optional Fields:</h6>
                                                <ul>
                                                    <?php foreach ($method_details['body']['optional'] as $field => $type): ?>
                                                        <li><code><?php echo htmlspecialchars($field); ?></code> (<?php echo htmlspecialchars($type); ?>)</li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>

                        <div class="feature-card">
                            <h3 class="h5">Status Codes</h3>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($project_docs['api_documentation']['status_codes'] as $code => $description): ?>
                                        <tr>
                                            <td><code><?php echo $code; ?></code></td>
                                            <td><?php echo htmlspecialchars($description); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Database Schema Section -->
                <div id="database" class="card mb-4">
                    <div class="card-header">
                        <h2 class="h4">Database Schema</h2>
                    </div>
                    <div class="card-body">
                        <div class="feature-card">
                            <h3 class="h5">Tables</h3>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Table Name</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($project_docs['database_schema']['tables'] as $table => $description): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($table); ?></code></td>
                                            <td><?php echo htmlspecialchars($description); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Security Section -->
                <div id="security" class="card mb-4">
                    <div class="card-header">
                        <h2 class="h4">Security</h2>
                    </div>
                    <div class="card-body">
                        <?php foreach ($project_docs['security'] as $category => $features): ?>
                            <div class="feature-card">
                                <h3 class="h5 mb-3"><?php echo ucfirst($category); ?></h3>
                                <ul class="feature-list">
                                    <?php foreach ($features as $feature): ?>
                                        <li><?php echo htmlspecialchars($feature); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 