<?php
/**
 * Vorlage für die Zugangsdaten des Kontaktformulars.
 *
 * 1. Kopieren als kontakt-config.php
 * 2. Werte eintragen (Einrichtung siehe README.md, Abschnitt „Kontaktformular“)
 * 3. Per FTP hochladen – am besten eine Ebene ÜBER dem Web-Ordner
 *    (z. B. neben "html"/"httpdocs"), sonst direkt neben kontakt.php.
 *
 * kontakt-config.php steht in .gitignore und darf NIE ins Git-Repository.
 */
return [
    // --- Microsoft 365 über Microsoft Graph (empfohlen) ---
    'methode'      => 'graph',
    'tenant'       => '00000000-0000-0000-0000-000000000000', // Verzeichnis-ID (Mandanten-ID)
    'clientId'     => '00000000-0000-0000-0000-000000000000', // Anwendungs-ID (Client-ID)
    'clientSecret' => 'GEHEIM',                                // Geheimer Clientschlüssel („Wert“, nicht die ID!)
    'from'         => 'hello@tennis-eglosheim.de',             // Postfach, aus dem gesendet wird
    'to'           => ['hello@tennis-eglosheim.de'],           // Empfänger, mehrere möglich

    // --- Alternative: klassisches SMTP (andere Mail-Anbieter) ---
    // 'methode'  => 'smtp',
    // 'host'     => 'smtp.example.de',
    // 'port'     => 587,                    // 587 (STARTTLS) oder 465 (SSL)
    // 'secure'   => 'tls',                  // 'tls' bei Port 587, 'ssl' bei Port 465
    // 'user'     => 'hello@tennis-eglosheim.de',
    // 'pass'     => 'GEHEIM',
    // 'from'     => 'hello@tennis-eglosheim.de',
    // 'fromName' => 'Website TA SKV Eglosheim',
    // 'to'       => ['hello@tennis-eglosheim.de'],
];
