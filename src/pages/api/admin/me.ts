import type { APIRoute } from 'astro';
import { getAuthRole, getAuthUsername, requireRole } from '../../../lib/auth';

export const GET: APIRoute = async (context) => {
  const denied = requireRole(context, 'author');
  if (denied) return denied;
  return new Response(JSON.stringify({ ok: true, username: getAuthUsername(context), role: getAuthRole(context) }));
};
