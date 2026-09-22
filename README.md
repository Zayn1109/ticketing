# Ticketing — 活動票務平台

一個從零打造的活動票務系統：主辦方建立活動並販售票券，使用者線上選位購票、完成付款後取得電子票券，現場以 QR Code 驗票入場。

本專案的重點不只是把功能做出來，而是處理真實票務系統會遇到的工程問題——**開賣瞬間的高併發搶票如何不超賣**、金流回調如何確保冪等、大量通知如何非同步處理、以及整套服務如何容器化並部署上雲。

> 🚧 開發中。目前進度見下方 [開發藍圖](#開發藍圖)。

---

## 技術棧

| 層級 | 技術 | 版本 |
| --- | --- | --- |
| 後端框架 | Laravel | 13.x |
| 語言 | PHP | 8.4 |
| 前端 | Inertia.js + Vue 3 + TypeScript | Vue 3.5 |
| 樣式 | Tailwind CSS | 4.x |
| 資料庫 | MySQL | 8.0 |
| 快取 / Session | Redis | 7 |
| 認證 | Laravel Fortify（信箱驗證、2FA、密碼二次確認） | 1.x |
| 測試 | Pest | 5.x |
| 靜態分析 | Larastan (PHPStan level 7) | 3.x |
| 程式碼風格 | Laravel Pint | 1.x |
| 本機環境 | Docker Compose | — |

---

## 系統需求

- PHP 8.3 以上，並啟用 `pdo_mysql`、`redis`、`mbstring`、`intl`、`gd`、`zip` 擴充
- Composer 2.x
- Node.js 20 以上
- Docker 與 Docker Compose

---

## 快速開始

### 1. 取得原始碼

```bash
git clone git@github.com:Zayn1109/ticketing.git ticketing
cd ticketing
```

### 2. 啟動資料庫與 Redis

```bash
docker compose up -d
docker compose ps
```

等到 `ticketing-mysql` 狀態顯示 `(healthy)` 再進行下一步——MySQL 首次啟動需要約 30 秒初始化。

### 3. 安裝相依套件並初始化

```bash
composer setup
```

這個指令會依序完成：安裝 PHP 套件 → 從 `.env.example` 建立 `.env` → 產生 `APP_KEY` → 執行 migration → 安裝並建置前端資源。

### 4. 啟動開發環境

```bash
composer run dev
```

會同時啟動 Laravel 開發伺服器、佇列 worker、即時日誌監看與 Vite。開啟 http://localhost:8000 即可看到首頁。

---

## 開發常用指令

| 指令 | 用途 |
| --- | --- |
| `composer run dev` | 一次啟動 server / queue / logs / vite |
| `php artisan test` | 執行 Pest 測試 |
| `composer lint` | 以 Pint 自動修正程式碼風格 |
| `composer lint:check` | 只檢查風格，不修改（CI 用） |
| `composer types:check` | 執行 Larastan 靜態分析 |
| `composer ci:check` | 一次跑完前端檢查、型別檢查與測試 |
| `php artisan migrate:fresh --seed` | 重建資料庫並填入測試資料 |

---

## 本機環境說明

### 連接埠

| 服務 | 容器內 | 主機對外 |
| --- | --- | --- |
| MySQL | 3306 | **3307** |
| Redis | 6379 | 6379 |
| 應用程式 | — | 8000 |

> ⚠️ MySQL 對外使用 **3307** 而非預設的 3306，以避免與開發機上既有的 MySQL 服務衝突。使用 GUI 工具連線時請填 `127.0.0.1:3307`。

### 資料儲存位置

| 資料 | 儲存於 |
| --- | --- |
| 應用程式資料 | MySQL（Docker named volume，容器重建不遺失） |
| 快取 | Redis db1 |
| Session | Redis db0 |
| 佇列 | MySQL `jobs` 資料表（將於後續階段改用 Redis + Horizon） |

查看 Redis 內容時需指定資料庫編號，例如快取要用 `redis-cli -n 1 KEYS '*'`。

### 寄信

本機 `MAIL_MAILER=log`，所有信件（含註冊驗證信）會寫入 `storage/logs/laravel.log`，不會實際寄出。

---

## 開發藍圖

- [x] **W1** 專案骨架、Docker 環境、開發規範
- [ ] **W2–W3** 領域模型與 ERD 設計、資料表建置
- [ ] **W4–W5** 身分驗證、角色權限、多租戶資料隔離
- [ ] **W6–W8** 活動管理後台與前台瀏覽
- [ ] **W9–W10** 購票流程與訂單狀態機
- [ ] **W11–W13** 高併發防超賣機制與壓力測試
- [ ] **W14–W15** 金流串接與 Webhook 冪等處理
- [ ] **W16–W18** 佇列、Horizon 與排程任務
- [ ] **W19–W20** 查詢效能優化、快取策略與全文搜尋
- [ ] **W21–W23** 即時票數推播、驗票 API、主辦方報表
- [ ] **W24–W25** 應用程式容器化與 CI 流程
- [ ] **W26–W27** 部署至 AWS
- [ ] **W28–W30** 壓測報告、架構文件與監控

---

## 授權

MIT
