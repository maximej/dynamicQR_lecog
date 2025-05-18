<?php
namespace App\Controllers;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;

class QrCodeController
{
    public function showForm($request = null, $response = null, $args = [])
    {
        $successMessage = $_SESSION['success'] ?? '';
        $errorMessage = $_SESSION['error'] ?? '';
        unset($_SESSION['error']);

        $formHtml = '<div class="container mt-5 d-flex justify-content-center align-items-center min-vh-80">
            <div class="w-100 d-flex justify-content-center">
                <div class="max-width-400 w-100">
                    <h1 class="text-center">QR Code Generator</h1>';

        $formHtml .= '<form method="POST" action="/generate.php" enctype="multipart/form-data">
            <div class="card mt-4 w-100">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#contentCard" aria-expanded="true" aria-controls="contentCard">
                    <span><i class="fas fa-edit"></i> QR Code Content</span>
                    <i class="fas fa-chevron-down ms-auto"></i>
                </div>
                <div id="contentCard" class="collapse show">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="url" class="form-label">Enter URL or Text:</label>
                        <input type="text" id="url" name="url" class="form-control" required>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" id="dynamic" name="dynamic" class="form-check-input">
                        <label for="dynamic" class="form-check-label">Dynamic QR Code (Redirected)</label>
                    </div>
                </div>
                </div>
            </div>

            <div class="card mt-4 w-100">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#paramsCard" aria-expanded="false" aria-controls="paramsCard">
                    <span><i class="fas fa-cogs"></i> QR Code Parameters</span>
                    <i class="fas fa-chevron-down ms-auto"></i>
                </div>
                <div id="paramsCard" class="collapse">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="size" class="form-label">QR Code Size (px):</label>
                            <input type="number" id="size" name="size" class="form-control" value="300" min="100" max="1000" required>
                        </div>
                        <div class="col-md-6">
                            <label for="margin" class="form-label">Margin (px):</label>
                            <input type="number" id="margin" name="margin" class="form-control" value="10" min="0" max="50" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="foreground_color" class="form-label">Foreground Color:</label>
                            <input type="color" id="foreground_color" name="foreground_color" class="form-control form-control-color" value="#000000" title="Choose QR code color">
                        </div>
                        <div class="col-md-6">
                            <label for="background_color" class="form-label">Background Color:</label>
                            <input type="color" id="background_color" name="background_color" class="form-control form-control-color" value="#FFFFFF" title="Choose background color">
                        </div>
                    </div>
                </div>
                </div>
            </div>

            <div class="card mt-4 w-100">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#logoCard" aria-expanded="false" aria-controls="logoCard">
                    <span><i class="fas fa-image"></i> Logo Options</span>
                    <i class="fas fa-chevron-down ms-auto"></i>
                </div>
                <div id="logoCard" class="collapse">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="logo" class="form-label">Upload Logo (PNG/JPG, max 200KB):</label>
                            <input type="file" id="logo" name="logo" class="form-control" accept="image/png,image/jpeg">
                        </div>
                        <div class="col-md-6">
                            <label for="logo_size" class="form-label">Logo Size (% of QR code):</label>
                            <input type="range" id="logo_size" name="logo_size" class="form-range" min="10" max="30" value="20" oninput="document.getElementById(\'logoSizeValue\').innerText = this.value + \'%\'">
                            <span id="logoSizeValue">20%</span>
                        </div>
                    </div>
                </div>
                </div>
            </div>

            <div class="mt-4 text-center">
                <button type="submit" class="btn btn-success w-100">
                    <i class="fas fa-qrcode"></i> Generate QR Code
                </button>
            </div>
        </form>';
        // Add icon preview script and fix collapse toggling
        $formHtml .= '<script>
        // Use Bootstrap collapse API for toggling
        document.querySelectorAll(".card-header[data-bs-toggle=collapse]").forEach(function(header) {
            header.addEventListener("click", function(e) {
                var targetSelector = header.getAttribute("data-bs-target");
                var target = document.querySelector(targetSelector);
                if (target) {
                    var bsCollapse = bootstrap.Collapse.getOrCreateInstance(target);
                    bsCollapse.toggle();
                }
            });
        });
        </script>';

        if ($response && method_exists($response, 'getBody')) {
            $response->getBody()->write($formHtml);
            return $response;
        } else {
            echo $formHtml;
            return null;
        }
    }

    public function generate($request = null, $response = null, $args = [])
    {
        global $pdo;

        try {
            $config = require __DIR__ . '/../config/app.php';
            $baseUrl = $config['base_url'];

            $data = $_POST;
            $url = $data['url'] ?? '';
            $size = $data['size'] ?? 300;
            $margin = $data['margin'] ?? 10;
            $foregroundColorHex = $data['foreground_color'] ?? '#000000';
            $backgroundColorHex = $data['background_color'] ?? '#FFFFFF';
            $isDynamic = isset($data['dynamic']) && $data['dynamic'] === 'on';

            if (empty($url)) {
                throw new \Exception('No URL or text provided for QR code generation.');
            }

            $foregroundColor = new Color(
                hexdec(substr($foregroundColorHex, 1, 2)),
                hexdec(substr($foregroundColorHex, 3, 2)),
                hexdec(substr($foregroundColorHex, 5, 2))
            );
            $backgroundColor = new Color(
                hexdec(substr($backgroundColorHex, 1, 2)),
                hexdec(substr($backgroundColorHex, 3, 2)),
                hexdec(substr($backgroundColorHex, 5, 2))
            );

            $uniqueId = uniqid();
            $qrCodeContent = $isDynamic ? "$baseUrl/redirect.php?id={$uniqueId}" : $url;

            $qrCode = new QrCode($qrCodeContent);
            $qrCode->setSize((int)$size);
            $qrCode->setMargin((int)$margin);
            $qrCode->setForegroundColor($foregroundColor);
            $qrCode->setBackgroundColor($backgroundColor);
            $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::High);
            $qrCodePath = 'qrcodes/' . $uniqueId . '.png';
            $writer = new PngWriter();
            $logoObj = null;
            $logoPath = null;
            $logoTmpPath = null;
            $logoSizePercent = isset($_POST['logo_size']) ? (int)$_POST['logo_size'] : 20;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/png', 'image/jpeg'];
                if (!in_array($_FILES['logo']['type'], $allowedTypes)) {
                    $_SESSION['error'] = 'Only PNG and JPG logo files are allowed.';
                    header('Location: /create.php');
                    exit;
                }
                if ($_FILES['logo']['size'] > 200 * 1024) {
                    $_SESSION['error'] = 'Logo file size must be 200KB or less.';
                    header('Location: /create.php');
                    exit;
                }
                $logoTmpPath = $_FILES['logo']['tmp_name'];
                $logoExt = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $logoPath = sys_get_temp_dir() . '/qrlogo_' . uniqid() . '.' . $logoExt;
                move_uploaded_file($logoTmpPath, $logoPath);
            }
            if ($logoPath && file_exists($logoPath)) {
                $logoWidth = (int)($size * ($logoSizePercent / 100));
                $logoObj = \Endroid\QrCode\Logo\Logo::create($logoPath)->setResizeToWidth($logoWidth);
            }
            if ($logoObj) {
                $writer->write($qrCode, $logoObj)->saveToFile($qrCodePath);
            } else {
                $writer->write($qrCode)->saveToFile($qrCodePath);
            }
            // Overlay COG_Logo_1000.jpg at bottom right (GD only, no Imagick)
            $cogLogoPath = __DIR__ . '/../public/COG_Logo_1000.jpg';
            if (file_exists($cogLogoPath)) {
                $qrImg = imagecreatefrompng($qrCodePath);
                $logoImg = imagecreatefromjpeg($cogLogoPath);
                $qrSize = imagesx($qrImg); // QR code is square
                $margin = (int)$margin;
                // Finder pattern is 7 modules, module size = (qrSize - 2*margin) / modules
                $modules = 21; // Default for QR version 1, adjust if needed
                $moduleSize = ($qrSize - 2 * $margin) / $modules;
                $finderSize = (int)round($moduleSize * 7 / 1.5); // Make logo half the finder pattern size
                $logoResized = imagecreatetruecolor($finderSize, $finderSize);
                imagealphablending($logoResized, false);
                imagesavealpha($logoResized, true);
                imagecopyresampled($logoResized, $logoImg, 0, 0, 0, 0, $finderSize, $finderSize, imagesx($logoImg), imagesy($logoImg));
                $dstX = $qrSize - $margin - $finderSize;
                $dstY = $qrSize - $margin - $finderSize;
                imagecopy($qrImg, $logoResized, $dstX, $dstY, 0, 0, $finderSize, $finderSize);
                imagepng($qrImg, $qrCodePath);
                imagedestroy($logoImg);
                imagedestroy($logoResized);
                imagedestroy($qrImg);
            }
            // Clean up temp logo/icon files
            if ($logoPath && file_exists($logoPath)) unlink($logoPath);

            $stmt = $pdo->prepare('INSERT INTO qrcodes (id, original_url, qr_code_path, redirect_url) VALUES (:id, :original_url, :qr_code_path, :redirect_url)');
            $stmt->execute([
                'id' => $uniqueId,
                'original_url' => $qrCodeContent, // always store the actual QR code content
                'qr_code_path' => $qrCodePath,
                'redirect_url' => $isDynamic ? $url : null // for dynamic, store the final destination; for static, null
            ]);

            $_SESSION['qr_code_path'] = $qrCodePath;
            $_SESSION['success'] = 'QR Code generated successfully!';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to generate QR Code: ' . $e->getMessage();
        }

        header('Location: /renderer.php');
        exit;
    }
}
