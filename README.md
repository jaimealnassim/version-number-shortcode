# Version Number Shortcode

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![License](https://img.shields.io/badge/license-GPLv2-green)
![WordPress](https://img.shields.io/badge/WordPress-5.9%2B-informational)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-informational)

A lightweight WordPress plugin that fetches a version number from a remote JSON endpoint and outputs it anywhere on your site via the `[version_number]` shortcode — no hardcoding required.

---

## Features

- `[version_number]` shortcode works in posts, pages, widgets, and page builders
- Point to any publicly accessible JSON file with a `"version"` key
- Configurable check frequency: every 1, 6, 12, or 24 hours
- Manual **Check Now** and **Save & Check Now** buttons in the admin
- Status panel showing cached version, last check time, and next scheduled check
- Falls back to the last known version on network errors — never blank
- Clean uninstall — all options and transients removed on deletion
- Translation-ready (text domain: `version-number-shortcode`)

---

## Installation

**From WordPress admin**

1. Download the latest `.zip` from [Releases](../../releases).
2. Go to **Plugins > Add New > Upload Plugin**.
3. Upload the zip and activate.

**Manual**

1. Clone or download this repository.
2. Upload the `version-number-shortcode` folder to `/wp-content/plugins/`.
3. Activate via **Plugins** in the WordPress admin.

---

## Configuration

1. Go to **Settings > Version Shortcode**.
2. Enter the URL of your remote JSON file (see format below).
3. Choose a check frequency.
4. Click **Save & Check Now** to fetch immediately.

---

## Usage

Place the shortcode anywhere WordPress processes shortcodes:

```
[version_number]
```

Works in the Classic Editor, Gutenberg (Shortcode block), Elementor, Bricks Builder, Divi, text widgets, and most other page builders.

---

## JSON Format

Your remote file must return valid JSON with a `version` key at the root:

```json
{
  "version": "1.2.0"
}
```

Additional keys are ignored. See [`release.json`](./release.json) in this repo for a working example.

---

## Screenshots

> **Settings page** — URL field, check frequency selector, action buttons, and status panel.

---

## Changelog

See [CHANGELOG.md](./CHANGELOG.md) for the full version history.

---

## License

Licensed under the [GNU General Public License v2.0 or later](./LICENSE).

---

## Contributing

Bug reports and feature requests are welcome. Please use the [issue templates](./.github/ISSUE_TEMPLATE/) when opening an issue.

Pull requests should target the `main` branch. Keep changes focused and include a clear description of what was changed and why.

---

## Credits

Built by [Nahnu Plugins](https://nahnuplugins.com/)
