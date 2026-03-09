import type { APIContext } from 'astro';

const COOKIE_NAME = 'cloudblog_admin';

type Role = 'admin' | 'editor' | 'author';

interface AdminUser {
  username: string;
  password: string;
  role: Role;
}

interface SessionPayload {
  username: string;
  role: Role;
  at: number;
}

function readRuntimeEnv(context: APIContext | any, name: string): string {
  const runtimeValue = (context?.locals as { runtime?: { env?: Record<string, string> } })?.runtime?.env?.[name];
  return String(runtimeValue || import.meta.env[name] || '').trim();
}

function parseRole(input: string): Role {
  if (input === 'admin' || input === 'editor' || input === 'author') return input;
  return 'author';
}

function parseUsersFromJson(raw: string): AdminUser[] {
  if (!raw) return [];
  try {
    const parsed = JSON.parse(raw);
    if (!Array.isArray(parsed)) return [];
    return parsed
      .map((row) => ({
        username: String(row?.username || '').trim(),
        password: String(row?.password || '').trim(),
        role: parseRole(String(row?.role || 'author').trim()),
      }))
      .filter((row) => row.username && row.password);
  } catch {
    return [];
  }
}

function getAdminUsers(context: APIContext): AdminUser[] {
  const users = parseUsersFromJson(readRuntimeEnv(context, 'ADMIN_USERS_JSON'));
  if (users.length > 0) return users;
  const legacy = readRuntimeEnv(context, 'ADMIN_PASSWORD');
  if (!legacy) return [];
  return [{ username: 'admin', password: legacy, role: 'admin' }];
}

function decodeSession(value: string): SessionPayload | null {
  try {
    const json = atob(value);
    const parsed = JSON.parse(json);
    if (!parsed?.username || !parsed?.role) return null;
    return {
      username: String(parsed.username),
      role: parseRole(String(parsed.role)),
      at: Number(parsed.at || Date.now()),
    };
  } catch {
    return null;
  }
}

function encodeSession(payload: SessionPayload): string {
  return btoa(JSON.stringify(payload));
}

export function getAuthSession(context: APIContext): SessionPayload | null {
  const token = context.cookies.get(COOKIE_NAME)?.value;
  if (!token) return null;
  return decodeSession(token);
}

export function getAuthRole(context: APIContext): Role | null {
  return getAuthSession(context)?.role || null;
}

export function getAuthUsername(context: APIContext): string | null {
  return getAuthSession(context)?.username || null;
}

export function isAuthed(context: APIContext): boolean {
  const session = getAuthSession(context);
  if (!session) return false;
  return getAdminUsers(context).some((u) => u.username === session.username && u.role === session.role);
}

export function hasRole(context: APIContext, required: Role): boolean {
  const role = getAuthRole(context);
  if (!role) return false;
  const rank: Record<Role, number> = { author: 1, editor: 2, admin: 3 };
  return rank[role] >= rank[required];
}

export function requireRole(context: APIContext, required: Role = 'author'): Response | null {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  if (!hasRole(context, required)) return new Response('Forbidden', { status: 403 });
  return null;
}

export function setAuthCookie(context: APIContext, username: string, role: Role): void {
  const payload = encodeSession({ username, role, at: Date.now() });
  context.cookies.set(COOKIE_NAME, payload, {
    httpOnly: true,
    sameSite: 'lax',
    secure: import.meta.env.PROD,
    path: '/',
    maxAge: 60 * 60 * 24 * 30,
  });
}

export function clearAuthCookie(context: APIContext): void {
  context.cookies.delete(COOKIE_NAME, { path: '/' });
}

export function verifyAdminCredentials(context: APIContext, username: string, password: string): { ok: true; role: Role } | { ok: false } {
  const users = getAdminUsers(context);
  const user = users.find((u) => u.username === String(username || '').trim() && u.password === String(password || ''));
  if (!user) return { ok: false };
  return { ok: true, role: user.role };
}
