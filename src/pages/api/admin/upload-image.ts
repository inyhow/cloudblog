import type { APIRoute } from 'astro';
import { isAuthed } from '../../../lib/auth';
import { putFile } from '../../../lib/github';
import { getRuntimeEnv } from '../../../lib/runtime-env';

function readEnv(name: string, runtimeEnv?: Record<string, string>): string {
  return String(runtimeEnv?.[name] || import.meta.env[name] || '').trim();
}

export const POST: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  const form = await context.request.formData();
  const file = form.get('file');
  if (!(file instanceof File)) return new Response('Bad Request', { status: 400 });
  const ext = file.name.split('.').pop() || 'png';
  const bytes = new Uint8Array(await file.arrayBuffer());
  let binary = '';
  bytes.forEach((b) => (binary += String.fromCharCode(b)));
  const base64 = btoa(binary);
  const path = `cloudblog/images/${Date.now()}.${ext}`;
  const runtimeEnv = getRuntimeEnv(context.locals);
  await putFile(path, base64, `feat: upload image ${file.name}`, { isBase64: true }, runtimeEnv);
  const owner = readEnv('GITHUB_OWNER', runtimeEnv);
  const repo = readEnv('GITHUB_REPO', runtimeEnv);
  const branch = readEnv('GITHUB_BRANCH', runtimeEnv) || 'main';
  const rawPrefix = readEnv('GITHUB_RAW_PREFIX', runtimeEnv);
  const cdnPrefix =
    readEnv('GITHUB_CDN_PREFIX', runtimeEnv) || `https://cdn.jsdelivr.net/gh/${owner}/${repo}@${branch}`;
  const rawUrl = rawPrefix ? `${rawPrefix.replace(/\/$/, '')}/${path}` : path;
  const cdnUrl = `${cdnPrefix.replace(/\/$/, '')}/${path}`;
  return new Response(JSON.stringify({ ok: true, url: cdnUrl, cdnUrl, rawUrl, path }));
};
