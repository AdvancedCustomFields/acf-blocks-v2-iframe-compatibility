# ACF Blocks V2 Iframe Compatibility

A WordPress plugin that turns off the block editor's iframed canvas on sites that still have ACF block version 2 blocks registered, so those blocks continue to work while their owners migrate to ACF Blocks v3.

## What this does

WordPress 7.1 moved the block editor canvas into an always-on iframe. ACF block version 2 was built for the non-iframed editor, and several of its behaviors — jQuery-based field widgets, the "Switch to Edit" toggle, media and TinyMCE integration — do not survive the move into the iframe.

When any registered ACF block is still on block version 2 or below, this plugin injects a small script into the editor that patches React's `createElement` / JSX runtime to flip `shouldIframe: true` to `false`. The result is that the editor renders without the iframe canvas, restoring the environment v2 blocks expect.

The shim only loads when a v2 block is actually registered on the site. Sites whose ACF blocks have all moved to version 3 get no script and no behavior change.

## Who needs it

Install this plugin if all of the following are true:

- You run WordPress 7.1 or newer.
- You use ACF PRO.
- You have ACF blocks on your site that you want to keep on version 2, and you want to edit them in the new iframed post editor.

If every ACF block on your site is explicitly registered as v3 — `"acf": { "blockVersion": 3 }` in `block.json`, or `'acf_block_version' => 3` for PHP-registered blocks — this plugin does nothing. Blocks registered without an explicit version are pinned to v2 while this plugin is active, via the `acf/blocks/default_block_version` filter, so nothing you registered under the old default silently changes type.

## We recommend upgrading to Blocks V3

This plugin is a temporary compatibility bridge and is not intended to be used permanently. The block editor is iframed for good reasons (style isolation, cleaner scoping, better parity with the site frontend) and running the editor without the iframe long-term will keep you off the path core is investing in.

ACF Blocks v3 was designed for the iframed editor from the start and delivers a substantially better editing experience. If you own the block definitions, migrate them to block version 3 and remove this plugin once the migration is complete.

See the [ACF Blocks v3 documentation](https://www.advancedcustomfields.com/resources/acf-blocks-v3/) for the migration guide.

## How to install

**From a ZIP file:**

1. Download the plugin ZIP.
2. In the WordPress admin, go to **Plugins → Add New → Upload Plugin**.
3. Choose the ZIP file and click **Install Now**.
4. Click **Activate**.

**Manually:**

1. Copy the `acf-blocks-v2-iframe-compatibility` folder into `wp-content/plugins/`.
2. In the WordPress admin, go to **Plugins**, find **ACF Blocks V2 Iframe Compatibility**, and click **Activate**.

ACF must be installed and active. No configuration is required; the plugin activates itself automatically on the block editor screen whenever a v2 ACF block is detected.
