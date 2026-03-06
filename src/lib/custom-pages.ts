import { getFile, listDir, putFile } from './github';
import { markdownToHtml } from './posts';

const PAGES_DIR = 'cloudblog/pages';

export interface CustomPage {
  slug: string;
  title: string;
  description: string;
  content: string;
  rawContent: string;
}

function normalizeSlug(value: string): string {
  return value
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9- ]/g, '')
    .replace(/\s+/g, '-');
}

function parseFrontmatter(raw: string): { data: Record<string, string>; content: string } {
  if (!raw.startsWith('---\n')) return { data: {}, content: raw };
  const end = raw.indexOf('\n---\n', 4);
  if (end === -1) return { data: {}, content: raw };
  const fm = raw.slice(4, end);
  const content = raw.slice(end + 5);
  const data: Record<string, string> = {};
  for (const line of fm.split('\n')) {
    const idx = line.indexOf(':');
    if (idx === -1) continue;
    data[line.slice(0, idx).trim()] = line.slice(idx + 1).trim().replace(/^"(.*)"$/, '$1');
  }
  return { data, content };
}

function stringifyFrontmatter(data: { title: string; description: string }, content: string): string {
  return `---\ntitle: "${data.title.replace(/"/g, '\\"')}"\ndescription: "${data.description.replace(/"/g, '\\"')}"\n---\n\n${content}`;
}

export async function getCustomPageBySlug(
  slug: string,
  runtimeEnv?: Record<string, string>,
): Promise<CustomPage | null> {
  try {
    const file = await getFile(`${PAGES_DIR}/${slug}.md`, runtimeEnv);
    if (!file) return null;
    const parsed = parseFrontmatter(file.content);
    return {
      slug,
      title: parsed.data.title || slug,
      description: parsed.data.description || '',
      content: markdownToHtml(parsed.content),
      rawContent: parsed.content,
    };
  } catch {
    return null;
  }
}

export async function listCustomPages(
  runtimeEnv?: Record<string, string>,
): Promise<Array<Pick<CustomPage, 'slug' | 'title' | 'description'>>> {
  try {
    const paths = await listDir(PAGES_DIR, runtimeEnv);
    const pages = await Promise.all(
      paths
        .filter((path) => path.endsWith('.md'))
        .map(async (path) => {
          const slug = path.split('/').pop()!.replace(/\.md$/, '');
          const page = await getCustomPageBySlug(slug, runtimeEnv);
          if (!page) return null;
          return { slug: page.slug, title: page.title, description: page.description };
        }),
    );
    return pages.filter((page): page is Pick<CustomPage, 'slug' | 'title' | 'description'> => Boolean(page));
  } catch {
    return [];
  }
}

export async function saveCustomPage(
  input: { slug?: string; title: string; description?: string; content: string },
  runtimeEnv?: Record<string, string>,
): Promise<string> {
  const slug = normalizeSlug(input.slug || input.title);
  const body = stringifyFrontmatter(
    {
      title: input.title,
      description: input.description || '',
    },
    input.content,
  );
  await putFile(`${PAGES_DIR}/${slug}.md`, body, `feat: update page ${slug}`, undefined, runtimeEnv);
  return slug;
}
