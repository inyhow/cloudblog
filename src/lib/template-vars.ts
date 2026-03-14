export interface TemplateContext {
  site: {
    title: string;
    description: string;
    footer: string;
    menuHtml: string;
  };
  post?: {
    title: string;
    description: string;
    content: string;
    pubDate: string;
    category: string;
    tags: string[];
    coverImage: string;
    price: string;
    affiliateLink: string;
    heroImage: string;
    [key: string]: any;
  };
  variables: Record<string, string | undefined>;
}

export function renderTemplateVariables(
  source: string,
  ctx: TemplateContext,
): string {
  if (!source) return '';

  const flatVars: Record<string, any> = {
    'site.title': ctx.site.title,
    'site.description': ctx.site.description,
    'site.footer': ctx.site.footer,
    'site.menu': ctx.site.menuHtml,
    ...ctx.variables,
  };

  if (ctx.post) {
    flatVars['post.title'] = ctx.post.title;
    flatVars['post.description'] = ctx.post.description;
    flatVars['post.content'] = ctx.post.content;
    flatVars['post.pubDate'] = ctx.post.pubDate;
    flatVars['post.category'] = ctx.post.category;
    flatVars['post.coverImage'] = ctx.post.coverImage;
    flatVars['post.price'] = ctx.post.price || ctx.post.customData?.price || '';
    flatVars['post.affiliateLink'] = ctx.post.affiliateLink || ctx.post.customData?.affiliateLink || '#';
    flatVars['post.heroImage'] = ctx.post.heroImage || ctx.post.customData?.heroImage || ctx.post.coverImage || '';
    
    // Legacy support for plain tags
    flatVars['title'] = ctx.post.title;
    flatVars['content'] = ctx.post.content;
    flatVars['price'] = flatVars['post.price'];
    flatVars['affiliateLink'] = flatVars['post.affiliateLink'];
    flatVars['heroImage'] = flatVars['post.heroImage'];
  }

  return source.replace(/\{\{\s*([a-zA-Z0-9._-]+)\s*\}\}/g, (_, key) => {
    const value = flatVars[key];
    return value == null ? '' : String(value);
  });
}

export function renderMenuAsHtml(menu: Array<{ label: string; href: string }>): string {
  return menu
    .map(
      (item) =>
        `<a href="${item.href}" class="text-sm font-semibold hover:text-primary transition-colors">${item.label}</a>`,
    )
    .join('\n');
}
