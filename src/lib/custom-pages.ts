import { getFile, listDir } from './github';
import { markdownToHtml } from './posts';

const PAGES_DIR = 'cloudblog/pages';

export interface CustomPage {
  slug: string;
  title: string;
  description: string;
  content: string;
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
    };
  } catch {
    return null;
  }
}

export async function listCustomPages(runtimeEnv?: Record<string, string>): Promise<string[]> {
  try {
    const paths = await listDir(PAGES_DIR, runtimeEnv);
    return paths
      .filter((path) => path.endsWith('.md'))
      .map((path) => path.split('/').pop()!.replace(/\.md$/, ''));
  } catch {
    return [];
  }
}
