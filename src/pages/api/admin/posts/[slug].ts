import type { APIRoute } from 'astro';
import { isAuthed } from '../../../../lib/auth';
import { deletePost, getPostBySlug, savePost } from '../../../../lib/posts';
import { getRuntimeEnv } from '../../../../lib/runtime-env';

export const GET: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  const slug = context.params.slug!;
  const post = await getPostBySlug(slug, getRuntimeEnv(context.locals));
  if (!post) return new Response('Not Found', { status: 404 });
  return new Response(JSON.stringify(post));
};

export const PUT: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  const currentSlug = context.params.slug!;
  const body = await context.request.json();
  const nextSlug = await savePost({ ...body, slug: body.slug || currentSlug }, getRuntimeEnv(context.locals));
  return new Response(JSON.stringify({ ok: true, slug: nextSlug }));
};

export const DELETE: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  try {
    const slug = context.params.slug!;
    await deletePost(slug, getRuntimeEnv(context.locals));
    return new Response(JSON.stringify({ ok: true }));
  } catch (err) {
    return new Response(JSON.stringify({ ok: false, error: err instanceof Error ? err.message : String(err) }), {
      status: 500,
    });
  }
};
