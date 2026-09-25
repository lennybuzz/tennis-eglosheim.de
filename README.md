# tennis-eglosheim.de

Website der Abteilung Tennis & Padel des SKV Eglosheim e.V., gebaut mit [Astro](https://astro.build).
Das Design stammt aus dem Claude-Design-Entwurf in `Entwurf_Website/` (nur noch Referenz, wird nicht ausgeliefert).

## Voraussetzungen

- Node.js ≥ 22.12
- einmalig: `npm install`

## Befehle

| Befehl            | Wirkung                                                  |
| ----------------- | -------------------------------------------------------- |
| `npm run dev`     | Entwicklungsserver auf http://localhost:4321             |
| `npm run build`   | fertige Website nach `dist/` bauen                       |
| `npm run preview` | den Build lokal ansehen                                  |
| `npx astro check` | Typ- und Template-Prüfung                                |

## Inhalte pflegen

Fast alles Laufende steht in **`src/data/inhalte.json`**: Mitgliederzahl, Plätze, Training, Termine,
Sommer-Special, Beiträge, Timeline, Ansprechpartner, Spielbetrieb, FAQ, Kontaktdaten und Links.
Nur Werte zwischen Anführungszeichen ändern. Tippfehler im JSON fallen beim Build sofort auf.

Feste Texte stehen direkt in den Seiten unter `src/pages/` (eine Datei pro Seite).

**Fotos:** Datei nach `src/assets/bilder/` legen und in der Seite den Platzhalter ersetzen, z. B.

```astro
---
import anlage from '../assets/bilder/anlage.jpg';
---
<Bild bild={anlage} alt="Blick auf die Tennisplätze" platzhalter="Foto der Anlage" hoehe="420px" />
```

Astro verkleinert die Bilder beim Build automatisch und wandelt sie in WebP um.

**PDFs** (Aufnahmeantrag usw.): nach `public/dokumente/` legen und in
`src/pages/mitglied-werden.astro` bei `dokumente` den Pfad eintragen.

## Veröffentlichen (FTP)

1. `npm run build`
2. Den **Inhalt** von `dist/` per FTP (z. B. FileZilla) in den Web-Ordner des Hosters hochladen
   (`html`, `httpdocs`, `public_html` …). Auch die versteckte Datei `.htaccess` mitnehmen.
3. Im Browser mit Strg+F5 prüfen.

Die `.htaccess` (Quelle: `public/.htaccess`) leitet auf https://www. um, sorgt für die 404-Seite und
leitet alte WordPress-Adressen (`/news/`, `/post/…`, `/tennis/mannschaften/` usw.) auf die neuen Seiten weiter.

## Kontaktformular (PHP)

Das Formular auf `/kontakt/` schickt an `/kontakt.php` (Quelle: `public/kontakt.php`). Der Hoster muss PHP ≥ 8.1 unterstützen.
Die Zugangsdaten stehen in `kontakt-config.php` (Vorlage: `kontakt-config.example.php`). Diese Datei per FTP
**eine Ebene über** den Web-Ordner legen (alternativ direkt daneben, die `.htaccess` sperrt den Abruf).
Sie steht in `.gitignore` und darf nie ins Repository.

### Microsoft 365 einrichten (Methode `graph`)

Microsoft schaltet SMTP mit Benutzername und Passwort ab. Das Formular sendet deshalb über die
Microsoft Graph API mit einer App-Anmeldung. Einmalig durch einen M365-Admin im
[Entra Admin Center](https://entra.microsoft.com):

1. **App-Registrierungen → Neue Registrierung**: Name z. B. „Website Kontaktformular“,
   „Nur Konten in diesem Organisationsverzeichnis“, keine Umleitungs-URI → Registrieren.
2. Auf der Übersichtsseite **Anwendungs-ID (Client)** und **Verzeichnis-ID (Mandant)** notieren.
3. **API-Berechtigungen → Berechtigung hinzufügen → Microsoft Graph → Anwendungsberechtigungen →
   `Mail.Send`** → Hinzufügen, dann **„Administratorzustimmung erteilen“**.
4. **Zertifikate & Geheimnisse → Neuer geheimer Clientschlüssel** (Ablauf z. B. 24 Monate)
   und sofort den **Wert** kopieren (er wird nur einmal angezeigt).
   ⚠️ Ablaufdatum in den Kalender eintragen – danach neuen Schlüssel erzeugen und in `kontakt-config.php` ersetzen,
   sonst landen Nachrichten auf `/kontakt/fehler/`.
5. `kontakt-config.example.php` → `kontakt-config.php` kopieren, `tenant`, `clientId`, `clientSecret` eintragen
   (`from` = Postfach, aus dem gesendet wird, `to` = Empfänger) und hochladen.
6. Testnachricht über das Formular schicken.

**Empfohlen:** `Mail.Send` als Anwendungsberechtigung erlaubt der App grundsätzlich, aus *jedem* Postfach des
Vereins zu senden. Mit Exchange Online „RBAC for Applications“ lässt sich das per PowerShell auf das
Absender-Postfach beschränken
([Anleitung](https://learn.microsoft.com/exchange/permissions-exo/application-rbac)).

### Andere Mail-Anbieter (Methode `smtp`)

Für klassisches SMTP mit Benutzername/Passwort den auskommentierten Block in der Vorlage verwenden.

### Fehlersuche

Bei Fehlern landet man auf `/kontakt/fehler/`. Die genaue Ursache (z. B. abgelaufener Schlüssel) steht im
PHP-Fehlerlog des Hosters (Zeilen beginnend mit `kontakt.php:`). Im `npm run dev` läuft kein PHP –
das Formular lässt sich nur auf dem Webspace testen.

## Externe Dienste

- Platzbuchung: eBuSy (`tennis-eglosheim.ebusy.de`)
- Termine: ClubDesk-Kalender auf `/verein/termine/` – wird erst nach Klick auf „Kalender laden“ geladen
- Online-Anmeldung: ClubDesk-Formular auf `/anmeldung/` – direkt eingebunden, mit Datenschutzhinweis darüber
- Karte: OpenStreetMap auf `/kontakt/` – wird erst nach Klick auf „Karte laden“ geladen
- Schriften werden lokal ausgeliefert (kein Google Fonts)

Neue fremde Inhalte (iframes) bitte immer über `src/components/Einbettung.astro` einbinden,
damit vor dem Klick nichts an Dritte geht, und in der Datenschutzerklärung ergänzen (siehe `design.md`).
