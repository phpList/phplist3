
## Vagrant allows running the Behat tests locally, with different versions of PHP
## to use, run "vagrant init" once and "vagrant plugin install vagrant-docker-compose"
## and after that "vagrant up" to run the tests
## to start from scratch run "vagrant destroy" and then "vagrant up"
## to change the version of PHP being used change the line 
#      PHPVERSION=7.0
## below

Vagrant.configure("2") do |config|
  config.vagrant.plugins = [
    "vagrant-docker-compose",
  ]
  config.vm.box = "ubuntu/bionic64"
  config.vm.provision :docker
  config.vm.provision :docker_compose,
    compose_version: "1.22.0"

  config.vm.provider "virtualbox" do |v|
      v.memory = 10640
      v.cpus = 2
  end

  config.vm.provision :shell,
    keep_color: true,
    privileged: true,
    run: "always",
    inline: <<-SCRIPT
      cd /vagrant
      PHPVERSION=7.4
      apt install software-properties-common
      add-apt-repository ppa:ondrej/php
      DEBIAN_FRONTEND=noninteractive apt update
      DEBIAN_FRONTEND=noninteractive apt install -y composer mariadb-client mariadb-server postfix chromium-chromedriver firefox openjdk-8-jre-headless fonts-liberation
      DEBIAN_FRONTEND=noninteractive apt install -y php7.0 php7.0-mbstring php7.0-curl php7.0-mysql php7.0-xml php7.0-zip 
      DEBIAN_FRONTEND=noninteractive apt install -y php7.1 php7.1-mbstring php7.1-curl php7.1-mysql php7.1-xml php7.1-zip 
      DEBIAN_FRONTEND=noninteractive apt install -y php7.2 php7.2-mbstring php7.2-curl php7.2-mysql php7.2-xml php7.2-zip 
      DEBIAN_FRONTEND=noninteractive apt install -y php7.3 php7.3-mbstring php7.3-curl php7.3-mysql php7.3-xml php7.3-zip 
      DEBIAN_FRONTEND=noninteractive apt install -y php7.4 php7.4-mbstring php7.4-curl php7.4-mysql php7.4-xml php7.4-zip 
      DEBIAN_FRONTEND=noninteractive apt install -y php8.0 php8.0-mbstring php8.0-curl php8.0-mysql php8.0-xml php8.0-zip 
      [[ ! -f /usr/bin/geckodriver ]] && {
        wget https://github.com/mozilla/geckodriver/releases/download/v0.29.0/geckodriver-v0.29.0-linux64.tar.gz
        tar zxf geckodriver-v0.29.0-linux64.tar.gz
        mv geckodriver /usr/bin/
      }
      update-alternatives --set php /usr/bin/php$PHPVERSION
      update-alternatives --set phar /usr/bin/phar$PHPVERSION
      update-alternatives --set phar.phar /usr/bin/phar.phar$PHPVERSION
      update-alternatives --set phpize /usr/bin/phpize$PHPVERSION
      update-alternatives --set php-config /usr/bin/php-config$PHPVERSION
      [[ ! -z $(which google-chrome) ]] || {
        wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
        dpkg -i google-chrome-stable_current_amd64.deb
      }
      google-chrome --version
      rm -rf vendor
      composer install
      service postfix stop
      service apache2 stop
      service mysqld start
      cp -fv tests/ci/config.php public_html/lists/config/config.php
      cp -fv tests/default.behat.yml tests/behat.yml
      [[ ! -d public_html/lists/admin/ui/phplist-ui-bootlist ]] && { 
        cd public_html/lists/admin/ui/ 
        wget https://github.com/phpList/phplist-ui-bootlist/archive/master.tar.gz
        tar -xzf master.tar.gz 
        mv phplist-ui-bootlist-master phplist-ui-bootlist
        rm master.tar.gz 
        cd /vagrant
      }
      (echo >/dev/tcp/localhost/80) &>/dev/null || {
        php -S 0.0.0.0:80 -t public_html > phpserver.log 2>&1 &
      }
      (echo >/dev/tcp/localhost/4444) &>/dev/null || {
        vendor/bin/selenium-server-standalone -p 4444 -Dwebdriver.chrome.driver="/usr/bin/chromedriver" -Dwebdriver.gecko.driver="/usr/bin/geckodriver" &
      }
      mkdir -p tests/build/mails
      cd tests/build/mails
      (echo >/dev/tcp/localhost/2500) &>/dev/null || {
        smtp-sink -u vagrant -d "%d.%H.%M.%S" localhost:2500 1000 &
      }
      cd ../../
      make test
    SCRIPT
end
