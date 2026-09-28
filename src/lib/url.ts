/**
 * Interne Links immer über url() schreiben, z. B. href={url('/kontakt/')}.
 * Auf www.tennis-eglosheim.de ist der Basispfad "/", in der Testversion auf
 * GitHub Pages "/tennis-eglosheim.de/" – url() setzt ihn automatisch davor.
 */
const basis = import.meta.env.BASE_URL.replace(/\/$/, '');

export const url = (pfad: string) => `${basis}${pfad}`;

/** true, wenn die Seite nicht unter der echten Domain läuft (GitHub-Pages-Test) */
export const testversion = basis !== '';
