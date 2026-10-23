/**
 * Interne Links immer über url() schreiben, z. B. href={url('/kontakt/')}.
 * Auf www.tennis-eglosheim.de ist der Basispfad "/", in einem Testbuild im
 * Unterordner z. B. "/test/" – url() setzt ihn automatisch davor.
 */
const basis = import.meta.env.BASE_URL.replace(/\/$/, '');

export const url = (pfad: string) => `${basis}${pfad}`;

/** true, wenn die Seite nicht unter der echten Domain läuft (Testbuild im Unterordner) */
export const testversion = basis !== '';
