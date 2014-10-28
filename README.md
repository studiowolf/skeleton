# Website Skeleton

Work in progress! But already here for your inspiration.
Framework for building and deploying Wordpress websites

## Start

1. Put your WP core in `/wp/`
2. Run 'npm install gulp' to install gulp in your project.
3. Run 'npm install' to install the dependencies
4. Your assets are in `/content/themes/v1/assets/` <- they'll be compiled to 'compiled-assets' by gulp.
5. Setup your database and put your db info in `/content/database.yml`
6. Run cap staging wp:setup:local to setup your `wp-config` locally.
7. Run 'gulp' and enjoy!

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


## Commandline interface for Wordpress
A useful resource to use when you're developing WP sites is the WP commandline.
You can find the install guide on http://wp-cli.org/
The wp-cli.phar is already in this project so if you don't want to install it globally you can use this file to use the commandline.

Run 'php wp-cli.phar --help' for the different usage options.
