Wir bauen die Tennis-Eglosheim-Website mit zehn HTML-Seiten plus zentraler inhalte.json. Die Startseite ist umgebaut auf eine Mischung aus 3a und 3b: dunkler Hero mit rotem Zahlenband (Hochzähler für Mitglieder 184, Teams, Events), darunter Info-Board mit vier Kacheln (Buchung, Trainingszeiten, Spielbetrieb 2026, nächste Termine), dann editorial ruhiger Bereich mit Vereinsblock, Padel-Split, Beiträge und Timeline. Scroll-Animationen und responsive Skalierung sind eingebaut.

**Entscheidungen unterwegs:**
- Hero mit `min-height` statt fixer Höhe für Stabilität bei schmalen Fenstern
- Einblendungen als reine CSS-Animation, kein JavaScript-Observer nötig
- Zahlenband zählt von echten Werten hoch, nicht von 0
- 184 Mitglieder in inhalte.json, Timeline mit Silbentrennung, Raster bricht automatisch auf Mobilgeräten um
- Kontaktseite komplett neu: Links OpenStreetMap-Karte (Koordinaten 48.9110 / 9.1735 für Tammer Straße 30) + Adresse, rechts Kontaktformular (Name, E-Mail, Telefon, Anliegen, Nachricht, Datenschutz-Häkchen)
- Datenschutz überarbeitet: OSM statt Google Maps

**Aktueller Stand:**
Alle zehn Seiten live, inhalte.json gepflegt, Design skaliert responsive, Formulargrundrisse stehen. Das Kontaktformular zeigt auf `kontakt.php` — diese Datei baue ich, sobald du die SMTP-Daten hast (Server, Port, Benutzer, Passwort, Empfängeradresse).

**Open:**
- SMTP-Daten für Kontaktformular
- Kartencenter-Koordinaten verifizieren (stimmt Eglosheim-Marker?)
- Bilder hochladen: Hero-Anlagenfoto, Padelplätze, Clubhaus, Tennis-Mannschaft, optional PNG-Logo
- Design auf übrigen neun Unterseiten aktivieren

**Files:** Startseite.dc.html (v1 als Backup), Kontakt.dc.html, Datenschutz.dc.html, inhalte.json, ANLEITUNG.md, plus alle neun Unterseiten.

Neue Seite FAQ.dc.html: 27 Fragen in sechs Themen (Mitgliedschaft & Beiträge, Arbeitsstunden, Platzbuchung & Anlage, Training, Padel, Verein & Spielbetrieb), mit Stichwortsuche, Sprungliste links, Aufklapp-Antworten und Kontakt-Band am Ende. Alle Fragen und Antworten stehen in inhalte.json unter faq — dort editierbar. Der Link „Häufige Fragen" ist im Verein-Menü und Footer aller Seiten ergänzt.
