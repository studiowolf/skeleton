############################################
# Setup project
############################################

set :application, ''
set :repo_url, ''
set :scm, :git


############################################
# Setup WordPress
############################################

set :wp_localurl, 'http://127.0.0.1:8000'


############################################
# Setup Capistrano
############################################

set :log_level, :info
set :use_sudo, false

set :ssh_options, {
  forward_agent: true
}

set :keep_releases, 3


############################################
# Linked files and directories (symlinks)
############################################

set :linked_files, %w{wp-config.php .htaccess content/advanced-cache.php}
set :linked_dirs, %w{content/uploads content/cache files}

namespace :deploy do

  # Todo:
  # https://github.com/sindresorhus/gulp-rev/blob/master/integration.md // Hash van files maken
  # Server omgeving opzetten via Directadmin CLI
  # Aatomatisch crontab maken
  # Work on theme, zodat alle symlinks worden gewijzigd naar de juiste positie (http://stackoverflow.com/questions/10216841/passing-parameters-to-capistrano)

  desc "create WordPress files for symlinking"
  task :create_wp_files do
    on roles(:app) do
      execute :touch, "#{shared_path}/wp-config.php"
      execute :touch, "#{shared_path}/.htaccess"
      if fetch(:stage) != :production then
      execute :touch, "#{shared_path}/.htpasswd"
      end
      execute :touch, "#{shared_path}/content/advanced-cache.php"
    end
  end

  after 'check:make_linked_dirs', :create_wp_files


  desc "Creates robots.txt for non-production envs"
  task :create_robots do
    on roles(:app) do
      if fetch(:stage) != :production then

        io = StringIO.new('User-agent: * Disallow: /')
        upload! io, File.join(release_path, "robots.txt")
        execute :chmod, "644 #{release_path}/robots.txt"
      end
    end
  end

  desc "Cleans the cache"
  task :clean_cache do
    on roles(:app) do
      execute :rm, "-rf #{shared_path}/content/cache/*"
    end
  end

  desc "Imports the build assets into the server environment"
  task :push_assets do

    on roles(:web) do

      now = Time.now
      backup_time = [now.year,now.month,now.day,now.hour,now.min,now.sec].join()
      asset_filename = "assets_#{fetch(:theme)}_#{backup_time}.tar.gz"

      puts "Compressing and uploading assets to the server"
      # Create asset tar and build the file
      run_locally do
        execute :gulp, "build --theme #{fetch(:theme)}"
        execute :mkdir, "-p build"
        execute :tar, "-zcvf build/#{asset_filename} --directory=content/themes/#{fetch(:theme)} compiled-assets"
      end

      upload! "build/#{asset_filename}", "#{shared_path}/#{asset_filename}"

      # Extract the assets on the server
      within release_path do
        execute :tar, "-zxvf #{shared_path}/#{asset_filename} -C #{release_path}/content/themes/#{fetch(:theme)}"
        execute :rm, "#{shared_path}/#{asset_filename}"
      end

      # Remove the tar from the build directory
      run_locally do
        execute :rm, "build/#{asset_filename}"
        if Dir['build/*'].empty?
          execute :rmdir, "build"
        end
      end

      puts "Finished and cleaned up assets"

    end
  end


  desc "Check if changes are pushed and the correct repo is set"
  task :git_check_changes do

    branch = %x(git branch --no-color 2>/dev/null | sed -e '/^[^*]/d' -e 's/* \\(.*\\)/\\1/').chomp
    if branch != fetch(:branch)
      puts "You are not on the #{fetch(:branch)} branch for #{fetch(:stage)} deployment."
      abort
    end

    status = %x(git status --porcelain).chomp
    if status != "" && status !~ %r{^[M ][M ] config/deploy.rb$}
      puts "Your local git repository has uncommitted changes. Commit before deploying."
      abort
    end
  end


  desc "Check if git repositories are aligned"
  task :git_check_repository do

    local_commit = %x(git rev-parse #{fetch(:branch)}).strip
    remote_commit = %x(git rev-parse origin/#{fetch(:branch)}).strip

    if local_commit != remote_commit

      puts "Local 'master' branch is not synchronized with 'origin' repository. Assets can be out of sync if you continue."
      ask(:continue, "Press 'y' to continue anyway")

      if fetch(:continue) != 'y'
        abort
      end

    end

  end

  after :finished, :create_robots
  after :finished, :clean_cache
  before :starting, :git_check_changes
  before :starting, :git_check_repository
  after :updating, :push_assets
  after :finishing, "deploy:cleanup"

end
