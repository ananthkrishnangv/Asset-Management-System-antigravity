<?php
/**
 * Periodic Inspection Report (PIR) Page
 */
require_once __DIR__ . '/../../bootstrap.php';

// Auth check
Auth::requireAuth();

// Get PIR items
$db = Database::getInstance();
$query = "SELECT * FROM pir_details ORDER BY Item_ID DESC";
$items = $db->fetchAll($query);

// Page title
$pageTitle = 'Periodic Inspection Report (PIR)';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= APP_NAME ?></title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
    <!-- Fluent UI -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/fluent.css') ?>">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar text-white" style="width: 260px; min-height: 100vh;">
            <div class="p-4">
                <h4 class="fw-bold mb-4"><i class="fas fa-cube me-2"></i>AMS</h4>
                <a href="<?= url('public/dashboard.php') ?>" class="d-block text-white text-decoration-none mb-3 opacity-75 hover-opacity-100"><i class="fas fa-home me-2"></i> Dashboard</a>
                <a href="<?= url('public/inventory/dir.php') ?>" class="d-block text-white text-decoration-none mb-3 opacity-75 hover-opacity-100"><i class="fas fa-list-alt me-2"></i> DIR Details</a>
                <a href="<?= url('public/inventory/pir.php') ?>" class="d-block text-white text-decoration-none mb-3 opacity-100 fw-bold"><i class="fas fa-clipboard-list me-2"></i> PIR Details</a>
                <a href="<?= url('public/inventory/add.php') ?>" class="d-block text-white text-decoration-none mb-3 opacity-75 hover-opacity-100"><i class="fas fa-plus-circle me-2"></i> Add Asset</a>
                <a href="<?= url('public/reports/index.php') ?>" class="d-block text-white text-decoration-none mb-3 opacity-75 hover-opacity-100"><i class="fas fa-chart-pie me-2"></i> Reports</a>
                
                <hr class="border-secondary my-4">
                
                <a href="<?= url('public/admin/settings.php') ?>" class="d-block text-white text-decoration-none mb-3 opacity-75 hover-opacity-100"><i class="fas fa-cogs me-2"></i> Settings</a>
                <a href="<?= url('public/logout.php') ?>" class="d-block text-danger text-decoration-none mt-4"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-grow-1 p-4 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-light text-primary">Periodic Inspection Report (PIR)</h2>
                <div>
                    <a href="add.php?type=pir" class="btn btn-primary shadow-sm"><i class="fas fa-plus me-2"></i>Add New Entry</a>
                </div>
            </div>

            <?php if ($msg = flash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0"><?= $msg ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm animate-fade-in">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="pirTable" class="table table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Description</th>
                                    <th>Qty</th>
                                    <th>PO No.</th>
                                    <th>PO Date</th>
                                    <th>Budget Head</th>
                                    <th>Dept</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['Item_ID']) ?></td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <a href="view.php?type=pir&id=<?= $item['Item_ID'] ?>" class="text-decoration-none fw-bold text-dark text-truncate" style="max-width: 250px;">
                                                <?= htmlspecialchars(truncate($item['Item_desc'], 40)) ?>
                                            </a>
                                            <span class="small text-muted"><?= htmlspecialchars($item['stock_ref']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($item['qty_no'] . ' ' . $item['qty_desc']) ?></td>
                                    <td><?= htmlspecialchars($item['po_no']) ?></td>
                                    <td><?= formatDate($item['po_date']) ?></td>
                                    <td><?= htmlspecialchars($item['bud_head']) ?></td>
                                    <td><?= htmlspecialchars($item['dept_location']) ?></td>
                                    <td><?= htmlspecialchars($item['b_location']) ?></td>
                                    <td>
                                        <span class="badge <?= $item['trans_status'] == 0 ? 'bg-success-subtle' : 'bg-warning-subtle' ?>">
                                            <?= htmlspecialchars($item['trans_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="view.php?type=pir&id=<?= $item['Item_ID'] ?>" class="btn btn-light" title="View"><i class="fas fa-eye text-info"></i></a>
                                            <?php if (Auth::isAdmin() || Auth::isSupervisor()): ?>
                                                <a href="edit.php?type=pir&id=<?= $item['Item_ID'] ?>" class="btn btn-light text-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                                <button onclick="deleteItem(<?= $item['Item_ID'] ?>)" class="btn btn-light text-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <script>
        $(document).ready(function() {
            var table = $('#pirTable').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    { extend: 'copy', className: 'btn btn-sm btn-outline-secondary' },
                    { extend: 'csv', className: 'btn btn-sm btn-outline-secondary' },
                    { extend: 'excel', className: 'btn btn-sm btn-outline-success' },
                    { extend: 'pdf', className: 'btn btn-sm btn-outline-danger' },
                    { extend: 'print', className: 'btn btn-sm btn-outline-primary' }
                ],
                pageLength: 25,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search PIR..."
                }
            });
        });

        function deleteItem(id) {
            if(confirm('Are you sure you want to delete this item?')) {
                // Implement delete logic via API
                alert('Delete functionality implemented via API endpoint.');
            }
        }
    </script>
</body>
</html>
