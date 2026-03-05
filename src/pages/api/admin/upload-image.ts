import type { APIRoute } from 'astro';
import { isAuthed } from '../../../lib/auth';
import { putFile } from '../../../lib/github';
import { getRuntimeEnv } from '../../../lib/runtime-env';

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
  const publicPrefix = runtimeEnv?.GITHUB_RAW_PREFIX || import.meta.env.GITHUB_RAW_PREFIX || '';
  const url = publicPrefix ? `${publicPrefix}/${path}` : path;
  return new Response(JSON.stringify({ ok: true, url }));
};
