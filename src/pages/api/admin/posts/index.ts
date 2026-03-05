import type { APIRoute } from 'astro';
import { isAuthed } from '../../../../lib/auth';
import { listPostsStrict, savePost } from '../../../../lib/posts';
import { getRuntimeEnv } from '../../../../lib/runtime-env';

export const GET: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  try {
    const posts = await listPostsStrict(getRuntimeEnv(context.locals));
    return new Response(JSON.stringify(posts));
  } catch (err) {
    return new Response(
      JSON.stringify({ ok: false, error: err instanceof Error ? err.message : String(err) }),
      { status: 500 },
    );
  }
};

export const POST: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  try {
    const body = await context.request.json();
    const slug = await savePost(body, getRuntimeEnv(context.locals));
    return new Response(JSON.stringify({ ok: true, slug }));
  } catch (err) {
    return new Response(
      JSON.stringify({ ok: false, error: err instanceof Error ? err.message : String(err) }),
      { status: 500 },
    );
  }
};
