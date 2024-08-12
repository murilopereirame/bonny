FROM debian:bookworm-slim

ARG DOCKER_SUBNET
ARG ROUTER_SUBNET
ARG DNS_ADDRESSES
ARG VPN_ENDPOINT
ARG VPN_PORT

WORKDIR /home/runner/rules

RUN chown -R $(whoami) /home/runner

RUN apt update && apt install -y nano iptables iputils-ping wireguard iproute2 openresolv procps curl sudo
RUN apt install -y php-mbstring php-xml php-bcmath php php-curl php-mbstring php-iconv php-exif php-simplexml php-zip php-phar php-curl php-sqlite3
RUN apt install -y libzip-dev libsodium-dev libicu-dev nodejs npm libonig-dev libcurl4-openssl-dev && \
  rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN cat >> killswitch-ip4 <<EOF
*filter

-P INPUT DROP
-P FORWARD DROP
-P OUTPUT DROP

-A INPUT -m conntrack --ctstate RELATED,ESTABLISHED -j ACCEPT

-A OUTPUT -o lo -j ACCEPT
-A OUTPUT -o wg0 -p icmp -j ACCEPT

EOF

RUN if [ -z ${DOCKER_SUBNET:+x} ]; then echo "-A OUTPUT -d $DOCKER_SUBNET -j ACCEPT" >> killswitch-ip4; fi
RUN if [ -z ${ROUTER_SUBNET:+x} ]; then echo "-A OUTPUT -d $ROUTER_SUBNET -j ACCEPT" >> killswitch-ip4; fi
RUN IFS=',' && \
  for ip in $DNS_ADDRESSES; do \
  echo "-A OUTPUT -d $ip -j ACCEPT" >> killswitch-ip4; \
  done
RUN echo "-A OUTPUT -d $VPN_ENDPOINT -j ACCEPT" >> killswitch-ip4
RUN echo "-A OUTPUT -p udp -m udp --dport $VPN_PORT -j ACCEPT" >> killswitch-ip4

RUN cat >> killswitch-ip4 <<EOF
-A OUTPUT -o wg0 -j ACCEPT

-A INPUT -p tcp --dport 8000 -j ACCEPT
-A OUTPUT -p tcp --sport 8000 -j ACCEPT
COMMIT
EOF

RUN cat >> killswitch-ip6 <<EOF
*filter

-P INPUT DROP
-P FORWARD DROP
-P OUTPUT DROP

COMMIT
EOF

COPY wg0.conf /etc/wireguard/

RUN cat >> entrypoint.sh <<EOF
#! /bin/bash
sudo iptables-restore < /home/runner/rules/killswitch-ip4
sudo ip6tables-restore < /home/runner/rules/killswitch-ip6

sudo wg-quick up wg0

php artisan serve --host 0.0.0.0 --port=8000
EOF

RUN chmod +x entrypoint.sh
WORKDIR /home/runner/app

COPY . .

RUN useradd runner
RUN usermod -aG sudo runner

RUN echo "runner ALL= NOPASSWD: ALL" >> /etc/sudoers

RUN chown -R runner /home/runner

USER runner

RUN composer install
RUN npm install && npm run build

EXPOSE 8000/tcp

CMD ["/home/runner/rules/entrypoint.sh"]