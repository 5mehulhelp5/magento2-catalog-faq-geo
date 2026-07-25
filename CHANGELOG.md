# Changelog

All notable changes to this project will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- **The test suite and the static analysis now run outside a Magento install.** Everything
  below was invisible locally, where the module lives inside an install, and broke the moment
  the repository was checked out on its own:
  - `composer install` aborted because `dealerdirect/phpcodesniffer-composer-installer` — a
    plugin `magento/magento-coding-standard` depends on — was not in `config.allow-plugins`.
  - `magento/magento-coding-standard` was pinned to `^32 || ^33`, neither of which supports
    PHP 8.4, so dependency resolution failed outright on current PHP. Now `^38 || ^39 || ^40`.
  - PHPStan excluded `vendor` from *reporting* but still indexed it as project source, which
    pulled in `rector/rector`'s stub of `PHPUnit\Framework\TestCase` — a stub with a single
    method — shadowing the real class and making every inherited assertion in the suite look
    undefined (177 phantom errors). It is now excluded from indexing as well.
  - The "Magento generates factories at DI compile time" ignore patterns used `[A-Za-z\\]+`
    and so never matched Magento's versioned service contracts, e.g.
    `Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory` — the `V1` contains a digit.
  - `phpunit.xml.dist` hard-coded a bootstrap path four levels up into the install. It now
    points at `Test/bootstrap.php`, which uses the install's unit framework when present and
    the module's own Composer autoloader otherwise, supplying the DI-generated factories the
    suite doubles.
  - `phpcs` was pointed at the checkout root, which in a standalone checkout also contains
    the installed `vendor/`.
- **`@var` annotations no longer contradict the type they narrow.** `Block/Faq/Hreflang.php`
  and `Model/UrlRewrite/FaqUrlRewriteGenerator.php` widened `$entity` to `AbstractModel` /
  `DataObject` to reach `getData()`; both now intersect with the declared interfaces instead.

### Changed

- CI no longer fails before it starts. The workflow referenced the `secrets` context from a
  step-level `if:`, which is not permitted and made the file invalid, so every push failed in
  zero seconds without running a step. Static analysis is scoped to the newest supported PHP
  (the suite's PHPUnit 12 attributes only exist where PHPUnit 12 can install); unit tests and
  the coding standard still run across PHP 8.1–8.4. The release job no longer fails when a
  release already exists for the tag being built.

## [2.0.1] - 2026-07-25

### Fixed

- **Exported tag names can now be re-imported.** `magendoo:faq:export` writes tags as names,
  but nothing mapped them back to ids, so the name reached the junction table, cast to `0`
  and violated the foreign key — aborting the whole row. A plain export → import round trip
  failed on any question that had tags. Import now resolves names to ids case-insensitively
  and **creates any tag that does not exist**, which also gives merchants the only bulk way to
  add tags, since the module has no admin screen for creating them. Generated URL keys are
  deterministic and de-duplicated, so importing the same file twice changes nothing.
- The junction writer now drops anything that is not a positive integer instead of turning it
  into a row that cannot exist, so no future caller can reproduce this class of failure.

## [2.0.0] - 2026-07-25

A review-and-hardening release: 43 fixes across routing, security, caching, the admin surface,
email and the REST API, plus the first real test suite. Several 1.0.0 features that could not
work at all (ratings, tags, customer-group restrictions, admin notifications, canonical links,
grid delete, icon upload) now do.

### Upgrade notes — required steps

```bash
bin/magento setup:upgrade          # new columns, unique url_key, new foreign keys
bin/magento magendoo:faq:reindex   # purge and regenerate ALL FAQ URL rewrites
bin/magento cache:flush
```

The reindex is **mandatory**: 1.0.0 wrote question rewrites against a literal `store_id = 0`
(which the router never matches — every question URL saved with "All Store Views" was a 404)
and never deleted rewrites for removed entities. The command now purges and rebuilds, which
repairs both. `setup:upgrade` will fail if existing rows violate the new `url_key` unique
constraint — deduplicate url_keys first on stores with hand-edited data.

### Breaking changes

- **API — `QuestionManagementInterface::rateQuestion()`** drops the `$customerId` and
  `$ipAddress` parameters (they let any caller stuff votes and attribute them to real
  customers). New signature: `rateQuestion(int $questionId, string $voteType)`. Voter identity
  is resolved server-side; extra request fields are ignored on the REST route.
- **API — `submitQuestion()`** now takes a `bool $gdprConsent` argument and returns the new
  `PublicQuestionInterface` projection.
- **REST — anonymous responses no longer leak asker PII.** `getProductQuestions()`,
  `getCategoryQuestions()`, `searchQuestions()` and the question url-key lookup return the
  public projection: `sender_name`, `sender_email`, `customer_id`, `status` and `visibility`
  are gone from anonymous payloads. The url-key route is now served by
  `getQuestionByUrlKey()` and returns 404 for non-public questions.
- **REST — `POST /V1/faq/questions/submit` requires an authenticated customer.** The
  anonymous route bypassed reCAPTCHA (and every other submit rule) entirely; guests submit
  through the protected storefront form.
- **`average_rating` changes meaning** from percent-positive to a 0–5 star average. The
  "Average Rating" mode is now a real 1–5 star input; on the wire the star value travels in
  `voteType` as `"1"`–`"5"`. (No stored data is affected in practice: 1.0.0 ratings always
  failed against a nonexistent table.)
- **Removed config fields** (they were read by no code): `general/add_to_toolbar`,
  `general/add_to_footer`, the entire `home_page` group (`use_cms_page`, `cms_page_id`,
  `layout`), `navigation/show_ask_button`, `navigation/include_categories_in_search`,
  `product_page/short_answer_behavior` and `seo/rich_breadcrumbs`.
- **Schema:** `url_key` is now unique on the category and question tables; the customer-group
  junction tables gained foreign keys.
- **Licence headers corrected from OSL-3.0 to MIT**, matching the LICENSE file and
  composer.json the project has always shipped. A correction of a contradiction, not a
  relicensing. `composer.json` no longer hardcodes a `version` (tags are the source of truth).

### Fixed — URLs and SEO

- Question URL rewrites are written against real store ids; saving with "All Store Views"
  no longer produces dead `store_id = 0` rows (every question URL 404'd on a stock install).
- `magendoo:faq:reindex` no longer un-scopes every rewrite (it now looks up each entity's
  store assignment) and no longer aborts on the first url_key collision — it skips and reports.
- URL rewrites are deleted when an entity is deleted or unpublished; url_key collisions
  produce an actionable error naming the conflicting path instead of "Something went wrong".
- The router verifies category/question membership on two-segment URLs (previously any
  category paired with any question served a duplicate 200) and redirects suffix and
  trailing-slash variants to the canonical form.
- A canonical `<link>` is actually emitted (gated by `seo/use_canonical`, with the per-entity
  override) — in 1.0.0 the config and entity fields had no frontend consumer.
- Hreflang alternates respect per-store prefixes, entity store assignment and module
  enablement instead of gluing the current path onto every store's base URL.
- JSON-LD is hex-escaped so a shopper-submitted question title containing `</script>` can no
  longer break out of the structured-data block (stored XSS).

### Fixed — features that had no working write path

- **Ratings**: votes are recorded against the real `magendoo_faq_rating` table — every vote in
  1.0.0 failed with "Base table not found" and the raw SQL error was shown to the shopper.
  Guest-rating enforcement now actually applies.
- **Tags**: the question form gained a Tags multiselect and save path — the tag cloud, tag
  pages and router tag branch previously read a junction nothing could populate.
- **Customer-group restrictions**: both admin forms gained Customer Groups multiselects, and
  the storefront listings, question page and anonymous REST reads all enforce them.
- **Admin notifications**: `sendAdminNotification()` is now called after a successful
  submission (form and REST) — it previously had no caller.
- **GDPR consent** is recorded: `consent_given_at` (UTC) plus a snapshot of the exact consent
  wording shown, displayed read-only on the admin form. The consent checkbox label also
  renders again (it was written to one config path and read from another).

### Fixed — caching

- Storefront blocks implement `IdentityInterface`, so answering, editing or unpublishing a
  question invalidates the affected full-page-cache pages (previously changes were invisible
  until a manual flush).
- Removed the hour-long block cache on the FAQ home block that served one customer group's
  content to all groups.
- The ask form no longer reads the customer session on a cacheable page (it gates
  client-side), so "please log in" can no longer get baked into the logged-in cache variant.
- Search results are non-cacheable so the search log counts repeat searches.

### Fixed — admin surface

- Grid row Delete and the edit-page Delete buttons work (they issued GETs against POST-only
  controllers and 404'd).
- Grids are no longer empty for non-superuser roles (the listings referenced ACL ids that
  did not exist).
- Category icon upload works: the missing `faq/category/upload` controller now exists, the
  form shows the stored image, saving without a new upload no longer wipes the icon, and the
  storefront renders the proper media URL.
- "Save and Send Email" reports what actually happened (sent / notifications disabled /
  transport failure) instead of always claiming success.
- A failed save restores everything the admin typed (the data persistor is now populated).
- Opening an edit form runs a constant number of queries instead of 3N+1 over the whole table.

### Fixed — email and submission

- The answer notification sends to exactly one validated recipient (a shopper-controlled
  field was previously split on commas into a recipient list), renders in the correct area,
  and resolves the store from the question's own assignment.
- The submit success redirect uses the validated referer helper instead of the raw
  `Referer` header (open redirect).
- The reCAPTCHA observer takes request/response from DI — enabling reCAPTCHA (the module's
  own recommendation) previously fataled every submission with HTTP 500.
- Storefront form and REST submit share one server-side rule set (guest permission, email
  validation, GDPR consent, slug sanitisation); caller-supplied answers, counters and
  customer ids are ignored.

### Fixed — data layer and performance

- Export carries the relation columns it silently dropped and defuses spreadsheet formula
  injection; import reports unknown ids instead of quietly creating duplicates.
- The tag cloud collapses from unbounded per-tag queries to one bounded query; the categories
  widget from N+2 queries to one; search uses the declared FULLTEXT index instead of a
  double `LIKE` scan.

### Added

- Unit test suite: 85 PHPUnit 12 tests concentrated on the previously broken logic
  (URL-rewrite store scoping, JSON-LD escaping, rating integrity, config getters).
- CI now runs phpcs, PHPStan (with a maintained baseline) and the unit suite on PHP 8.1–8.4;
  fork PRs no longer fail on unavailable marketplace credentials.
- Complete `en_US.csv` translation (269 phrases; 1.0.0 shipped 31).
- Hard dependencies declared: `Magento_Sitemap`, `Magento_Widget` and the reCAPTCHA modules.

### Removed

- Fabricated README/marketing claims (Playwright coverage, unreleased versions 1.1.0/1.2.0).
- The dead config fields listed under breaking changes.

## [1.0.0] - 2026-04-13

### Added
- SEO-optimized FAQ home page, category pages, and question pages with configurable URL prefix and suffix
- Custom frontend router for `faq/{category-slug}/{question-slug}` URL structure
- Product Questions tab on product detail pages (configurable tab name and position)
- Ask-a-Question form on product pages with guest/logged-in support and GDPR consent checkbox
- Answer helpfulness rating system (Yes/No, Voting, and Average Rating modes)
- Social share buttons on question pages (Facebook, Twitter, LinkedIn, Pinterest, Email)
- Admin grids and forms for FAQ Categories and FAQ Questions with full CRUD
- WYSIWYG editor for full answers; short answer field for listing previews
- Question workflow: Pending → Answered → Rejected status transitions
- Question visibility: Public, Logged-in only, Hidden
- 12 database tables: categories, questions, tags, ratings, search log, and M:N junction tables for stores, products, customer groups
- REST API: category CRUD (`/V1/faq/categories`), question CRUD (`/V1/faq/questions`), product questions, category questions, search, submit, rate
- URL rewrites generated on category/question save for SEO tool compatibility
- FAQPage JSON-LD structured data on category and question pages
- XML sitemap integration via `ItemProviderInterface`
- Hreflang tag support for multi-store setups
- Breadcrumbs with Home > FAQ > Category > Question hierarchy
- FAQ search with search terms report in admin
- Tag system with tag cloud and tag pages
- Three FAQ Widgets: Questions List, Categories List, Search Box
- Per-entity robots meta tag (noindex/nofollow) override
- Email notifications: admin notified on new question, customer notified on answer
- Customer group visibility restrictions on categories and questions
- Admin system configuration under Stores > Magendoo Extensions > FAQ and Product Questions
- CLI command `magendoo:faq:reindex` to regenerate URL rewrites
- i18n/en_US.csv translation file

[Unreleased]: https://github.com/magendooro/magento2-catalog-faq-geo/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/magendooro/magento2-catalog-faq-geo/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/magendooro/magento2-catalog-faq-geo/releases/tag/v1.0.0
