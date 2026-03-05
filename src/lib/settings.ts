import { getFile, putFile } from './github';

const SETTINGS_PATH = 'cloudblog/settings.json';
const THEME_PATH = 'cloudblog/theme.css';

export interface SiteSettings {
  title: string;
  description: string;
  menu: Array<{ label: string; href: string }>;
  footer: string;
  analyticsJs: string;
  giscus?: {
    repo: string;
    repoId: string;
    category: string;
    categoryId: string;
  };
}

const defaults: SiteSettings = {
  title: 'Cloud Blog',
  description: 'Blog powered by Astro + Cloudflare Pages',
  menu: [
    { label: '首页', href: '/' },
    { label: '博客', href: '/blog' },
  ],
  footer: '© Cloud Blog',
  analyticsJs: '',
};

export async function readSettings(): Promise<SiteSettings> {
  try {
    const file = await getFile(SETTINGS_PATH);
    if (!file) return defaults;
    return { ...defaults, ...JSON.parse(file.content) };
  } catch {
    return defaults;
  }
}

export async function saveSettings(settings: SiteSettings): Promise<void> {
  await putFile(SETTINGS_PATH, JSON.stringify(settings, null, 2), 'chore: update site settings');
}

export async function readThemeCss(): Promise<string> {
  try {
    const file = await getFile(THEME_PATH);
    return file?.content ?? '';
  } catch {
    return '';
  }
}

export async function saveThemeCss(css: string): Promise<void> {
  await putFile(THEME_PATH, css, 'chore: update theme css');
}
