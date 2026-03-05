import type { APIRoute } from 'astro';
import { isAuthed } from '../../../../lib/auth';
import { getPostBySlug, savePost } from '../../../../lib/posts';

export const GET: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  const slug = context.params.slug!;
  const post = await getPostBySlug(slug);
  if (!post) return new Response('Not Found', { status: 404 });
  return new Response(JSON.stringify(post));
};

export const PUT: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  const slug = context.params.slug!;
  const body = await context.request.json();
  const nextSlug = await savePost({ ...body, slug });
  return new Response(JSON.stringify({ ok: true, slug: nextSlug }));
};
