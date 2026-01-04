<?php
/**
 * Simple QR Code Generator
 * Uses Google Charts API as a fallback (no dependencies required)
 * For production, install: composer require endroid/qr-code
 */

class QRCodeGenerator {
    private $size;
    private $margin;
    
    public function __construct($size = 200, $margin = 10) {
        $this->size = $size;
        $this->margin = $margin;
    }
    
    /**
     * Generate QR code and save to file
     * Uses Google Charts API (online) or local library if available
     */
    public function generate($data, $outputPath = null) {
        // Try using endroid/qr-code if installed
        if (class_exists('Endroid\QrCode\QrCode')) {
            return $this->generateWithEndroid($data, $outputPath);
        }
        
        // Fallback to Google Charts API
        return $this->generateWithGoogleAPI($data, $outputPath);
    }
    
    /**
     * Generate using endroid/qr-code library
     */
    private function generateWithEndroid($data, $outputPath) {
        $qrCode = new \Endroid\QrCode\QrCode($data);
        $qrCode->setSize($this->size);
        $qrCode->setMargin($this->margin);
        
        if ($outputPath) {
            $qrCode->writeFile($outputPath);
            return $outputPath;
        }
        
        return $qrCode->getDataUri();
    }
    
    /**
     * Generate using Google Charts API (online, no dependencies)
     */
    private function generateWithGoogleAPI($data, $outputPath) {
        $encodedData = urlencode($data);
        $url = "https://chart.googleapis.com/chart?chs={$this->size}x{$this->size}&cht=qr&chl={$encodedData}&choe=UTF-8";
        
        $imageData = @file_get_contents($url);
        
        if ($imageData === false) {
            // Try alternative API
            $url = "https://api.qrserver.com/v1/create-qr-code/?size={$this->size}x{$this->size}&data={$encodedData}";
            $imageData = @file_get_contents($url);
        }
        
        if ($imageData && $outputPath) {
            file_put_contents($outputPath, $imageData);
            return $outputPath;
        }
        
        return $imageData ? 'data:image/png;base64,' . base64_encode($imageData) : false;
    }
    
    /**
     * Generate QR code as base64 data URI
     */
    public function getDataUri($data) {
        return $this->generate($data, null);
    }
    
    /**
     * Batch generate QR codes for multiple items
     */
    public function batchGenerate($items, $outputDir) {
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }
        
        $results = [];
        foreach ($items as $item) {
            $filename = 'qr_' . $item['id'] . '_' . time() . '.png';
            $outputPath = $outputDir . '/' . $filename;
            
            $data = $item['qr_data'] ?? APP_URL . '/public/inventory/view.php?id=' . $item['id'];
            
            if ($this->generate($data, $outputPath)) {
                $results[] = [
                    'id' => $item['id'],
                    'path' => $filename,
                    'data' => $data,
                    'success' => true
                ];
            } else {
                $results[] = [
                    'id' => $item['id'],
                    'success' => false,
                    'error' => 'Failed to generate QR code'
                ];
            }
        }
        
        return $results;
    }
}
