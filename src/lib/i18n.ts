import en from '../i18n/en';
import zhCN from '../i18n/zh-CN';

const locales = {
  en,
  'zh-CN': zhCN,
} as const;

export type LocaleCode = keyof typeof locales;

export function getLocalePack(locale?: string) {
  return locales[(locale as LocaleCode) || 'en'] || locales.en;
}

export function tr(locale: string | undefined, path: string, fallback = ''): string {
  const pack = getLocalePack(locale);
  const value = path.split('.').reduce((acc: any, key) => (acc && typeof acc === 'object' ? acc[key] : undefined), pack as any);
  return typeof value === 'string' ? value : fallback;
}
