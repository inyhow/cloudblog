import type { APIRoute } from 'astro';
import { isAuthed } from '../../../../lib/auth';
import { listPosts, savePost } from '../../../../lib/posts';

export const GET: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  const posts = await listPosts();
  return new Response(JSON.stringify(posts));
};

export const POST: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  const body = await context.request.json();
  const slug = await savePost(body);
  return new Response(JSON.stringify({ ok: true, slug }));
};
