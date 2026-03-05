import type { APIRoute } from 'astro';
import { clearAuthCookie } from '../../../lib/auth';

export const POST: APIRoute = async (context) => {
  clearAuthCookie(context);
  return new Response(JSON.stringify({ ok: true }));
};
