---
title: "socket5 + Netch 实现 Google Antigravity稳定连接（免费方案）"
description: ""
category: ""
tags:
  -
status: published
reviewNote: ""
pinned: false
coverImage: ""
pubDate: 2026-03-13T04:34:07.430Z
updatedDate: 2026-03-13T04:34:07.430Z
scheduledAt: ""
---

# Clash + Netch 实现 Google Antigravity AI 稳定连接（免费方案）

**核心痛点**：TUN 模式网络不稳定、Proxifier 配置复杂且需付费

## 推荐方案：Clash + Netch 进程级精准代理

- **优点**：稳定、免费、只代理 Antigravity、不影响全局网速
- **适用人群**：Windows 用户（Mac 未适配）
- **所需软件**：

  - Clash for Windows / Clash Verge（已配置好代理）

  - Netch（免费开源进程代理工具）

## 三步保姆级教程

### 第一步：代理就绪（Proxy Ready）

1. 打开 Clash（Windows 或 Verge）

2. 开启 **系统代理（System Proxy）**

3. **重要**：Clash for Windows 用户必须同时开启 **Allow LAN** 选项

### 第二步：Netch 配置

1. **下载 Netch**（自行搜索最新版或 GitHub 下载）

2. 添加服务器：

   - 类型：**Socks5**

   - 地址：`127.0.0.1`

   - 端口：`7890`（Clash 默认 HTTP/SOCKS 端口，根据你实际配置调整）

3. 创建专属进程模式：

   - 菜单 → **模式** → **创建进程模式**

   - 点击 **扫描**，选择 Antigravity 安装目录

   - Netch 会自动识别相关进程（Antigravity.exe 等）

### 第三步：启动并使用

**启动顺序非常重要**：

1. 先启动 Clash（保持运行）

2. 再打开 Netch

3. 服务器选择刚刚添加的 **Socks5 127.0.0.1**

4. 模式选择刚刚创建的 **Antigravity 专属模式**

5. 点击 **启动**

成功后即可正常登录和使用 Antigravity AI，告别 TUN 卡顿、掉线、认证失败等问题。

## 推荐辅助工具（作者后续补充）

- 项目名称：**Antigravity-Manager**
- GitHub：https://github.com/lbjlaq/Antigravity-Manager
- 功能：

  - 解决部分账号登录问题

  - 实时查看各模型剩余额度

  - 配合上述代理方案使用效果更佳

## 用户反馈摘录（部分回复）

- “好用，解决了我的问题……TUN 折腾了好久，这个反重力折磨我好久，现在好了很多，谢谢”
- “很好，但不支持 Mac”
- 作者回复：“这个确实没注意，我主要是 Windows”

## 总结一句话

**Clash（系统代理）+ Netch（进程代理）** 是目前最稳定、免费、易上手的 Antigravity AI 科学上网组合方案，强烈推荐 Windows 用户尝试。

#AI工具 #Antigravity #Clash #Netch #网络教程 #AI编程