import type { APIContext } from 'astro';

const COOKIE_NAME = 'cloudblog_admin';

function getAdminPassword(context: APIContext): string {
	const runtimeValue = (context.locals as { runtime?: { env?: Record<string, string> } })?.runtime?.env
		?.ADMIN_PASSWORD;
	return runtimeValue || import.meta.env.ADMIN_PASSWORD || '';
}

export function isAuthed(context: APIContext): boolean {
	const token = context.cookies.get(COOKIE_NAME)?.value;
	const password = getAdminPassword(context);
	return Boolean(password && token && token === password);
}

export function setAuthCookie(context: APIContext): void {
	const password = getAdminPassword(context);
	context.cookies.set(COOKIE_NAME, password, {
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

export function verifyAdminPassword(context: APIContext, input: string): boolean {
	const password = getAdminPassword(context);
	return Boolean(password && input === password);
}
