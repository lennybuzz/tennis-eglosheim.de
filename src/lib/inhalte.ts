// Zugriff auf die zentrale Inhaltsdatei src/data/inhalte.json.
// Die Datei wird beim Build eingelesen – nach Änderungen neu bauen.
import inhalte from '../data/inhalte.json';

export default inhalte;
export const { kontakt, links } = inhalte;

export interface Mannschaft {
  name: string;
  liga: string;
}
/** Gemeldete Mannschaften der aktuellen Saison – leer, solange für die Saison noch nichts gemeldet ist. */
export const mannschaften: Mannschaft[] = inhalte.spielbetrieb.teams as Mannschaft[];

export interface ArchivSaison {
  saison: string;
  teams: Mannschaft[];
  hinweis: string;
}
/** Abgeschlossene Saisons, neueste zuerst. */
export const archiv: ArchivSaison[] = inhalte.spielbetrieb.archiv;

/** Kleinster Jahresbeitrag über alle Tarife, z. B. 115 */
export function minBeitrag(): number {
  const preise = inhalte.beitraege.gruppen
    .flatMap((g) => g.tarife)
    .map((t) => parseInt(t.preis.replace(/[^0-9]/g, ''), 10))
    .filter((n) => !Number.isNaN(n));
  return Math.min(...preise);
}

export function initialen(name: string): string {
  return name
    .split(' ')
    .map((w) => w[0])
    .join('');
}

const escapeHtml = (s: string) =>
  s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

/**
 * Text sicher als HTML ausgeben (für FAQ-Antworten):
 * - Links in der Schreibweise [Linktext](/pfad/) werden verlinkt
 * - E-Mail-Adressen werden automatisch zu mailto-Links
 */
export function mitLinks(text: string): string {
  return escapeHtml(text).replace(
    /\[([^\]]+)\]\(([^)\s]+)\)|[\w.+-]+@[\w-]+\.[\w.-]*\w/g,
    (treffer, linkText?: string, ziel?: string) =>
      linkText ? `<a href="${ziel}">${linkText}</a>` : `<a href="mailto:${treffer}">${treffer}</a>`,
  );
}
