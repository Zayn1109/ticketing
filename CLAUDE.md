# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 專案性質與協作方式

這是一個**學習專案**（活動票務平台，進度見 `README.md` 的「開發藍圖」，目前在 W4–W5），使用者同時在 macOS 筆電與 Linux 桌機開發，透過 Git 同步。回覆一律使用繁體中文。

- **開發流程**：程式由 Claude 撰寫，使用者審核。做功能時：
    1. 先分析並列出**分階段計畫**，每個階段簡短說明要做什麼、預計更動哪些檔案
    2. **一次只改一個檔案**：先說明要改的內容與理由（說明「為什麼」，不只給答案），等使用者確認沒有問題或討論完畢，才動檔案
    3. **一個小階段結束時**，詢問是否要 commit，並提供分類好的 commit 草稿（訊息＋檔案）
- **指令執行**：測試、建置等指令通常由使用者自己執行，除非被要求代跑
- **Commit**：拆成合理的小 commit，每個都是一行的 Conventional Commit（繁體中文，如 `feat: …`、`test: …`、`fix: …`），**不加 body、不加 Co-Authored-By**。先列出草稿（訊息＋檔案）等確認再執行；**不要 push**，由使用者自己推。每個 commit 單獨 checkout 都應能通過測試
- **換機器 pull 後的同步檢查**：
  `git diff --stat HEAD@{1} HEAD -- composer.lock package-lock.json database/migrations .env.example`
  有變動就對應執行 `composer install` / `npm install` / `php artisan migrate` / 比對 `.env`。Wayfinder 產生的 `resources/js/routes`、`resources/js/actions` 在 `.gitignore` 裡，pull 後要跑一次 `npm run dev`（或 `composer run dev`）重新產生

## 常用指令

```bash
docker compose up -d                 # MySQL（主機埠 3307，非 3306）與 Redis
composer run dev                     # server(:8000) + queue + logs + Vite(:5173)
php artisan migrate:fresh --seed     # 重建 DB 並灌入 DemoSeeder 示範資料

php artisan test                                   # 全部測試（Pest，SQLite in-memory）
php artisan test --filter=PermissionMatrixTest     # 單一測試類別或方法
php artisan test --filter=Organizer --display-warnings

composer types:check     # Larastan / PHPStan level 7
composer lint            # Pint 自動修正；composer lint:check 只檢查
npm run types:check      # vue-tsc
npm run build            # vp build（Vite+）
composer ci:check        # CI 跑的完整檢查：npm run check + vue-tsc + composer test
```

`composer test` 本身就包含 `lint:check` 與 `types:check`，再執行 `php artisan test`。

示範帳號（`database/seeders/DemoSeeder.php`，密碼為 UserFactory 預設）：`owner-a`/`admin-a`/`scanner-a@example.test`（甲娛樂）、`-b` 結尾為乙娛樂、`multi@example.test`（甲 admin＋乙 scanner）、`buyer@example.test`（非成員）。

## 架構重點

技術棧：Laravel 13 + Inertia v3 + Vue 3 + TypeScript + Tailwind 4，Fortify 處理認證。設計文件在 `docs/`（`erd.md`、`state-machines.md`、`events.md`、`permissions.md`、`adr/`）；做相關功能前先讀對應文件，重大決策另寫 ADR（慣例見 `docs/adr/README.md`，ADR 寫完不改內容）。

### 多租戶隔離（ADR 0005）

租戶（主辦方）**由網址決定**：主辦方後台路由一律在 `organizers/{organizer}` 底下（`routes/web.php`）。不使用 Global Scope，也不把目前主辦方存在 session。隔離分四層：

1. `organizer.member` middleware（`app/Http/Middleware/EnsureOrganizerMember.php`）：非成員回 **404**，不透露主辦方是否存在
2. `->scopeBindings()`：網址中的子資源必須屬於該主辦方，否則 404
3. Gate（`->can('event.viewAny', 'organizer')`）：成員但角色不足回 **403**
4. **查詢一律從主辦方往下**（`$organizer->events()`），列表頁的隔離只靠這一層，`scopeBindings` 管不到

`tests/Feature/Organizer/TenantIsolationTest.php` 守前三層，各頁面的測試（例如 `EventIndexTest` 的 `has('events', 2)`）守第四層。

### 權限（ADR 0004、`docs/permissions.md`）

角色是固定三級權限：`owner ⊇ admin ⊇ scanner`（`app/Enums/OrganizerRole.php` 的 `atLeast()`）。所有 ability 都在 `app/Providers/AuthServiceProvider.php` 以「ability ⇒ 最低角色」對照表定義，透過 `User::roleIn($organizer)` 判斷。`tests/Feature/Organizer/PermissionMatrixTest.php` **刻意手寫一份矩陣**對照 `docs/permissions.md`，不讀 AuthServiceProvider，改權限時三處要一起改。

### Inertia 頁面與 props

- `Inertia::render('organizer/events/Show')` 對應 `resources/js/pages/organizer/events/Show.vue`，**大小寫必須一致**（macOS 不分大小寫但 Linux 會分）。`config/inertia.php` 的 `testing.ensure_pages_exist` 為 true，頁面檔不存在時測試會失敗
- 版面由 `resources/js/app.ts` 的 `layout()` 依頁面名稱前綴決定（`auth/`、`settings/`、其他用 `AppLayout`）；頁面解析由 `@inertiajs/vite` plugin 自動注入
- **props 一律手動列白名單**，不直接傳 Model（props 會完整寫進 HTML 的 page 物件）。日期用 `toIso8601String()`，前端依場次的 `timezone` 欄位以 `Intl.DateTimeFormat` 顯示（DB 與 app 時區為 UTC）。測試用 `assertInertia` 搭配 `missing()` 或整個物件比對確認沒有多傳欄位
- Vue 的 `defineProps` 型別是**手動與 Controller 對應**的；共用 props（`HandleInertiaRequests::share()`：`auth`、`organizers`、`sidebarOpen`）的型別宣告在 `resources/js/types/global.d.ts`
- 網址用 Wayfinder 產生的函式（`import { show } from '@/routes/organizers/events'`），不手寫字串
- PHP enum 對應的前端型別／標籤放在 `resources/js/lib/`（例如 `eventStatus.ts`），用 `Record<Enum, …>` 讓新增狀態時型別檢查會提醒

### 已知的型別陷阱

多對多關聯的 `->get()`（以及關聯屬性）在 PHPStan 中**會遺失 pivot 型別**，只有 `first()` 保留。需要讀取角色清單時改從中介表查詢：`User::organizerMemberships()`（`HasMany<OrganizerUser>`）搭配 `OrganizerUser::organizer()`，不要用 `@phpstan-ignore` 或手寫 `@param`／`@var` 蓋過推斷。不確定型別時用 `\PHPStan\dumpType()` 查證。

專案以 `Date::use(CarbonImmutable::class)` 讓所有日期都是 `CarbonImmutable`，model 的 `@property` 也要寫 `CarbonImmutable`。方法參數只需要讀取時間時宣告成 `CarbonInterface`，不要宣告成 `Illuminate\Support\Carbon`（PHPStan 會相信註解而放行，執行時才會 TypeError）。

### 慣例

- 測試採 PHPUnit class 風格（`class XxxTest extends TestCase`、`test_` 方法、`#[DataProvider]`）。專案裝了 Pest，`php artisan make:test` 預設會產生 Pest 函式風格，要加 `--phpunit`
- DataProvider 每筆資料的參數數量要和測試方法一致（多傳會出 warning）；只需部分欄位時另寫一個由主 provider 衍生的 provider
- 測試排序不要依賴中文名稱（SQLite 與 MySQL collation 不同），改用 id 或明確的時間
- Model 欄位以 `@property` 註解宣告，`withCount` 產生的欄位用 `@property-read`

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:

- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
    - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Tests (PHPUnit class style)

- This project runs tests with Pest as the runner, but ALL tests are written in PHPUnit class style (`class XxxTest extends TestCase`, `use RefreshDatabase;`, `public function test_xxx(): void`). Never write Pest functional syntax (`it()`, `test()`, `expect()`).
- Create tests with `php artisan make:test {name}` (without `--pest`), e.g. `php artisan make:test Organizer/TenantIsolationTest`.
- Check sibling test files under `tests/Feature` for structure before writing a new test.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval.

## Running Tests

- Run the narrowest set of tests that covers the change: `php artisan test --compact --filter=testName` or a file path.
- Run `composer test` (pint + phpstan + tests) before committing.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.

- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>
