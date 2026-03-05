import { marked } from 'marked';
import { getFile, listDir, putFile } from './github';

const POSTS_DIR = 'cloudblog/posts';

export interface BlogPost {
  slug: string;
  title: string;
  description: string;
  tags: string[];
  pubDate: string;
  updatedDate?: string;
  content: string;
}

function normalizeSlug(value: string): string {
  return value
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9\u4e00-\u9fa5- ]/g, '')
    .replace(/\s+/g, '-');
}

function filePathFromSlug(slug: string): string {
  return `${POSTS_DIR}/${slug}.md`;
}

function parseFrontmatter(raw: string): { data: Record<string, unknown>; content: string } {
  if (!raw.startsWith('---\n')) return { data: {}, content: raw };
  const end = raw.indexOf('\n---\n', 4);
  if (end === -1) return { data: {}, content: raw };
  const fm = raw.slice(4, end);
  const content = raw.slice(end + 5);
  const data: Record<string, unknown> = {};
  let currentArrayKey = '';
  for (const line of fm.split('\n')) {
    if (line.startsWith('- ') && currentArrayKey) {
      const arr = (data[currentArrayKey] as string[]) || [];
      arr.push(line.slice(2).trim());
      data[currentArrayKey] = arr;
      continue;
    }
    const idx = line.indexOf(':');
    if (idx === -1) continue;
    const key = line.slice(0, idx).trim();
    const value = line.slice(idx + 1).trim();
    if (value === '') {
      currentArrayKey = key;
      data[key] = [];
    } else {
      currentArrayKey = '';
      data[key] = value;
    }
  }
  return { data, content };
}

function stringifyFrontmatter(data: {
  title: string;
  description: string;
  tags: string[];
  pubDate: string;
  updatedDate: string;
}, content: string): string {
  const tagsBlock = data.tags.map((t) => `  - ${t}`).join('\n');
  return `---\ntitle: ${data.title}\ndescription: ${data.description}\ntags:\n${tagsBlock || '  -'}\npubDate: ${data.pubDate}\nupdatedDate: ${data.updatedDate}\n---\n\n${content}`;
}

async function listPostsCore(runtimeEnv?: Record<string, string>): Promise<BlogPost[]> {
  const paths = await listDir(POSTS_DIR, runtimeEnv);
  const posts = await Promise.all(
    paths.filter((p) => p.endsWith('.md')).map(async (path) => {
      const f = await getFile(path, runtimeEnv);
      if (!f) return null;
      const parsed = parseFrontmatter(f.content);
      return {
        slug: path.split('/').pop()!.replace(/\.md$/, ''),
        title: String(parsed.data.title ?? 'Untitled'),
        description: String(parsed.data.description ?? ''),
        tags: Array.isArray(parsed.data.tags) ? (parsed.data.tags as string[]) : [],
        pubDate: String(parsed.data.pubDate ?? new Date().toISOString()),
        updatedDate: parsed.data.updatedDate ? String(parsed.data.updatedDate) : undefined,
        content: parsed.content,
      } satisfies BlogPost;
    }),
  );
  return posts
    .filter((p): p is BlogPost => Boolean(p))
    .sort((a, b) => new Date(b.pubDate).valueOf() - new Date(a.pubDate).valueOf());
}

export async function listPosts(runtimeEnv?: Record<string, string>): Promise<BlogPost[]> {
  try {
    return await listPostsCore(runtimeEnv);
  } catch {
    return [];
  }
}

export async function listPostsStrict(runtimeEnv?: Record<string, string>): Promise<BlogPost[]> {
  return listPostsCore(runtimeEnv);
}

export async function getPostBySlug(slug: string, runtimeEnv?: Record<string, string>): Promise<BlogPost | null> {
  try {
    const path = filePathFromSlug(slug);
    const f = await getFile(path, runtimeEnv);
    if (!f) return null;
    const parsed = parseFrontmatter(f.content);
    return {
      slug,
      title: String(parsed.data.title ?? 'Untitled'),
      description: String(parsed.data.description ?? ''),
      tags: Array.isArray(parsed.data.tags) ? (parsed.data.tags as string[]) : [],
      pubDate: String(parsed.data.pubDate ?? new Date().toISOString()),
      updatedDate: parsed.data.updatedDate ? String(parsed.data.updatedDate) : undefined,
      content: parsed.content,
    };
  } catch {
    return null;
  }
}

export async function savePost(
  input: Partial<BlogPost> & { title: string; content: string; slug?: string },
  runtimeEnv?: Record<string, string>,
) {
  const slug = normalizeSlug(input.slug || input.title);
  const body = stringifyFrontmatter({
    title: input.title,
    description: input.description ?? '',
    tags: input.tags ?? [],
    pubDate: input.pubDate ?? new Date().toISOString(),
    updatedDate: new Date().toISOString(),
  }, input.content);
  await putFile(filePathFromSlug(slug), body, `feat: update post ${slug}`, undefined, runtimeEnv);
  return slug;
}

export function markdownToHtml(markdown: string): string {
  return marked.parse(markdown) as string;
}
