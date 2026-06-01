# Changelog

All notable changes to Version Number Shortcode are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] — 2025-06-01

First public release.

### Added
- `[version_number]` shortcode fetching a version string from a remote JSON URL.
- Admin settings page under **Settings > Version Shortcode**.
- Configurable check frequency: every 1, 6, 12, or 24 hours.
- **Check Now** and **Save & Check Now** buttons for immediate manual refresh.
- Status panel showing cached version, last check time, and next scheduled check.
- Falls back to the last known version on network errors — output is never blank after the first successful fetch.
- Clean uninstall — all options and transients removed on plugin deletion.
- Translation-ready with full i18n support (text domain: `version-number-shortcode`).
