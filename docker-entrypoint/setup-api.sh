#! /bin/bash

FILE=/home/runner/torrents-api/.env
API_HASH=$(echo -n "$MONGODB_ENDPOINT.$ONE337X_COOKIE.$TGX_COOKIE.$MAGNET_DL_COOKIE.$ANIDEX_COOKIE.$PIRATEIRO_COOKIE" | sha512sum | tr -d "\n *-")
OLD_HASH=$(cat /home/runner/api.hash 2>/dev/null)

create_env () {
  cp /home/runner/torrents-api/.env.sample $FILE
  sed -i "s/MONGODB_URI=.*/MONGODB_URI=mongodb:\/\/$MONGODB_ENDPOINT:27017\/apiDB/g" $FILE
  sed -i "s/ONE337X_COOKIE=.*/ONE337X_COOKIE=$ONE337X_COOKIE/g" $FILE
  sed -i "s/TGX_COOKIE=.*/TGX_COOKIE=$TGX_COOKIE/g" $FILE
  sed -i "s/MAGNET_DL_COOKIE=.*/MAGNET_DL_COOKIE=$MAGNET_DL_COOKIE/g" $FILE
  sed -i "s/ANIDEX_COOKIE=.*/ANIDEX_COOKIE=$ANIDEX_COOKIE/g" $FILE
  sed -i "s/PIRATEIRO_COOKIE=.*/PIRATEIRO_COOKIE=$PIRATEIRO_COOKIE/g" $FILE

  echo -n "$API_HASH" > /home/runner/api.hash
  echo "[API] Env created"
}

if ! [ "$API_HASH" = "$OLD_HASH" ]; then
  echo "[API] API env changed, recreating"
  if [ -f "$FILE" ]; then
    rm $FILE
  fi
fi

if ! [ -f "$FILE" ]; then
    create_env
fi