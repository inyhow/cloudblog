import type { APIRoute } from 'astro';
import { isAuthed } from '../../../../lib/auth';
import { getRuntimeEnv } from '../../../../lib/runtime-env';
import { getTemplateFile, listTemplateDefinitions, saveTemplateFile } from '../../../../lib/template-files';

export const GET: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  const key = context.url.searchParams.get('key');
  if (!key) {
    return new Response(JSON.stringify(listTemplateDefinitions()));
  }
  const file = await getTemplateFile(key, getRuntimeEnv(context.locals));
  if (!file) return new Response('Not Found', { status: 404 });
  return new Response(JSON.stringify(file));
};

export const PUT: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  try {
    const body = await context.request.json();
    await saveTemplateFile(body.key, body.content || '', getRuntimeEnv(context.locals));
    return new Response(JSON.stringify({ ok: true }));
  } catch (err) {
    return new Response(JSON.stringify({ ok: false, error: err instanceof Error ? err.message : String(err) }), {
      status: 500,
    });
  }
};
