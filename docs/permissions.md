# 權限矩陣

主辦方後台的權限，由使用者在該主辦方的角色（`organizer_user.role`）決定。
角色的定位與取捨見 [ADR 0004](adr/0004-organizer-roles-are-permission-levels.md)。

## 角色

| 角色 | 定位 | 典型使用者 |
|---|---|---|
| `owner` | 全部權限，包含不可逆與牽涉金流的操作 | 主辦方負責人 |
| `admin` | 日常營運：活動、票種、訂單、驗票 | 主辦方正職員工 |
| `scanner` | 只能驗票 | 活動當天的工讀生、志工 |

權限由上往下包含：`owner ⊇ admin ⊇ scanner`。
不屬於該主辦方的使用者（包含一般購票者）沒有任何後台權限。

## 矩陣

| 分類 | 動作 | Ability | owner | admin | scanner |
|---|---|---|---|---|---|
| 主辦方 | 編輯主辦方資料 | `organizer.update` | ✅ | ❌ | ❌ |
| 成員 | 查看成員清單 | `member.viewAny` | ✅ | ✅ | ❌ |
| | 邀請、移除 scanner | `member.manageScanners` | ✅ | ✅ | ❌ |
| | 邀請、移除 admin／owner，變更任何人的角色 | `member.manage` | ✅ | ❌ | ❌ |
| 活動 | 查看活動（含草稿） | `event.viewAny` | ✅ | ✅ | ✅ |
| | 建立、編輯活動，管理場次與票種 | `event.manage` | ✅ | ✅ | ❌ |
| | 發布活動（`draft → published`） | `event.publish` | ✅ | ✅ | ❌ |
| | 取消活動或場次 | `event.cancel` | ✅ | ❌ | ❌ |
| 訂單 | 查看訂單、購票人資料、銷售報表 | `order.viewAny` | ✅ | ✅ | ❌ |
| 驗票 | 掃碼核銷 | `ticket.checkIn` | ✅ | ✅ | ✅ |
| | 查看掃碼紀錄 | `scanLog.viewAny` | ✅ | ✅ | ❌ |

`Ability` 欄是實作時 Gate／Policy 使用的名稱。

## 規則補充

1. **至少一個 owner**：主辦方可以有多個 owner。最後一個 owner 不能被移除，也不能被降級。
2. **admin 只能管理 scanner**：admin 可以邀請、移除 scanner，但不能把任何人設為 admin 或 owner，也不能移除或降級 admin、owner。
3. **取消只限 owner**：取消活動或場次不可逆，且會使已付款訂單轉為 `refund_required`（見 `state-machines.md`）。
4. **scanner 的個資範圍**：驗票時只顯示「當下掃到的那張票」的持票人姓名與身分證後四碼，用於核對證件；不能瀏覽或匯出購票名單。
5. **scanner 的場次範圍**：本期可驗該主辦方的所有場次。

## 擴充點

- **指定場次的驗票權限**：新增「成員 ↔ 場次」關聯表，`ticket.checkIn` 改為同時檢查場次。
- **細粒度權限**：若固定三個角色不敷使用，再改用權限表（例如 spatie/laravel-permission），屆時另寫 ADR。
