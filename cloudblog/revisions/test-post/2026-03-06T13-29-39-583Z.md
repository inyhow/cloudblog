---
title: "更新 OpenClaw 2026.3.2 后觉得 agent 变蠢了"
description: "更新 OpenClaw 2026.3.2 后觉得 agent 变蠢了，只对话不做事"
tags:
  - openclaw
status: published
pinned: false
coverImage: ""
pubDate: 2026-03-06T02:27:50.254Z
updatedDate: 2026-03-06T02:27:50.254Z
---

# 更新 OpenClaw 2026.3.2 后觉得 agent 变蠢了？

不是模型问题 — 这个版本默认把新 agent 的工具权限全关了。exec、web_fetch 都用不了，等于只会说话不会干活。

在 openclaw.json 加上这段就好：

{

  "tools": {

    "profile": "full",

    "sessions": {

      "visibility": "all"

    }

  }

}

感觉这默认改动有点离谱啊 ![😂](https://abs-0.twimg.com/emoji/v2/svg/1f602.svg)

# 貌似这么改还是有问题

光加 profile: "full" 可能还不够

3.2 之后 exec 权限要单独声明：

"tools": {

  "profile": "full",

  "exec": {

    "security": "full",

    "ask": "off"

  }

}

ask: "off" 不加的话 agent 可能卡在等权限确认，但 Telegram/CLI 根本不弹窗，看起来就像 agent 挂了。

多 agent 面对这个更新简直搞笑了…顶层 tools 只管全局默认，每个 agent 要单独配：

"agents": {

  "list": [{

    "id": "my-agent",

    "tools": { "profile": "full" }

  }]

}

不然主 agent 修好了，其他 agent 还是残废。

另外：升级用户如果之前一切正常，大概率不受影响 — 这个改动主要针对全新安装的默认值。

![⚠️](https://abs-0.twimg.com/emoji/v2/svg/26a0.svg) ask: "off" = agent 不经确认直接跑命令

不在 Docker/sandbox 里的话自己掂量