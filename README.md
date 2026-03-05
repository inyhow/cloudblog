# CloudBlog

基于 `Astro + Tailwind + Cloudflare Pages` 的博客系统，支持：

- GitHub 仓库存储文章（Markdown）与图片
- 后台登录、文章列表、发布与编辑
- 网站设置（SEO、菜单、页脚、统计脚本）
- 手动主题 CSS 管理
- 前台首页、详情、标签页
- Giscus 评论

## 开发

```bash
npm install
cp .env.example .env
npm run dev
```

## 环境变量

参考 `.env.example`：

- `ADMIN_PASSWORD`: 后台登录密码
- `GITHUB_OWNER` / `GITHUB_REPO` / `GITHUB_BRANCH` / `GITHUB_TOKEN`: GitHub API 配置
- `GITHUB_RAW_PREFIX`: 图片 URL 前缀（可选）

## Cloudflare Pages

1. 构建命令：`npm run build`
2. 输出目录：`dist`
3. 在 Pages 项目里配置上述环境变量
