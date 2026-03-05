import type { APIRoute } from 'astro';
import { setAuthCookie, verifyAdminPassword } from '../../../lib/auth';

export const POST: APIRoute = async (context) => {
  const body = await context.request.json().catch(() => ({}));
  if (!verifyAdminPassword(context, body.password ?? '')) {
    return new Response(JSON.stringify({ error: 'Invalid password' }), { status: 401 });
  }
  setAuthCookie(context);
  return new Response(JSON.stringify({ ok: true }));
};
