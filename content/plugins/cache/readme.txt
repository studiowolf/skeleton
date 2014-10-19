=== Cache ===
Requires at least: 2.5
Tested up to: 4.0

Cache is a very basic, but fast cache system for WordPress.
This plugin is part of the Studio Wolf admin-less plugins, settings only via
hooks and constants.

=== Things to consider ===

* Cache never invalidates
* Cached pages are also served to robots
* Feeds are cached as well
* how to handle old posts, do we want to keep them cached at all times?
* Now an updated post invalidates the whole cache, I can image that in near
  future only the post itself and the related pages will be invalidated

=== Future ===

* Add option to exclude certain user agents from cache
* Add option to create separate cache for mobile
* Add options to exclude pages from cache
