# Calendar Plus 2.2.27: release branch review

## Baselines and scope

The target is `campuspress/calendar-plus:release` at commit `23688fcf484bbbc051b2b31923f359e7b3695e69`, whose plugin version is **2.2.18**. The supplied deployment ZIP was **2.2.19**. The original deployment-based patch does not apply directly to this Git baseline.

This proposal brings packaged plugin files to the tested 2.2.27 candidate: 435 files match exactly, and three owned PHP/JS files have only end-of-line whitespace normalization so the Git diff passes whitespace checks. It includes differences inherited from the supplied 2.2.19 deployment: Location search markup/positioning and accompanying CSS/bundle, native archive-query/date-retention helper changes, and the CampusPress/Edublogs teacher-role integration. Review these alongside the migration/display changes. The Edublogs module is inherited deployment behavior, not a new general WordPress requirement.

Repository metadata and the upstream legacy test harness are preserved. Existing `.travis.yml` still targets obsolete PHP/WordPress versions and is not the QA environment for this proposal. Its modernization and legacy test expectations remain to review. Retaining development tests in Git does not mean they belong in the installable distribution.

The TEC Migration Exporter 1.0.0 is a separate source-side plugin and is not added to this repository. It gathers supported event dependencies in compatible WXR. Standard exports remain usable when complete. XML contains media URLs, not file bytes.

## Product changes

- Existing TEC importer: bounded XML parsing, native media transfer/URL and attachment-ID mapping, native dates/all-day handling, venue-to-Location relationships, tags/categories, comments and validated local-author mapping.
- Up to three companion Events/Venues/Organisers files identifying one source; selectable paginated preview, existing-Location choices and guarded enrichment.
- Public organiser, website and venue details retained in editable body sections when no native equivalent exists. Unsupported features generate warnings rather than claimed conversions.
- Persistent source identities, local-edit preservation, media repair, resumable bounded per-site jobs, counters/warnings and cancellation that retains completed records.
- Event Feed, image-fit controls, editable Events Showcase grid/list patterns/fields, optional Single Event presentation, contextual List View labels and wrapping.
- Whole-plugin PCP/WPCS remediation, native permissions/REST/input/output/cache/CSV/lifecycle fixes, remote-updater removal and updated package metadata.
- Multisite scheduling-table/settings isolation, first-request capabilities, stricter media URL/redirect validation and missing Gutenberg Image ID/class recovery after failed transfers.

This change is broader than TEC migration and should be reviewed by subsystem. No new scheduling schema is introduced. Existing native scheduling SQL/custom tables remain.

## QA evidence and limits

Development tests ran on 8–11 October 2026 using WordPress 7.0.2/PHP 8.3.32/MariaDB 11.4, with regression suites on WordPress 6.7 and syntax on PHP 8.2.29. The 20 October Git adaptation verifies all 438 packaged files against the candidate ZIP (435 exact, three whitespace-only differences), checks patch application/results, and repeats packaged PHP syntax checks. It is not a fresh full network run.

- Subdirectory network: 50 TEC sources and 50 Calendar Plus destinations plus the main site, with 500 events per source.
- Reconciled 25,000 events, 1,000 Locations, 2,000 attachments, 2,500 comments and 1,750 term definitions.
- Two destination passes: 1,311,000 property checks, not independent scenarios. All 25,000 event fingerprints unchanged after final reruns. Source audit: 803,600 property comparisons.
- Fifty final reruns created/updated nothing, reused 28,000 posts and reported zero failures/warnings. Initial attempts had 251 media failures: 240 harness-route failures and eleven host-route timeouts. All were recovered; the first pass was not uniformly clean.
- All exports/final reruns used normal HTTP export/upload/preview/commit/batch paths. Bulk first jobs used a test-only native start adapter. No test MU-plugin ships.
- 453 HTTP authorization/isolation checks plus switch/media/quota/redirect/role/renderer/network-activation suites passed. Chrome checked all fifty public feeds and sampled mobile/admin/import UI.
- PCP 2.1.0: no errors found. Configured WordPress standards: zero errors/warnings. All 271 packaged PHP files passed syntax. Importer/mapping/compatibility regression suites passed.
- Eight concurrent final reruns: batch p95 59.8 ms, allocated PHP peak maximum 14 MiB. These reused small/shared media, not fresh large downloads, and do not establish fleet capacity.

Full developer notes, illustrated QA and test evidence are supplied separately to the reviewer. Customer exports, databases, credentials, local Docker controls and test fixture media are not committed here.

## Release decisions

1. Configure historical retention before production cron/cutover. Default cleanup removed 200 of 500 schedule rows in a controlled probe while retaining posts; the existing `calendarp_keep_old_dates` filter prevented deletion. All rows were restored exactly. No new retention setting ships, and reruns are not a rollback for pruning.
2. CampusPress must assess inherited SQL/custom tables and optional external integrations. Static checks do not establish policy approval.
3. Independent security/hosting review remains necessary for DNS rebinding, IPv6/dual-stack, media redirects/same-origin trust and host egress. The IPv4 lookup does not pin transport addresses.
4. The prior audit found 89 development dependency advisories. Those tools are excluded from the runtime ZIP. PHP 8.0 runtime, authenticated Google/OAuth, persistent caches, multi-network/domain mapping and broader hosting combinations need more validation.
5. Expand large/slow-media/quota, hard-crash/race/soak, cross-zone/DST, additional media-block and full saved-editor validation. Fixtures used shared small posters/PDFs and minimal Image blocks. Complete TEC Pro/Tickets/attendee migration and offline media archives are not implemented.
6. Limits remain 8 MiB aggregate XML and 1,000 total related post records, not 1,000 events. Media checks bound size, pixels and quota; cooperative budgets do not interrupt in-flight native processing.
7. `switch_to_blog()` does not load another site's plugins. Integrations must bootstrap the intended context and balance restores. Widget/module/admin/theme initialization now happens at appropriate hooks; early-access custom integrations need review.
8. Existing role customizations are preserved. Review capability migration and the inherited teacher integration on representative installations.
9. Back up database/uploads and stage the exact candidate. File rollback/cancellation does not undo completed imports. Preserve source identity and local-edit protections.

Use the separate developer handoff for complete build, upgrade, rollback and unsupported-feature details. This is a review candidate, not production approval.
