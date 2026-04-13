# Changelog

All notable changes to this project will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/magendooro/magento2-catalog-faq-geo/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/magendooro/magento2-catalog-faq-geo/releases/tag/v1.0.0
