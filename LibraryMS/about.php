<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="custom.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Library Management System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="books.php">Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="members.php">Members</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="borrowings.php">Borrowings</a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">Admin Panel</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="documentation.php">Documentation</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="api/documentation.php">API</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['username'])): ?>
                        <li class="nav-item">
                            <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?logout=1">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg">
                    <div class="card-body">
                        <h1 class="text-center mb-4">About This Project</h1>
                        
                        <div class="text-center mb-5">
                            <i class="fas fa-book display-1 text-primary"></i>
                        </div>

                        <div class="mb-5">
                            <h2 class="h4 mb-3">Project Overview</h2>
                            <p class="lead">
                                This Library Management System is a comprehensive web application developed as a project for the Data Engineering course. 
                                It provides a modern and efficient solution for managing library resources, including books, members, and borrowing operations.
                            </p>
                        </div>

                        <div class="mb-5">
                            <h2 class="h4 mb-3">Key Features</h2>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i>Book Management and Cataloging</li>
                                <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i>Member Management System</li>
                                <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i>Borrowing and Return Tracking</li>
                                <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i>Reservation System</li>
                                <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i>Admin Dashboard</li>
                                <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i>User Authentication and Authorization</li>
                            </ul>
                        </div>

                        <div class="mb-5">
                            <h2 class="h4 mb-3">Development Team</h2>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>ID</th>
                                            <th>Role</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="table-warning">
                                            <td><strong>Hassan Hashem</strong></td>
                                            <td>120210068</td>
                                            <td><span class="badge bg-warning text-dark">Team Leader</span></td>
                                        </tr>
                                        <tr>
                                            <td>Karim Walid Fathy</td>
                                            <td>120210220</td>
                                            <td>Team Member</td>
                                        </tr>
                                        <tr class="table-danger">
                                            <td><strong>Marwan Sobih</strong></td>
                                            <td>120200146</td>
                                            <td><span class="text-danger fw-bold">We are going to kick him out of the group as he didn't work at all</span></td>
                                        </tr>
                                        <tr>
                                            <td>Mina Ramsis</td>
                                            <td>120210169</td>
                                            <td>Team Member</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Library Management System. All rights reserved.</p>
            <p class="small mt-2">Developed by Team El Daba7eeeeeeeeeeeeeen</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 