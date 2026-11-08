#!/usr/bin/env bash
# Lädt dist/ per FTPS auf den Webspace, aber nur Dateien, deren Inhalt sich geändert hat.
#
# Ablauf:
#   1. Für jede Datei in dist/ wird eine SHA-256-Prüfsumme berechnet (neue Liste).
#   2. Die Liste vom letzten Upload (.deploy-manifest.txt auf dem Server) wird geholt.
#   3. Hochgeladen werden nur neue oder geänderte Dateien (HTML und .htaccess zuletzt).
#   4. Gelöscht werden nur Dateien, die der Workflow selbst hochgeladen hat (alte Liste)
#      und die es nicht mehr gibt, plus veraltete generierte Dateien in _astro/.
#   5. Zum Schluss wird die neue Liste auf den Server gelegt.
#
# kontakt-config.php wird nie hochgeladen und nie gelöscht.
#
# Umgebung:
#   FTP_HOST, FTP_USER, LFTP_PASSWORD   Zugangsdaten (Pflicht)
#   ALLES_HOCHLADEN=true                alte Liste ignorieren und alles neu hochladen
#   FTP_OHNE_TLS=true                   nur für lokale Tests gegen einen FTP-Testserver
#   FTP_PORT                            optional, Standard 21
#
# Aufruf: produktiv-upload.sh <Build-Ordner>

set -euo pipefail

DIST="${1:?Build-Ordner fehlt (z. B. dist)}"
MANIFEST=".deploy-manifest.txt"
GESCHUETZT="kontakt-config.php"

: "${FTP_HOST:?FTP_HOST fehlt}"
: "${FTP_USER:?FTP_USER fehlt}"
: "${LFTP_PASSWORD:?FTP-Passwort fehlt}"

ARBEIT="$(mktemp -d)"
trap 'rm -rf "$ARBEIT"' EXIT

# --- lftp-Aufruf mit festen Einstellungen -----------------------------------
lftp_ausfuehren() {
  local befehle="$1" fehler_abbruch="${2:-yes}"
  local tls="set ftp:ssl-force true; set ftp:ssl-protect-data true; set ssl:verify-certificate true"
  if [[ "${FTP_OHNE_TLS:-}" == "true" ]]; then
    tls="set ftp:ssl-allow false"
  fi
  {
    echo "set cmd:fail-exit $fehler_abbruch"
    echo "set net:max-retries 2"
    echo "set xfer:clobber on"
    echo "set ftp:list-options -a"
    echo "set net:reconnect-interval-base 5"
    echo "$tls"
    echo "open --env-password -u \"$FTP_USER\" -p ${FTP_PORT:-21} ftp://$FTP_HOST"
    echo "$befehle"
  } > "$ARBEIT/befehle.lftp"
  lftp -f "$ARBEIT/befehle.lftp"
}

# --- 1. Neue Prüfsummenliste ------------------------------------------------
(
  cd "$DIST"
  find . -type f -printf '%P\0' | LC_ALL=C sort -z | xargs -0 -r sha256sum
) | grep -v -E "(^|/)${GESCHUETZT//./\\.}\$" > "$ARBEIT/neu.txt" || true

if ! grep -q '  index\.html$' "$ARBEIT/neu.txt"; then
  echo "Abbruch: $DIST/index.html fehlt, der Build ist offenbar unvollständig." >&2
  exit 1
fi

# Nur einfache Dateinamen zulassen, damit nichts in den lftp-Befehlen schiefgeht.
if cut -c67- "$ARBEIT/neu.txt" | LC_ALL=C grep -v -E '^[A-Za-z0-9._@+-][A-Za-z0-9._/@+-]*$' > "$ARBEIT/ungueltig.txt"; then
  echo "Abbruch: Dateinamen mit nicht erlaubten Zeichen:" >&2
  cat "$ARBEIT/ungueltig.txt" >&2
  exit 1
fi

# --- 2. Stand auf dem Server holen -------------------------------------------
# Verbindung prüfen und Wurzel auflisten. Ein Fehler hier bricht ab.
lftp_ausfuehren "cls -1 -a > \"$ARBEIT/wurzel.txt\""
# _astro/ darf fehlen (erster Upload), daher ohne Abbruch.
: > "$ARBEIT/astro.txt"
if grep -qx '_astro/\?' "$ARBEIT/wurzel.txt"; then
  lftp_ausfuehren "cls -1 -a _astro/ > \"$ARBEIT/astro.txt\""
fi

if [[ "${ALLES_HOCHLADEN:-}" == "true" ]]; then
  echo "ALLES_HOCHLADEN ist gesetzt: alle Dateien werden neu hochgeladen."
fi
# Direkt holen statt in der Auflistung suchen, weil manche FTP-Server Dateien mit
# Punkt am Anfang nicht auflisten. Die Verbindung wurde oben schon geprüft; fehlt
# die Liste, wird einfach alles hochgeladen (und nur in _astro/ aufgeräumt).
rm -f "$ARBEIT/alt.txt"
lftp_ausfuehren "get \"$MANIFEST\" -o \"$ARBEIT/alt.txt\"" no > /dev/null 2>&1 || true
if [[ ! -s "$ARBEIT/alt.txt" ]]; then
  : > "$ARBEIT/alt.txt"
  echo "Keine Liste vom letzten Upload gefunden: alle Dateien werden hochgeladen."
fi

# --- 3. Vergleichen -----------------------------------------------------------
pfade() { cut -c67- "$1" | LC_ALL=C sort -u; }
pfade "$ARBEIT/neu.txt" > "$ARBEIT/neu-pfade.txt"
pfade "$ARBEIT/alt.txt" > "$ARBEIT/alt-pfade.txt"

if [[ "${ALLES_HOCHLADEN:-}" == "true" ]]; then
  cp "$ARBEIT/neu-pfade.txt" "$ARBEIT/hoch.txt"
else
  # Zeilen (Prüfsumme + Pfad), die es in der alten Liste nicht exakt so gibt.
  LC_ALL=C sort "$ARBEIT/alt.txt" > "$ARBEIT/alt-sortiert.txt"
  LC_ALL=C sort "$ARBEIT/neu.txt" | LC_ALL=C comm -23 - "$ARBEIT/alt-sortiert.txt" \
    | cut -c67- | LC_ALL=C sort -u > "$ARBEIT/hoch.txt"
fi

# Zu löschen: (a) früher hochgeladen, jetzt nicht mehr im Build
LC_ALL=C comm -23 "$ARBEIT/alt-pfade.txt" "$ARBEIT/neu-pfade.txt" > "$ARBEIT/weg.txt"
# (b) generierte Dateien in _astro/, die nicht mehr zum Build gehören
if [[ -s "$ARBEIT/astro.txt" ]]; then
  sed -e 's#^\./##' -e 's#/$##' "$ARBEIT/astro.txt" \
    | grep -v -E '(^|/)\.\.?$' \
    | sed -E 's#^(_astro/)?#_astro/#' \
    | LC_ALL=C sort -u \
    | LC_ALL=C comm -23 - "$ARBEIT/neu-pfade.txt" >> "$ARBEIT/weg.txt" || true
fi
# Schutz: kontakt-config.php und die Liste selbst nie löschen, nur einfache Namen
LC_ALL=C sort -u "$ARBEIT/weg.txt" \
  | grep -v -E "(^|/)${GESCHUETZT//./\\.}\$" \
  | grep -v -x -F "$MANIFEST" \
  | LC_ALL=C grep -E '^[A-Za-z0-9._@+-][A-Za-z0-9._/@+-]*$' \
  | grep -v -E '(^|/)\.\.?(/|$)' > "$ARBEIT/weg-sicher.txt" || true

anzahl_neu=$(wc -l < "$ARBEIT/neu-pfade.txt")
anzahl_hoch=$(wc -l < "$ARBEIT/hoch.txt")
anzahl_weg=$(wc -l < "$ARBEIT/weg-sicher.txt")
echo "Dateien im Build: $anzahl_neu, davon geändert/neu: $anzahl_hoch, unverändert: $((anzahl_neu - anzahl_hoch)), zu löschen: $anzahl_weg"

# --- 4. Befehle erzeugen -----------------------------------------------------
{
  # Zuerst Bilder, CSS, JS usw., danach HTML und .htaccess, damit Seiten nie auf
  # Dateien verweisen, die noch fehlen.
  grep -v -E '(\.html|(^|/)\.htaccess)$' "$ARBEIT/hoch.txt" || true
  grep -E '(\.html|(^|/)\.htaccess)$' "$ARBEIT/hoch.txt" || true
} > "$ARBEIT/hoch-reihenfolge.txt"

{
  # benötigte Ordner anlegen (vorhandene sind kein Fehler)
  { grep '/' "$ARBEIT/hoch-reihenfolge.txt" || true; } | sed 's#/[^/]*$##' | LC_ALL=C sort -u \
    | while read -r ordner; do echo "mkdir -p -f \"$ordner\""; done
  while read -r datei; do
    echo "echo \"Hochladen: $datei\""
    echo "put \"$DIST/$datei\" -o \"$datei\""
  done < "$ARBEIT/hoch-reihenfolge.txt"
  while read -r datei; do
    echo "echo \"Löschen: $datei\""
    echo "rm -f \"$datei\""
  done < "$ARBEIT/weg-sicher.txt"
} > "$ARBEIT/upload.lftp"

# --- 5. Ausführen ------------------------------------------------------------
if [[ -s "$ARBEIT/upload.lftp" ]]; then
  lftp_ausfuehren "$(cat "$ARBEIT/upload.lftp")"
fi

# Leer gewordene Ordner entfernen (nur Ordner, die im Build nicht mehr vorkommen;
# rmdir scheitert ohnehin, wenn noch etwas darin liegt).
{ grep '/' "$ARBEIT/weg-sicher.txt" || true; } | sed 's#/[^/]*$##' | LC_ALL=C sort -u -r \
  | while read -r ordner; do
      if ! grep -q "^$ordner/" "$ARBEIT/neu-pfade.txt"; then echo "rmdir \"$ordner\""; fi
    done > "$ARBEIT/rmdir.lftp"
if [[ -s "$ARBEIT/rmdir.lftp" ]]; then
  lftp_ausfuehren "$(cat "$ARBEIT/rmdir.lftp")" no || true
fi

# Neue Liste erst ganz am Ende ablegen. Bricht der Lauf vorher ab, vergleicht der
# nächste Lauf noch mit der alten Liste und lädt die fehlenden Dateien nach.
lftp_ausfuehren "put \"$ARBEIT/neu.txt\" -o \"$MANIFEST\""

echo "Fertig: $anzahl_hoch hochgeladen, $((anzahl_neu - anzahl_hoch)) unverändert übersprungen, $anzahl_weg gelöscht."
