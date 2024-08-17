FROM debian:bookworm-slim

WORKDIR /home/runner/
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY . .

RUN apt update && apt install -y nano iptables iputils-ping wireguard iproute2 openresolv procps curl sudo coreutils
RUN apt install -y php-mbstring php-xml php-bcmath php php-curl php-mbstring php-iconv php-exif php-simplexml php-zip php-phar php-curl php-sqlite3
RUN apt install -y libzip-dev libsodium-dev libicu-dev nodejs npm libonig-dev libcurl4-openssl-dev && \
  rm -rf /var/lib/apt/lists/*

RUN useradd runner
RUN usermod -aG sudo runner
RUN echo "runner ALL= NOPASSWD: ALL" >> /etc/sudoers
RUN chown -R runner /home/runner

RUN cd /home/runner/torrents-api; npm install

USER runner

RUN cd /home/runner/server; composer install
RUN cd /home/runner/server; npm install && npm run build

RUN chmod a+x /home/runner/docker-entrypoint/*.sh

EXPOSE 8000/tcp
CMD ["/home/runner/docker-entrypoint/entrypoint.sh"]