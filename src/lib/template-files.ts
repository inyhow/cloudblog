import { getFile, listDir, putFile } from './github';

const TEMPLATE_DIR = 'cloudblog/templates';

export interface TemplateFile {
  key: string;
  label: string;
  path: string;
  content: string;
}

const templateRegistry = [
  { key: 'theme', label: 'Theme Extra CSS', path: `cloudblog/templates/theme.css` },
] as const;

function getDefinition(key: string) {
  return templateRegistry.find((item) => item.key === key);
}

export function listTemplateDefinitions() {
  return templateRegistry.map((item) => ({ key: item.key, label: item.label, path: item.path }));
}

export async function getTemplateFile(key: string, runtimeEnv?: Record<string, string>): Promise<TemplateFile | null> {
  const def = getDefinition(key);
  if (!def) return null;
  const file = await getFile(def.path, runtimeEnv);
  return {
    key: def.key,
    label: def.label,
    path: def.path,
    content: file?.content ?? '',
  };
}

export async function saveTemplateFile(
  key: string,
  content: string,
  runtimeEnv?: Record<string, string>,
): Promise<void> {
  const def = getDefinition(key);
  if (!def) throw new Error(`Unknown template key: ${key}`);
  await putFile(def.path, content, `feat: update template ${key}`, undefined, runtimeEnv);
}

export async function listCustomTemplateFiles(runtimeEnv?: Record<string, string>): Promise<string[]> {
  try {
    return await listDir(TEMPLATE_DIR, runtimeEnv);
  } catch {
    return [];
  }
}
