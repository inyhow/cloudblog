import { marked } from 'marked';
import { markedHighlight } from 'marked-highlight';
import hljs from 'highlight.js';
import { deleteFile, getFile, listDir, putFile } from './github';

const POSTS_DIR = 'cloudblog/posts';
const REVISIONS_DIR = 'cloudblog/revisions';

marked.use(
  markedHighlight({
    langPrefix: 'hljs language-',
    highlight(code, lang) {
      const validLang = lang && hljs.getLanguage(lang) ? lang : 'plaintext';
      return hljs.highlight(code, { language: validLang }).value;
    },
  }),
);

export interface BlogPost {
  slug: string;
  title: string;
  description: string;
  category?: string;
  tags: string[];
  status: 'draft' | 'review' | 'scheduled' | 'published' | 'archived' | 'deleted';
  reviewNote?: string;
  pinned: boolean;
  coverImage?: string;
  pubDate: string;
  updatedDate?: string;
  scheduledAt?: string;
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
      data[key] = value.replace(/^"(.*)"$/, '$1');
    }
  }
  return { data, content };
}

function quote(value: string): string {
  return `"${value.replace(/"/g, '\\"')}"`;
}

function stringifyFrontmatter(data: {
  title: string;
  description: string;
  category?: string;
  tags: string[];
  status: 'draft' | 'review' | 'scheduled' | 'published' | 'archived' | 'deleted';
  reviewNote?: string;
  pinned: boolean;
  coverImage?: string;
  pubDate: string;
  updatedDate: string;
  scheduledAt?: string;
}, content: string): string {
  const tagsBlock = data.tags.map((t) => `  - ${t}`).join('\n');
  return `---\ntitle: ${quote(data.title)}\ndescription: ${quote(data.description)}\ncategory: ${quote(data.category || '')}\ntags:\n${tagsBlock || '  -'}\nstatus: ${data.status}\nreviewNote: ${quote(data.reviewNote || '')}\npinned: ${data.pinned}\ncoverImage: ${quote(data.coverImage || '')}\npubDate: ${data.pubDate}\nupdatedDate: ${data.updatedDate}\nscheduledAt: ${quote(data.scheduledAt || '')}\n---\n\n${content}`;
}

function normalizeStatus(value: unknown): BlogPost['status'] {
  if (value === 'draft' || value === 'review' || value === 'scheduled' || value === 'published' || value === 'archived' || value === 'deleted') {
    return value;
  }
  return 'published';
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
        category: String(parsed.data.category ?? '').replace(/^"|"$/g, '') || undefined,
        tags: Array.isArray(parsed.data.tags) ? (parsed.data.tags as string[]) : [],
        status: normalizeStatus(parsed.data.status),
        reviewNote: String(parsed.data.reviewNote ?? '').replace(/^"|"$/g, '') || undefined,
        pinned: String(parsed.data.pinned ?? 'false') === 'true',
        coverImage: String(parsed.data.coverImage ?? '').replace(/^"|"$/g, '') || undefined,
        pubDate: String(parsed.data.pubDate ?? new Date().toISOString()),
        updatedDate: parsed.data.updatedDate ? String(parsed.data.updatedDate) : undefined,
        scheduledAt: String(parsed.data.scheduledAt ?? '').replace(/^"|"$/g, '') || undefined,
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
      category: String(parsed.data.category ?? '').replace(/^"|"$/g, '') || undefined,
      tags: Array.isArray(parsed.data.tags) ? (parsed.data.tags as string[]) : [],
      status: normalizeStatus(parsed.data.status),
      reviewNote: String(parsed.data.reviewNote ?? '').replace(/^"|"$/g, '') || undefined,
      pinned: String(parsed.data.pinned ?? 'false') === 'true',
      coverImage: String(parsed.data.coverImage ?? '').replace(/^"|"$/g, '') || undefined,
      pubDate: String(parsed.data.pubDate ?? new Date().toISOString()),
      updatedDate: parsed.data.updatedDate ? String(parsed.data.updatedDate) : undefined,
      scheduledAt: String(parsed.data.scheduledAt ?? '').replace(/^"|"$/g, '') || undefined,
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
  const existing = await getFile(filePathFromSlug(slug), runtimeEnv);
  if (existing?.content) {
    const stamp = new Date().toISOString().replace(/[:.]/g, '-');
    await putFile(
      `${REVISIONS_DIR}/${slug}/${stamp}.md`,
      existing.content,
      `chore: snapshot ${slug} ${stamp}`,
      undefined,
      runtimeEnv,
    );
  }
  const body = stringifyFrontmatter({
    title: input.title,
    description: input.description ?? '',
    category: input.category ?? '',
    tags: input.tags ?? [],
    status: input.status ?? 'published',
    reviewNote: input.reviewNote ?? '',
    pinned: input.pinned ?? false,
    coverImage: input.coverImage ?? '',
    pubDate: input.pubDate ?? new Date().toISOString(),
    updatedDate: new Date().toISOString(),
    scheduledAt: input.scheduledAt ?? '',
  }, input.content);
  await putFile(filePathFromSlug(slug), body, `feat: update post ${slug}`, undefined, runtimeEnv);
  return slug;
}

export async function deletePost(slug: string, runtimeEnv?: Record<string, string>): Promise<void> {
  await deleteFile(filePathFromSlug(slug), `feat: delete post ${slug}`, runtimeEnv);
}

export async function trashPost(slug: string, runtimeEnv?: Record<string, string>): Promise<void> {
  const current = await getPostBySlug(slug, runtimeEnv);
  if (!current) return;
  await savePost({ ...current, status: 'deleted', slug }, runtimeEnv);
}

export function markdownToHtml(markdown: string): string {
  return marked.parse(markdown) as string;
}

export interface TocItem {
  id: string;
  title: string;
  level: 2 | 3;
}

function slugify(text: string): string {
  return text
    .toLowerCase()
    .trim()
    .replace(/<[^>]+>/g, '')
    .replace(/[^a-z0-9\u4e00-\u9fa5 -]/g, '')
    .replace(/\s+/g, '-');
}

export function renderPostWithToc(markdown: string): { html: string; toc: TocItem[] } {
  const rawHtml = markdownToHtml(markdown);
  const toc: TocItem[] = [];
  const used = new Map<string, number>();
  const html = rawHtml.replace(/<(h[23])>(.*?)<\/\1>/g, (_, tag: string, text: string) => {
    const level = tag === 'h2' ? 2 : 3;
    const base = slugify(text) || `section-${toc.length + 1}`;
    const count = used.get(base) ?? 0;
    used.set(base, count + 1);
    const id = count === 0 ? base : `${base}-${count + 1}`;
    toc.push({ id, title: text.replace(/<[^>]+>/g, ''), level: level as 2 | 3 });
    return `<${tag} id="${id}">${text}</${tag}>`;
  });
  return { html, toc };
}

export function isPostPublic(post: BlogPost, now = new Date()): boolean {
  if (post.status === 'published') return true;
  if (post.status !== 'scheduled') return false;
  if (!post.scheduledAt) return false;
  return new Date(post.scheduledAt).valueOf() <= now.valueOf();
}

export function canTransitionStatus(from: BlogPost['status'], to: BlogPost['status']): boolean {
  if (from === to) return true;
  const allowed: Record<BlogPost['status'], BlogPost['status'][]> = {
    draft: ['review', 'scheduled', 'published', 'archived', 'deleted'],
    review: ['draft', 'published', 'archived', 'deleted'],
    scheduled: ['draft', 'published', 'archived', 'deleted'],
    published: ['draft', 'archived', 'deleted'],
    archived: ['draft', 'published', 'deleted'],
    deleted: ['draft'],
  };
  return allowed[from].includes(to);
}

export async function listPostRevisions(slug: string, runtimeEnv?: Record<string, string>): Promise<string[]> {
  const paths = await listDir(`${REVISIONS_DIR}/${slug}`, runtimeEnv);
  return paths
    .filter((path) => path.endsWith('.md'))
    .sort((a, b) => b.localeCompare(a));
}

export async function restorePostRevision(slug: string, revisionPath: string, runtimeEnv?: Record<string, string>): Promise<void> {
  const revision = await getFile(revisionPath, runtimeEnv);
  if (!revision) throw new Error('Revision not found');
  await putFile(filePathFromSlug(slug), revision.content, `feat: restore ${slug} from revision`, undefined, runtimeEnv);
}
