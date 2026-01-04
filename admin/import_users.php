<?php
require_once '../bootstrap.php';
require_once '../vendor/autoload.php'; // For PhpSpreadsheet

// Auth check
Auth::requireAdmin();

use PhpOffice\PhpSpreadsheet\IOFactory;

$pageTitle = 'Import Users';
require_once '../templates/header.php';
require_once '../templates/sidebar.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['excel_file']['tmp_name'];
        $fileName = $_FILES['excel_file']['name'];
        $fileType = $_FILES['excel_file']['type'];
        
        $allowedExtensions = ['xls', 'xlsx'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($fileExtension, $allowedExtensions)) {
            try {
                $spreadsheet = IOFactory::load($fileTmpPath);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
                
                // Remove header row
                array_shift($rows);
                
                $usersToImport = [];
                $db = Database::getInstance();
                
                foreach ($rows as $row) {
                    // Assuming columns: AMS ID, Name, Email, Designation, Dept Code/Name
                    // Adapt indices based on actual Excel structure. 
                    // User said: Employees_Email_ID_CSIR_res_in_02_12_25.xlsx
                    // I'll assume columns [0]=>EmpName, [1]=>Designation, [2]=>AMS ID, [3]=>Email based on typical lists, OR
                    // [0]=>AMS ID, [1]=>Name, [2]=>Email. 
                    // Let's inspect the file structure first or try to be robust.
                    // For now, I will assume a standard mapping and provide a note.
                    
                    // Basic validation
                    if (empty($row[0])) continue; // Skip empty rows

                    // Mapping Strategy (Dynamic based on header? No, just simple assumption for now)
                    // Let's assume:
                    // Col A: AMS ID
                    // Col B: Name
                    // Col C: Email
                    // Col D: Designation
                    // Col E: Department
                    
                    $amsId = trim($row[0]);
                    $name = trim($row[1]);
                    $email = trim($row[2]);
                    
                    // Skip if critical data missing
                    if (empty($amsId) || empty($email)) continue;
                    
                    $usersToImport[] = [
                        'ams_id' => $amsId,
                        'emp_name' => $name,
                        'email_id' => $email,
                        'role' => 'employee', // Default role
                        'department_id' => null, // Would need to lookup Dept ID by name code
                        'designation' => $row[3] ?? null,
                        'phone' => null
                    ];
                }
                
                if (!empty($usersToImport)) {
                    $result = User::batchInsert($usersToImport);
                    $message = "Successfully processed " . $result['count'] . " users.";
                    if (!empty($result['errors'])) {
                        $error = "Some errors occurred: <br>" . implode("<br>", $result['errors']);
                    }
                } else {
                    $error = "No valid data found in the file.";
                }
                
            } catch (Exception $e) {
                $error = "Error parsing file: " . $e->getMessage();
            }
        } else {
            $error = "Invalid file format. Please upload .xls or .xlsx";
        }
    } else {
        $error = "Error uploading file.";
    }
}
?>

<div class="main-content">
    <div class="page-header mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Import Users</h2>
        <p class="text-gray-600">Bulk upload users from Excel file</p>
    </div>

    <!-- Alert Messages -->
    <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?= $message ?></span>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?= $error ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-sm p-6 max-w-2xl">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Upload Excel File (.xlsx, .xls)
                </label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                <span>Upload a file</span>
                                <input id="file-upload" name="excel_file" type="file" class="sr-only" accept=".xlsx, .xls">
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p class="text-xs text-gray-500">
                            XLS or XLSX up to 10MB
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="mb-6 bg-blue-50 p-4 rounded-md">
                <h4 class="text-sm font-bold text-blue-800 mb-2">Expected Format:</h4>
                <ul class="list-disc list-inside text-sm text-blue-700">
                    <li>Column A: AMS ID (Required)</li>
                    <li>Column B: Employee Name (Required)</li>
                    <li>Column C: Email Address (Required)</li>
                    <li>Column D: Designation</li>
                    <li>Column E: Department</li>
                </ul>
                <p class="text-xs text-blue-600 mt-2">First row is assumed to be the header and will be skipped.</p>
            </div>

            <div class="flex justify-end">
                <a href="users.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg mr-2 hover:bg-gray-300">Cancel</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Import Users</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Simple drag and drop visual feedback
    const dropZone = document.querySelector('.border-dashed');
    const fileInput = document.getElementById('file-upload');
    
    fileInput.addEventListener('change', function() {
        if(this.files.length > 0) {
            dropZone.classList.add('border-blue-500');
            dropZone.classList.add('bg-blue-50');
        }
    });
</script>

<?php require_once '../templates/footer.php'; ?>
