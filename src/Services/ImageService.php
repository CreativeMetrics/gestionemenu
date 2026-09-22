<?php

namespace App\Services;

class ImageService
{
    private string $dir;
    private int $latoLungoMax;
    private int $qualita;

    public function __construct()
    {
        $upload = config_get('upload', []);
        $this->dir = rtrim($upload['dir'] ?? __DIR__ . '/../../public/uploads/piatti', '/');
        $this->latoLungoMax = (int) ($upload['max_lato_lungo_px'] ?? 1600);
        $this->qualita = (int) ($upload['jpeg_quality'] ?? 82);
    }

    /**
     * Valida e salva una foto caricata (array $_FILES['foto']) ridimensionata lato server.
     * Restituisce il nome file salvato (da mettere in piatti.foto_path) o lancia un'eccezione.
     */
    public function salvaFotoCaricata(array $file, int $piattoId): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Errore nel caricamento del file.');
        }
        if ($file['size'] > 15 * 1024 * 1024) {
            throw new \RuntimeException('File troppo grande (massimo 15MB).');
        }

        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            throw new \RuntimeException('Il file caricato non è un\'immagine valida.');
        }

        $mime = $info['mime'];
        $sorgente = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
            'image/png' => imagecreatefrompng($file['tmp_name']),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($file['tmp_name']) : false,
            default => false,
        };
        if ($sorgente === false) {
            throw new \RuntimeException('Formato immagine non supportato (usa JPEG, PNG o WEBP).');
        }

        // Corregge l'orientamento da EXIF (comune con le foto da smartphone).
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $sorgente = $this->correggiOrientamento($sorgente, $file['tmp_name']);
        }

        $larghezza = imagesx($sorgente);
        $altezza = imagesy($sorgente);
        $latoLungo = max($larghezza, $altezza);

        if ($latoLungo > $this->latoLungoMax) {
            $scala = $this->latoLungoMax / $latoLungo;
            $nuovaLarghezza = (int) round($larghezza * $scala);
            $nuovaAltezza = (int) round($altezza * $scala);
            $ridimensionata = imagecreatetruecolor($nuovaLarghezza, $nuovaAltezza);
            imagecopyresampled($ridimensionata, $sorgente, 0, 0, 0, 0, $nuovaLarghezza, $nuovaAltezza, $larghezza, $altezza);
            imagedestroy($sorgente);
            $sorgente = $ridimensionata;
        }

        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0775, true);
        }

        $nomeFile = 'piatto_' . $piattoId . '_' . time() . '.jpg';
        $percorsoCompleto = $this->dir . '/' . $nomeFile;
        imagejpeg($sorgente, $percorsoCompleto, $this->qualita);
        imagedestroy($sorgente);

        return $nomeFile;
    }

    public function elimina(?string $nomeFile): void
    {
        if (!$nomeFile) {
            return;
        }
        $percorso = $this->dir . '/' . basename($nomeFile);
        if (is_file($percorso)) {
            @unlink($percorso);
        }
    }

    /** @return \GdImage */
    private function correggiOrientamento(\GdImage $img, string $percorsoTmp): \GdImage
    {
        $exif = @exif_read_data($percorsoTmp);
        if (!$exif || empty($exif['Orientation'])) {
            return $img;
        }
        return match ($exif['Orientation']) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, -90, 0),
            8 => imagerotate($img, 90, 0),
            default => $img,
        };
    }
}
