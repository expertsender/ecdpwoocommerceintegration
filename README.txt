=== ExpertSender CDP ===
Requires at least: 5.6
Tested up to: 6.7
Stable tag: 1.0.6
Requires PHP: 7.4
WC requires at least: 5.0
WC tested up to: 9.4
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

== Changelog ==

= 1.0.6 =
* Developers can now change where the consent checkbox appears on the checkout and registration pages, via the `expertsender_cdp_checkout_consent_hook` and `expertsender_cdp_register_consent_hook` filters.

= 1.0.5 =
* Fixed a fatal error on the consent forms settings page.
* Orders sent to ECDP now include the store's correct timezone instead of a hardcoded UTC value.
* The order synchronization page now shows the date of the last sync run.
* Fixed a duplicate view_item event firing for variable products with a default variation preselected.
* Fixed a fatal error that prevented newsletter consent submissions from being saved.
