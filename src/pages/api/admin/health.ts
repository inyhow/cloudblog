import type { APIRoute } from 'astro';
import { isAuthed } from '../../../lib/auth';
import { getRuntimeEnv } from '../../../lib/runtime-env';
import { listDir, putFile } from '../../../lib/github';

async function githubProbe(runtimeEnv?: Record<string, string>) {
  const owner = runtimeEnv?.GITHUB_OWNER || import.meta.env.GITHUB_OWNER || '';
  const repo = runtimeEnv?.GITHUB_REPO || import.meta.env.GITHUB_REPO || '';
  const branch = (runtimeEnv?.GITHUB_BRANCH || import.meta.env.GITHUB_BRANCH || 'main').trim();
  const token = runtimeEnv?.GITHUB_TOKEN || import.meta.env.GITHUB_TOKEN || '';
  const url = `https://api.github.com/repos/${owner}/${repo}/contents/cloudblog/posts?ref=${encodeURIComponent(branch)}`;
  const resp = await fetch(url, {
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/vnd.github+json',
    },
  });
  const text = await resp.text();
  return {
    status: resp.status,
    scopes: resp.headers.get('x-oauth-scopes') || '',
    acceptedScopes: resp.headers.get('x-accepted-oauth-scopes') || '',
    body: text.slice(0, 1000),
  };
}

export const GET: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  const runtimeEnv = getRuntimeEnv(context.locals);
  const envCheck = {
    GITHUB_OWNER: Boolean(runtimeEnv?.GITHUB_OWNER || import.meta.env.GITHUB_OWNER),
    GITHUB_REPO: Boolean(runtimeEnv?.GITHUB_REPO || import.meta.env.GITHUB_REPO),
    GITHUB_BRANCH: runtimeEnv?.GITHUB_BRANCH || import.meta.env.GITHUB_BRANCH || 'main',
    GITHUB_TOKEN: Boolean(runtimeEnv?.GITHUB_TOKEN || import.meta.env.GITHUB_TOKEN),
  };

  try {
    const files = await listDir('cloudblog/posts', runtimeEnv);
    return new Response(
      JSON.stringify({
        ok: true,
        envCheck,
        postCount: files.length,
      }),
    );
  } catch (err) {
    const probe = await githubProbe(runtimeEnv).catch(() => null);
    return new Response(
      JSON.stringify({
        ok: false,
        envCheck,
        error: err instanceof Error ? err.message : String(err),
        githubProbe: probe,
      }),
      { status: 500 },
    );
  }
};

export const POST: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  const runtimeEnv = getRuntimeEnv(context.locals);
  try {
    const path = `cloudblog/posts/__health-${Date.now()}.md`;
    await putFile(path, '# health check', 'chore: health check', undefined, runtimeEnv);
    return new Response(JSON.stringify({ ok: true, path }));
  } catch (err) {
    const probe = await githubProbe(runtimeEnv).catch(() => null);
    return new Response(
      JSON.stringify({
        ok: false,
        error: err instanceof Error ? err.message : String(err),
        githubProbe: probe,
      }),
      { status: 500 },
    );
  }
};
