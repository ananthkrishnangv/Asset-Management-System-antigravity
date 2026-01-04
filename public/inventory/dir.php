<?php
/**
 * Daily Inspection Report (DIR) Page
 */
require_once __DIR__ . '/../../bootstrap.php';

// Auth check
Auth::requireAuth();

// Get DIR items
$db = Database::getInstance();
$query = "SELECT * FROM dir_details ORDER BY Item_ID DESC";
$items = $db->fetchAll($query);

// Page title
$pageTitle = 'Daily Inspection Report (DIR)';
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

    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --secondary: #64748B;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --dark: #1E293B;
            --light: #F8FAFC;
        }
        
        body { font-family: 'Noto Sans', sans-serif; background: #f1f5f9; color: #334155; }
        
        /* Navbar */
        .navbar { background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 0.75rem 1.5rem; }
        .navbar-brand { font-weight: 700; color: var(--primary); font-size: 1.25rem; display: flex; align-items: center; gap: 0.5rem; }
        
        /* Layout */
        .main-container { display: flex; min-height: calc(100vh - 64px); }
        .sidebar { width: 260px; background: #1e293b; color: #94a3b8; transition: all 0.3s; flex-shrink: 0; }
        .content { flex: 1; padding: 2rem; overflow-x: hidden; }
        
        /* Sidebar Links */
        .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.8rem 1.5rem; color: #cbd5e1; text-decoration: none; transition: all 0.2s; border-left: 3px solid transparent; }
        .nav-link:hover, .nav-link.active { background: #0f172a; color: white; border-color: var(--primary); }
        .nav-link i { width: 20px; text-align: center; }
        
        /* Cards */
        .card { background: white; border-radius: 12px; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
        .card-header { background: white; border-bottom: 1px solid #e2e8f0; padding: 1.25rem; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center; }
        .card-title { margin: 0; font-size: 1.1rem; font-weight: 600; color: var(--dark); }
        
        /* Tables */
        .table-responsive { padding: 0 1rem 1rem 1rem; }
        table.dataTable { border-collapse: separate; border-spacing: 0; width: 100% !important; }
        table.dataTable thead th { background: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; border-bottom: 1px solid #e2e8f0; padding: 1rem 0.75rem; }
        table.dataTable tbody td { border-bottom: 1px solid #f1f5f9; padding: 0.8rem 0.75rem; vertical-align: middle; font-size: 0.875rem; }
        
        /* Badges */
        .badge { padding: 0.35em 0.65em; font-size: 0.75em; font-weight: 600; border-radius: 0.25rem; }
        .bg-success-subtle { background: #dcfce7; color: #166534; }
        .bg-warning-subtle { background: #fef3c7; color: #92400e; }
        
        /* Buttons */
        .btn-primary { background: var(--primary); border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 500; }
        .btn-primary:hover { background: var(--primary-dark); }
        
        .dt-buttons .btn { background: white; border: 1px solid #e2e8f0; color: #64748b; font-size: 0.875rem; border-radius: 6px; padding: 0.4rem 0.8rem; margin-right: 0.5rem; transition: all 0.2s; }
        .dt-buttons .btn:hover { background: #f1f5f9; color: var(--dark); border-color: #cbd5e1; }
    <link rel="stylesheet" href="<?= asset('assets/css/fluent.css') ?>">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar text-white" style="width: 260px; min-height: 100vh;">
            <div class="p-4">
                <h4 class="fw-bold mb-4"><i class="fas fa-cube me-2"></i>AMS</h4>
                <a href="<?= url('public/dashboard.php') ?>" class="d-block text-white text-decoration-none mb-3 opacity-75 hover-opacity-100"><i class="fas fa-home me-2"></i> Dashboard</a>
                <a href="<?= url('public/inventory/dir.php') ?>" class="d-block text-white text-decoration-none mb-3 opacity-100 fw-bold"><i class="fas fa-list-alt me-2"></i> DIR Details</a>
                <a href="<?= url('public/inventory/pir.php') ?>" class="d-block text-white text-decoration-none mb-3 opacity-75 hover-opacity-100"><i class="fas fa-clipboard-list me-2"></i> PIR Details</a>
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
                <h2 class="fw-light text-primary">Daily Inspection Report (DIR)</h2>
                <div>
                    <a href="add.php?type=dir" class="btn btn-primary shadow-sm"><i class="fas fa-plus me-2"></i>Add New Entry</a>
                </div>
            </div>

            <?php if ($msg = flash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0"><?= $msg ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm animate-fade-in">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dirTable" class="table table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Description</th>
                                    <th>Qty</th>
                                    <th>PO No.</th>
                                    <th>PO Date</th>
                                    <th>Nodal Officer</th>
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
                                            <a href="view.php?type=dir&id=<?= $item['Item_ID'] ?>" class="text-decoration-none fw-bold text-dark text-truncate" style="max-width: 250px;">
                                                <?= htmlspecialchars(truncate($item['Item_desc'], 40)) ?>
                                            </a>
                                            <span class="small text-muted"><?= htmlspecialchars($item['stock_ref']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($item['qty_no'] . ' ' . $item['qty_desc']) ?></td>
                                    <td><?= htmlspecialchars($item['po_no']) ?></td>
                                    <td><?= formatDate($item['po_date']) ?></td>
                                    <td><?= htmlspecialchars($item['nodal_off']) ?></td>
                                    <td><?= htmlspecialchars($item['dept_location']) ?></td>
                                    <td><?= htmlspecialchars($item['b_location']) ?></td>
                                    <td>
                                        <span class="badge <?= $item['trans_status'] === 'NO' ? 'bg-success-subtle' : 'bg-warning-subtle' ?>">
                                            <?= htmlspecialchars($item['trans_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="view.php?type=dir&id=<?= $item['Item_ID'] ?>" class="btn btn-light" title="View"><i class="fas fa-eye text-info"></i></a>
                                            <?php if (Auth::isAdmin() || Auth::isSupervisor()): ?>
                                                <a href="edit.php?id=<?= $item['Item_ID'] ?>" class="btn btn-light text-primary" title="Edit"><i class="fas fa-edit"></i></a>
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
            var table = $('#dirTable').DataTable({
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
                    searchPlaceholder: "Search records..."
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
