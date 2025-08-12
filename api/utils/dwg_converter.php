<?php
require_once '../config/config.php';

class DWGConverter {
    private $convertApiSecret;
    
    public function __construct() {
        // Registrate en https://www.convertapi.com para obtener tu API key gratuita
        $this->convertApiSecret = 'TU_API_KEY_AQUI'; // Reemplaza con tu API key real
    }
    
    public function convertDWGToImage($dwgFilePath, $outputPath) {
        try {
            // Configurar la conversión usando cURL
            $url = 'https://v2.convertapi.com/convert/dwg/to/jpg';
            
            $postData = [
                'Parameters' => [
                    [
                        'Name' => 'File',
                        'FileValue' => [
                            'Name' => basename($dwgFilePath),
                            'Data' => base64_encode(file_get_contents($dwgFilePath))
                        ]
                    ],
                    [
                        'Name' => 'ImageResolution',
                        'Value' => '150' // DPI para buena calidad
                    ],
                    [
                        'Name' => 'SpaceToConvert',
                        'Value' => 'Model space' // Convertir el espacio modelo
                    ]
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->convertApiSecret,
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $result = json_decode($response, true);
                
                if (isset($result['Files'][0]['FileData'])) {
                    // Guardar la imagen convertida
                    $imageData = base64_decode($result['Files'][0]['FileData']);
                    file_put_contents($outputPath, $imageData);
                    return true;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('Error converting DWG: ' . $e->getMessage());
            return false;
        }
    }
    
    public function generateThumbnail($imagePath, $thumbnailPath, $maxWidth = 400, $maxHeight = 300) {
        try {
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) return false;
            
            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];
            $imageType = $imageInfo[2];
            
            // Calcular nuevas dimensiones manteniendo proporción
            $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
            $newWidth = intval($originalWidth * $ratio);
            $newHeight = intval($originalHeight * $ratio);
            
            // Crear imagen desde archivo
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $sourceImage = imagecreatefromjpeg($imagePath);
                    break;
                case IMAGETYPE_PNG:
                    $sourceImage = imagecreatefrompng($imagePath);
                    break;
                default:
                    return false;
            }
            
            // Crear thumbnail
            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preservar transparencia para PNG
            if ($imageType === IMAGETYPE_PNG) {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
                imagefill($thumbnail, 0, 0, $transparent);
            }
            
            imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
            
            // Guardar thumbnail
            $success = imagejpeg($thumbnail, $thumbnailPath, 85);
            
            imagedestroy($sourceImage);
            imagedestroy($thumbnail);
            
            return $success;
            
        } catch (Exception $e) {
            error_log('Error generating thumbnail: ' . $e->getMessage());
            return false;
        }
    }
    
    public function convertForCADViewer($dwgFilePath, $outputDir) {
        $results = [
            'svg' => null,
            'image' => null,
            'thumbnail' => null
        ];
        
        try {
            // Intentar conversión a SVG para CADViewer
            $svgPath = $outputDir . '/' . pathinfo($dwgFilePath, PATHINFO_FILENAME) . '.svg';
            if ($this->convertDWGToSVG($dwgFilePath, $svgPath)) {
                $results['svg'] = basename($svgPath);
            }
            
            // Fallback a imagen
            $imagePath = $outputDir . '/' . pathinfo($dwgFilePath, PATHINFO_FILENAME) . '_preview.jpg';
            if ($this->convertDWGToImage($dwgFilePath, $imagePath)) {
                $results['image'] = basename($imagePath);
                
                // Generar thumbnail
                $thumbPath = $outputDir . '/' . pathinfo($dwgFilePath, PATHINFO_FILENAME) . '_thumb.jpg';
                if ($this->generateThumbnail($imagePath, $thumbPath)) {
                    $results['thumbnail'] = basename($thumbPath);
                }
            }
            
        } catch (Exception $e) {
            error_log('Error en conversión CADViewer: ' . $e->getMessage());
        }
        
        return $results;
    }
    
    private function convertDWGToSVG($dwgFilePath, $svgPath) {
        // Implementar conversión a SVG usando ConvertAPI o similar
        // Por ahora retorna false para usar fallback
        return false;
    }
}
?>