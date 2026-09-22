<?php
/**
 * Crea (o aggiorna la password di) un utente da riga di comando. Serve soprattutto per creare
 * il primo utente admin, dato che l'applicazione non ha una pagina di registrazione pubblica.
 *
 * Uso:
 *   php cron/crea_utente.php "Nome Cognome" email@esempio.it "PasswordSicura123" admin
 *
 * Il quarto parametro (ruolo) è opzionale: admin oppure editor (default editor).
 */

require __DIR__ . '/../src/autoload.php';

use App\Repositories\UserRepository;

[$script, $nome, $email, $password, $ruolo] = array_pad($argv, 5, null);

if (!$nome || !$email || !$password) {
    fwrite(STDERR, "Uso: php cron/crea_utente.php \"Nome\" email@esempio.it Password123 [admin|editor]\n");
    exit(1);
}
if (strlen($password) < 8) {
    fwrite(STDERR, "La password deve avere almeno 8 caratteri.\n");
    exit(1);
}
$ruolo = $ruolo === 'admin' ? 'admin' : 'editor';

$repo = new UserRepository();
if ($repo->emailEsiste($email)) {
    $esistente = $repo->findByEmailQualunqueStato($email);
    $repo->updatePassword((int) $esistente['id'], $password);
    $repo->updateRuolo((int) $esistente['id'], $ruolo);
    echo "Utente esistente aggiornato (password e ruolo): {$email}\n";
    exit(0);
}

$id = $repo->create($nome, $email, $password, $ruolo);
echo "Utente creato (id {$id}): {$email} — ruolo {$ruolo}\n";
