=== Dummy Site Inflator ===
Contributors: lumiblog
Tags: dummy content, test data, site size, QA, load testing
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate dummy posts with large images to inflate your test site for QA, load testing, and hosting benchmarks.

== Description ==

**Dummy Site Inflator** is a developer and QA tool that lets you quickly inflate a test WordPress site to a desired size by generating dummy posts, each containing a large embedded image.

This is useful for:

* Testing how your hosting plan handles large site sizes
* Benchmarking backup and restore times
* Load testing your server with realistic content
* Simulating a production-sized site on a staging environment

**How it works:**

1. You choose how many posts to generate (e.g. 200 posts = ~9 GB, 400 posts = ~18 GB)
2. The plugin downloads a source image once from a remote server (one-time download)
3. For each post, a unique copy of the image is created on your server and registered as a proper WordPress media attachment
4. Each post includes ~300 words of varied Lorem Ipsum content with the image embedded in the middle
5. All posts are tagged internally so they can be found and deleted cleanly at any time

**Key Features:**

* Batch processing with a live progress bar — no timeouts on shared hosting
* Size estimator shows you the projected disk usage before you generate
* One-click cleanup deletes all dummy posts and their images
* Fully translation ready

> ⚠️ **This plugin is intended for use on test/staging sites only.** Do not use it on production websites.

== Installation ==

1. Upload the `dummy-site-inflator` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the **Plugins** menu in WordPress
3. Navigate to **Tools → Dummy Site Inflator**
4. Enter the number of posts you want to generate and click **Generate Posts**

== Frequently Asked Questions ==

= Is this safe to use on a live site? =

No. This plugin is designed exclusively for test and staging environments. It will fill your uploads directory with large image files and create hundreds of published posts.

= How large is each post's image? =

Approximately 46 MB per image. So 100 posts ≈ 4.6 GB, 200 posts ≈ 9.2 GB, etc.

= Where is the source image downloaded from? =

The image is downloaded once from a remote server and cached locally in your uploads folder under `dummy-site-inflator/`. Subsequent post generations reuse this cached copy.

= Can I delete all the dummy posts later? =

Yes. The plugin includes a **Delete All Dummy Posts** button that permanently removes all generated posts and their associated image files from your server.

= Does the plugin slow down during large batch generation? =

No. Posts are generated in small batches via AJAX (5 per request) with a live progress bar, so PHP execution time limits are not an issue.

= Will the plugin be removed cleanly if I delete it? =

Yes. The cached source image directory is removed on plugin deletion. Note that dummy posts themselves are not auto-deleted — use the Cleanup feature inside the plugin before deleting it.

== Screenshots ==

1. The main plugin interface showing the generate form, size estimator, and progress bar.
2. The status card showing current dummy post count and disk usage.
3. The cleanup section for deleting all dummy posts.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
