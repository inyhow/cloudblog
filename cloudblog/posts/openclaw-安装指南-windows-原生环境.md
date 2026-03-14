---
title: "OpenClaw 安装指南 · Windows 原生环境"
description: ""
category: "openclaw"
tags:
  - openclaw
status: published
reviewNote: ""
pinned: false
coverImage: ""
pubDate: 2026-03-14T12:09:26.525Z
updatedDate: 2026-03-14T12:09:26.525Z
scheduledAt: ""
affiliate: false
template: 
customData: {"price":"999","affiliateLink":"http://google.com","heroImage":""}
---

# OpenClaw 安装指南 · Windows 原生环境

> 适用系统：Windows 10 / Windows 11

> 安装时间：约 5 分钟

> 最新版本：`2026.3.12`

---

## 前置条件

### Node.js 版本要求

OpenClaw 要求 **Node.js v20 或更高版本**，推荐使用 v22 / v24。

在 PowerShell 中验证当前版本：

```
node -v
```

示例输出：

```
v24.14.0
```

如果未安装 Node.js，前往 [nodejs.org](https://nodejs.org) 下载 LTS 版本安装器，或使用 Chocolatey：

```
choco install nodejs-lts
```

---

## 安装步骤

### 第一步：全局安装 OpenClaw

打开 **PowerShell**（无需管理员权限），执行：

```
npm install -g openclaw@latest
```

安装过程中会看到类似输出：

```
npm warn deprecated node-domexception@1.0.0: Use your platform's native DOMException instead

added 13 packages, removed 104 packages, and changed 542 packages in 4m

95 packages are looking for funding
  run `npm fund` for details
```

> [!NOTE]

> `npm warn deprecated` 是正常提示，不影响安装结果，可忽略。

### 第二步：验证安装

```
openclaw --version
```

成功输出示例：

```
OpenClaw 2026.3.12 (6472949)
```

---

## 后续配置

### 初始化向导（可选）

OpenClaw 提供完整的 onboard 向导，帮助你配置 AI 网关、工作空间和频道：

```
openclaw onboard --install-daemon
```

### 查看帮助

```
openclaw --help
```

---

## 日常更新

如需升级到最新版本，重新执行安装命令即可：

```
npm install -g openclaw@latest
```

---

## 常见问题

| 问题 | 原因 | 解决方法 |

| ------ | ------ | ---------- |

| openclaw 命令不存在 | npm 全局目录未加入 PATH | 重启 PowerShell 或检查 npm bin -g 路径 |

| 安装速度慢 | 网络问题 | 配置 npm 镜像：npm config set registry https://registry.npmmirror.com |

| Node.js 版本过低 | v20 以下不支持 | 升级 Node.js |

---

*文档生成时间：2026-03-13*
