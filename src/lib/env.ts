export function requireEnv(name: string): string {
	const value = import.meta.env[name];
	if (!value) {
		throw new Error(`Missing required env: ${name}`);
	}
	return value;
}

export function optionalEnv(name: string, fallback = ''): string {
	return import.meta.env[name] ?? fallback;
}
