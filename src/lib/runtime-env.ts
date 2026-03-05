export function getRuntimeEnv(locals: unknown): Record<string, string> | undefined {
  const env = (locals as { runtime?: { env?: Record<string, string> } })?.runtime?.env;
  return env;
}
