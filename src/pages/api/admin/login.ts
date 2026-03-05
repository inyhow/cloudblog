import type { APIRoute } from 'astro';
import { setAuthCookie } from '../../../lib/auth';
import { requireEnv } from '../../../lib/env';

export const POST: APIRoute = async (context) => {
  const body = await context.request.json().catch(() => ({}));
  if (body.password !== requireEnv('ADMIN_PASSWORD')) {
    return new Response(JSON.stringify({ error: 'Invalid password' }), { status: 401 });
  }
  setAuthCookie(context);
  return new Response(JSON.stringify({ ok: true }));
};
