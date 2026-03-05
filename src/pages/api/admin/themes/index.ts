import type { APIRoute } from 'astro';
import { isAuthed } from '../../../../lib/auth';
import { readThemeCss, saveThemeCss } from '../../../../lib/settings';

export const GET: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  return new Response(JSON.stringify({ css: await readThemeCss() }));
};

export const PUT: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  const body = await context.request.json();
  await saveThemeCss(body.css ?? '');
  return new Response(JSON.stringify({ ok: true }));
};
