import { getFile, putFile } from './github';

const SETTINGS_PATH = 'cloudblog/settings.json';
const THEME_PATH = 'cloudblog/theme.css';

export interface SiteSettings {
  locale: 'en' | 'zh-CN';
  title: string;
  description: string;
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
  locale: 'en',
  title: 'Cloud Blog',
  description: 'Blog powered by Astro + Cloudflare Pages',
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

function mergeSettings(parsed: Record<string, unknown>): SiteSettings {
  return {
    ...defaults,
    ...parsed,
    seo: { ...defaults.seo, ...(parsed.seo || {}) },
    templates: { ...defaults.templates, ...(parsed.templates || {}) },
    templateContent: { ...defaults.templateContent, ...(parsed.templateContent || {}) },
  };
}

export async function readSettingsWithMeta(runtimeEnv?: Record<string, string>): Promise<{ settings: SiteSettings; sha: string | null }> {
  try {
    const file = await getFile(SETTINGS_PATH, runtimeEnv);
    if (!file) {
      return { settings: defaults, sha: null };
    }
    const parsed = JSON.parse(file.content) as Record<string, unknown>;
    return {
      settings: mergeSettings(parsed),
      sha: file.sha || null,
    };
  } catch {
    return { settings: defaults, sha: null };
  }
}

export async function readSettings(runtimeEnv?: Record<string, string>): Promise<SiteSettings> {
  return (await readSettingsWithMeta(runtimeEnv)).settings;
}

export async function saveSettings(
  settings: SiteSettings,
  runtimeEnv?: Record<string, string>,
  sha?: string | null,
): Promise<string | null> {
  return await putFile(
    SETTINGS_PATH,
    JSON.stringify(settings, null, 2),
    'chore: update site settings',
    typeof sha === 'undefined' ? undefined : { sha },
    runtimeEnv,
  );
}

export async function readThemeCssWithMeta(runtimeEnv?: Record<string, string>): Promise<{ css: string; sha: string | null }> {
  try {
    const file = await getFile(THEME_PATH, runtimeEnv);
    return {
      css: file?.content ?? '',
      sha: file?.sha || null,
    };
  } catch {
    return { css: '', sha: null };
  }
}

export async function readThemeCss(runtimeEnv?: Record<string, string>): Promise<string> {
  return (await readThemeCssWithMeta(runtimeEnv)).css;
}

export async function saveThemeCss(css: string, runtimeEnv?: Record<string, string>, sha?: string | null): Promise<string | null> {
  return await putFile(
    THEME_PATH,
    css,
    'chore: update theme css',
    typeof sha === 'undefined' ? undefined : { sha },
    runtimeEnv,
  );
}
