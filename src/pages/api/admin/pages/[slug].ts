import type { APIRoute } from 'astro';
import { requireRole } from '../../../../lib/auth';
import { getRuntimeEnv } from '../../../../lib/runtime-env';
import { getCustomPageBySlug, saveCustomPage } from '../../../../lib/custom-pages';

export const GET: APIRoute = async (context) => {
  const denied = requireRole(context, 'author');
  if (denied) return denied;
  const slug = context.params.slug!;
  const page = await getCustomPageBySlug(slug, getRuntimeEnv(context.locals));
  if (!page) return new Response('Not Found', { status: 404 });
  return new Response(JSON.stringify(page));
};

export const PUT: APIRoute = async (context) => {
  const denied = requireRole(context, 'author');
  if (denied) return denied;
  try {
    const currentSlug = context.params.slug!;
    const body = await context.request.json();
    const slug = await saveCustomPage({ ...body, slug: body.slug || currentSlug }, getRuntimeEnv(context.locals));
    return new Response(JSON.stringify({ ok: true, slug }));
  } catch (err) {
    return new Response(JSON.stringify({ ok: false, error: err instanceof Error ? err.message : String(err) }), {
      status: 500,
    });
  }
};
