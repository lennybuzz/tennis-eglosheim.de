<?php
/**
 * Versand des Kontaktformulars (/kontakt/).
 *
 * Zwei Versandarten (Einstellung 'methode' in kontakt-config.php):
 *  - 'graph': Microsoft 365 über die Microsoft Graph API (OAuth, empfohlen für M365 –
 *             Microsoft schaltet SMTP mit Benutzername/Passwort ab)
 *  - 'smtp':  klassisches SMTP mit Benutzername/Passwort (andere Mail-Anbieter)
 *
 * Die Zugangsdaten stehen NICHT hier, sondern in kontakt-config.php
 * (Vorlage: kontakt-config.example.php im Projektordner). Diese Datei am besten
 * eine Ebene ÜBER dem Web-Ordner ablegen; alternativ im Web-Ordner neben
 * kontakt.php – dort sperrt die .htaccess den direkten Abruf.
 *
 * Spamschutz (ohne Drittanbieter):
 *  - Honeypot-Feld "website" (füllen nur Bots aus)
 *  - ALTCHA (Proof of Work): Das Widget im Formular holt sich per GET kontakt.php?altcha
 *    eine Rechenaufgabe, löst sie im Browser und schickt die Lösung im Feld "altcha" mit.
 *    Aufgabe und Lösung werden hier mit HMAC signiert bzw. geprüft (ALTCHA-Format v1, SHA-256).
 *  - Zeitprüfung: Zwischen Abruf der Aufgabe und Absenden müssen mindestens ALTCHA_MIN_SEKUNDEN liegen.
 *  - Jede gelöste Aufgabe gilt nur einmal (Merkliste im Temp-Ordner des Servers).
 * Erkannter Spam landet unauffällig auf der Danke-Seite, damit Bots nichts merken.
 *
 * Kommt ohne Zusatzbibliotheken aus (kein Composer/PHPMailer nötig).
 */

declare(strict_types=1);

date_default_timezone_set('Europe/Berlin');

const SEITE_DANKE  = '/kontakt/danke/';
const SEITE_FEHLER = '/kontakt/fehler/';
const ANLIEGEN     = ['Probetraining', 'Mitgliedschaft', 'Padel & Platzbuchung', 'Jugend & Training', 'Sonstiges'];

const ALTCHA_MAX_ZAHL     = 50000; // Schwierigkeit: Größte Zahl, die der Browser suchen muss (ca. 1 Sekunde)
const ALTCHA_GUELTIG      = 3600;  // Sekunden, wie lange eine Aufgabe gültig ist
const ALTCHA_MIN_SEKUNDEN = 3;     // Mindestzeit zwischen Aufgabe holen und Absenden

function weiter(string $ziel): never
{
    header('Location: ' . $ziel, true, 303);
    exit;
}

function feld(string $name, int $max): string
{
    $wert = trim((string)($_POST[$name] ?? ''));
    return mb_substr($wert, 0, $max);
}

/** Zeilenumbrüche entfernen – verhindert das Einschleusen zusätzlicher Mail-Header. */
function einzeilig(string $s): string
{
    return trim(preg_replace('/[\r\n\t]+/', ' ', $s) ?? '');
}

function config_laden(): ?array
{
    foreach ([dirname(__DIR__) . '/kontakt-config.php', __DIR__ . '/kontakt-config.php'] as $pfad) {
        if (is_readable($pfad)) {
            $cfg = require $pfad;
            return is_array($cfg) ? $cfg : null;
        }
    }
    error_log('kontakt.php: kontakt-config.php nicht gefunden');
    return null;
}

$cfg = config_laden();

// Rechenaufgabe für das ALTCHA-Widget ausliefern
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && isset($_GET['altcha'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    if ($cfg === null) {
        http_response_code(500);
        echo '{"error":"Konfiguration fehlt"}';
        exit;
    }
    echo json_encode(altcha_aufgabe(altcha_schluessel($cfg)), JSON_THROW_ON_ERROR);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    weiter('/kontakt/');
}

// Spamschutz: Das versteckte Feld "website" füllen nur Bots aus.
if (feld('website', 200) !== '') {
    weiter(SEITE_DANKE);
}

if ($cfg === null) {
    weiter(SEITE_FEHLER);
}

// ALTCHA: Fehlt die Lösung ganz (z. B. JavaScript aus), Fehlerseite mit Hinweis auf die E-Mail-Adresse.
// Ist sie falsch, abgelaufen, schon benutzt oder kam das Formular zu schnell: unauffällig zur Danke-Seite.
$altcha = (string)($_POST['altcha'] ?? '');
if ($altcha === '') {
    weiter(SEITE_FEHLER);
}
$grund = altcha_pruefen($altcha, altcha_schluessel($cfg));
if ($grund !== null) {
    error_log('kontakt.php: Spam abgewiesen (' . $grund . ')');
    weiter(SEITE_DANKE);
}

$name      = einzeilig(feld('name', 200));
$email     = einzeilig(feld('email', 200));
$telefon   = einzeilig(feld('telefon', 50));
$anliegen  = einzeilig(feld('anliegen', 100));
$nachricht = feld('nachricht', 5000);

if (
    $name === '' || $nachricht === ''
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || ($_POST['datenschutz'] ?? '') !== 'ja'
) {
    weiter(SEITE_FEHLER);
}
if (!in_array($anliegen, ANLIEGEN, true)) {
    $anliegen = 'Sonstiges';
}


$betreff = "Kontaktformular: {$anliegen} – {$name}";
$text = "Neue Nachricht über das Kontaktformular auf tennis-eglosheim.de\n\n"
    . "Name:     {$name}\n"
    . "E-Mail:   {$email}\n"
    . 'Telefon:  ' . ($telefon !== '' ? $telefon : '–') . "\n"
    . "Anliegen: {$anliegen}\n\n"
    . "Nachricht:\n{$nachricht}\n\n"
    . "--\nDatenschutzerklärung wurde akzeptiert am " . date('d.m.Y \u\m H:i') . " Uhr.\n";

try {
    if (($cfg['methode'] ?? 'smtp') === 'graph') {
        graph_senden($cfg, $betreff, $text, $email, $name);
    } else {
        smtp_senden($cfg, $betreff, $text, $email, $name);
    }
} catch (Throwable $e) {
    error_log('kontakt.php: ' . $e->getMessage());
    weiter(SEITE_FEHLER);
}
weiter(SEITE_DANKE);


// ---------------------------------------------------------------------------
// ALTCHA (Format v1, SHA-256): Aufgabe erzeugen und Lösung prüfen
// ---------------------------------------------------------------------------

/**
 * Geheimer HMAC-Schlüssel. Optional als 'altchaSchluessel' in kontakt-config.php,
 * sonst aus dem vorhandenen Geheimnis (Client-Secret bzw. SMTP-Passwort) abgeleitet.
 */
function altcha_schluessel(array $cfg): string
{
    $basis = (string)($cfg['altchaSchluessel'] ?? $cfg['clientSecret'] ?? $cfg['pass'] ?? '');
    if ($basis === '') {
        throw new RuntimeException('kein Geheimnis für ALTCHA in kontakt-config.php');
    }
    return hash_hmac('sha256', 'altcha-kontaktformular', $basis);
}

/** @return array{algorithm:string,challenge:string,maxnumber:int,salt:string,signature:string} */
function altcha_aufgabe(string $schluessel): array
{
    $jetzt = time();
    // Ausgabezeit und Ablauf stecken im Salt und sind damit über Hash + Signatur fälschungssicher.
    $salt = bin2hex(random_bytes(12)) . '?' . http_build_query(['expires' => $jetzt + ALTCHA_GUELTIG, 'ab' => $jetzt]);
    $zahl = random_int(0, ALTCHA_MAX_ZAHL);
    $challenge = hash('sha256', $salt . $zahl);
    return [
        'algorithm' => 'SHA-256',
        'challenge' => $challenge,
        'maxnumber' => ALTCHA_MAX_ZAHL,
        'salt'      => $salt,
        'signature' => hash_hmac('sha256', $challenge, $schluessel),
    ];
}

/** @return string|null null = in Ordnung, sonst der Grund der Ablehnung */
function altcha_pruefen(string $payload, string $schluessel): ?string
{
    $daten = json_decode((string)base64_decode($payload, true), true);
    if (!is_array($daten)) {
        return 'ungültiges Format';
    }
    $algorithmus = $daten['algorithm'] ?? null;
    $challenge   = $daten['challenge'] ?? null;
    $zahl        = $daten['number'] ?? null;
    $salt        = $daten['salt'] ?? null;
    $signatur    = $daten['signature'] ?? null;
    if ($algorithmus !== 'SHA-256' || !is_string($challenge) || !is_int($zahl) || !is_string($salt) || !is_string($signatur)) {
        return 'ungültige Felder';
    }
    if (!hash_equals(hash_hmac('sha256', $challenge, $schluessel), $signatur)) {
        return 'falsche Signatur';
    }
    if (!hash_equals($challenge, hash('sha256', $salt . $zahl))) {
        return 'falsche Lösung';
    }
    parse_str(explode('?', $salt, 2)[1] ?? '', $parameter);
    $jetzt = time();
    $ablauf = (int)($parameter['expires'] ?? 0);
    $ab = (int)($parameter['ab'] ?? 0);
    if ($ablauf < $jetzt) {
        return 'abgelaufen';
    }
    if ($ab === 0 || $jetzt - $ab < ALTCHA_MIN_SEKUNDEN) {
        return 'zu schnell abgeschickt';
    }
    if (!altcha_einmalig($challenge)) {
        return 'bereits benutzt';
    }
    return null;
}

/** Merkt sich gelöste Aufgaben, damit eine Lösung nicht mehrfach verwendet werden kann. */
function altcha_einmalig(string $challenge): bool
{
    $ordner = sys_get_temp_dir() . '/altcha-kontakt-' . substr(md5(__DIR__), 0, 8);
    if (!is_dir($ordner) && !@mkdir($ordner, 0700) && !is_dir($ordner)) {
        return true; // Ohne beschreibbaren Temp-Ordner nur auf die übrigen Prüfungen verlassen
    }
    // Gelegentlich aufräumen: Einträge älter als die Gültigkeit löschen
    if (random_int(1, 20) === 1) {
        foreach (glob($ordner . '/*') ?: [] as $datei) {
            if (@filemtime($datei) < time() - ALTCHA_GUELTIG - 60) {
                @unlink($datei);
            }
        }
    }
    $fp = @fopen($ordner . '/' . $challenge, 'x');
    if ($fp === false) {
        return !file_exists($ordner . '/' . $challenge);
    }
    fclose($fp);
    return true;
}


// ---------------------------------------------------------------------------
// Microsoft Graph (Microsoft 365): App-Anmeldung per Client-Secret, dann sendMail
// ---------------------------------------------------------------------------

function graph_senden(array $cfg, string $betreff, string $text, string $replyTo, string $replyName): void
{
    [$status, $antwort] = http_post(
        'https://login.microsoftonline.com/' . rawurlencode((string)$cfg['tenant']) . '/oauth2/v2.0/token',
        ['Content-Type: application/x-www-form-urlencoded'],
        http_build_query([
            'client_id'     => $cfg['clientId'],
            'client_secret' => $cfg['clientSecret'],
            'scope'         => 'https://graph.microsoft.com/.default',
            'grant_type'    => 'client_credentials',
        ]),
    );
    $token = json_decode($antwort, true)['access_token'] ?? null;
    if ($status !== 200 || !is_string($token)) {
        throw new RuntimeException("Graph-Token fehlgeschlagen (HTTP {$status}): {$antwort}");
    }

    $adresse = fn(string $mail, string $name = '') => ['emailAddress' => array_filter(['address' => $mail, 'name' => $name])];
    $mail = [
        'message' => [
            'subject'      => $betreff,
            'body'         => ['contentType' => 'Text', 'content' => $text],
            'toRecipients' => array_map($adresse, (array)$cfg['to']),
            'replyTo'      => [$adresse($replyTo, $replyName)],
        ],
        'saveToSentItems' => false,
    ];
    [$status, $antwort] = http_post(
        'https://graph.microsoft.com/v1.0/users/' . rawurlencode((string)$cfg['from']) . '/sendMail',
        ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        json_encode($mail, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
    );
    if ($status !== 202) {
        throw new RuntimeException("Graph-sendMail fehlgeschlagen (HTTP {$status}): {$antwort}");
    }
}

/** @return array{0:int,1:string} HTTP-Status und Antworttext */
function http_post(string $url, array $header, string $body): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $header,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $antwort = curl_exec($ch);
        if ($antwort === false) {
            throw new RuntimeException('HTTP-Fehler: ' . curl_error($ch));
        }
        return [(int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE), (string)$antwort];
    }
    $kontext = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => implode("\r\n", $header),
        'content'       => $body,
        'timeout'       => 15,
        'ignore_errors' => true,
    ]]);
    $antwort = @file_get_contents($url, false, $kontext);
    if ($antwort === false) {
        throw new RuntimeException("HTTP-Fehler bei {$url}");
    }
    preg_match('#^HTTP/\S+\s+(\d{3})#', $http_response_header[0] ?? '', $m);
    return [(int)($m[1] ?? 0), $antwort];
}


// ---------------------------------------------------------------------------
// Minimaler SMTP-Client (SSL auf Port 465 oder STARTTLS auf Port 587)
// ---------------------------------------------------------------------------

function smtp_senden(array $cfg, string $betreff, string $text, string $replyTo, string $replyName): void
{
    $secure = strtolower((string)($cfg['secure'] ?? 'tls'));
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $cfg['host'] . ':' . (int)$cfg['port'];
    $fp = @stream_socket_client($remote, $errno, $errstr, 15);
    if (!$fp) {
        throw new RuntimeException("Verbindung zu {$remote} fehlgeschlagen: {$errstr} ({$errno})");
    }
    stream_set_timeout($fp, 15);

    $antwort = function () use ($fp): array {
        $zeilen = '';
        while (($zeile = fgets($fp, 1024)) !== false) {
            $zeilen .= $zeile;
            if (strlen($zeile) < 4 || $zeile[3] === ' ') {
                break;
            }
        }
        return [(int)substr($zeilen, 0, 3), $zeilen];
    };
    $befehl = function (?string $cmd, array $erwartet) use ($fp, $antwort): void {
        if ($cmd !== null) {
            fwrite($fp, $cmd . "\r\n");
        }
        [$code, $text] = $antwort();
        if (!in_array($code, $erwartet, true)) {
            $log = str_starts_with((string)$cmd, 'AUTH') || $cmd === null ? '' : " auf '{$cmd}'";
            throw new RuntimeException("SMTP-Fehler{$log}: " . trim($text));
        }
    };

    $helo = 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $befehl(null, [220]);
    $befehl($helo, [250]);
    if ($secure === 'tls') {
        $befehl('STARTTLS', [220]);
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('STARTTLS fehlgeschlagen');
        }
        $befehl($helo, [250]);
    }
    $befehl('AUTH LOGIN', [334]);
    $befehl(base64_encode((string)$cfg['user']), [334]);
    $befehl(base64_encode((string)$cfg['pass']), [235]);

    $from = (string)$cfg['from'];
    $empfaenger = (array)$cfg['to'];
    $befehl("MAIL FROM:<{$from}>", [250]);
    foreach ($empfaenger as $to) {
        $befehl("RCPT TO:<{$to}>", [250, 251]);
    }
    $befehl('DATA', [354]);

    $kodiert = fn(string $s) => '=?UTF-8?B?' . base64_encode($s) . '?=';
    $domain = substr(strrchr($from, '@') ?: '@localhost', 1);
    $header = [
        'Date: ' . date('r'),
        'From: ' . $kodiert((string)($cfg['fromName'] ?? 'Website')) . " <{$from}>",
        'To: ' . implode(', ', array_map(fn($a) => "<{$a}>", $empfaenger)),
        'Reply-To: ' . $kodiert($replyName) . " <{$replyTo}>",
        'Subject: ' . $kodiert($betreff),
        'Message-ID: <' . bin2hex(random_bytes(12)) . "@{$domain}>",
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
    ];
    $daten = implode("\r\n", $header) . "\r\n\r\n"
        . rtrim(chunk_split(base64_encode(str_replace("\n", "\r\n", str_replace("\r\n", "\n", $text))), 76, "\r\n"));
    $befehl($daten . "\r\n.", [250]);

    fwrite($fp, "QUIT\r\n");
    fclose($fp);
}
