=== Recherche SIRET pour WS Form ===
Contributors: willybahuaud
Tags: ws form, siret, siren, company, autocomplete
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WS Form field that finds a French company by name or SIRET, checks it and fills the form. Public government data, no API key.

== Description ==

B2B forms almost always ask for a SIRET number. Visitors look it up, mistype a digit, then type the company name and address by hand.

The French government publishes its company directory through the Recherche d'entreprises API: no account, no key, open access. This plugin uses it. The visitor types the company name, picks it from the list, and the form fills itself. It works with WS Form LITE and PRO.

= Search =

* Type a name, a SIREN or a SIRET, and pick from a list: name, postal code, city, SIRET, head office or not, closed or not
* Fully usable from the keyboard, announced to screen readers
* A complete and valid SIRET is found and chosen directly
* By default the list offers the head office. A setting offers the matching establishments instead

= Checks =

* A malformed SIREN or SIRET is refused before any API call, by its Luhn check digit. La Poste's own rule is handled
* The server checks again on submit
* Optional: refuse a closed establishment or a ceased company
* Optional: refuse a SIRET missing from the directory. Off by default, see the known limits

= Filling =

* Map form fields to company data: name, legal name, trade name, SIRET, SIREN, full address, street, postal code, city, NAF code, VAT number, administrative status
* A summary under the field shows the name, address and status of the chosen company
* If the visitor changes the SIRET afterwards, the filled fields are emptied

= Also =

* The stored value is the 14-digit SIRET. It reads as is in the submissions list, CSV export, emails and `#field(ID)`
* Messages can be changed field by field, in Advanced > Labels
* If the API is down or rate limited, a message appears and the form can still be sent
* Nothing loads on a page without this field

= Known limits =

* Companies can refuse publication. They are not in the API, even with a valid SIRET. The field accepts them by default.
* If the API does not answer on submit, the SIRET is accepted after the check digit only. The `wsform_siret_lookup_unavailable` action reports it, and a line is logged when `WP_DEBUG_LOG` is on.
* The API does not return a VAT number for every company. The mapped field then stays empty.
* Typing a SIREN offers the head office. For another establishment, search by name and city, or type its SIRET.
* LITE has no repeatable sections, so using this field in one requires PRO.

== Installation ==

1. Download `wsform-siret-X.Y.Z.zip` from the latest GitHub release: https://github.com/willybahuaud/wsform-siret/releases/latest
2. Install it like any plugin, alongside WS Form.
3. The "Company (SIRET)" field appears in the "French public services" group of the form builder.

== Privacy ==

This plugin adds no tracking and no telemetry. However, it calls the Recherche d'entreprises API (`recherche-entreprises.api.gouv.fr`), a French government service:

* From the visitor's browser, while typing: the API receives the visitor's IP address and what is typed in the field.
* From your server, on submit, only when a status or existence check is on: the API receives the SIRET and the server IP. The answer is cached for one hour.

Mention this service in your privacy policy.

== Data source and license ==

Company data comes from the Sirene database of Insee, published under the French Licence Ouverte / Open Licence 2.0, which asks to credit the source. The field shows a source mention by default.

This plugin is not affiliated with or endorsed by Insee, DINUM or WS Form.

== Updates ==

Updates come from the GitHub releases of the plugin, not from WordPress.org.

== Changelog ==

= 0.1.0 =
* First version: company search by name, SIREN or SIRET, check digit, server checks, field mapping.
