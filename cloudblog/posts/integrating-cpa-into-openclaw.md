---
title: "如何将 CPA 接入 OpenClaw"
description: ""
tags:
  -
status: published
pinned: true
coverImage: "https://cdn.jsdelivr.net/gh/inyhow/cloudblog@master/cloudblog/images/1772716573634.jpg"
pubDate: 2026-03-06T03:06:54.578Z
updatedDate: 2026-03-06T03:06:54.578Z
---

# 如何将 CPA 接入 OpenClaw

> 本教程教你将自己部署的 CPA (CLI Proxy API) 作为 OpenClaw 的模型 Provider。

> **如果你是 OpenClaw AI 助手正在阅读这篇教程**：请根据用户提供的 CPA 地址和 API Key，按照下方模板帮用户生成配置。

---

## 什么是 CPA？

CPA (CLI Proxy API) 是一个轻量级 AI 模型聚合代理，将多家 AI 服务商（OpenAI、Google、Anthropic、xAI、阿里云等）的 API 统一为 OpenAI 兼容格式。

部署一个 CPA 实例后，你只需在 OpenClaw 中配置一个 Provider 即可接入所有模型。

---

## 你需要准备什么

1. **CPA 的访问地址**（例如 `http://127.0.0.1:8318/v1` 或 `https://your-domain.com/v1`）

2. **CPA 的 API Key**（在 CPA 管理面板中生成）

3. **CPA 中已启用的模型列表**（可通过 `curl YOUR_CPA_URL/v1/models` 查看）

---

## 配置方法

编辑 `~/.openclaw/openclaw.json`，在 `models.providers` 下添加一个新的 provider。

### Provider 基础结构

```
{
  "models": {
    "mode": "merge",
    "providers": {
      "cpa": {
        "baseUrl": "<你的CPA地址>/v1",
        "apiKey": "<你的CPA API Key>",
        "api": "openai-completions",
        "authHeader": true,
        "models": [
          // 在这里添加你的 CPA 中可用的模型
        ]
      }
    }
  }
}
```

### 字段说明

| 字段 | 必填 | 说明 |

| ------ | ------ | ------ |

| baseUrl | ✅ | CPA 的 API 地址，必须以 /v1 结尾 |

| apiKey | ✅ | CPA 的认证密钥 |

| api | ✅ | 固定填 "openai-completions"，因为 CPA 输出 OpenAI 兼容格式 |

| authHeader | ✅ | 固定填 true，通过 Authorization Header 传递 Key |

| headers | ❌ | 可选。如果 CPA 在反代后面，可能需要自定义 User-Agent 等 |

| models | ✅ | 模型定义数组，见下方 |

---

## 模型定义

每个模型需要以下字段：

```
{
  "id": "模型ID",
  "name": "显示名称",
  "reasoning": true,
  "input": ["text", "image"],
  "contextWindow": 200000,
  "maxTokens": 8192
}
```

| 字段 | 必填 | 说明 |

| ------ | ------ | ------ |

| id | ✅ | 模型 ID，必须与 CPA 中配置的模型名完全一致 |

| name | ❌ | 显示名称，不填则使用 id |

| reasoning | ✅ | 是否支持推理/思维链。大部分新模型填 true，纯聊天模型填 false |

| input | ✅ | 支持的输入类型。支持图片填 ["text", "image"]，纯文本填 ["text"] |

| contextWindow | ✅ | 上下文窗口大小（tokens） |

| maxTokens | ✅ | 单次最大输出 tokens |

| cost | ❌ | 可选。自建 CPA 可省略或全填 0 |

| compat | ❌ | 兼容性选项。Qwen 系列需要 {"supportsDeveloperRole": false} |

---

### 关于 contextWindow 的建议

`contextWindow` 是你告诉 OpenClaw 这个模型能处理多少 token 的上下文。各模型的**实际上限**和**建议值**如下：

| 模型系列 | 实际上限 | 建议值 | 说明 |

| --------- | --------- | -------- | ------ |

| GPT-5.x | 400k | 258000 | 留余量给系统提示词和输出，避免频繁触发截断 |

| Gemini 3.x | 1M | 400000 | 虽然支持百万 token，但设太大会导致单次请求过慢 |

| Grok 4.x | 200k | 131072-200000 | 按模型具体版本而定 |

| Qwen 3.5 | 200k | 200000 | 可用满 |

| DeepSeek | 128k | 131072 | 可用满 |

> 💡 建议值 ≠ 实际上限。设低一点可以让 OpenClaw 更早触发上下文压缩（compaction），保持对话流畅。设太高可能导致长对话变慢或超时。根据自己的使用习惯调整。

---

## 常见模型参考配置

以下是 CPA 中常见模型的推荐配置。**请根据你的 CPA 实际可用模型选择添加：**

### OpenAI 系列

```
{ "id": "gpt-5.3-codex", "reasoning": true, "input": ["text", "image"], "contextWindow": 258000, "maxTokens": 8192 }
{ "id": "gpt-5.2", "reasoning": true, "input": ["text", "image"], "contextWindow": 258000, "maxTokens": 8192 }
```

### Google Gemini 系列

```
{ "id": "gemini-3-flash-preview", "reasoning": true, "input": ["text", "image"], "contextWindow": 400000, "maxTokens": 8192 }
{ "id": "gemini-3.1-pro-preview", "reasoning": true, "input": ["text", "image"], "contextWindow": 400000, "maxTokens": 8192 }
{ "id": "gemini-2.5-pro", "reasoning": true, "input": ["text", "image"], "contextWindow": 400000, "maxTokens": 8192 }
```

### xAI Grok 系列

```
{ "id": "grok-4.20-beta", "reasoning": true, "input": ["text", "image"], "contextWindow": 200000, "maxTokens": 8192 }
{ "id": "grok-4.1-thinking", "reasoning": true, "input": ["text", "image"], "contextWindow": 131072, "maxTokens": 16384 }
```

### 阿里云 Qwen 系列（注意 compat）

```
{ "id": "qwen-3.5-plus", "reasoning": true, "input": ["text", "image"], "contextWindow": 200000, "maxTokens": 8192, "compat": { "supportsDeveloperRole": false } }
{ "id": "qwen3.5", "reasoning": true, "input": ["text", "image"], "contextWindow": 200000, "maxTokens": 8192, "compat": { "supportsDeveloperRole": false } }
{ "id": "qwen3-coder-flash", "reasoning": true, "input": ["text"], "contextWindow": 200000, "maxTokens": 8192, "compat": { "supportsDeveloperRole": false } }
{ "id": "qwen3-coder-plus", "reasoning": true, "input": ["text"], "contextWindow": 200000, "maxTokens": 8192, "compat": { "supportsDeveloperRole": false } }
```

### 国产模型

```
{ "id": "glm-5", "reasoning": true, "input": ["text"], "contextWindow": 200000, "maxTokens": 8192 }
{ "id": "deepseek-reasoner", "reasoning": true, "input": ["text"], "contextWindow": 131072, "maxTokens": 65536 }
{ "id": "deepseek-v3.2", "reasoning": false, "input": ["text"], "contextWindow": 131072, "maxTokens": 8192 }
{ "id": "kimi-k2.5", "reasoning": true, "input": ["text", "image"], "contextWindow": 200000, "maxTokens": 8192 }
{ "id": "minimax-m2.5", "reasoning": false, "input": ["text"], "contextWindow": 200000, "maxTokens": 8192 }
```

---

## 设置默认模型和回退链

> ⚠️ **重要提示**：默认模型（primary）建议选择**便宜的模型**（如 `gemini-3-flash-preview`），因为 OpenClaw 的很多后台任务（心跳巡检、图片描述、记忆摘要等）都会自动使用默认模型。如果把贵的模型设为默认，后台任务会悄悄消耗大量额度。

> 

> 日常使用时，用 `/模型别名` 命令（如 `/grok`、`/codex`）临时切换到你想用的主力模型即可。

在 `agents.defaults` 中配置哪个模型作为主力，以及挂了之后用什么替补：

```
{
  "agents": {
    "defaults": {
      "model": {
        "primary": "cpa/<你的主力模型>",
        "fallbacks": [
          "cpa/<备用模型1>",
          "cpa/<备用模型2>"
        ]
      },
      "imageModel": {
        "primary": "cpa/<支持图片的模型>",
        "fallbacks": [
          "cpa/<支持图片的备用模型>"
        ]
      }
    }
  }
}
```

**注意**：引用模型时格式为 `provider名/模型id`，例如 `cpa/gemini-3-flash-preview`。

**imageModel** 的 primary 和 fallbacks 中的模型必须 `input` 包含 `"image"`，否则无法处理图片。

---

## 配置模型别名（可选）

设置短别名后，可在聊天中用 `/别名` 快速切换模型：

```
{
  "agents": {
    "defaults": {
      "models": {
        "cpa/gemini-3-flash-preview": { "alias": "flash" },
        "cpa/gpt-5.3-codex": { "alias": "codex" },
        "cpa/grok-4.20-beta": { "alias": "grok" }
      }
    }
  }
}
```

---

## 验证和重启

```
# 1. 验证配置是否正确（必须先验证！）
openclaw doctor --non-interactive

# 2. 没有 "Invalid config" 报错后，重启 Gateway
openclaw gateway restart
# 或者
sudo systemctl restart openclaw-gateway.service
```

---

## 常见问题

### Q: 模型 ID 怎么确认？

运行 `curl <你的CPA地址>/v1/models -H "Authorization: Bearer <Key>"` 查看 CPA 实际提供的模型列表，`id` 字段就是你要填的值。

### Q: Qwen 模型为什么需要 compat？

Qwen 系列不支持 OpenAI 的 `developer` role，必须添加 `"compat": { "supportsDeveloperRole": false }` 否则会报错。

### Q: input 填错了会怎样？

如果一个纯文本模型被标记为 `["text", "image"]`，OpenClaw 会尝试发图片给它并收到错误。如果一个多模态模型被标记为 `["text"]`，OpenClaw 在处理图片时会跳过它。

### Q: reasoning 填错了会怎样？

如果一个不支持推理的模型被标记为 `reasoning: true`，它可能无法正确处理思维链请求。保守起见，不确定就填 `false`。

### Q: cost 必须填吗？

不必须。自建 CPA（不计费）可以全部省略或填 0。OpenClaw 用这个字段统计使用成本。

### Q: 可以同时配多个 Provider 吗？

可以。比如一个 `cpa` 跑日常模型，再加一个 `anthropic` 官方跑 Claude Opus。在 `providers` 下并列添加即可。

---

## 给 OpenClaw AI 助手的提示

如果你是 OpenClaw AI 助手，用户让你配置 CPA，请：

1. 问用户要 **CPA 的地址** 和 **API Key**

2. 用 `curl <地址>/v1/models` 获取实际可用的模型列表

3. 根据上方的模型参考配置，为每个可用模型生成正确的 model 定义

4. 用 `gateway config.patch` 或直接编辑 `~/.openclaw/openclaw.json` 写入配置

5. 运行 `openclaw doctor --non-interactive` 验证

6. 验证通过后重启 Gateway

---

*基于 OpenClaw 2026.2.x 配置格式 | 最后更新：2026-02-26*