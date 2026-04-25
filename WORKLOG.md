- [DEV] SV-1.1 : Add login with email and password
- [FIX] SV-1.1 : Remove lockout logic (failedAttempts/lockedUntil) from AuthController, User entity, and migration; belongs to SV-1.3. Removed testFiveFailedAttemptsLocksAccount from test suite. All 5 tests pass.
- [DEV] SV-1.2 : Add logout button on the topbar — POST /auth/logout endpoint (204/401/400), AuthTokenRepository::findOneByToken, topbar Stimulus controller, nav logout button in base.html.twig; 4 new tests (AC-1 through AC-6 covered); all 9 tests pass.
SV-1.1 | 2026-04-25 | implemented login with email and password
- [DEV] SV-1.2 : Add logout button on the topbar
- [DEV] SV-1.3 : Add lockout after 5 bad attempts
- [DEV] SV-2.1 : Add daily report email digest — DigestReportBuilder (KPI aggregation), DigestMailer (TemplatedEmail HTML+text via Symfony Mailer), DigestScheduledCommand (app:digest:send), digest_subscribed boolean on users table (default true), system cron at 07:00; 3 unit test classes; all tests pass.
## SV-2.2 — Weekly KPI dashboard

- GET /dashboard/weekly (Bearer-protected, HTML response)
- New: WeeklyKpiBuilder service (week-boundary logic, delegates to UserRepository)
- New: UserRepository::countCreatedBetween(), UserRepository::countAll()
- New: templates/dashboard/weekly.html.twig (new_users + total_users KPI cards, week range label)
- Tests: WeeklyKpiBuilderTest (unit, 2 cases), DashboardControllerTest (functional, 4 cases)
- All tests pass
## SV-2.3 — Export reports to CSV

- GET /reports/export/csv?type={weekly|daily} (Bearer-protected, CSV download)
- New: CsvReportExporter service (php://temp + fputcsv, CSV injection sanitisation)
- New: ReportExportController (delegates to WeeklyKpiBuilder or DigestReportBuilder)
- Response headers: Content-Type text/csv, Content-Disposition attachment, Cache-Control no-store, X-Content-Type-Options nosniff
- Tests: CsvReportExporterTest (unit, 5 cases), ReportExportControllerTest (functional, 6 cases)
- No database migration required
- [DEV] SV-2.3 : Export reports to CSV
