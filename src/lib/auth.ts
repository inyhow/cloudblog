import type { APIContext } from 'astro';
import { requireEnv } from './env';

const COOKIE_NAME = 'cloudblog_admin';

export function isAuthed(context: APIContext): boolean {
	const token = context.cookies.get(COOKIE_NAME)?.value;
	return Boolean(token && token === requireEnv('ADMIN_PASSWORD'));
}

export function setAuthCookie(context: APIContext): void {
	context.cookies.set(COOKIE_NAME, requireEnv('ADMIN_PASSWORD'), {
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
