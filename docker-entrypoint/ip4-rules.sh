#! /bin/bash

iptables -A OUTPUT -o eth0 -d "$DOCKER_SUBNET" -j ACCEPT
iptables -A INPUT -s "$DOCKER_SUBNET" -j ACCEPT
iptables -A FORWARD -d "$DOCKER_SUBNET" -o eth0 -m state --state RELATED,ESTABLISHED -j ACCEPT

iptables -P INPUT DROP
iptables -P FORWARD DROP
iptables -P OUTPUT DROP

IFS=','
for ip in $VPN_DNS; do
  iptables -A OUTPUT -d "$ip" -j ACCEPT;
done

iptables -A INPUT -m conntrack --ctstate RELATED,ESTABLISHED -j ACCEPT

iptables -A OUTPUT -o lo -j ACCEPT
iptables -A OUTPUT -o wg0 -p icmp -j ACCEPT
iptables -A OUTPUT -o wg0 -j ACCEPT

iptables -A INPUT -s 127.0.0.1 -j ACCEPT
iptables -A OUTPUT -d "$VPN_ENDPOINT" -j ACCEPT

iptables -A OUTPUT -p udp -m udp --dport "$VPN_PORT" -j ACCEPT
iptables -A INPUT -p tcp --dport 8000 -j ACCEPT
iptables -A OUTPUT -p tcp --sport 8000 -j ACCEPT
iptables -A OUTPUT -p tcp --sport 27017 -j ACCEPT