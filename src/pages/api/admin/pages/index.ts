import type { APIRoute } from 'astro';
import { isAuthed } from '../../../../lib/auth';
import { getRuntimeEnv } from '../../../../lib/runtime-env';
import { listCustomPages, saveCustomPage } from '../../../../lib/custom-pages';

export const GET: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  return new Response(JSON.stringify(await listCustomPages(getRuntimeEnv(context.locals))));
};

export const POST: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  try {
    const body = await context.request.json();
    const slug = await saveCustomPage(body, getRuntimeEnv(context.locals));
    return new Response(JSON.stringify({ ok: true, slug }));
  } catch (err) {
    return new Response(JSON.stringify({ ok: false, error: err instanceof Error ? err.message : String(err) }), {
      status: 500,
    });
  }
};
