# Design – TA SKV Eglosheim (Tennis & Padel)

Verbindliches Designsystem für alle Seiten. Jede Überarbeitung einer Seite liest
zuerst diese Datei. Nicht pro Seite neu erfinden – wenn das System etwas nicht
abdeckt, zuerst diese Datei ergänzen.

Werte stehen in `src/styles/tokens.css`. Seiten verwenden ausschließlich die
Variablen (`var(--color-accent)`, `var(--space-md)`), nie Hex-, RGB- oder
OKLCH-Werte direkt.

## Haltung

Ein Vereinsbrett, kein Prospekt. Warm, direkt, sportlich. Echte Zeiten, Preise,
Termine und Namen stehen vorne; keine Werbesprache, keine erfundenen Zahlen.
Anrede durchgehend **du**.

## Genre

editorial (sportlich) – eigenes Farbschema auf Basis des Vereinsrots.

## Seitentypen und Aufbau

Alle Seiten teilen Schrift, Farbe und Button-Stil. Der Aufbau unterscheidet sich
je Seitentyp:

| Seiten | Aufbau | Kern |
| --- | --- | --- |
| Startseite | **Index-First mit kleinem Hero** | Kleiner dunkler Hero (Titel, ein Satz, „Platz buchen“ + „Mitglied werden“, Adresse und Spielzeiten). Dann „Das Wichtigste“ als vier Fragen: Wo kann ich buchen? · Wie buche ich? · Was kostet eine Mitgliedschaft? · Welche Bedingungen gelten? Danach das Verzeichnis (Training, Mannschaften, Termine, Padel, Verein, Kontakt, Chronik). Buchungsdaten kommen aus `inhalte.json` → `buchung`. |
| Tennis, Padel | **Split Studio** | Text und Beleg wechseln die Seite. Beleg ist immer eine echte Tabelle (Mannschaften, Trainingszeiten) oder ein Foto – keine Kartenreihen. |
| Mitglied werden | **Narrative Workflow** | Vier echte Schritte: Probetraining → Anmeldung → Schlüssel & Einführung → Plätze buchen. Danach Beiträge als Preistabelle. |
| Verein, Termine, Ansprechpartner | **Long Document** | Fließtext mit eingestreuten Fakten; Ansprechpartner als Liste. |
| FAQ | **Conversational FAQ** | `<details>`-Akkordeon, Themenindex links. |
| Rechtliches, 404, Kontakt | **Long Document** | Nur Typografie. |

## Farben

| Token | Wert | Verwendung |
| --- | --- | --- |
| `--color-paper` | oklch(99.2% 0.005 55) | Seitenhintergrund (leicht warm, kein Reinweiß) |
| `--color-paper-2` | oklch(96.9% 0.006 60) | Sand: Bänder, Kacheln |
| `--color-rule` | oklch(91.3% 0.009 63) | Haarlinien |
| `--color-ink` | oklch(23.2% 0.015 33) | Schrift, dunkle Flächen |
| `--color-ink-2` | oklch(44.7% 0.014 37) | Fließtext gedämpft |
| `--color-ink-3` | oklch(53.5% 0.013 45) | Nebeninfos (≥ 4,5:1) |
| `--color-accent` | oklch(49.8% 0.189 28) | Vereinsrot: Primärbutton, Links, Akzentfläche |
| `--color-accent-2` | oklch(58.2% 0.2 28) | Rot auf dunklem Grund, Fokusring |
| `--color-accent-soft` | oklch(78.6% 0.099 28) | Rosé: aktive Links auf Dunkel |
| `--color-on-dark` / `-2` / `-3` | Creme-Abstufungen | Schrift auf `--color-ink` |

Rot ist Signal, nicht Fläche: pro Bildschirm höchstens eine große rote Fläche
(Startseite: keine – dort ist nur der Eintrag „Platz buchen“ rot gesetzt).

## Typografie

- Überschriften: **Familjen Grotesk**, 600–700, immer aufrecht (nie kursiv)
- Fließtext: **Source Sans 3**, 400/600
- Überschriften-Laufweite: −0.02 bis −0.035 em
- Größen: `--text-xs` … `--text-display` (tokens.css)
- Zahlen in Tabellen und Preisen: `font-variant-numeric: tabular-nums`

## Breite Bildschirme

Ab 1200 px Breite wird die Fläche genutzt statt leer gelassen: Antworten teilen
sich in zwei bis drei Spalten (z. B. Plätze | Spielzeiten, Schritte
nebeneinander, Beiträge | Gastpreise), Verzeichnisse werden zum 3er-Raster.
Fließtext bleibt trotzdem bei höchstens ~64 Zeichen pro Zeile. Unter 1200 px
bleibt alles einspaltig bzw. Titel links, Inhalt rechts; unter 760 px
untereinander.

## Abstände

4-Punkt-Raster `--space-3xs` (4 px) bis `--space-3xl` (112 px), seitlicher
Rand `--gutter`. Abschnitte bewusst unterschiedlich hoch polstern, nicht überall
64 px.

## Bewegung

- Easings: `--ease-out`, `--ease-in`, `--ease-in-out` – nie das Browser-`ease`
- Kein Auftritt beim Laden, keine gestaffelten Karten, kein Hochzählen, keine
  Scroll-Animationen. Einzige Bewegung: der Pfeil im Verzeichnis rückt beim
  Hover 4 px nach rechts.
- `prefers-reduced-motion`: alles aus.
- Animiert werden nur `transform` und `opacity`.

## Interaktion

- Fokusring: 2 px `--color-focus`, sofort sichtbar, nie animiert. `outline: none`
  ist verboten.
- Buttons: Hover (Farbwechsel), Active (1 px nach unten), Disabled, Busy
  (`aria-busy="true"` während des Sendens).
- Erfolg still: nach dem Absenden eine Danke-Seite, keine Toasts.

## Buttons

- Primär: rot gefüllt, Radius 2 px, `white-space: nowrap`, kurze Verben
  („Platz buchen“, „Mitglied werden“, „Online anmelden“)
- Sekundär: Kontur in Schriftfarbe
- Innerhalb von Kacheln und Fließtext: Textlink mit →

## Navigation und Fußzeile

- Navigation: **N12 Banner + Leiste**. Banner nur bei zeitlich begrenzter
  Ankündigung (Sommer-Special), verschwindet beim Scrollen, lässt sich
  schließen. Darunter die Leiste: Verein ▾ · Tennis · Padel · Kontakt ·
  Platz buchen ↗ · [Mitglied werden].
- Fußzeile: **Ft1 Mast-headed**. Großes Logo, Anschrift/Telefon/E-Mail/Instagram
  in einer Zeile, Seitenlinks als eine Reihe, Rechtliches als zweite Reihe.

## Kleine Überschriften (Eyebrows)

Standardmäßig aus. Erlaubt nur als Brotkrumen auf Unterseiten
(„Verein · Termine“) und als Schrittnummer auf „Mitglied werden“. Immer über
der Überschrift, nie links daneben.

## Was alle Seiten teilen

- Logo, Vereinsrot und seine sparsame Verwendung
- Schriftpaar und Größenstufen
- Button-Stil
- Haarlinien statt Schatten, eckige Formen: Radius 3 px für Flächen, 2 px für Buttons und Felder – keine Pillen, keine Kreise

## Was sich unterscheiden darf

- Der Aufbau innerhalb des Seitentyps (siehe Tabelle oben)
- Fotos: nur echte Vereinsfotos. Solange keins da ist, zeigt `<Bild>` einen
  Platzhalter – Seiten mit reinen Platzhalter-Galerien gehen nicht live.
