# Website Skeleton

Work in progress! But already here for your inspiration.
Framework for building and deploying Wordpress websites

## Assumptions

* Custom content directory in `/content/` (cleaner, and also because it can't be in `/wp/`)
* `wp-config.php` in the root (because it can't be in `/wp/`)

## Capistrano commands

Install capistrano and dependencies via npm install

cap staging deploy

cap staging wp:setup:generate_remote_files

cap staging db:backup (creates backup form environment)
cap staging db:pull
cap staging db:push (use with caution!)

cap staging media:regenerate (sync media)
cap staging media:sync (sync media)


## Interesting resources
- https://github.com/pixline/wp-cli-php-devtools
