import type { APIRoute } from 'astro';
import { isAuthed } from '../../../lib/auth';
import { appendOpsLog } from '../../../lib/ops-log';
import { readSettings, saveSettings } from '../../../lib/settings';
import { getRuntimeEnv } from '../../../lib/runtime-env';

export const GET: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  return new Response(JSON.stringify(await readSettings(getRuntimeEnv(context.locals))));
};

export const PUT: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  const body = await context.request.json();
  const runtimeEnv = getRuntimeEnv(context.locals);
  await saveSettings(body, runtimeEnv);
  await appendOpsLog({ at: new Date().toISOString(), action: 'settings.update', target: 'site' }, runtimeEnv);
  return new Response(JSON.stringify({ ok: true }));
};
