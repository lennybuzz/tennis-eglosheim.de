# Website betreiben und pflegen – TA SKV Eglosheim

Die Website besteht nur aus Dateien (HTML, JSON, Bilder). Es gibt keine Datenbank
und kein CMS – ein normaler Webspace mit FTP-Zugang reicht. SSH wird nicht benötigt.

---

## 1. Was auf den Server muss

Alle Dateien aus diesem Projekt, in einen Ordner:

| Datei | Zweck |
|---|---|
| `Startseite.dc.html` | Startseite |
| `Verein.dc.html`, `Ansprechpartner.dc.html`, `Termine.dc.html` | Bereich Verein |
| `Tennis.dc.html`, `Padel.dc.html` | Sport |
| `Mitglied-werden.dc.html`, `Kontakt.dc.html` | Mitgliedschaft, Kontakt |
| `Impressum.dc.html`, `Datenschutz.dc.html` | Rechtliches |
| `inhalte.json` | **Zentrale Inhaltsdatei – hier wird gepflegt** |
| `logo.svg`, `logo-mark.svg` | Logo |
| `support.js`, `image-slot.js` | Technik, nie ändern |
| Bilddateien (JPG/PNG) | Fotos, sobald vorhanden |

**Nicht** hochladen: `Startseite Entwuerfe.dc.html` (Archiv), `ANLEITUNG.md`,
Dateien, die mit `.` beginnen, und der Ordner `uploads/`.

---

## 2. Hochladen per FTP (ohne SSH)

Einmalig:

1. **FTP-Zugangsdaten besorgen.** Stehen im Kundenmenü des Hosters
   (z. B. Strato, IONOS, All-Inkl): Server/Host, Benutzername, Passwort.
2. **FTP-Programm installieren**, z. B. FileZilla (kostenlos, Windows/Mac).
3. In FileZilla oben Server, Benutzername und Passwort eintragen → **Verbinden**.
4. Rechts erscheint der Webspace. In den Web-Ordner wechseln – je nach Hoster
   heißt er `html`, `httpdocs`, `public_html` oder `www`.
5. Links die Projektdateien markieren und per Doppelklick oder Drag & Drop
   nach rechts übertragen.

Die Seite ist danach unter der Domain erreichbar, z. B.
`https://www.tennis-eglosheim.de/Startseite.dc.html`.

**Damit die Domain direkt auf die Startseite führt**, eine Datei `index.html`
mit diesem Inhalt hochladen:

```html
<!DOCTYPE html>
<meta charset="utf-8">
<meta http-equiv="refresh" content="0; url=./Startseite.dc.html">
<a href="./Startseite.dc.html">Zur Startseite</a>
```

---

## 2b. Vorher lokal testen (Linux-Laptop)

Wichtig: Die Seiten laden `inhalte.json` per JavaScript nach. Öffnet man die
Datei mit Doppelklick (`file:///…`), blockiert der Browser dieses Laden – die
dynamischen Inhalte (Termine, Beiträge, Teams) bleiben leer. Deshalb einen
kleinen lokalen Webserver starten; Python ist auf jedem Linux vorinstalliert.

1. Projektordner herunterladen (im Chat gibt es den Download als ZIP) und entpacken,
   z. B. nach `~/tennis-website`.
2. Terminal öffnen und in den Ordner wechseln:

   ```bash
   cd ~/tennis-website
   python3 -m http.server 8000
   ```

3. Im Browser aufrufen: **http://localhost:8000/Startseite.dc.html**
4. Zum Beenden im Terminal `Strg+C`.

Solange der Server läuft, kann man `inhalte.json` im Editor ändern, speichern
und im Browser mit `Strg+F5` neu laden – die Änderung ist sofort sichtbar.
Erst wenn alles passt, per FTP hochladen.

---

## 3. Inhalte ändern – nur inhalte.json anfassen

Fast alles Laufende steht in **einer** Datei: `inhalte.json`.
Die Seiten lesen sie bei jedem Aufruf – HTML muss dafür nie geändert werden.

So geht eine Änderung:

1. `inhalte.json` per FTP herunterladen (oder die lokale Kopie nehmen).
2. Mit einem Texteditor öffnen (Windows: Editor/Notepad; besser: Notepad++ oder VS Code).
3. Nur die **Werte** zwischen den Anführungszeichen ändern. Anführungszeichen,
   Kommas und Klammern stehen lassen.
4. Speichern (Kodierung UTF-8) und per FTP wieder hochladen – fertig.

Was wo steht:

| Abschnitt | Steuert |
|---|---|
| `anlage` | Anzahl Tennis-/Padelplätze |
| `training` | Trainingszeitraum und -zeiten (Startseite, Tennis) |
| `termine` | Die drei Termine auf der Startseite |
| `sommerSpecial` | Aktionsbanner. `"aktiv": true` zeigt es, `"aktiv": false` blendet es aus |
| `beitraege` | Alle Beitragssätze (Mitglied-werden, Startseite) |
| `mannschaften` | Mannschaftsliste auf der Startseite |
| `spielbetrieb` | Saison, gemeldete Teams mit Liga/Gruppe (Tennis-Seite) |
| `ansprechpartner` | Alle Personen mit Rolle, Aufgaben, E-Mail (Ansprechpartner, Kontakt) |
| `kontakt` | Adresse, Telefon, zentrale E-Mail |

Typische Fälle:

- **Saison wechseln:** in `spielbetrieb` → `"saison"` und die `teams` anpassen.
- **Special beenden:** in `sommerSpecial` → `"aktiv": false`.
- **Neuer Termin:** in `termine` eine Zeile nach dem Muster
  `{ "datum": "12.09.2026", "titel": "Clubmeisterschaft" },` einfügen
  (Komma zwischen den Einträgen, keins nach dem letzten).
- **Person wechselt:** in `ansprechpartner` Name/Rolle/E-Mail der Zeile ändern.

**Tipp:** Nach dem Bearbeiten die Datei auf https://jsonlint.com einfügen und
prüfen lassen. Ein vergessenes Komma ist der häufigste Fehler – die Seiten
zeigen dann die dynamischen Inhalte nicht an.

---

## 4. Texte und Bilder ändern

- **Feste Texte** (z. B. Vereinstext, Überschriften) stehen direkt in den
  `.dc.html`-Dateien. Änderung: Datei herunterladen, Text im Editor suchen und
  ersetzen, hochladen. Nichts an Zeichen wie `<div ...>` ändern.
- **Fotos:** endgültige Bilder am besten hier im Projekt einbauen lassen
  (Bild einfach in die Platzhalterfläche ziehen oder mir geben), dann liegen
  sie als Dateien bei und werden mit hochgeladen.
- **Termine-Kalender:** kommt live von ClubDesk, dort pflegen wie bisher.
- **Platzbuchung:** läuft weiter über eBuSy, kein Pflegeaufwand.

---

## 4b. Kontaktformular scharf schalten (SMTP)

Das Formular auf `Kontakt.dc.html` sendet an `kontakt.php` im gleichen Ordner.
Diese Datei gibt es noch nicht – bis sie hochgeladen ist, passiert beim Absenden
nichts. Zum Aktivieren beim Hoster prüfen, ob PHP verfügbar ist (bei Strato,
IONOS, All-Inkl ja), und dann eine `kontakt.php` mit den SMTP-Zugangsdaten des
Vereinspostfachs hinterlegen. Sag mir Bescheid, wenn die Zugangsdaten vorliegen
(SMTP-Server, Port, Benutzername, Passwort, Empfängeradresse) – dann baue ich
die Datei fertig ein.

Die Karte ist OpenStreetMap, kein Google Maps – kein Einverständnis nötig,
kein Pflegeaufwand. Der Marker sitzt auf Tammer Straße 30; falls er nicht genau
stimmt, kurz melden, ich verschiebe die Koordinaten.

---

## 5. Kurz-Checkliste bei jeder Änderung

1. Datei ändern (meist nur `inhalte.json`)
2. JSON prüfen (jsonlint.com)
3. Per FTP hochladen
4. Seite im Browser aufrufen und mit Strg+F5 neu laden
