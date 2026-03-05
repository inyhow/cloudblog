import matter from 'gray-matter';
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

export async function listPosts(runtimeEnv?: Record<string, string>): Promise<BlogPost[]> {
  try {
    const paths = await listDir(POSTS_DIR, runtimeEnv);
    const posts = await Promise.all(
      paths.filter((p) => p.endsWith('.md')).map(async (path) => {
        const f = await getFile(path, runtimeEnv);
        if (!f) return null;
        const parsed = matter(f.content);
        return {
          slug: path.split('/').pop()!.replace(/\.md$/, ''),
          title: parsed.data.title ?? 'Untitled',
          description: parsed.data.description ?? '',
          tags: parsed.data.tags ?? [],
          pubDate: parsed.data.pubDate ?? new Date().toISOString(),
          updatedDate: parsed.data.updatedDate,
          content: parsed.content,
        } satisfies BlogPost;
      }),
    );
    return posts
      .filter((p): p is BlogPost => Boolean(p))
      .sort((a, b) => new Date(b.pubDate).valueOf() - new Date(a.pubDate).valueOf());
  } catch {
    return [];
  }
}

export async function getPostBySlug(slug: string, runtimeEnv?: Record<string, string>): Promise<BlogPost | null> {
  try {
    const path = filePathFromSlug(slug);
    const f = await getFile(path, runtimeEnv);
    if (!f) return null;
    const parsed = matter(f.content);
    return {
      slug,
      title: parsed.data.title ?? 'Untitled',
      description: parsed.data.description ?? '',
      tags: parsed.data.tags ?? [],
      pubDate: parsed.data.pubDate ?? new Date().toISOString(),
      updatedDate: parsed.data.updatedDate,
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
  const body = matter.stringify(input.content, {
    title: input.title,
    description: input.description ?? '',
    tags: input.tags ?? [],
    pubDate: input.pubDate ?? new Date().toISOString(),
    updatedDate: new Date().toISOString(),
  });
  await putFile(filePathFromSlug(slug), body, `feat: update post ${slug}`, undefined, runtimeEnv);
  return slug;
}

export function markdownToHtml(markdown: string): string {
  return marked.parse(markdown) as string;
}
