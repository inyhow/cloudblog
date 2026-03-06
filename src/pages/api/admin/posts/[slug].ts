import type { APIRoute } from 'astro';
import { isAuthed } from '../../../../lib/auth';
import { appendOpsLog } from '../../../../lib/ops-log';
import { canTransitionStatus, deletePost, getPostBySlug, savePost, trashPost } from '../../../../lib/posts';
import { getRuntimeEnv } from '../../../../lib/runtime-env';

export const GET: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  const slug = context.params.slug!;
  const post = await getPostBySlug(slug, getRuntimeEnv(context.locals));
  if (!post) return new Response('Not Found', { status: 404 });
  return new Response(JSON.stringify(post));
};

export const PUT: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  try {
    const currentSlug = context.params.slug!;
    const body = await context.request.json();
    const runtimeEnv = getRuntimeEnv(context.locals);
    const current = await getPostBySlug(currentSlug, runtimeEnv);
    if (!current) return new Response('Not Found', { status: 404 });
    const nextStatus = (body.status || current.status) as typeof current.status;
    if (!canTransitionStatus(current.status, nextStatus)) {
      return new Response(JSON.stringify({ ok: false, error: `Invalid status transition: ${current.status} -> ${nextStatus}` }), { status: 400 });
    }
    const nextSlug = await savePost({ ...body, slug: body.slug || currentSlug }, runtimeEnv);
    if (nextSlug !== currentSlug) {
      await deletePost(currentSlug, runtimeEnv);
    }
    await appendOpsLog({ at: new Date().toISOString(), action: 'post.update', target: nextSlug }, runtimeEnv);
    return new Response(JSON.stringify({ ok: true, slug: nextSlug }));
  } catch (err) {
    return new Response(JSON.stringify({ ok: false, error: err instanceof Error ? err.message : String(err) }), {
      status: 500,
    });
  }
};

export const DELETE: APIRoute = async (context) => {
  if (!isAuthed(context)) return new Response('Unauthorized', { status: 401 });
  try {
    const slug = context.params.slug!;
    const runtimeEnv = getRuntimeEnv(context.locals);
    const hardDelete = new URL(context.request.url).searchParams.get('hard') === '1';
    if (hardDelete) {
      await deletePost(slug, runtimeEnv);
      await appendOpsLog({ at: new Date().toISOString(), action: 'post.delete.hard', target: slug }, runtimeEnv);
      return new Response(JSON.stringify({ ok: true, mode: 'hard' }));
    }
    await trashPost(slug, runtimeEnv);
    await appendOpsLog({ at: new Date().toISOString(), action: 'post.delete.soft', target: slug }, runtimeEnv);
    return new Response(JSON.stringify({ ok: true, mode: 'soft' }));
  } catch (err) {
    return new Response(JSON.stringify({ ok: false, error: err instanceof Error ? err.message : String(err) }), {
      status: 500,
    });
  }
};
