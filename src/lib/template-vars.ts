export function renderTemplateVariables(
  source: string,
  variables: Record<string, string | undefined>,
): string {
  if (!source) return '';
  return source.replace(/\{\{\s*([a-zA-Z0-9._-]+)\s*\}\}/g, (_, key) => {
    const value = variables[key];
    return value == null ? '' : String(value);
  });
}
