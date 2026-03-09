import type { APIRoute } from 'astro';
import { requireRole } from '../../../lib/auth';
import { listOpsLogs } from '../../../lib/ops-log';
import { getRuntimeEnv } from '../../../lib/runtime-env';

export const GET: APIRoute = async (context) => {
  const denied = requireRole(context, 'admin');
  if (denied) return denied;
  const logs = await listOpsLogs(getRuntimeEnv(context.locals));
  return new Response(JSON.stringify({ ok: true, logs }));
};
