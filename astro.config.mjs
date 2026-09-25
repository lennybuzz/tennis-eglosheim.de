// @ts-check
import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';

// https://astro.build/config
export default defineConfig({
  site: 'https://www.tennis-eglosheim.de',
  trailingSlash: 'always',
  integrations: [
    sitemap({
      filter: (page) => !page.includes('/kontakt/danke/') && !page.includes('/kontakt/fehler/'),
    }),
  ],
});
