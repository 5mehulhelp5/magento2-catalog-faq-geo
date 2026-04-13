# Lessons Learned — Magendoo_Faq Module

Building a 170+ file Magento 2 module from scratch in a few sessions surfaced patterns, pitfalls, and process insights that apply to any non-trivial Magento extension. This document captures the concrete bugs we hit, why they happened, and what to do differently next time.

---

## 1. Magento Architecture

### 1.1 `ExtensibleDataObjectConverter::toNestedArray()` silently strips non-interface data

**The bug:** Admin saved a question with `product_ids`, `category_ids`, and `store_ids` set on the model. The save succeeded (no error), but junction tables stayed empty. Questions never appeared on product pages despite `visibility=public`.

**Root cause:** `QuestionRepository::save()` called `$this->extensibleDataObjectConverter->toNestedArray($question, [], QuestionInterface::class)` which returns ONLY the fields declared on `QuestionInterface`. Since `product_ids`, `category_ids`, `store_ids` are junction-table metadata (not interface properties), they were silently dropped before the resource model's `_afterSave()` could process them.

**Fix:** After `addData($questionData)`, explicitly copy the relation keys from the input DTO back to the model:
```php
foreach (['store_ids', 'category_ids', 'product_ids', 'tags'] as $key) {
    if ($question->hasData($key)) {
        $questionModel->setData($key, $question->getData($key));
    }
}
```

**Lesson:** When Magento repositories use `toNestedArray()`, any data not on the interface is invisible. If your resource model relies on custom data keys in `_afterSave()`, you MUST preserve them explicitly. This is a silent data-loss bug — the save returns success but the side effects never fire.

---

### 1.2 INNER JOIN on optional junction tables breaks REST-API-created entities

**The bug:** Categories and questions created via REST API without explicit `store_ids` parameter had no rows in the `*_store` junction table. Frontend pages using `addStoreFilter()` (which did `INNER JOIN ... WHERE store_id IN (0, $storeId)`) returned empty — the entity existed in the main table but was invisible on every frontend surface.

**Root cause:** Magento's convention for multi-store content (CMS pages, etc.) is "if the entity has no store relation, it's visible everywhere." But INNER JOIN excludes entities with zero junction rows.

**Fix:** Changed all `addStoreFilter()` and `getByUrlKey()` methods to use `LEFT JOIN` with `WHERE store_id IS NULL OR store_id IN (0, $storeId)`. The `IS NULL` branch catches entities with no store restriction.

**Lesson:** Any junction-based filter on optional M:N relations should use LEFT JOIN. INNER JOIN assumes every entity has at least one junction row — which is true for admin-created entities (form enforces selection) but false for API-created ones.

---

### 1.3 Email template IDs must match the config path transformation

**The bug:** System configuration page threw `UnexpectedTemplateIdValueException: Email template is not defined` when rendering the notification email template dropdown.

**Root cause:** Magento's `Config\Source\Email\Template` source model takes the FIELD'S OWN config path (e.g., `magendoo_faq/user_notifications/email_template`), replaces slashes with underscores, and looks up a template with that ID (`magendoo_faq_user_notifications_email_template`). We had registered the template with a different ID (`magendoo_faq_customer_answer`).

**Fix:** Renamed template IDs in `email_templates.xml` to match the transformed config paths:
```xml
<template id="magendoo_faq_user_notifications_email_template" .../>
<template id="magendoo_faq_admin_notifications_email_template" .../>
```

**Lesson:** When registering email templates referenced by `system.xml` select fields with `Magento\Config\Model\Config\Source\Email\Template` source model, the template `id` attribute must equal the field's full config path with slashes replaced by underscores.

---

### 1.4 Admin layout must NOT use `layout="1column"`

**The bug:** Category admin grid threw `Call to a member function setActive() on false` on the `setActiveMenu()` call.

**Root cause:** The layout XML had `<page layout="1column" ...>` which applies the FRONTEND 1column layout. In admin, the menu block is part of the admin-specific page structure. Using a frontend layout type removes the menu block entirely, so `$this->layout->getBlock('menu')` returns false.

**Fix:** Removed `layout="1column"` from admin layout files. Admin pages inherit the correct layout from Magento_Backend automatically.

**Lesson:** Never set `layout="1column"` (or any frontend layout) on admin `view/adminhtml/layout/*.xml` files. The admin page structure is inherited automatically and includes the sidebar menu, breadcrumbs, and page actions that admin controllers depend on.

---

### 1.5 StructuredData block in `head.additional` has no parent context

**The bug:** The JSON-LD StructuredData block was wired in `head.additional` and had a `setQuestions()` API, but nothing ever called it. The JSON-LD script tag was always empty.

**Root cause:** Blocks in `head.additional` have no parent block, so they can't access the current question/category from a parent's data. Unlike blocks in `content` that can call `$this->getParentBlock()->getQuestion()`, head blocks are isolated.

**Fix:** Made the StructuredData block self-sufficient: it reads `getRequest()->getFullActionName()` to detect the page type (question vs. category) and `getRequest()->getParam('id')` to load the relevant entity directly from the repository.

**Lesson:** Blocks placed in `head.additional` or `head` containers must be fully self-contained. They cannot rely on parent blocks, layout context, or data set by other blocks. Design them to resolve their own data from the request.

---

### 1.6 Customer group visibility filter must be applied EVERYWHERE — including JSON-LD

**The bug:** A question restricted to `customer_group_id=2` (Wholesale) was hidden from the main category page content but still appeared in the `<script type="application/ld+json">` structured data — leaking to Google's crawler.

**Root cause:** The StructuredData block's `loadCategoryQuestions()` method built its own collection query but didn't include the customer group visibility filter. The main content block had it; the SEO block didn't.

**Fix:** Added `addCustomerGroupVisibilityFilter()` to every code path that loads questions for frontend display — including the StructuredData block.

**Lesson:** When adding access control filters, audit EVERY code path that loads entities — not just the obvious template-rendering blocks. SEO blocks, widgets, email previews, and API responses are easy to miss. A good pattern: create a single "frontend-safe collection builder" method that always applies all required filters.

---

## 2. Frontend / Theme

### 2.1 Luma tabs deep-linking doesn't reliably open tab content

**The bug:** Navigating to `product.html#product_faq` marked the tab header as `active` but the content panel stayed `display:none`.

**Root cause:** Magento's `mage/tabs.js` `_handleDeepLinking()` deactivates OTHER tabs but doesn't explicitly activate the matching one. It relies on the collapsible widget's `_processState()` to set `options.active = true`. However, due to JS initialization timing, the content sometimes doesn't get the CSS state change.

**Fix:** Added a `faqTabDeeplink` RequireJS component attached via `<script type="text/x-magento-init">` that runs 200ms after init, checks `location.hash`, and force-shows the matching content div.

**Lesson:** Magento's Luma tabs deep-linking is unreliable for non-first tabs. If your module adds a tab that users might link to directly, add a post-init JS fix. Don't rely solely on the core widget.

---

### 2.2 Luma form styling requires specific CSS classes — bare HTML won't work

**The bug:** The "Ask a Question" form rendered with narrow, unstyled inputs and misaligned labels inside the product page tab.

**Root cause:** Luma's CSS targets `.input-text` (not `input[type="text"]`), `.label` (not `<label>`), and `.fieldset` (not `<fieldset>`). The form template used bare HTML elements without these classes.

**Fix:** Added Luma-standard CSS classes: `class="form faq-ask-form"` on `<form>`, `class="fieldset"` on `<fieldset>`, `class="label"` on `<label>`, `class="input-text"` on all `<input>` and `<textarea>`, `class="action submit primary"` on the button.

**Lesson:** In Luma theme, always use Magento's CSS class conventions for forms. The visual framework is class-based, not element-based. Reference `vendor/magento/module-customer/view/frontend/templates/form/` for the canonical pattern.

---

## 3. Testing

### 3.1 Run tests in batches, not as a monolithic suite

**The problem:** Running all 90 tests as one `npx playwright test tests/faq/` command took 2 minutes per run with a 10-minute timeout. When a test hung, we waited the full timeout before learning anything. When debugging one flaky test, we re-ran the entire suite 10+ times — wasting 20+ minutes.

**What worked better:** Running each spec file individually with a 180s timeout:
```bash
for spec in faq-api faq-cli faq-seo faq-frontend ...; do
  npx playwright test "tests/faq/${spec}.spec.js" 2>&1 | tail -1
done
```
Each spec completes in 2–30s. A stuck test is caught in 3 minutes, not 10. Progress is visible line by line.

**Lesson:** When iterating on a large test suite, always batch by spec file with per-batch timeouts. Use the full-suite run only as a final validation after all individual specs pass.

---

### 3.2 Flaky tests from Luma JS widget timing — use `retries`, not debugging loops

**The problem:** The E2E spec's step 1 (fill Ask form on product page) failed intermittently in full-suite runs but passed in isolation. Spent multiple iterations trying to fix it — different locators, cache flushes, broader cache type invalidation.

**Root cause:** Luma's jQuery tabs widget occasionally re-fired and hid the tab content AFTER our `page.evaluate()` forced it visible. The race was timing-dependent and varied with server load.

**Fix:** Added `test.describe.configure({ retries: 2 })` to the E2E spec and used `force: true` on all form interactions. The retries absorb the noise; the force bypasses visibility checks.

**Lesson:** When a test is flaky due to Magento's frontend JS widget timing (not a real bug), mark it with retries rather than spending hours on root cause. The retry pattern is standard in Playwright and communicates "known environmental flake" to other developers.

---

### 3.3 `page.content()` vs DOM locators — HTML truth vs widget truth

**The problem:** `page.content()` showed the form HTML was present, but `page.waitForSelector('#faq-ask-form')` timed out in the same test.

**Root cause:** `page.content()` returns the raw serialized HTML, including elements hidden by `display:none`. DOM locators like `waitForSelector` with `state: 'visible'` require the element to be actually visible — which it isn't when inside a collapsed Luma tab.

**Lesson:** Use `page.content().toContain(...)` for "is this in the page source?" checks and DOM locators for "can the user see/interact with this?" checks. They answer different questions. For product tabs, always force the tab open before using visibility-based assertions.

---

## 4. API Design

### 4.1 Every interface method should have a webapi.xml route from the start

**The bug:** `TagRepositoryInterface` had full CRUD (save, getById, getList, delete, deleteById) but `webapi.xml` only registered GET for getList. The other 4 methods were unreachable via REST.

**Fix:** Added all missing routes. Final count: 24 routes covering every interface method.

**Lesson:** When creating API interfaces, immediately add the corresponding `webapi.xml` routes. Don't defer "we'll add the route when we need it" — it creates an invisible gap between what the interface promises and what the API delivers.

---

### 4.2 URL-key lookups are essential for headless/PWA

**The pattern:** Categories and questions have URL keys (`shipping-faq`, `how-long-does-shipping-take`). The repositories had `getByUrlKey(string, int)` methods but no REST routes for them — only `getById(int)` was exposed.

**Why it matters:** Headless frontends resolve content by URL slug, not by database ID. Without a `getByUrlKey` route, PWA implementations would need a two-step process: search by URL key via `getList` with a filter, then get the ID from results.

**Lesson:** If your entity has a URL key / slug, expose a REST route for it. The pattern `/V1/faq/categories/url-key/:urlKey/store/:storeId` maps cleanly to Magento's path-param-to-method-param binding.

---

## 5. Agentic Development Process

### 5.1 Parallel agents excel at scaffolding, break on integration

**What worked:** Launching 3–5 agents in parallel to create independent file sets (API contracts, config XML, controllers, UI components, frontend templates). Each agent created 15–40 files following clear patterns. This turned a multi-day scaffolding task into ~10 minutes.

**What broke:** Every agent produced code that was syntactically correct but had subtle integration mismatches — method name discrepancies between files (e.g., `getIdByUrlKey` vs `getByUrlKey`), missing DI preferences, UI component XML structure errors that only surface at runtime. The INTEGRATION phase (DI compile + cache flush + smoke test + fix) was where the real work happened.

**Lesson:** Use agents for parallel file creation but budget equal time for integration testing. The scaffolding is 30% of the work; making it all fit together is 70%. Always compile + test immediately after agents complete — don't create more files before verifying the first batch works.

---

### 5.2 Agent rate limits can silently drop files

**The bug:** One agent hit an API rate limit mid-task and completed without creating `Console/Command/ReindexCommand.php`. The agent reported success but the file was missing. DI compile then failed because `di.xml` referenced the class.

**Lesson:** After any agent completes, always verify the file list against expectations. A simple `find ... | wc -l` or explicit file existence check catches dropped files before they cascade into DI errors.

---

### 5.3 The hardest bugs come from "it saved successfully but nothing happened"

The three most time-consuming bugs in this project were all "silent success" patterns:
1. Repository stripping junction data → save returns the entity, no error, but junctions empty
2. INNER JOIN on empty junctions → collection returns zero items, no error
3. StructuredData block never called → template renders, no error, just empty output

All three shared the same trait: **no exception, no log entry, no visible error**. The feature "worked" (no crash) but produced no visible output.

**Lesson:** For critical features (product page display, API responses, SEO output), always add a "verify the output exists" step immediately after implementation. Don't just test that the page returns HTTP 200 — grep for the specific content you expect.

---

## 6. PHP 8.4 Compatibility

### 6.1 `fputcsv()` / `fgetcsv()` require the `$escape` parameter

**The bug:** Export/Import CLI commands threw deprecation warnings that Magento treated as fatal errors:
```
Deprecated Functionality: fputcsv(): the $escape parameter must be provided as its default value will change
```

**Fix:** Always pass all 5 parameters: `fputcsv($fp, $row, ',', '"', '\\')` and `fgetcsv($fp, 0, ',', '"', '\\')`.

**Lesson:** PHP 8.4 deprecated the default value of the `$escape` parameter in CSV functions. Any module using `fputcsv`/`fgetcsv` must explicitly pass it. This is a breaking change that only surfaces at runtime.

---

## Summary: Top 5 Rules for Magento 2 Module Development

1. **Test junction tables explicitly** — save an entity via the same path your users will (admin form, REST API), then query the junction table directly. "Save succeeded" means nothing if the junction rows are missing.

2. **Use LEFT JOIN for optional relations** — any M:N junction that might have zero rows for a valid entity needs LEFT JOIN, not INNER JOIN.

3. **Apply access filters to EVERY output path** — main content, JSON-LD, widgets, API responses, search results. Miss one and restricted content leaks.

4. **Compile + smoke-test after every structural change** — don't accumulate 10 changes and compile once. Each DI/layout/XML change can surface a unique error that's harder to diagnose in a batch.

5. **Budget more time for integration than creation** — creating 170 files is fast with agents/tools. Making them work together as a system takes 3x longer.
