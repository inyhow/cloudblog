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
pubDate: 2026-03-13T04:35:45.184Z
updatedDate: 2026-03-13T04:35:45.184Z
scheduledAt: ""
---

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

## 总结一句话

**Clash（系统代理）+ Netch（进程代理）** 是目前最稳定、免费、易上手的 Antigravity AI 科学上网组合方案，强烈推荐 Windows 用户尝试。