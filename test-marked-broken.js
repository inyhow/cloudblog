import { marked } from 'marked';

const markdown = `
| 问题 | 原因 | 解决方法 |

| ------ | ------ | ---------- |

| openclaw 命令不存在 | npm 全局目录未加入 PATH | 重启 PowerShell 或检查 npm bin -g 路径 |
`;

console.log(marked.parse(markdown));
