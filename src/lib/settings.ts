import { getFile, putFile } from './github';

const SETTINGS_PATH = 'cloudblog/settings.json';
const THEME_PATH = 'cloudblog/theme.css';

export interface CategoryConfig {
  slug: string;
  name: string;
  description?: string;
  seoTitle?: string;
  seoDescription?: string;
  template?: 'classic' | 'grid' | 'minimal';
  sort?: number;
  showInMenu?: boolean;
  adTop?: string;
  adSidebar?: string;
  modules?: Array<'hero' | 'list' | 'sidebar'>;
}

export interface SiteSettings {
  title: string;
  description: string;
  categories: CategoryConfig[];
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
  adSlots: {
    homeTop: string;
    listTop: string;
    postTop: string;
    postBottom: string;
    postSidebar: string;
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
  adSlots: {
    homeTop: '',
    listTop: '',
    postTop: '',
    postBottom: '',
    postSidebar: '',
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

function normalizeCategories(input: unknown): CategoryConfig[] {
  if (!Array.isArray(input)) return [];
  return input
    .map((item) => {
      if (!item || typeof item !== 'object') return null;
      const row = item as Record<string, unknown>;
      const slug = String(row.slug || '').trim();
      const name = String(row.name || '').trim();
      if (!slug || !name) return null;
      return {
        slug,
        name,
        description: String(row.description || '').trim() || undefined,
        seoTitle: String(row.seoTitle || '').trim() || undefined,
        seoDescription: String(row.seoDescription || '').trim() || undefined,
        template: row.template === 'grid' || row.template === 'minimal' ? row.template : 'classic',
        sort: Number.isFinite(Number(row.sort)) ? Number(row.sort) : 0,
        showInMenu: String(row.showInMenu ?? 'false') === 'true',
        adTop: String(row.adTop || '').trim() || undefined,
        adSidebar: String(row.adSidebar || '').trim() || undefined,
        modules: Array.isArray(row.modules)
          ? row.modules.filter((m): m is 'hero' | 'list' | 'sidebar' => m === 'hero' || m === 'list' || m === 'sidebar')
          : undefined,
      } satisfies CategoryConfig;
    })
    .filter((item): item is CategoryConfig => Boolean(item))
    .sort((a, b) => (a.sort || 0) - (b.sort || 0));
}

export async function readSettings(runtimeEnv?: Record<string, string>): Promise<SiteSettings> {
  try {
    const file = await getFile(SETTINGS_PATH, runtimeEnv);
    if (!file) return defaults;
    const parsed = JSON.parse(file.content);
    return {
      ...defaults,
      ...parsed,
      categories: normalizeCategories(parsed.categories),
      seo: { ...defaults.seo, ...(parsed.seo || {}) },
      adSlots: { ...defaults.adSlots, ...(parsed.adSlots || {}) },
      templates: { ...defaults.templates, ...(parsed.templates || {}) },
      templateContent: { ...defaults.templateContent, ...(parsed.templateContent || {}) },
    };
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
