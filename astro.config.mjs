// @ts-check

import cloudflare from '@astrojs/cloudflare';
import mdx from '@astrojs/mdx';
import sitemap from '@astrojs/sitemap';
import { defineConfig } from 'astro/config';
import tailwindcss from '@tailwindcss/vite';

// https://astro.build/config
export default defineConfig({
	site: 'https://example.com/',
	output: 'server',
	adapter: cloudflare(),
	integrations: [mdx(), sitemap()],
	vite: {
		plugins: [tailwindcss()],
	},
});
