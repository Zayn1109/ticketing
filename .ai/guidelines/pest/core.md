# Tests (PHPUnit class style)

- This project runs tests with Pest as the runner, but ALL tests are written in PHPUnit class style (`class XxxTest extends TestCase`, `use RefreshDatabase;`, `public function test_xxx(): void`). Never write Pest functional syntax (`it()`, `test()`, `expect()`).
- Create tests with `php artisan make:test {name}` (without `--pest`), e.g. `php artisan make:test Organizer/TenantIsolationTest`.
- Check sibling test files under `tests/Feature` for structure before writing a new test.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval.

## Running Tests

- Run the narrowest set of tests that covers the change: `php artisan test --compact --filter=testName` or a file path.
- Run `composer test` (pint + phpstan + tests) before committing.
