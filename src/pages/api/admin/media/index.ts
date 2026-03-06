import type { APIRoute } from 'astro';
import { isAuthed } from '../../../../lib/auth';
import { listDir } from '../../../../lib/github';
import { getRuntimeEnv } from '../../../../lib/runtime-env';

function readEnv(name: string, runtimeEnv?: Record<string, string>): string {
  return String(runtimeEnv?.[name] || import.meta.env[name] || '').trim();
}

export const GET: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  const runtimeEnv = getRuntimeEnv(context.locals);
  const owner = readEnv('GITHUB_OWNER', runtimeEnv);
  const repo = readEnv('GITHUB_REPO', runtimeEnv);
  const branch = readEnv('GITHUB_BRANCH', runtimeEnv) || 'main';
  const cdnPrefix =
    readEnv('GITHUB_CDN_PREFIX', runtimeEnv) || `https://cdn.jsdelivr.net/gh/${owner}/${repo}@${branch}`;
  try {
    const files = await listDir('cloudblog/images', runtimeEnv);
    return new Response(
      JSON.stringify(
        files.map((path) => ({
          path,
          url: `${cdnPrefix.replace(/\/$/, '')}/${path}`,
          name: path.split('/').pop(),
        })),
      ),
    );
  } catch (err) {
    return new Response(JSON.stringify({ ok: false, error: err instanceof Error ? err.message : String(err) }), {
      status: 500,
    });
  }
};
