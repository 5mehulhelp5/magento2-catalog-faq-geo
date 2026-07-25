# Magendoo FAQ — Admin & Merchant Guide

This guide covers day-to-day use of the FAQ module from the Magento admin: creating and
organising content, moderating customer questions, reading the search report, and every
configuration setting. It matches module version 2.0.0.

## Where everything lives

| Task | Admin location |
|------|----------------|
| Manage FAQ categories | **Content → FAQ → FAQ Categories** |
| Manage FAQ questions | **Content → FAQ → FAQ Questions** |
| Search analytics | **Content → FAQ → FAQ Search Terms** |
| Module settings | **Stores → Configuration → Magendoo Extensions → FAQ** |
| reCAPTCHA for the ask form | **Stores → Configuration → Security → Google reCAPTCHA Storefront** |
| Admin role permissions | **System → Permissions → User Roles** (resources under *Content → FAQ*) |

On the storefront, the FAQ lives at `/faq` (the URL prefix is configurable per store view),
and each product page gets a **Product Questions** tab.

## Creating FAQ categories

*Content → FAQ → FAQ Categories → Add New Category.*

Categories group questions into browsable pages (`/faq/{category-url-key}`) and appear on the
FAQ home page.

- **Name**, **URL Key**, **Page Title** — the URL key must be unique across all FAQ categories
  *and* questions (they share one URL namespace). A duplicate produces an error naming the
  conflicting path.
- **Enabled** — disabled categories disappear from the storefront and their URL rewrite is
  removed.
- **Position** — sort order when "Sort Categories By" is set to *Position*.
- **Description** — shown on the category page.
- **Icon** — upload a JPG, JPEG, GIF, PNG or SVG; shown on the FAQ home page. Saving without
  choosing a new file keeps the existing icon.
- **Store Views** — which store views the category (and its page) appears on.
- **Customer Groups** — restrict the category to selected groups. Leave empty for "all
  groups". Restrictions apply on the storefront and on the anonymous REST API.
- **Search Engine Optimization** — meta title, meta description, canonical URL override,
  noindex/nofollow, and *Exclude from Sitemap*.

## Creating and answering questions

*Content → FAQ → FAQ Questions → Add New Question.* The question editor is shown in
[the admin editor screenshot](magento2-catalog-faq-geo-edit.png). *(Note: that screenshot
predates version 2.0.0 — the current form additionally has **Customer Groups** and **Tags**
fieldsets.)*

### General

- **Title** — the question as shown to shoppers.
- **URL Key** — unique across all FAQ questions and categories.
- **Status** — the moderation workflow:
  - **Pending** — waiting for review; not visible on the storefront.
  - **Answered** — published (subject to Visibility below).
  - **Rejected** — archived; not visible.
- **Visibility** — who can see an *Answered* question:
  - **None** — hidden everywhere, including its direct URL.
  - **Public** — everyone.
  - **Logged In Only** — signed-in customers only.
- **Position** — sort order when "Sort Questions By" is *Position*.
- **Show Full Answer** / **Hide Direct URL** — these two checkboxes are stored but **not yet
  used by the storefront**; changing them currently has no effect.

### Answer

- **Short Answer** — plain-text preview used in listings (subject to the *Answer Length
  Limit* and *Short Answer Behavior* settings).
- **Full Answer** — WYSIWYG content shown on the question page and the product tab.

### Customer Information

Filled automatically for customer-submitted questions: sender name, sender e-mail, customer
ID, and — when GDPR consent is enabled — the read-only **Privacy Consent Given At (UTC)** and
**Privacy Consent Text Shown** record (the exact wording the customer agreed to, captured at
submission time).

### Assignments

- **Store Views** — the stores where the question appears.
- **Customer Groups** — restrict to selected groups; empty = all groups.
- **Assign to FAQ Categories** — one question can live in several categories.
- **Related Products** — enter product entity IDs, comma-separated (e.g. `1,24,57`). The
  question then appears in each product's **Product Questions** tab.
- **Tags** — select existing tags; the question then appears on those tag pages
  (`/faq/tag/{tag-url-key}`) and in the tag cloud. Note: tags themselves (name + URL key) are
  created via the REST API (`POST /V1/faq/tags`) or by a developer — there is currently **no
  admin grid for creating tags**; the form only assigns tags that already exist.

### Search Engine Optimization

Meta title, meta description, canonical URL override, noindex/nofollow, *Exclude from
Sitemap*. The canonical link is only emitted when **SEO → Use Canonical URL** is enabled in
configuration.

### Saving

- **Save Question** — saves, regenerates the question's URL rewrite, and returns to the form.
- **Save and Send Email** — additionally sends the "your question was answered" e-mail to the
  asker. This requires **User Notifications** to be enabled in configuration; the result
  message tells you exactly what happened (sent, notifications disabled, or a sending error).
- **Delete** — removes the question and its URL rewrite.

If a save fails (for example a duplicate URL key), everything you typed is restored when the
form reloads.

## Moderating customer-submitted questions

Customers ask questions from the **Product Questions** tab on product pages (guests too, if
*Allow Guest Questions* is on; enable reCAPTCHA for the form to keep bots out). Logged-in
customers can also submit via the REST API. New submissions arrive with status **Pending**
and visibility **None**.

Workflow, using the grid ([screenshot](magento2-catalog-faq-geo-list.png)):

1. Filter the grid by **Status = Pending**. If *Admin Notifications* is enabled you also get
   an e-mail per new question.
2. Open the question, write the **Full Answer** (and optionally a Short Answer).
3. Set **Status = Answered** and **Visibility = Public** (or *Logged In Only*).
4. Assign categories/products/tags/stores as needed.
5. Use **Save and Send Email** to publish and notify the asker in one step.

Grid mass actions: **Delete**, **Mark as Answered**, **Mark as Pending**, **Make Public**,
**Hide**. The category grid offers **Delete**, **Enable**, **Disable**.

## Search Terms report

*Content → FAQ → FAQ Search Terms* shows what shoppers search for in the FAQ — the fastest
way to find content gaps (searches with **Results Count = 0** are questions you haven't
answered yet).

Columns: ID, Search Query, Store View, Results Count, Hits, Created.

Two honest notes:

- **Each executed search is logged as its own row**, so the *Hits* column always shows 1.
  To gauge how often a term is searched, sort or filter by *Search Query* and count rows.
- The endpoint is public, so bot traffic can appear in the log.

## Ratings

Configure under **Rating** in the module settings. Three modes:

- **Yes / No** — "Was this answer helpful?"
- **Voting** — thumbs up/down with visible counts.
- **Average Rating** — shoppers pick 1–5 stars; the question page shows the average (0–5).

Duplicate votes are prevented per customer (logged in) or per IP address (guests). Turn
**Allow Guest Rating** off to require login for voting.

## Configuration walkthrough

*Stores → Configuration → Magendoo Extensions → FAQ.* All settings can differ per store view.

- **General Settings** — *Enable FAQ* (master switch), *FAQ Title* (landing page heading,
  default "FAQ"), *URL Prefix* (default `faq`), *Allow Guest Questions* (default yes).
- **Navigation** — *Show Breadcrumbs*; *Sort Categories By* / *Sort Questions By* (Position /
  Name / Most Viewed); *Answer Length Limit* (default 250 characters for listing previews);
  *Show Search Box*; *No Results Text*; *Questions Per Category Page* and *Questions Per
  Search Page* (default 10 each); *Short Answer Behavior* — whether listing previews show the
  dedicated Short Answer field or a truncated Full Answer; *Tags Limit* — maximum tags in the
  tag cloud (default 20, 0 = unlimited).
- **Product Page** — *Enable FAQ on Product Page*; *Tab Name* (default "Product Questions";
  write `{count}` anywhere in it to display the number of questions, e.g. `FAQ ({count})`);
  *Tab Position* (default 40); *Show Ask Button* (show the ask-a-question form in the tab);
  *Questions Limit* (default 10).
- **Rating** — see [Ratings](#ratings) above.
- **Social Sharing** — off by default; choose networks (Facebook, Twitter, LinkedIn,
  Pinterest, Email) to show share buttons on question pages.
- **SEO** — *Enable URL Suffix* + *URL Suffix* (e.g. `.html`); *Remove Trailing Slash*
  (redirect `/faq/foo/` → `/faq/foo`); *Use Canonical URL* (emit canonical links on FAQ
  pages — needed if you care about duplicate-content signals); *Enable Structured Data*
  (FAQPage JSON-LD, on by default); *Robots Meta for Search Results* (default
  NOINDEX,FOLLOW); *Add to Sitemap* plus *Sitemap Frequency* / *Sitemap Priority* (fall back
  to `weekly` / `0.5` when empty); *Enable Hreflang Tags* for multi-store language setups.
- **User Notifications** — off by default. When enabled, "Save and Send Email" (and the REST
  notify endpoint) e-mails the asker that their question was answered, using the selected
  sender identity and template.
- **Admin Notifications** — off by default. When enabled, every successful submission (form
  or REST) e-mails the comma-separated *Send To* addresses.
- **GDPR** — off by default. When enabled, the ask form requires a consent checkbox with your
  *Consent Text*; the consent timestamp and the exact wording shown are stored on the
  question and displayed read-only in the editor.

## Embedding FAQ content on CMS pages

Three widgets are available through the standard widget inserter (*Content → Widgets*, or the
Insert Widget button in any WYSIWYG editor):

- **FAQ Questions List** — questions, optionally limited to one category; list or accordion
  template; optional answer previews.
- **FAQ Categories List** — category links, optionally with question counts.
- **FAQ Search Box** — a FAQ search form with configurable placeholder text.

## Import, export and URL maintenance

For bulk work, three CLI commands (run by your developer or hosting team):

```bash
bin/magento magendoo:faq:export -e questions        # CSV to var/export/faq-questions.csv
bin/magento magendoo:faq:import -e questions -f file.csv
bin/magento magendoo:faq:reindex                    # purge + regenerate all FAQ URL rewrites
```

- Export includes store, category, product and customer-group assignments (as comma-separated
  IDs) and tag names.
- Import updates rows whose `question_id`/`category_id` exists, creates rows with an empty id
  cell, and skips-with-error rows referencing an unknown id. The four ID-list relation
  columns are applied on import; the **tags column is not** (tag names are not mapped back to
  tag IDs) — clear that column before re-importing and assign tags in the admin form.
- Run the reindex after changing the URL prefix/suffix settings, or whenever FAQ URLs 404.

## Troubleshooting quick list

| Symptom | Check |
|---------|-------|
| Question not visible on storefront | Status = Answered? Visibility = Public? Store view assigned? Customer group not excluding you? |
| FAQ URL 404s | Run `bin/magento magendoo:faq:reindex`, flush cache |
| No notification e-mails | User/Admin Notifications are **off by default** — enable them in configuration |
| Ask form accepts bots | Enable reCAPTCHA for "FAQ Ask a Question Form" under the Security section |
| Duplicate URL key error on save | Another FAQ question *or category* already uses that URL key — they share one namespace |
