import { marked } from 'marked';

// Simulating Windows line endings
const markdown = "---\r\ntitle: test\r\n---\r\n\r\n| col1 | col2 |\r\n|---|---|\r\n| v1 | v2 |\r\n";

function parseFrontmatter(raw) {
  if (!raw.startsWith('---\n')) return { data: {}, content: raw };
  const end = raw.indexOf('\n---\n', 4);
  if (end === -1) return { data: {}, content: raw };
  const fm = raw.slice(4, end);
  const content = raw.slice(end + 5);
  return { data: { title: 'found' }, content };
}

console.log('Result with \\r\\n:');
console.log(parseFrontmatter(markdown));
