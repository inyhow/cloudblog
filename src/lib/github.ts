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

function readEnv(name: string, runtimeEnv?: Record<string, string>): string {
  const value = runtimeEnv?.[name] || import.meta.env[name] || '';
  return String(value).trim();
}

function repoConfig(runtimeEnv?: Record<string, string>) {
  const owner = readEnv('GITHUB_OWNER', runtimeEnv);
  const repo = readEnv('GITHUB_REPO', runtimeEnv);
  const branch = readEnv('GITHUB_BRANCH', runtimeEnv) || 'main';
  const token = readEnv('GITHUB_TOKEN', runtimeEnv);
  if (!owner || !repo || !token) {
    throw new Error('Missing GitHub env. Required: GITHUB_OWNER, GITHUB_REPO, GITHUB_TOKEN');
  }
  return { owner, repo, branch, token };
}

function jsonHeaders(token: string) {
  return {
    Authorization: `Bearer ${token}`,
    Accept: 'application/vnd.github+json',
    'Content-Type': 'application/json',
    'User-Agent': 'cloudblog-app',
  };
}

export async function getFile(path: string, runtimeEnv?: Record<string, string>): Promise<GithubFile | null> {
  const { owner, repo, token, branch } = repoConfig(runtimeEnv);
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

export async function listDir(path: string, runtimeEnv?: Record<string, string>): Promise<string[]> {
  const { owner, repo, token, branch } = repoConfig(runtimeEnv);
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
  runtimeEnv?: Record<string, string>,
): Promise<void> {
  const { owner, repo, token, branch } = repoConfig(runtimeEnv);
  const current = await getFile(path, runtimeEnv);
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

export async function deleteFile(path: string, message: string, runtimeEnv?: Record<string, string>): Promise<void> {
  const { owner, repo, token, branch } = repoConfig(runtimeEnv);
  const current = await getFile(path, runtimeEnv);
  if (!current) return;
  const url = `${apiBase}/repos/${owner}/${repo}/contents/${path}`;
  const resp = await fetch(url, {
    method: 'DELETE',
    headers: jsonHeaders(token),
    body: JSON.stringify({
      message,
      sha: current.sha,
      branch,
    }),
  });
  if (!resp.ok) throw new Error(`GitHub delete file failed: ${resp.status}`);
}
