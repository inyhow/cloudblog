import type { APIRoute } from 'astro';
import { isAuthed } from '../../../../../lib/auth';
import { appendOpsLog } from '../../../../../lib/ops-log';
import { listPostRevisions, restorePostRevision } from '../../../../../lib/posts';
import { getRuntimeEnv } from '../../../../../lib/runtime-env';

export const GET: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  try {
    const slug = context.params.slug!;
    const revisions = await listPostRevisions(slug, getRuntimeEnv(context.locals));
    return new Response(JSON.stringify({ ok: true, revisions }));
  } catch (err) {
    return new Response(JSON.stringify({ ok: false, error: err instanceof Error ? err.message : String(err) }), { status: 500 });
  }
};

export const POST: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  try {
    const slug = context.params.slug!;
    const body = await context.request.json();
    const runtimeEnv = getRuntimeEnv(context.locals);
    await restorePostRevision(slug, String(body.revisionPath || ''), runtimeEnv);
    await appendOpsLog({ at: new Date().toISOString(), action: 'post.restore', target: slug, detail: String(body.revisionPath || '') }, runtimeEnv);
    return new Response(JSON.stringify({ ok: true }));
  } catch (err) {
    return new Response(JSON.stringify({ ok: false, error: err instanceof Error ? err.message : String(err) }), { status: 500 });
  }
};
