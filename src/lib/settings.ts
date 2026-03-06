import { getFile, putFile } from './github';

const SETTINGS_PATH = 'cloudblog/settings.json';
const THEME_PATH = 'cloudblog/theme.css';

export interface SiteSettings {
  title: string;
  description: string;
  categories: Array<{ slug: string; name: string }>;
  menu: Array<{ label: string; href: string }>;
  footer: string;
  analyticsJs: string;
  seo: {
    homeTitle: string;
    homeDescription: string;
    googleAnalyticsId: string;
    googleSiteVerification: string;
    googleAdsenseClient: string;
  };
  templateVariables: Record<string, string>;
  templates: {
    home: 'classic' | 'magazine' | 'minimal';
    post: 'classic' | 'cover' | 'minimal';
    tag: 'classic' | 'grid' | 'minimal';
    page: 'classic' | 'minimal';
  };
  templateContent: {
    homeHeroHtml: string;
    postHeaderHtml: string;
    tagHeaderHtml: string;
    pageHeaderHtml: string;
  };
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
  categories: [],
  menu: [
    { label: 'Home', href: '/' },
    { label: 'Blog', href: '/blog' },
  ],
  footer: '© Cloud Blog',
  analyticsJs: '',
  seo: {
    homeTitle: 'Cloud Blog',
    homeDescription: 'Blog powered by Astro + Cloudflare Pages',
    googleAnalyticsId: '',
    googleSiteVerification: '',
    googleAdsenseClient: '',
  },
  templateVariables: {},
  templates: {
    home: 'classic',
    post: 'classic',
    tag: 'classic',
    page: 'classic',
  },
  templateContent: {
    homeHeroHtml: '',
    postHeaderHtml: '',
    tagHeaderHtml: '',
    pageHeaderHtml: '',
  },
};

export async function readSettings(runtimeEnv?: Record<string, string>): Promise<SiteSettings> {
  try {
    const file = await getFile(SETTINGS_PATH, runtimeEnv);
    if (!file) return defaults;
    return { ...defaults, ...JSON.parse(file.content) };
  } catch {
    return defaults;
  }
}

export async function saveSettings(settings: SiteSettings, runtimeEnv?: Record<string, string>): Promise<void> {
  await putFile(
    SETTINGS_PATH,
    JSON.stringify(settings, null, 2),
    'chore: update site settings',
    undefined,
    runtimeEnv,
  );
}

export async function readThemeCss(runtimeEnv?: Record<string, string>): Promise<string> {
  try {
    const file = await getFile(THEME_PATH, runtimeEnv);
    return file?.content ?? '';
  } catch {
    return '';
  }
}

export async function saveThemeCss(css: string, runtimeEnv?: Record<string, string>): Promise<void> {
  await putFile(THEME_PATH, css, 'chore: update theme css', undefined, runtimeEnv);
}
