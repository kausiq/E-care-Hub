<?php
require 'connection.php';

// Initialize search variables
$searchName = '';
$searchSpecialty = '';
$searchLocation = '';
$searchLanguage = '';

// Process search form submission
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $searchName = trim($_GET['name'] ?? '');
    $searchSpecialty = trim($_GET['specialty'] ?? '');
    $searchLocation = trim($_GET['location'] ?? '');
    $searchLanguage = trim($_GET['language'] ?? '');
}

// Build the SQL query with search filters
$where = [];
$params = [];
$types = '';

if (!empty($searchName)) {
    $where[] = "u.name LIKE ?";
    $params[] = '%' . $searchName . '%';
    $types .= 's';
}

if (!empty($searchSpecialty)) {
    $where[] = "d.specialty LIKE ?";
    $params[] = '%' . $searchSpecialty . '%';
    $types .= 's';
}

if (!empty($searchLocation)) {
    $where[] = "d.location LIKE ?";
    $params[] = '%' . $searchLocation . '%';
    $types .= 's';
}

if (!empty($searchLanguage)) {
    $where[] = "d.languages LIKE ?";
    $params[] = '%' . $searchLanguage . '%';
    $types .= 's';
}

// Get distinct specialties for filter dropdown
$stmt = $pdo->prepare("SELECT DISTINCT specialty FROM doctors WHERE specialty IS NOT NULL AND specialty != ''");
$stmt->execute();
$specialties = $stmt->fetchAll();

// Get distinct locations for filter dropdown
$stmt = $pdo->prepare("SELECT DISTINCT location FROM doctors WHERE location IS NOT NULL AND location != ''");
$stmt->execute();
$locations = $stmt->fetchAll();

// Get distinct languages for filter dropdown
$stmt = $pdo->prepare("SELECT DISTINCT languages FROM doctors WHERE languages IS NOT NULL AND languages != ''");
$stmt->execute();
$languages = $stmt->fetchAll();

// Base query for doctors
$sql = "SELECT u.id, u.name, d.specialty, d.languages, d.location, 
               d.consultation_fee, d.rating, d.bio
        FROM users u
        JOIN doctors d ON u.id = d.user_id
        WHERE u.is_verified = TRUE";

if (!empty($where)) {
    $sql .= " AND " . implode(" AND ", $where);
}

$sql .= " ORDER BY d.rating DESC, u.name ASC";

// Prepare and execute the query
$stmt = $pdo->prepare($sql);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$doctors = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find a Doctor - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #43aa8b;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: #333;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            color: white;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }
        
        .nav-link:hover {
            color: white;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }
        
        .search-section {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .doctor-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            transition: all 0.3s;
            margin-bottom: 25px;
            height: 100%;
        }
        
        .doctor-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            transform: translateY(-5px);
        }
        
        .doctor-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 600;
            margin: 0 auto 15px;
        }
        
        .doctor-rating {
            color: var(--warning);
            font-weight: 600;
        }
        
        .doctor-fee {
            font-weight: 600;
            color: var(--dark);
        }
        
        .doctor-specialty {
            color: var(--primary);
            font-weight: 500;
        }
        
        .badge-language {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-right: 5px;
            margin-bottom: 5px;
            display: inline-block;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--light-gray);
            margin-bottom: 20px;
        }
        
        footer {
            background-color: var(--dark);
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }
    </style>
</head>
<body>

    <!-- Main Content -->
    <div class="container py-5">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h1 class="font-weight-bold">Find a Doctor</h1>
                <p class="lead">Search from our network of verified healthcare professionals</p>
                <!-- Add Back to Home button at top -->
                <div class="mb-4">
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Search Section -->
        <div class="search-section">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Doctor Name</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user-md"></i></span>
                            </div>
                            <input type="text" name="name" class="form-control" placeholder="Search by name" value="<?= htmlspecialchars($searchName) ?>">
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Specialty</label>
                        <select name="specialty" class="form-control">
                            <option value="">All Specialties</option>
                            <?php foreach ($specialties as $specialty): ?>
                                <option value="<?= htmlspecialchars($specialty['specialty']) ?>" 
                                    <?= $searchSpecialty == $specialty['specialty'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($specialty['specialty']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Location</label>
                        <select name="location" class="form-control">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= htmlspecialchars($location['location']) ?>" 
                                    <?= $searchLocation == $location['location'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($location['location']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search mr-1"></i> Search
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Language</label>
                        <select name="language" class="form-control">
                            <option value="">All Languages</option>
                            <?php foreach ($languages as $language): ?>
                                <?php if (!empty($language['languages'])): ?>
                                    <option value="<?= htmlspecialchars($language['languages']) ?>" 
                                        <?= $searchLanguage == $language['languages'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($language['languages']) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8 mb-3 text-right">
                        <a href="doctorlist.php" class="btn btn-outline-secondary mt-4">
                            <i class="fas fa-sync-alt mr-1"></i> Reset Filters
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Doctors List -->
        <div class="row">
            <?php if (empty($doctors)): ?>
                <div class="col-12">
                    <div class="card shadow">
                        <div class="empty-state">
                            <i class="fas fa-user-md"></i>
                            <h4 class="text-muted">No doctors found</h4>
                            <p class="text-muted">Try adjusting your search filters</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($doctors as $doctor): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="doctor-card shadow">
                            <div class="card-body text-center">
                                <div class="doctor-avatar">
                                    <?= strtoupper(substr($doctor['name'], 0, 1)) ?>
                                </div>
                                <h4>Dr. <?= htmlspecialchars($doctor['name']) ?></h4>
                                <p class="doctor-specialty mb-2">
                                    <i class="fas fa-stethoscope mr-1"></i>
                                    <?= htmlspecialchars($doctor['specialty']) ?>
                                </p>
                                
                                <div class="d-flex justify-content-center mb-3">
                                    <div class="doctor-rating mr-3">
                                        <i class="fas fa-star"></i> <?= number_format($doctor['rating'], 1) ?>
                                    </div>
                                    <div class="doctor-fee">
                                        <i class="fas fa-money-bill-wave"></i> $<?= number_format($doctor['consultation_fee'], 2) ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($doctor['languages'])): ?>
                                    <div class="mb-3">
                                        <?php $langs = explode(',', $doctor['languages']); ?>
                                        <?php foreach ($langs as $lang): ?>
                                            <span class="badge-language"><?= trim($lang) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="mb-3">
                                    <i class="fas fa-map-marker-alt text-muted mr-1"></i>
                                    <?= htmlspecialchars($doctor['location'] ?? 'Location not specified') ?>
                                </p>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="doctorprofile.php?id=<?= $doctor['id'] ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye mr-1"></i> View Profile
                                    </a>
                                    <a href="patientlogin.php?redirect=book&doctor=<?= $doctor['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-calendar-plus mr-1"></i> Book Appointment
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>