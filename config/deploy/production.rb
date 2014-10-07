############################################
# Setup Server
############################################

set :stage, :production
set :stage_url, 'http://www.domain.com'
set :theme, 'v1'
server 'domain.com', user: 'user', roles: %w{web app db}
set :deploy_to, '/home/user/domains/domain.com/public_html'
set :tmp_dir, '/home/user/tmp'


############################################
# Setup Git
############################################

set :branch, 'master'
