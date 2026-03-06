import type { APIRoute } from 'astro';
import { isAuthed } from '../../../lib/auth';
import { listOpsLogs } from '../../../lib/ops-log';
import { getRuntimeEnv } from '../../../lib/runtime-env';

export const GET: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  const logs = await listOpsLogs(getRuntimeEnv(context.locals));
  return new Response(JSON.stringify({ ok: true, logs }));
};
