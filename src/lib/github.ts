import { requireEnv } from './env';

export interface GithubFile {
  path: string;
  content: string;
  sha: string;
}

const apiBase = 'https://api.github.com';

function encodeBase64(value: string): string {
  const bytes = new TextEncoder().encode(value);
  let binary = '';
  bytes.forEach((b) => {
    binary += String.fromCharCode(b);
  });
  return btoa(binary);
}

function decodeBase64(value: string): string {
  const binary = atob(value);
  const bytes = Uint8Array.from(binary, (c) => c.charCodeAt(0));
  return new TextDecoder().decode(bytes);
}

function repoConfig() {
  const owner = requireEnv('GITHUB_OWNER');
  const repo = requireEnv('GITHUB_REPO');
  const branch = import.meta.env.GITHUB_BRANCH ?? 'main';
  const token = requireEnv('GITHUB_TOKEN');
  return { owner, repo, branch, token };
}

function jsonHeaders(token: string) {
  return {
    Authorization: `Bearer ${token}`,
    Accept: 'application/vnd.github+json',
    'Content-Type': 'application/json',
  };
}

export async function getFile(path: string): Promise<GithubFile | null> {
  const { owner, repo, token, branch } = repoConfig();
  const url = `${apiBase}/repos/${owner}/${repo}/contents/${path}?ref=${branch}`;
  const resp = await fetch(url, { headers: jsonHeaders(token) });
  if (resp.status === 404) return null;
  if (!resp.ok) throw new Error(`GitHub get file failed: ${resp.status}`);
  const data = await resp.json();
  return {
    path: data.path,
    sha: data.sha,
    content: decodeBase64(data.content),
  };
}

export async function listDir(path: string): Promise<string[]> {
  const { owner, repo, token, branch } = repoConfig();
  const url = `${apiBase}/repos/${owner}/${repo}/contents/${path}?ref=${branch}`;
  const resp = await fetch(url, { headers: jsonHeaders(token) });
  if (resp.status === 404) return [];
  if (!resp.ok) throw new Error(`GitHub list dir failed: ${resp.status}`);
  const data = await resp.json();
  if (!Array.isArray(data)) return [];
  return data.filter((it) => it.type === 'file').map((it) => it.path);
}

export async function putFile(
  path: string,
  content: string,
  message: string,
  options?: { isBase64?: boolean },
): Promise<void> {
  const { owner, repo, token, branch } = repoConfig();
  const current = await getFile(path);
  const url = `${apiBase}/repos/${owner}/${repo}/contents/${path}`;
  const body: Record<string, unknown> = {
    message,
    content: options?.isBase64 ? content : encodeBase64(content),
    branch,
  };
  if (current?.sha) body.sha = current.sha;
  const resp = await fetch(url, {
    method: 'PUT',
    headers: jsonHeaders(token),
    body: JSON.stringify(body),
  });
  if (!resp.ok) throw new Error(`GitHub put file failed: ${resp.status}`);
}
