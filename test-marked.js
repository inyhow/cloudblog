import { marked } from 'marked';

const markdown = `
| 问题 | 原因 | 解决方法 |
| ------ | ------ | ---------- |
| openclaw 命令不存在 | npm 全局目录未加入 PATH | 重启 PowerShell 或检查 npm bin -g 路径 |
| 安装速度慢 | 网络问题 | 配置 npm 镜像：npm config set registry https://registry.npmmirror.com |
| Node.js 版本过低 | v20 以下不支持 | 升级 Node.js |
`;

console.log(marked.parse(markdown));
