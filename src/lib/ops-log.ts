import { getFile, putFile } from './github';

const OPS_LOG_PATH = 'cloudblog/system/ops-log.json';

export interface OpsLogItem {
  at: string;
  action: string;
  target: string;
  detail?: string;
}

export async function listOpsLogs(runtimeEnv?: Record<string, string>): Promise<OpsLogItem[]> {
  try {
    const file = await getFile(OPS_LOG_PATH, runtimeEnv);
    if (!file) return [];
    const parsed = JSON.parse(file.content);
    if (!Array.isArray(parsed)) return [];
    return parsed as OpsLogItem[];
  } catch {
    return [];
  }
}

export async function appendOpsLog(item: OpsLogItem, runtimeEnv?: Record<string, string>): Promise<void> {
  const logs = await listOpsLogs(runtimeEnv);
  logs.unshift(item);
  const capped = logs.slice(0, 500);
  await putFile(OPS_LOG_PATH, JSON.stringify(capped, null, 2), `chore: ops log ${item.action}`, undefined, runtimeEnv);
}
