import type { APIRoute } from 'astro';
import { setAuthCookie, verifyAdminCredentials } from '../../../lib/auth';

export const POST: APIRoute = async (context) => {
  const body = await context.request.json().catch(() => ({}));
  const username = String(body.username ?? 'admin').trim();
  const verified = verifyAdminCredentials(context, username, body.password ?? '');
  if (!verified.ok) {
    return new Response(JSON.stringify({ error: 'Invalid password' }), { status: 401 });
  }
  setAuthCookie(context, username, verified.role);
  return new Response(JSON.stringify({ ok: true, role: verified.role, username }));
};
