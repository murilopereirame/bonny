#! /bin/bash

FILE=/etc/wireguard/wg0.conf

VPN_HASH=$(echo -n "$INTERNAL_VPN_ADDRESS.$VPN_PRIVATE_KEY.$VPN_PUBLIC_KEY.$VPN_ENDPOINT.$VPN_PORT" | sha512sum | tr -d "\n *-")
OLD_HASH=$(cat /etc/wg0.hash 2>/dev/null)

create_wg0 () {
cat >> $FILE <<EOF
  [Interface]
  Address = $INTERNAL_VPN_ADDRESS
  PrivateKey = $VPN_PRIVATE_KEY
  DNS = $VPN_DNS

  [Peer]
  PublicKey = $VPN_PUBLIC_KEY
  AllowedIPs = 0.0.0.0/0
  Endpoint = $VPN_ENDPOINT:$VPN_PORT
EOF
  echo -n "$VPN_HASH" > /etc/wg0.hash
  echo "[WG0] Config created"
}

if ! [ "$VPN_HASH" = "$OLD_HASH" ]; then
  echo "[WG0] VPN config changed, recreating"
  if [ -f "$FILE" ]; then
      rm $FILE
  fi
fi

if ! [ -f "$FILE" ]; then
    create_wg0
fi