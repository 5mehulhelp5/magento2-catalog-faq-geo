# Magendoo FAQ & Product Questions Module for Magento 2

[![Magento 2](https://img.shields.io/badge/Magento-2.4.x-orange.svg)](https://magento.com)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-OSL--3.0-green.svg)](https://opensource.org/licenses/OSL-3.0)

A comprehensive FAQ and Product Questions management system for Magento 2 that transforms customer support into a conversion driver through SEO-optimized knowledge bases, product-specific Q&A, and customer engagement tools.

## Features

- 📚 **Hierarchical FAQ Organization** — Categories, tags, and product associations
- 🔍 **Advanced Search & Analytics** — Full-text search with search term reporting
- ⭐ **Customer Engagement** — Rating system (thumbs up/down, star ratings)
- 🔗 **Product Page Integration** — Dedicated FAQ tab on product detail pages
- 📱 **Headless Ready** — Full REST API coverage for PWA/GraphQL implementations
- 🔒 **Security First** — reCAPTCHA, GDPR compliance, ACL permissions
- 📈 **SEO Optimized** — Structured data, sitemap integration, clean URLs
- 🌍 **Multi-Store Support** — Store-specific content and customer group restrictions

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation--deployment)
- [Configuration](#configuration-reference)
- [For E-Commerce Managers](#for-e-commerce-managers-business-value)
- [For Developers](#for-developers-technical-architecture)
- [REST API](#rest-api-endpoints)
- [FAQ](#faq)
- [Use Cases](#use-cases)
- [Troubleshooting](#troubleshooting)
- [Changelog](#changelog)
- [License](#license)
- [Contributing](#contributing)
- [Support & Resources](#support--resources)

## Requirements

| Requirement | Version |
|-------------|---------|
| Magento | 2.4.x |
| PHP | 8.0+ |
| MySQL/MariaDB | 8.0+ / 10.4+ |
| Composer | 2.x |

### Supported Magento Editions

- ✅ Magento Open Source (Community)
- ✅ Adobe Commerce (Enterprise)
- ✅ Adobe Commerce Cloud

---

## For E-Commerce Managers: Business Value

### Reduce Support Costs, Increase Conversions

| Metric | Impact |
|--------|--------|
| **Support Tickets** | Proactive FAQ addressing reduces repetitive inquiries |
| **SEO Traffic** | Structured data and SEO-friendly URLs drive organic discovery |
| **Conversion Rate** | Product-specific Q&A removes purchase hesitation |
| **Customer Trust** | Social proof through ratings and helpfulness voting |
| **Content ROI** | Search analytics reveal knowledge gaps and content opportunities |

### Core Business Features

#### 📚 **Hierarchical Knowledge Organization**
- **Categories** — Organize FAQs by topic, product line, or customer journey stage
- **Tags** — Cross-reference questions for flexible discovery paths
- **Product Association** — Link specific questions directly to product pages
- **Multi-Store** — Tailor FAQs per storefront, language, or regional requirements

#### 🔍 **Intelligent Search & Discovery**
- Full-text search across questions and answers
- Search term analytics dashboard — identify what customers can't find
- Configurable results ranking and pagination
- Search results with pagination and sorting

#### ⭐ **Customer Engagement & Social Proof**
- **Three Rating Modes:**
  - *Yes/No Helpfulness* — "Was this answer helpful?"
  - *Voting* — Thumbs up/down with vote counts
  - *Star Rating* — Average 5-star rating display
- **Social Sharing** — Drive traffic via Facebook, Twitter, LinkedIn, Pinterest
- **View Counts** — Surface most-accessed content automatically

#### 📧 **Automated Communication Workflow**
- **Admin Notifications** — Instant alerts for new customer questions
- **Customer Follow-up** — Automatic email when their question is answered
- **Question Status Workflow:**
  - `Pending` → Queue for review
  - `Answered` → Published to storefront
  - `Rejected` → Archived with note

#### 🛡️ **Compliance & Security**
- **GDPR Consent** — Configurable consent checkbox for question submissions
- **reCAPTCHA Integration** — Native Magento reCAPTCHA v2/v3/invisible support
- **Customer Group Restrictions** — Show/hide content by B2B/B2C segments
- **Visibility Controls** — Public, logged-in-only, or hidden per question

#### 📊 **Analytics & Insights**
- Search Terms Report — What are customers searching for?
- Rating analytics — Which answers are most/least helpful?
- View tracking — Content performance metrics
- Question submission trends — Identify emerging support topics

#### 🔗 **Product Page Integration**
- Dedicated FAQ tab on product detail pages
- Configurable tab position and labeling
- "Ask a Question" form embedded in product context
- Shows only relevant Q&A for that specific product
- Reduces cart abandonment by addressing objections at point of decision

---

## For Developers: Technical Architecture

### Service Contract Architecture

The module implements Magento's Service Contract pattern for full API coverage and extensibility:

```
Api/
├── CategoryRepositoryInterface      # Category CRUD
├── QuestionRepositoryInterface      # Question CRUD  
├── QuestionManagementInterface      # Business operations
└── TagRepositoryInterface           # Tag CRUD
```

All repositories support:
- `SearchCriteria` pattern for flexible querying
- `getByUrlKey()` for SEO-friendly lookups
- Full extension through DI preferences and plugins

### REST API Endpoints

Complete REST coverage for headless implementations:

| Endpoint | Method | Access | Description |
|----------|--------|--------|-------------|
| `/V1/faq/categories` | GET/POST | admin | List or create categories |
| `/V1/faq/categories/:id` | GET/PUT/DELETE | admin | Category CRUD |
| `/V1/faq/categories/url-key/:key/store/:id` | GET | public | Lookup by URL key |
| `/V1/faq/questions` | GET/POST | admin | List or create questions |
| `/V1/faq/questions/:id` | GET/PUT/DELETE | admin | Question CRUD |
| `/V1/faq/questions/url-key/:key/store/:id` | GET | public | Lookup by URL key |
| `/V1/faq/questions/submit` | POST | public | Anonymous question submission |
| `/V1/faq/questions/:id/rate` | POST | public | Submit rating/vote |
| `/V1/faq/questions/:id/view` | POST | public | Increment view count |
| `/V1/faq/questions/:id/notify` | POST | admin | Send answer notification email |
| `/V1/faq/products/:id/questions` | GET | public | Product-specific Q&A |
| `/V1/faq/categories/:id/questions` | GET | public | Category questions |
| `/V1/faq/questions/search` | GET | public | Full-text search |
| `/V1/faq/tags` | GET/POST | admin | List or create tags |
| `/V1/faq/tags/:id` | GET/PUT/DELETE | admin | Tag CRUD |

**24 endpoints total — 100% test coverage via Playwright.**

### Custom URL Router

Implements `Magento\Framework\App\RouterInterface` for clean SEO URLs:

```
/faq/                                    → Home page (category listing + search)
/faq/{category-url-key}                  → Category page
/faq/{category-url-key}/{question-key}   → Question detail (within category)
/faq/{question-url-key}                  → Question detail (URL rewrite)
/faq/tag/{tag-url-key}                   → Tag page (questions with this tag)
/faq/search?q=keyword                    → Search results
/faq/question/suggest?q=keyword          → AJAX autocomplete (JSON)
```

**Features:**
- Configurable URL prefix per store
- Optional `.html` suffix
- Trailing slash normalization
- Automatic URL rewrite generation on entity save
- CLI command for bulk regeneration: `bin/magento magendoo:faq:reindex`

### CLI Commands

```bash
# Regenerate FAQ URL rewrites for all categories and questions
bin/magento magendoo:faq:reindex

# Export questions or categories to CSV
bin/magento magendoo:faq:export -e questions -f var/export/faq-questions.csv
bin/magento magendoo:faq:export -e categories -f var/export/faq-categories.csv

# Import questions or categories from CSV (upsert — creates new, updates existing)
bin/magento magendoo:faq:import -e questions -f var/export/faq-questions.csv
bin/magento magendoo:faq:import -e categories -f var/export/faq-categories.csv
```

### CMS Widgets

Three widgets for embedding FAQ content on any CMS page via the admin widget inserter:

| Widget | Description |
|--------|-------------|
| **FAQ Questions List** | Displays questions, optionally filtered by category. List or accordion template. |
| **FAQ Categories List** | Renders category links with optional question counts. |
| **FAQ Search Box** | Embeds a search form with configurable placeholder text. |

### Database Schema (12 Tables)

**Core Entities:**
- `magendoo_faq_category` — Category data with SEO fields
- `magendoo_faq_question` — Question workflow, content, engagement metrics
- `magendoo_faq_tag` — Tag taxonomy

**Relationship Tables:**
- `*_store` — Multi-store visibility
- `*_customer_group` — Access control
- `magendoo_faq_question_category` — Many-to-many categories
- `magendoo_faq_question_product` — Product associations
- `magendoo_faq_question_tag` — Tag assignments

**Analytics Tables:**
- `magendoo_faq_rating` — Individual vote tracking (prevents duplicates)
- `magendoo_faq_search_log` — Search query analytics

### SEO & Structured Data

**Sitemap Integration:**
- Implements `Magento\Sitemap\Model\ItemProvider\ItemProviderInterface`
- Automatic inclusion in `sitemap.xml`
- Configurable change frequency and priority per entity

**Structured Data (Schema.org):**
```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [{
    "@type": "Question",
    "name": "Question title",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "Answer content"
    }
  }]
}
```

**Meta Controls:**
- Per-entity meta title, description
- Canonical URL override
- Noindex/nofollow per entity
- Robots meta for search results page

### Extensibility Points

**Events:**
- Standard Magento events on save/load/delete
- Custom events for workflow transitions

**Plugins:**
- All public methods in repository interfaces
- Controller actions
- Block rendering

**DI Preferences:**
- All interfaces can be overridden
- Collection classes extensible

**UI Components:**
- Admin grids use Magento UI Component framework
- XML merge support for customization
- Custom column types available

### Security Implementation

**ACL Permissions (`etc/acl.xml`):**
```
Magendoo_Faq::faq                    # Root permission
├── Magendoo_Faq::category_view      # View categories
├── Magendoo_Faq::category_edit      # Create/edit categories
├── Magendoo_Faq::category_delete    # Delete categories
├── Magendoo_Faq::question_view      # View questions
├── Magendoo_Faq::question_edit      # Create/edit questions
├── Magendoo_Faq::question_delete    # Delete questions
├── Magendoo_Faq::question_approve   # Approve pending questions
├── Magendoo_Faq::search_log         # View search analytics
└── Magendoo_Faq::config             # Module configuration
```

**Form Security:**
- Form key validation on all POST controllers
- CSRF protection on AJAX rating endpoints
- Input sanitization via Magento framework
- SQL injection prevention via prepared statements

**Spam Prevention:**
- reCAPTCHA observer on question submission
- IP-based duplicate vote prevention
- Customer ID tracking for authenticated users

### Code Quality Standards

- **PHP 8+ Features:** Constructor property promotion, union types, match expressions
- **Strict Typing:** `declare(strict_types=1)` on all files
- **Return Types:** Explicit declarations on all methods
- **Static Analysis Friendly:** Full type coverage for IDE support
- **PSR-12 Compliant:** Standard Magento code formatting

### Frontend Architecture

**RequireJS Components:**
```javascript
faqRating        // AJAX voting handler with duplicate-vote prevention
faqSearch        // Search form validation
faqAutocomplete  // Debounced AJAX autocomplete dropdown
faqTabDeeplink   // Deep-link fix for product page FAQ tab (#product_faq)
```

**Layout Handles:**
```
faq_index_index      # FAQ homepage
category_view        # Category page
question_view        # Question detail
question_search      # Search results
```

**Block Structure:**
```
Block/
├── Faq/
│   ├── Home.php              # Category grid + search
│   ├── Category/View.php     # Category question list
│   ├── Question/
│   │   ├── View.php          # Question display
│   │   ├── AskForm.php       # Submission form (GDPR + reCAPTCHA)
│   │   ├── Rating.php        # Voting widget (3 modes)
│   │   └── SocialShare.php   # Social buttons
│   ├── Tag/
│   │   ├── Cloud.php         # Tag cloud with size classes
│   │   └── View.php          # Tag page question list
│   ├── Search.php            # Search results
│   ├── Breadcrumbs.php       # Navigation
│   ├── Hreflang.php          # Multi-store SEO tags
│   └── StructuredData.php    # JSON-LD (FAQPage schema)
├── Product/Questions.php     # Product tab + deep-link fix
├── Widget/
│   ├── QuestionsList.php     # CMS widget: questions
│   ├── CategoriesList.php    # CMS widget: categories
│   └── SearchBox.php         # CMS widget: search form
└── Adminhtml/                # Admin form buttons
```

### Admin Interface

**UI Components:**
- `faq_category_listing` — Category grid with mass actions
- `faq_category_form` — Category edit with store/group selectors
- `faq_question_listing` — Question grid with workflow filters
- `faq_question_form` — Question edit with associations
- `faq_search_log_grid` — Search analytics

**Mass Actions:**
- Delete, Change Status, Change Visibility for questions
- Delete, Change Status for categories

---

## Configuration Reference

### Path: `magendoo_faq/general`
| Field | Default | Description |
|-------|---------|-------------|
| `enabled` | 1 | Module on/off |
| `title` | "FAQ" | Page title |
| `url_prefix` | "faq" | URL segment |
| `allow_guest_questions` | 1 | Anonymous submissions |

### Path: `magendoo_faq/navigation`
| Field | Default | Description |
|-------|---------|-------------|
| `show_breadcrumbs` | 1 | Breadcrumb display |
| `show_search_box` | 1 | Search visibility |
| `questions_per_category_page` | 10 | Pagination limit |
| `answer_length_limit` | 200 | Short answer truncation |
| `short_answer_behavior` | expand | How to show full answer |

### Path: `magendoo_faq/product_page`
| Field | Default | Description |
|-------|---------|-------------|
| `enabled` | 1 | Tab visibility |
| `tab_name` | "FAQ ({count})" | Tab label with count placeholder |
| `tab_position` | 100 | Sort order |
| `questions_limit` | 10 | Max questions shown |

### Path: `magendoo_faq/rating`
| Field | Default | Description |
|-------|---------|-------------|
| `enabled` | 1 | Ratings active |
| `type` | yes_no | yes_no / voting / average_rating |
| `allow_guest_rating` | 1 | Guest voting allowed |

### Path: `magendoo_faq/seo`
| Field | Default | Description |
|-------|---------|-------------|
| `url_suffix_enabled` | 0 | Append .html |
| `structured_data_enabled` | 1 | JSON-LD output |
| `add_to_sitemap` | 1 | Include in sitemap.xml |

### Path: `magendoo_faq/gdpr`
| Field | Default | Description |
|-------|---------|-------------|
| `enabled` | 0 | Consent checkbox |
| `consent_text` | ... | Custom consent message |

---

## Installation & Deployment

### Composer Installation
```bash
composer require magendoo/module-faq
bin/magento module:enable Magendoo_Faq
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento magendoo:faq:reindex
```

### Manual Installation
```bash
cp -r Magendoo/Faq app/code/
bin/magento module:enable Magendoo_Faq
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:clean
bin/magento magendoo:faq:reindex
```

### Uninstallation

To completely remove the module and its data:

```bash
# Disable the module
bin/magento module:disable Magendoo_Faq

# Remove database tables (optional - will delete all FAQ data)
bin/magento setup:db-schema:upgrade

# Clean up generated files
bin/magento setup:di:compile
bin/magento cache:flush

# For Composer installations
composer remove magendoo/module-faq
```

**⚠️ Warning:** Uninstalling will permanently delete all FAQ categories, questions, tags, ratings, and search logs. Backup your database before proceeding.

### Post-Installation Setup

1. **Configure Module:**
   - Navigate to: *Stores → Configuration → Magendoo Extensions → FAQ*
   - Set URL prefix, enable features per store view

2. **Set Permissions:**
   - Navigate to: *System → Permissions → User Roles*
   - Assign FAQ permissions to appropriate admin roles

3. **Configure reCAPTCHA:**
   - Navigate to: *Stores → Configuration → Security → Google reCAPTCHA Storefront*
   - Enable for "FAQ Question Submit" form

4. **Configure Email Templates:**
   - Navigate to: *Marketing → Email Templates*
   - Customize: `Admin New Question`, `Customer Answer Notification`

5. **Create Initial Content:**
   - Navigate to: *Magendoo → FAQ → Categories* → Create categories
   - Navigate to: *Magendoo → FAQ → Questions* → Create questions

---

## FAQ

**Q: Can customers submit questions without creating an account?**

Yes, guest submissions can be enabled in configuration (`magendoo_faq/general/allow_guest_questions`). reCAPTCHA validation is recommended for anonymous submissions.

**Q: Does this module work with Magento's Page Builder?**

Yes, the module is compatible with Page Builder. You can link to FAQ pages or embed FAQ content using widgets (custom development may be required for specific Page Builder integrations).

**Q: Can I migrate FAQs from another platform?**

Yes! Use the built-in CLI import command with a CSV file:
```bash
bin/magento magendoo:faq:import -e questions -f path/to/questions.csv
bin/magento magendoo:faq:import -e categories -f path/to/categories.csv
```
The CSV header row must match the database column names. You can also use the REST API for programmatic imports.

**Q: Is the FAQ content indexed by search engines?**

Yes, the module includes:
- SEO-friendly URLs
- XML sitemap integration
- JSON-LD structured data (Schema.org FAQPage)
- Customizable meta titles and descriptions

**Q: Can I restrict FAQs to specific customer groups?**

Yes, both categories and questions support customer group restrictions. This is ideal for B2B scenarios where you want different content for wholesale vs. retail customers.

**Q: Does the module support multiple languages?**

Yes, full multi-store support allows you to create store-view-specific FAQs. Translation CSV files are included in `i18n/`.

**Q: What rating systems are available?**

Three rating modes:
- **Yes/No** — "Was this answer helpful?"
- **Voting** — Thumbs up/down with vote counts
- **Average Rating** — 5-star rating display

**Q: How do I prevent spam submissions?**

Enable reCAPTCHA in *Stores → Configuration → Security → Google reCAPTCHA Storefront* and select the "FAQ Question Submit" form.

**Q: Can questions be associated with multiple categories?**

Yes, questions support many-to-many relationships with categories for flexible organization.

**Q: Is there a limit to the number of FAQs?**

No hard limit. Performance depends on your server resources and database optimization.

## Use Cases

### Fashion E-Commerce
- Size guide FAQs by product category
- Care instructions linked to specific materials
- "Ask a Question" on product pages for fit inquiries
- Customer-submitted Q&A builds confidence in sizing

### Electronics Retailer
- Technical specifications explained per product line
- Troubleshooting guides organized by symptom
- Warranty and return policy FAQs
- Product comparison questions and answers

### B2B Wholesale
- Customer group restrictions show different FAQs for retail vs. wholesale
- Bulk ordering process documentation
- Payment term explanations per customer segment
- Account application Q&A

### Multi-Regional Stores
- Store-specific FAQs for shipping policies by country
- Localized return procedures
- Currency and payment method guides
- Language-specific content management

---

## Support & Extension

### Extension Points

Create custom plugins in `app/code/Vendor/Module/etc/di.xml`:

```xml
<!-- Extend question submission logic -->
<type name="Magendoo\Faq\Api\QuestionManagementInterface">
    <plugin name="vendor_custom_question_logic" 
            type="Vendor\Module\Plugin\QuestionManagementPlugin"/>
</type>

<!-- Custom rating calculation -->
<type name="Magendoo\Faq\Model\Question">
    <plugin name="vendor_custom_rating" 
            type="Vendor\Module\Plugin\QuestionPlugin"/>
</type>
```

---

## Troubleshooting

### Common Issues

#### FAQ URLs Return 404

**Problem:** FAQ pages show "404 Not Found" error.

**Solutions:**
1. Regenerate URL rewrites:
   ```bash
   bin/magento magendoo:faq:reindex
   ```
2. Check URL prefix configuration doesn't conflict with existing CMS pages
3. Verify web server rewrite rules are enabled:
   - Apache: `.htaccess` in root directory
   - Nginx: `nginx.conf` sample configuration
4. Flush Magento cache:
   ```bash
   bin/magento cache:flush
   ```

#### Admin Grid Not Loading

**Problem:** FAQ grid shows infinite loading spinner.

**Solutions:**
1. Check browser console for JavaScript errors
2. Verify file permissions on `var/` and `pub/` directories
3. Check `var/log/system.log` for PHP errors
4. Recompile static assets:
   ```bash
   bin/magento setup:static-content:deploy
   ```

#### Questions Not Visible on Frontend

**Problem:** Questions exist in admin but don't appear on storefront.

**Solutions:**
1. Verify question status is set to **Answered**
2. Check visibility is set to **Public** (or **Logged In** if testing as customer)
3. Confirm store assignment in the question edit page
4. Check customer group restrictions don't exclude current user
5. Verify category association if viewing category-specific list
6. Clear cache and reindex:
   ```bash
   bin/magento cache:flush
   bin/magento indexer:reindex
   ```

#### Email Notifications Not Sending

**Problem:** Admin or customer notifications not received.

**Solutions:**
1. Check Magento email configuration: *Stores → Configuration → Sales → Sales Emails*
2. Verify cron is running: `bin/magento cron:run`
3. Check email templates are configured correctly
4. Review `var/log/exception.log` for email errors
5. Test with a simple PHP mail script to verify server email capability

#### reCAPTCHA Not Working

**Problem:** "Ask Question" form submits without reCAPTCHA validation.

**Solutions:**
1. Verify reCAPTCHA keys are configured: *Stores → Configuration → Security → Google reCAPTCHA Storefront*
2. Ensure form key `magendoo_faq_question_submit` is enabled
3. Check JavaScript console for reCAPTCHA loading errors
4. Verify domain is registered in Google reCAPTCHA admin console

#### Product Tab Not Showing

**Problem:** FAQ tab missing on product detail pages.

**Solutions:**
1. Verify module is enabled in product page config: *Stores → Configuration → Magendoo Extensions → FAQ → Product Page → Enabled*
2. Check if questions are associated with the product
3. Ensure layout XML is loaded (check for theme overrides)
4. Verify tab position doesn't conflict with other tabs

### Getting Help

1. **Check Logs:** Review `var/log/system.log` and `var/log/exception.log`
2. **Enable Developer Mode:** Temporarily switch to developer mode for detailed errors:
   ```bash
   bin/magento deploy:mode:set developer
   ```
3. **Contact Support:** Create an issue on GitHub for assistance

## Changelog

### [1.2.0] - 2026-04-13

**Phase 3+4 Features**

- FAQ CMS Widgets — Questions List (list/accordion), Categories List, Search Box
- Tag system frontend — tag cloud, tag pages with linked questions, Router support
- Search autocomplete — AJAX suggest endpoint + JS dropdown on FAQ home
- CSV Import/Export CLI — `magendoo:faq:export` and `magendoo:faq:import`
- Product tab deep-link fix — `#product_faq` hash now reliably opens the tab
- Complete REST API — 24 endpoints with 100% test coverage
- Tag CRUD API + URL-key lookups + view count + notification endpoints

### [1.1.0] - 2026-04-12

**Phase 2 + SEO Sprint**

- Ask a Question form on product pages (guest + logged-in)
- reCAPTCHA integration via Magento's native framework
- GDPR consent checkbox with configurable text
- Email notifications (admin new question + customer answer)
- Rating system — 3 modes (Yes/No, Voting, Average Stars)
- Social share buttons (Facebook, Twitter, LinkedIn, Pinterest, Email)
- Structured data JSON-LD (FAQPage schema) on question + category pages
- Robots meta per entity (noindex/nofollow from DB fields)
- XML Sitemap integration via ItemProviderInterface
- Search terms logging + admin report
- Hreflang tags for multi-store SEO
- Customer group visibility filtering on all frontend surfaces
- Product/category assignment in admin question form

### [1.0.0] - 2026-04-10

**Initial Release (Phase 1 MVP)**

- 12 database tables with declarative schema
- FAQ category + question + tag management (admin CRUD)
- Product page FAQ tab integration
- Custom URL router with SEO-friendly paths
- Full-text search with pagination
- REST API (category + question CRUD)
- ACL permissions, admin menu, system configuration
- URL rewrite generation + CLI reindex command
- Multi-store support via junction tables

## License

This module is licensed under the **Open Software License v. 3.0 (OSL-3.0)**.

See https://opensource.org/licenses/OSL-3.0 for the full license text.

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please ensure your code:
- Follows PSR-12 coding standards
- Includes proper PHPDoc comments
- Uses strict typing (`declare(strict_types=1)`)
- Includes unit tests where applicable

## Support & Resources

- **Issue Tracker:** https://github.com/magendooro/magento2-catalog-faq-geo/issues

---

## Summary

**Magendoo_Faq** delivers enterprise-grade FAQ management that scales from single-store boutiques to multi-store, multi-region B2B/B2C operations. With full API coverage for headless implementations, comprehensive SEO features for organic traffic growth, and deep product page integration for conversion optimization, it transforms static FAQ pages into dynamic, customer-driven knowledge platforms.

**Key Differentiators:**
- ✅ Full REST API for PWA/headless implementations
- ✅ Product-specific Q&A to reduce purchase hesitation  
- ✅ Advanced SEO with structured data and sitemap integration
- ✅ Customer engagement through ratings and social sharing
- ✅ Enterprise access control (multi-store, customer groups)
- ✅ GDPR-compliant with native reCAPTCHA integration
- ✅ Analytics-driven content insights
