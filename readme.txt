=== Version Number Shortcode ===
Contributors:      nahnuplugins
Tags:              shortcode, version, changelog, release
Requires at least: 5.9
Tested up to:      6.7
Requires PHP:      7.4
Stable tag:        1.0.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Display a live version number anywhere on your site by fetching it from a remote JSON file — no hardcoding required.

== Description ==

**Version Number Shortcode** lets you display a software version number anywhere on your WordPress site using the `[version_number]` shortcode. The version is fetched from a remote JSON endpoint you control (for example, a `release.json` file in a GitHub repository), cached locally for performance, and automatically refreshed on a schedule you choose.

**Features**

* Simple `[version_number]` shortcode — works in posts, pages, widgets, and page builders.
* Point to any JSON file that contains a `"version"` key.
* Configurable check frequency: every 1, 6, 12, or 24 hours.
* Manual "Check Now" button to force an immediate refresh from the admin.
* "Save & Check Now" — save settings and refresh in one click.
* Status panel showing the cached version, last check time, and next scheduled check.
* Falls back to the last known version on network errors — no blank output.
* Clean uninstall — all options and transients removed on plugin deletion.
* Translation-ready with full i18n support.

**Expected JSON format**

Your remote file must contain a `version` key at the root level:

`{ "version": "2.1.0" }`

**External service disclosure**

This plugin makes HTTP requests to a URL you provide in the plugin settings. No data is sent to that URL — it is a read-only fetch. You are responsible for ensuring the URL you configure complies with applicable privacy regulations. No third-party service is contacted by default; the plugin only calls the URL you explicitly set.

== Installation ==

1. Upload the `version-number-shortcode` folder to the `/wp-content/plugins/` directory, or install directly through the WordPress plugin screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Settings > Version Shortcode**.
4. Enter the URL of your remote `release.json` file.
5. Choose how frequently the version should be refreshed.
6. Click **Save & Check Now** to fetch the version immediately.
7. Place `[version_number]` anywhere on your site.

== Frequently Asked Questions ==

= What JSON format does the remote file need to use? =

The file must be valid JSON with a `version` key at the root level. Example:

`{ "version": "1.4.2" }`

Additional keys in the file are ignored.

= Can I use a GitHub raw file URL? =

Yes. GitHub raw URLs (`https://raw.githubusercontent.com/…`) work perfectly. Make sure the file is publicly accessible (no authentication required).

= What happens if the remote URL is unreachable? =

The plugin returns the last successfully fetched version. Output will never be blank as long as at least one successful fetch has occurred.

= Where does the shortcode work? =

Anywhere WordPress processes shortcodes: posts, pages, text widgets, the Classic Editor, Gutenberg (via the Shortcode block), and most page builders including Elementor, Bricks, and Divi.

= How do I force an immediate refresh? =

Go to **Settings > Version Shortcode** and click **Check Now** or **Save & Check Now**.

= Will my data be deleted if I uninstall the plugin? =

Yes. Deleting the plugin removes all options and cached data created by Version Number Shortcode.

== Screenshots ==

1. The Settings page showing the URL field, check frequency selector, action buttons, and status panel.

== Changelog ==

= 1.0.0 =
* First public release.
* `[version_number]` shortcode fetching a version string from a remote JSON URL.
* Admin settings page under Settings > Version Shortcode.
* Configurable check frequency: every 1, 6, 12, or 24 hours.
* Check Now and Save & Check Now buttons for immediate manual refresh.
* Status panel showing cached version, last check time, and next scheduled check.
* Falls back to last known version on network errors.
* Clean uninstall — all options and transients removed on deletion.
* Translation-ready with full i18n support.

== Upgrade Notice ==

= 1.0.0 =
First public release.
