import type { APIRoute } from 'astro';
import { isAuthed } from '../../../lib/auth';
import { readSettings, saveSettings } from '../../../lib/settings';

export const GET: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  return new Response(JSON.stringify(await readSettings()));
};

export const PUT: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  const body = await context.request.json();
  await saveSettings(body);
  return new Response(JSON.stringify({ ok: true }));
};
