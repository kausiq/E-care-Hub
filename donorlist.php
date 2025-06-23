<?php
require_once 'connection.php';

// Initialize blood type filter
$selectedBloodType = isset($_GET['blood_type']) ? $_GET['blood_type'] : '';

// Prepare the SQL query based on filter
if (!empty($selectedBloodType)) {
    $sql = "SELECT id, full_name, email, phone, blood_type, address, last_donation_date 
            FROM donors 
            WHERE blood_type = :blood_type 
            AND can_donate = 1 
            AND is_verified = 1 
            ORDER BY last_donation_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['blood_type' => $selectedBloodType]);
} else {
    $sql = "SELECT id, full_name, email, phone, blood_type, address, last_donation_date 
            FROM donors 
            WHERE can_donate = 1 
            AND is_verified = 1 
            ORDER BY last_donation_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

$donors = $stmt->fetchAll();

// Get all available blood types for the filter dropdown
$bloodTypesQuery = "SELECT DISTINCT blood_type FROM donors WHERE can_donate = 1 AND is_verified = 1";
$bloodTypesStmt = $pdo->prepare($bloodTypesQuery);
$bloodTypesStmt->execute();
$bloodTypes = $bloodTypesStmt->fetchAll(PDO::FETCH_COLUMN);

// Get counts for each blood type
$bloodTypeCountsQuery = "SELECT blood_type, COUNT(*) as count FROM donors 
                         WHERE can_donate = 1 AND is_verified = 1 
                         GROUP BY blood_type";
$countStmt = $pdo->prepare($bloodTypeCountsQuery);
$countStmt->execute();
$bloodTypeCounts = [];
while ($row = $countStmt->fetch()) {
    $bloodTypeCounts[$row['blood_type']] = $row['count'];
}

// Function to get blood type badge class
function getBloodTypeClass($bloodType) {
    $type = strtolower(str_replace('+', '', str_replace('-', '', $bloodType)));
    if (strpos($type, 'a') !== false && strpos($type, 'b') !== false) {
        return 'blood-ab';
    } elseif (strpos($type, 'a') !== false) {
        return 'blood-a';
    } elseif (strpos($type, 'b') !== false) {
        return 'blood-b';
    } elseif (strpos($type, 'o') !== false) {
        return 'blood-o';
    }
    return '';
}

// Function to format date
function formatDonationDate($date) {
    return date('F j, Y', strtotime($date));
}

// Get total donor count
$totalDonors = count($donors);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Donors Directory | E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/donorlist.css">
</head>
<body>
    <div class="main-container">
        <div class="header text-center">
            <h1 class="page-title">
                <i class="bi bi-droplet-fill blood-icon"></i>
                Blood Donors Directory
            </h1>
            <p class="page-subtitle">Find verified blood donors who are ready to help in emergency situations</p>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card h-100 text-center p-3 bg-light">
                    <h5>Total Available Donors</h5>
                    <h2 class="text-danger"><?= $totalDonors ?></h2>
                </div>
            </div>
            
            <?php foreach ($bloodTypes as $type): ?>
                <?php if (!empty($type)): ?>
                <div class="col-md-3 col-6 mb-3">
                    <div class="card h-100 text-center p-3">
                        <h5>Blood Type <?= htmlspecialchars($type) ?></h5>
                        <h2 class="<?= getBloodTypeClass($type) ?> text-white badge mx-auto">
                            <?= isset($bloodTypeCounts[$type]) ? $bloodTypeCounts[$type] : 0 ?>
                        </h2>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <div class="filter-section">
            <form method="GET" action="" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <h5 class="filter-title">
                        <i class="bi bi-funnel me-2"></i>
                        Filter by Blood Type
                    </h5>
                </div>
                <div class="col-md-5">
                    <select name="blood_type" id="blood_type" class="form-select">
                        <option value="">All Blood Types</option>
                        <?php foreach ($bloodTypes as $type): ?>
                            <?php if (!empty($type)): ?>
                            <option value="<?= htmlspecialchars($type) ?>" 
                                <?= $selectedBloodType === $type ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type) ?> 
                                (<?= isset($bloodTypeCounts[$type]) ? $bloodTypeCounts[$type] : 0 ?> donors)
                            </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-danger filter-btn w-100">
                        <i class="bi bi-search me-2"></i> Find Donors
                    </button>
                </div>
            </form>
        </div>

        <?php if (!empty($selectedBloodType)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle-fill me-2"></i>
                Showing donors with blood type: <strong><?= htmlspecialchars($selectedBloodType) ?></strong>
                <a href="donorlist.php" class="float-end text-decoration-none">
                    <i class="bi bi-x-circle"></i> Clear Filter
                </a>
            </div>
        <?php endif; ?>

        <?php if (count($donors) > 0): ?>
            <div class="row">
                <?php foreach ($donors as $donor): ?>
                    <div class="col-md-6">
                        <div class="card donor-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="donor-name">
                                        <?= htmlspecialchars($donor['full_name']) ?>
                                        <span class="verified-badge">
                                            <i class="bi bi-patch-check-fill"></i>
                                        </span>
                                    </div>
                                    <span class="badge <?= getBloodTypeClass($donor['blood_type']) ?> blood-type-badge">
                                        <?= htmlspecialchars($donor['blood_type']) ?>
                                    </span>
                                </div>
                                
                                <div class="section-divider"></div>
                                
                                <div class="donor-info">
                                    <p>
                                        <i class="bi bi-telephone-fill"></i>
                                        <strong>Phone:</strong> <?= htmlspecialchars($donor['phone']) ?>
                                    </p>
                                    <p>
                                        <i class="bi bi-envelope-fill"></i>
                                        <strong>Email:</strong> <?= htmlspecialchars($donor['email']) ?>
                                    </p>
                                    <p>
                                        <i class="bi bi-geo-alt-fill"></i>
                                        <strong>Location:</strong> <?= htmlspecialchars($donor['address']) ?>
                                    </p>
                                    <?php if (!empty($donor['last_donation_date'])): ?>
                                    <p>
                                        <i class="bi bi-calendar-check"></i>
                                        <strong>Last Donated:</strong> <?= formatDonationDate($donor['last_donation_date']) ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="donor-actions">
                                    <a href="tel:<?= htmlspecialchars($donor['phone']) ?>" class="btn btn-success">
                                        <i class="bi bi-telephone-fill"></i> Call
                                    </a>
                                    <a href="sms:<?= htmlspecialchars($donor['phone']) ?>" class="btn btn-info text-white">
                                        <i class="bi bi-chat-dots-fill"></i> SMS
                                    </a>
                                    <a href="mailto:<?= htmlspecialchars($donor['email']) ?>" class="btn btn-primary">
                                        <i class="bi bi-envelope-fill"></i> Email
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-donors">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <h3>No donors found</h3>
                    <?php if (!empty($selectedBloodType)): ?>
                        <p>No donors with blood type <?= htmlspecialchars($selectedBloodType) ?> are currently available in our database.</p>
                        <p><a href="donorlist.php" class="btn btn-outline-primary">View All Donors</a></p>
                    <?php else: ?>
                        <p>There are no verified donors available at the moment. Please check back later or contact the hospital directly.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
         <div class="text-center mt-5 mb-4">
            <div class="d-flex justify-content-center gap-3">
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="bi bi-house-door-fill"></i> Back to Home
                </a>
                <a href="emergencyservices.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Emergency Services
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>