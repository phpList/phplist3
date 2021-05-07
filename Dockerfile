
FROM debian:buster-slim

LABEL maintainer="michiel@phplist.com" 

RUN apt -y update && apt -y upgrade

RUN apt install -y -qq postfix

RUN apt install -y php-cli mariadb-server bash sudo composer git php-curl php-mysqli php-dom make firefox-esr wget
## otherwise jdk fails, https://github.com/geerlingguy/ansible-role-java/issues/64
RUN mkdir -p /usr/share/man/man1
RUN apt install -y default-jdk

RUN wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb && apt -y install ./google-chrome-stable_current_amd64.deb

## debugging utils, that can be removed once it works
RUN apt install -y vim curl telnet psutils

RUN useradd -m -s /bin/bash -d /home/phplist phplist

COPY . /var/www/phplist3
RUN chown -R phplist: /var/www
USER phplist
WORKDIR /var/www/phplist3/
RUN rm -rf vendor


ENTRYPOINT [ "./scripts/run-tests.sh" ]

#ENTRYPOINT [ "/bin/bash" ]
