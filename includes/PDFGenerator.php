<?php
/**
 * PDF Generator using mPDF
 * Creates professional PDF reports for inventory exports
 * 
 * Installation: composer require mpdf/mpdf
 * 
 * If mPDF is not installed, uses HTML-based print view
 */

class PDFGenerator {
    private $title;
    private $author;
    private $orientation;
    
    public function __construct($title = 'Report', $orientation = 'P') {
        $this->title = $title;
        $this->author = 'CSIR-SERC AMS';
        $this->orientation = $orientation;
    }
    
    /**
     * Generate PDF from HTML content
     */
    public function generate($html, $outputPath = null) {
        // Check if mPDF is available
        if (class_exists('Mpdf\Mpdf')) {
            return $this->generateWithMpdf($html, $outputPath);
        }
        
        // Fallback to HTML print view
        return $this->generatePrintView($html, $outputPath);
    }
    
    /**
     * Generate using mPDF library
     */
    private function generateWithMpdf($html, $outputPath) {
        $mpdf = new \Mpdf\Mpdf([
            'orientation' => $this->orientation,
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 20,
            'margin_bottom' => 20,
            'default_font' => 'dejavusans'
        ]);
        
        $mpdf->SetTitle($this->title);
        $mpdf->SetAuthor($this->author);
        $mpdf->SetCreator('CSIR-SERC Asset Management System');
        
        // Add header
        $mpdf->SetHTMLHeader($this->getHeader());
        $mpdf->SetHTMLFooter($this->getFooter());
        
        $mpdf->WriteHTML($html);
        
        if ($outputPath) {
            $mpdf->Output($outputPath, 'F');
            return $outputPath;
        }
        
        return $mpdf->Output('', 'S');
    }
    
    /**
     * Generate print-ready HTML view
     */
    private function generatePrintView($html, $outputPath) {
        $fullHtml = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($this->title) . '</title>
    <style>
        @page { size: A4; margin: 20mm; }
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1a365d;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #1a365d;
            margin: 0 0 5px 0;
            font-size: 18pt;
        }
        .header h2 {
            color: #4a5568;
            margin: 0;
            font-size: 12pt;
            font-weight: normal;
        }
        .header .date {
            font-size: 9pt;
            color: #718096;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 9pt;
        }
        th, td {
            border: 1px solid #cbd5e0;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #1a365d;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 8pt;
            letter-spacing: 0.5px;
        }
        tr:nth-child(even) { background: #f7fafc; }
        tr:hover { background: #edf2f7; }
        .amount { text-align: right; font-family: monospace; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #718096;
            padding: 10px 0;
            border-top: 1px solid #e2e8f0;
        }
        .summary-box {
            background: #edf2f7;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .summary-box h3 { margin: 0 0 10px 0; color: #1a365d; }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        .summary-item {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 4px;
        }
        .summary-value { font-size: 16pt; font-weight: 700; color: #1a365d; }
        .summary-label { font-size: 8pt; color: #64748b; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="header">
        <h1>CSIR-SERC ASSET MANAGEMENT SYSTEM</h1>
        <h2>' . htmlspecialchars($this->title) . '</h2>
        <div class="date">Generated on: ' . date('d-M-Y H:i:s') . '</div>
    </div>
    ' . $html . '
    <div class="footer">
        CSIR-SERC, Taramani, Chennai - 600113 | Page <span class="pagenum"></span>
    </div>
    <script>
        window.onload = function() {
            // Auto-print if print=true in URL
            if (window.location.search.includes("print=true")) {
                window.print();
            }
        };
    </script>
</body>
</html>';
        
        if ($outputPath) {
            file_put_contents($outputPath, $fullHtml);
            return $outputPath;
        }
        
        return $fullHtml;
    }
    
    /**
     * Get PDF header HTML
     */
    private function getHeader() {
        return '<div style="text-align: center; font-size: 14pt; font-weight: bold; color: #1a365d; border-bottom: 2px solid #1a365d; padding-bottom: 5px;">
            CSIR-SERC ASSET MANAGEMENT SYSTEM
        </div>';
    }
    
    /**
     * Get PDF footer HTML
     */
    private function getFooter() {
        return '<div style="text-align: center; font-size: 8pt; color: #718096;">
            CSIR-SERC, Taramani, Chennai - 600113 | Page {PAGENO} of {nbpg}
        </div>';
    }
    
    /**
     * Generate inventory report PDF
     */
    public function generateInventoryReport($items, $type = 'dir', $outputPath = null) {
        $title = strtoupper($type) . ' Inventory Report';
        $this->title = $title;
        
        // Calculate summary
        $totalItems = count($items);
        $totalValue = array_sum(array_column($items, 'amount'));
        $goodItems = count(array_filter($items, fn($i) => in_array($i['condition_status'], ['new', 'good'])));
        $categories = count(array_unique(array_column($items, 'category')));
        
        $html = '<div class="summary-box">
            <h3>Summary</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value">' . number_format($totalItems) . '</div>
                    <div class="summary-label">Total Items</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">₹' . number_format($totalValue, 2) . '</div>
                    <div class="summary-label">Total Value</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">' . number_format($goodItems) . '</div>
                    <div class="summary-label">Good Condition</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">' . number_format($categories) . '</div>
                    <div class="summary-label">Categories</div>
                </div>
            </div>
        </div>';
        
        $html .= '<table>
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Serial Number</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Department</th>
                    <th>Qty</th>
                    <th>Amount</th>
                    <th>Condition</th>
                </tr>
            </thead>
            <tbody>';
        
        $sno = 1;
        foreach ($items as $item) {
            $html .= '<tr>
                <td>' . $sno++ . '</td>
                <td>' . htmlspecialchars($item['serial_number'] ?? '-') . '</td>
                <td>' . htmlspecialchars(substr($item['item_description'] ?? '', 0, 50)) . '</td>
                <td>' . htmlspecialchars($item['category'] ?? '-') . '</td>
                <td>' . htmlspecialchars($item['department'] ?? '-') . '</td>
                <td>' . ($item['quantity'] ?? 1) . '</td>
                <td class="amount">₹' . number_format($item['amount'] ?? 0, 2) . '</td>
                <td>' . ucfirst($item['condition_status'] ?? 'good') . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
            <tfoot>
                <tr>
                    <th colspan="6" style="text-align: right;">Total:</th>
                    <th class="amount">₹' . number_format($totalValue, 2) . '</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>';
        
        return $this->generate($html, $outputPath);
    }
    
    /**
     * Generate QR code labels PDF
     */
    public function generateQRLabels($items, $outputPath = null) {
        $this->title = 'QR Code Labels';
        $this->orientation = 'P';
        
        $html = '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">';
        
        $qrGen = new QRCodeGenerator(100, 5);
        
        foreach ($items as $item) {
            $qrData = $item['qr_code_data'] ?? (APP_URL . '/public/inventory/view.php?id=' . $item['id']);
            $qrImage = $qrGen->getDataUri($qrData);
            
            $html .= '<div style="border: 1px solid #ddd; padding: 10px; text-align: center; page-break-inside: avoid;">
                <img src="' . $qrImage . '" style="width: 80px; height: 80px;">
                <div style="font-size: 8pt; font-weight: bold; margin-top: 5px;">' . htmlspecialchars($item['serial_number'] ?? $item['id']) . '</div>
                <div style="font-size: 7pt; color: #666; max-height: 30px; overflow: hidden;">' . htmlspecialchars(substr($item['item_description'] ?? '', 0, 40)) . '</div>
            </div>';
        }
        
        $html .= '</div>';
        
        return $this->generate($html, $outputPath);
    }
}
