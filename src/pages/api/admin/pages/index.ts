import type { APIRoute } from 'astro';
import { requireRole } from '../../../../lib/auth';
import { getRuntimeEnv } from '../../../../lib/runtime-env';
import { listCustomPages, saveCustomPage } from '../../../../lib/custom-pages';

export const GET: APIRoute = async (context) => {
  const denied = requireRole(context, 'author');
  if (denied) return denied;
  return new Response(JSON.stringify(await listCustomPages(getRuntimeEnv(context.locals))));
};

export const POST: APIRoute = async (context) => {
  const denied = requireRole(context, 'author');
  if (denied) return denied;
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
