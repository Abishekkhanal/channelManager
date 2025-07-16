<?php
session_start();
require_once '../config/config.php';

$auth = Auth::getInstance();
$auth->requireLogin();

$user = $auth->getCurrentUser();
$db = Database::getInstance();

// Get dashboard statistics
$stats = [
    'total_reservations' => $db->count('reservations'),
    'today_checkins' => $db->count('reservations', ['check_in' => date('Y-m-d')]),
    'total_revenue' => $db->fetch("SELECT SUM(total_amount) as total FROM reservations WHERE status = 'confirmed'")['total'] ?? 0,
    'active_channels' => $db->count('ota_channels', ['is_active' => 1])
];

// Get recent reservations
$recent_reservations = $db->fetchAll("
    SELECT r.*, rt.name as room_type_name, oc.name as ota_name 
    FROM reservations r
    LEFT JOIN room_types rt ON r.room_type_id = rt.id
    LEFT JOIN ota_channels oc ON r.ota_channel_id = oc.id
    ORDER BY r.created_at DESC 
    LIMIT 10
");

// Get OTA sync status
$otaManager = new OTAManager();
$channelStatus = $otaManager->getChannelStatus($user['hotel_id'] ?? 1);

// Get sync logs
$syncLogs = $otaManager->getSyncLogs($user['hotel_id'] ?? 1, 5);

$pageTitle = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@2.0.0/dist/chartjs-adapter-date-fns.bundle.min.js" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: white;
        }
        .sidebar .nav-link {
            color: #adb5bd;
            padding: 0.75rem 1rem;
            border-radius: 5px;
            margin: 0.25rem;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: #495057;
            color: white;
        }
        .main-content {
            margin-left: 0;
            padding: 2rem;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
        }
        .stats-card.success {
            border-left-color: #28a745;
        }
        .stats-card.warning {
            border-left-color: #ffc107;
        }
        .stats-card.danger {
            border-left-color: #dc3545;
        }
        .stats-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .stats-card p {
            margin: 0;
            color: #666;
        }
        .ota-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #28a745;
        }
        .status-indicator.warning {
            background: #ffc107;
        }
        .status-indicator.danger {
            background: #dc3545;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .quick-action {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            text-decoration: none;
            color: #333;
        }
        .quick-action:hover {
            transform: translateY(-2px);
            color: #333;
        }
        .quick-action i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #007bff;
        }
        .table-responsive {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar {
            background: white !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .sidebar-toggle {
            display: none;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                width: 250px;
                z-index: 1000;
                transition: left 0.3s;
            }
            .sidebar.show {
                left: 0;
            }
            .sidebar-toggle {
                display: block;
            }
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container-fluid">
            <button class="btn btn-outline-secondary sidebar-toggle" type="button" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand" href="#">
                <i class="fas fa-hotel"></i> <?php echo APP_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?php echo $user['full_name']; ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <nav class="sidebar">
                    <div class="position-sticky">
                        <div class="p-3">
                            <h5 class="text-white">Menu</h5>
                        </div>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link active" href="dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="reservations.php">
                                    <i class="fas fa-bed"></i> Reservations
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="inventory.php">
                                    <i class="fas fa-calendar"></i> Inventory
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="rates.php">
                                    <i class="fas fa-dollar-sign"></i> Rates
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="ota-channels.php">
                                    <i class="fas fa-globe"></i> OTA Channels
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="sync-logs.php">
                                    <i class="fas fa-sync"></i> Sync Logs
                                </a>
                            </li>
                            <?php if ($auth->hasRole('admin')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="hotels.php">
                                    <i class="fas fa-building"></i> Hotels
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="users.php">
                                    <i class="fas fa-users"></i> Users
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto">
                <div class="main-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1><?php echo $pageTitle; ?></h1>
                        <div>
                            <span class="text-muted">Last updated: <?php echo date('M d, Y H:i'); ?></span>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <h3><?php echo number_format($stats['total_reservations']); ?></h3>
                                <p>Total Reservations</p>
                                <small class="text-muted">
                                    <i class="fas fa-calendar"></i> All time
                                </small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card success">
                                <h3><?php echo number_format($stats['today_checkins']); ?></h3>
                                <p>Today's Check-ins</p>
                                <small class="text-muted">
                                    <i class="fas fa-calendar-day"></i> <?php echo date('M d, Y'); ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card warning">
                                <h3><?php echo formatCurrency($stats['total_revenue']); ?></h3>
                                <p>Total Revenue</p>
                                <small class="text-muted">
                                    <i class="fas fa-dollar-sign"></i> Confirmed bookings
                                </small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card danger">
                                <h3><?php echo number_format($stats['active_channels']); ?></h3>
                                <p>Active Channels</p>
                                <small class="text-muted">
                                    <i class="fas fa-globe"></i> OTA connections
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <a href="reservations.php?action=new" class="quick-action">
                            <i class="fas fa-plus"></i>
                            <h5>New Reservation</h5>
                            <p>Create a new booking</p>
                        </a>
                        <a href="inventory.php?action=bulk-update" class="quick-action">
                            <i class="fas fa-calendar-alt"></i>
                            <h5>Update Inventory</h5>
                            <p>Bulk update availability</p>
                        </a>
                        <a href="rates.php?action=bulk-update" class="quick-action">
                            <i class="fas fa-dollar-sign"></i>
                            <h5>Update Rates</h5>
                            <p>Bulk update pricing</p>
                        </a>
                        <a href="ota-channels.php?action=sync-all" class="quick-action">
                            <i class="fas fa-sync"></i>
                            <h5>Sync All OTAs</h5>
                            <p>Synchronize all channels</p>
                        </a>
                    </div>

                    <div class="row">
                        <!-- OTA Status -->
                        <div class="col-md-6">
                            <div class="table-responsive">
                                <h4>OTA Channel Status</h4>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Channel</th>
                                            <th>Status</th>
                                            <th>Last Sync</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($channelStatus as $channel => $status): ?>
                                        <tr>
                                            <td><?php echo $channel; ?></td>
                                            <td>
                                                <div class="ota-status">
                                                    <span class="status-indicator <?php echo $status['last_status'] === 'success' ? '' : 'danger'; ?>"></span>
                                                    <?php echo ucfirst($status['last_status'] ?? 'unknown'); ?>
                                                </div>
                                            </td>
                                            <td><?php echo formatDateTime($status['last_sync']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="syncChannel('<?php echo $channel; ?>')">
                                                    <i class="fas fa-sync"></i> Sync
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Recent Reservations -->
                        <div class="col-md-6">
                            <div class="table-responsive">
                                <h4>Recent Reservations</h4>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Guest</th>
                                            <th>Room Type</th>
                                            <th>Check-in</th>
                                            <th>Status</th>
                                            <th>OTA</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_reservations as $reservation): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($reservation['guest_name']); ?></td>
                                            <td><?php echo htmlspecialchars($reservation['room_type_name']); ?></td>
                                            <td><?php echo formatDate($reservation['check_in']); ?></td>
                                            <td><?php echo getStatusBadge($reservation['status']); ?></td>
                                            <td><?php echo htmlspecialchars($reservation['ota_name'] ?? 'Direct'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Sync Logs -->
                    <div class="row">
                        <div class="col-12">
                            <div class="table-responsive">
                                <h4>Recent Sync Activity</h4>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>OTA</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Message</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($syncLogs as $log): ?>
                                        <tr>
                                            <td><?php echo formatDateTime($log['created_at']); ?></td>
                                            <td><?php echo htmlspecialchars($log['ota_name']); ?></td>
                                            <td><?php echo ucfirst($log['sync_type']); ?></td>
                                            <td><?php echo getStatusBadge($log['status']); ?></td>
                                            <td><?php echo htmlspecialchars($log['message']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('show');
        }

        function syncChannel(channel) {
            if (confirm(`Are you sure you want to sync ${channel}?`)) {
                fetch('/api/sync.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ channel: channel })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Sync initiated successfully');
                        location.reload();
                    } else {
                        alert('Sync failed: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while syncing');
                });
            }
        }

        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>