// @ts-check
import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';

// https://astro.build/config
// SITE und BASE_PATH setzt nur der GitHub-Pages-Workflow (Testversion).
// Ohne sie wird wie bisher für www.tennis-eglosheim.de gebaut.
export default defineConfig({
  site: process.env.SITE ?? 'https://www.tennis-eglosheim.de',
  base: process.env.BASE_PATH ?? '/',
  trailingSlash: 'always',
  integrations: [
    sitemap({
      filter: (page) => !page.includes('/kontakt/danke/') && !page.includes('/kontakt/fehler/'),
    }),
  ],
});
