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
    public function salvaFotoCaricata(array $file, int $piattoId, string $nomePiatto = ''): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Errore nel caricamento del file.');
        }
        if ($file['size'] > 15 * 1024 * 1024) {
            throw new \RuntimeException('File troppo grande (massimo 15MB).');
        }
        if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatetruecolor')) {
            throw new \RuntimeException('Il server non ha l\'estensione GD per elaborare le immagini. Contatta l\'assistenza hosting.');
        }

        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            throw new \RuntimeException('Il file caricato non è un\'immagine valida.');
        }

        // Le foto degli smartphone moderni possono avere risoluzioni molto alte (40+ megapixel):
        // decodificarle con GD richiede parecchia RAM. Alza il limite per questa richiesta, se possibile,
        // per evitare un "Allowed memory size exhausted" che manderebbe in errore 500 la pagina.
        $limiteMemoria = trim((string) ini_get('memory_limit'));
        if ($limiteMemoria !== '-1' && function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(60);
        }

        $mime = $info['mime'];
        $sorgente = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
            'image/png' => imagecreatefrompng($file['tmp_name']),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($file['tmp_name']) : false,
            default => false,
        };
        if (!($sorgente instanceof \GdImage)) {
            throw new \RuntimeException('Formato immagine non supportato (usa JPEG, PNG o WEBP).');
        }

        // Corregge l'orientamento da EXIF (comune con le foto da smartphone).
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $ruotata = $this->correggiOrientamento($sorgente, $file['tmp_name']);
            if ($ruotata instanceof \GdImage) {
                $sorgente = $ruotata;
            }
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
        // Su alcuni hosting condivisi (Plesk con PHP-FPM) i file creati da PHP hanno permessi
        // troppo restrittivi perché il webserver possa poi leggerli e servirli come immagine:
        // l'upload sembra riuscire ma la foto non si vede. Forza permessi leggibili da chiunque.
        @chmod($this->dir, 0755);

        // Il timestamp evita che ricaricando una nuova foto per lo stesso piatto il nome file
        // resti identico: il browser potrebbe altrimenti mostrare la vecchia foto dalla cache.
        $nomeFile = self::slug($nomePiatto) . '-' . $piattoId . '-' . time() . '.jpg';
        $percorsoCompleto = $this->dir . '/' . $nomeFile;
        imagejpeg($sorgente, $percorsoCompleto, $this->qualita);
        imagedestroy($sorgente);
        @chmod($percorsoCompleto, 0644);

        return $nomeFile;
    }

    /** Trasforma un testo libero in un nome file leggibile (es. "Uovo al tegamino" -> "uovo-al-tegamino"). */
    public static function slug(string $testo): string
    {
        $traslitterato = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT', $testo) : false;
        $testo = mb_strtolower($traslitterato !== false ? $traslitterato : $testo);
        $testo = preg_replace('/[^a-z0-9]+/', '-', $testo) ?? '';
        $testo = trim($testo, '-');
        return $testo !== '' ? substr($testo, 0, 60) : 'piatto';
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

    /** @return array<int, string> nomi dei file immagine presenti nella cartella upload */
    public function elencoFile(): array
    {
        if (!is_dir($this->dir)) {
            return [];
        }
        $file = [];
        foreach (scandir($this->dir) ?: [] as $voce) {
            if (is_file($this->dir . '/' . $voce) && !str_starts_with($voce, '.')) {
                $file[] = $voce;
            }
        }
        return $file;
    }

    public function esiste(string $nomeFile): bool
    {
        return is_file($this->percorsoCompleto($nomeFile));
    }

    /** Percorso assoluto sicuro per un nome file (impedisce di uscire dalla cartella upload). */
    public function percorsoCompleto(string $nomeFile): string
    {
        return $this->dir . '/' . basename($nomeFile);
    }

    /**
     * Restituisce l'immagine ruotata, oppure false se imagerotate() fallisce (in tal caso il
     * chiamante deve tenere l'immagine originale invece di propagare l'errore).
     */
    private function correggiOrientamento(\GdImage $img, string $percorsoTmp): \GdImage|false
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
