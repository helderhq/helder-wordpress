=== Helder ===
Contributors: helderhq
Tags: chat, live chat, customer service, ai, support
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add Helder chat, AI customer service from your own pages, to your WordPress site with one click.

== Description ==

Helder chat answers your visitors' questions using the pages of your own site, and hands the conversation to your team when needed. This plugin connects your site with Helder:

* **One click to connect.** Click "Connect with Helder", pick your workspace and you are done. No code to paste.
* **Works with every theme and page builder.** The chat appears on every page of your site.
* **Logged-in visitors are recognised.** Visitors who are logged in on your site do not have to verify themselves again in the chat.
* **WooCommerce orders.** With WooCommerce, give Helder read-only access with WooCommerce's own approval screen, so the chat can tell customers where their order is.
* **Everything else in Helder.** Opening hours, appearance, language and on which pages the chat appears are managed in Helder.

You need a Helder account. You can create one while connecting.

== External services ==

This plugin connects to Helder, a service by Helder (a trade name of Inspiration Concepts B.V., the Netherlands), to show the chat on your site.

* **When you click "Connect with Helder"**, your browser goes to app.helderhq.com with your site address. After you pick a workspace, the plugin sends the one-time code and your site address to app.helderhq.com and receives the keys for your site.
* **Once a day and when you open the plugin settings**, the plugin asks app.helderhq.com whether the connection still exists, using the site's public key and connection id.
* **When you click "Disconnect" or delete the plugin**, the plugin tells app.helderhq.com and deletes the WooCommerce keys it made for Helder.
* **With WooCommerce, when you click "Give Helder access to orders"**, the plugin asks app.helderhq.com for a short-lived code and sends you to WooCommerce's own approval screen. After you approve, WooCommerce sends read-only API keys from your server to app.helderhq.com. Helder then reads orders, customers and products when a customer asks about them in the chat. "Withdraw access" tells app.helderhq.com and deletes the keys.
* **On every page of your site**, visitors load the Helder chat script from widget.helderhq.com. When a visitor opens the chat, the chat talks to Helder. When passing on logged-in visitors is turned on, a logged-in visitor's email address is sent to Helder in a signed token.

Nothing is sent to Helder before you click "Connect with Helder".

* Terms: https://helderhq.com/voorwaarden
* Privacy: https://helderhq.com/privacy

== Installation ==

1. Install and activate the plugin.
2. Go to Settings > Helder and click "Connect with Helder".
3. Log in to Helder (or create an account) and pick your workspace.

Your site must use https.

== Frequently Asked Questions ==

= Does the chat slow down my site? =

The plugin adds one small script that loads asynchronously. The chat itself only loads when Helder chat is on for your workspace.

= Does it work with page caching? =

Yes. Who is logged in is never written into the page; the chat asks your site for it separately.

= Where do I change the opening hours or the colours? =

In Helder, under Chat. The plugin only connects your site.

== Changelog ==

= 0.2.0 =
* WooCommerce: give Helder read-only access to orders with WooCommerce's approval screen, so the chat can answer questions about orders.

= 0.1.0 =
* First version: connect with one click, chat on every page, logged-in visitors are recognised.
