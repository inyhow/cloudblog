import type { APIRoute } from 'astro';
import { requireRole } from '../../../lib/auth';
import { appendOpsLog } from '../../../lib/ops-log';
import { readSettingsWithMeta, readThemeCssWithMeta, saveSettings, saveThemeCss, type SiteSettings } from '../../../lib/settings';
import { getRuntimeEnv } from '../../../lib/runtime-env';

function asRecord(value: unknown): Record<string, unknown> {
  return value && typeof value === 'object' && !Array.isArray(value) ? (value as Record<string, unknown>) : {};
}

function asString(value: unknown): string {
  return typeof value === 'string' ? value : value == null ? '' : String(value);
}

function asSha(value: unknown): string | undefined {
  return typeof value === 'string' && value ? value : undefined;
}

function normalizeMenu(input: unknown): SiteSettings['menu'] {
  if (!Array.isArray(input)) return [];
  return input
    .map((item) => {
      const row = asRecord(item);
      const label = asString(row.label).trim();
      const href = asString(row.href).trim();
      if (!label || !href) return null;
      return { label, href };
    })
    .filter((item): item is SiteSettings['menu'][number] => item !== null);
}

function normalizeTemplateVariables(input: unknown): SiteSettings['templateVariables'] {
  const record = asRecord(input);
  return Object.fromEntries(
    Object.entries(record)
      .map(([key, value]) => [key.trim(), asString(value)] as const)
      .filter(([key]) => key),
  );
}

function normalizeTemplates(input: unknown): SiteSettings['templates'] {
  const record = asRecord(input);
  return {
    home: record.home === 'magazine' || record.home === 'minimal' || record.home === 'imoo' ? record.home : 'classic',
    post: record.post === 'cover' || record.post === 'minimal' || record.post === 'imoo' ? record.post : 'classic',
    tag: record.tag === 'grid' || record.tag === 'minimal' || record.tag === 'imoo' ? record.tag : 'classic',
    page: record.page === 'minimal' || record.page === 'imoo' ? record.page : 'classic',
  };
}

function normalizeTemplateContent(input: unknown): SiteSettings['templateContent'] {
  const record = asRecord(input);
  return {
    homeHeroHtml: asString(record.homeHeroHtml),
    postHeaderHtml: asString(record.postHeaderHtml),
    tagHeaderHtml: asString(record.tagHeaderHtml),
    pageHeaderHtml: asString(record.pageHeaderHtml),
  };
}

function normalizeSeo(input: unknown): SiteSettings['seo'] {
  const record = asRecord(input);
  return {
    homeTitle: asString(record.homeTitle),
    homeDescription: asString(record.homeDescription),
    googleAnalyticsId: asString(record.googleAnalyticsId),
    googleSiteVerification: asString(record.googleSiteVerification),
    googleAdsenseClient: asString(record.googleAdsenseClient),
  };
}

function normalizeGiscus(input: unknown): SiteSettings['giscus'] {
  const record = asRecord(input);
  const repo = asString(record.repo).trim();
  const repoId = asString(record.repoId).trim();
  const category = asString(record.category).trim();
  const categoryId = asString(record.categoryId).trim();
  if (!repo && !repoId && !category && !categoryId) {
    return undefined;
  }
  return { repo, repoId, category, categoryId };
}

function normalizeSettingsPayload(body: Record<string, unknown>): SiteSettings {
  return {
    locale: body.locale === 'zh-CN' ? 'zh-CN' : 'en',
    title: asString(body.title),
    description: asString(body.description),
    menu: normalizeMenu(body.menu),
    footer: asString(body.footer),
    analyticsJs: asString(body.analyticsJs),
    seo: normalizeSeo(body.seo),
    templateVariables: normalizeTemplateVariables(body.templateVariables),
    templates: normalizeTemplates(body.templates),
    templateContent: normalizeTemplateContent(body.templateContent),
    giscus: normalizeGiscus(body.giscus),
  };
}

export const GET: APIRoute = async (context) => {
  const denied = requireRole(context, 'admin');
  if (denied) return denied;
  const runtimeEnv = getRuntimeEnv(context.locals);
  const [{ settings, sha: settingsSha }, { css: themeCss, sha: themeSha }] = await Promise.all([
    readSettingsWithMeta(runtimeEnv),
    readThemeCssWithMeta(runtimeEnv),
  ]);
  return new Response(JSON.stringify({
    ...settings,
    themeCss,
    _meta: {
      settingsSha,
      themeSha,
    },
  }));
};

export const PUT: APIRoute = async (context) => {
  const denied = requireRole(context, 'admin');
  if (denied) return denied;

  const body = asRecord(await context.request.json());
  const meta = asRecord(body._meta);
  const runtimeEnv = getRuntimeEnv(context.locals);
  const saveTheme = body.saveTheme === true;
  const nextSettingsSha = await saveSettings(normalizeSettingsPayload(body), runtimeEnv, asSha(meta.settingsSha));
  let nextThemeSha = asSha(meta.themeSha) ?? null;

  if (saveTheme) {
    nextThemeSha = await saveThemeCss(asString(body.themeCss), runtimeEnv, asSha(meta.themeSha));
  }

  appendOpsLog(
    {
      at: new Date().toISOString(),
      action: saveTheme ? 'settings.update+theme' : 'settings.update',
      target: saveTheme ? 'site+theme.css' : 'site',
    },
    runtimeEnv,
  ).catch((error) => {
    console.error('appendOpsLog(settings.update) failed', error);
  });

  return new Response(JSON.stringify({
    ok: true,
    settingsSha: nextSettingsSha,
    themeSha: nextThemeSha,
  }));
};
