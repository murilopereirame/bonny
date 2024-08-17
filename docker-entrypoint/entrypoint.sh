#! /bin/bash

sudo -E /bin/bash /home/runner/docker-entrypoint/ip4-rules.sh
sudo -E /bin/bash /home/runner/docker-entrypoint/ip6-rules.sh
sudo -E /bin/bash /home/runner/docker-entrypoint/build-wg0.sh

/bin/bash /home/runner/docker-entrypoint/setup-api.sh

sudo wg-quick up /home/runner/wg0.conf
cd /home/runner/server; php artisan serve --host 0.0.0.0 --port=8000 &
cd /home/runner/torrents-api/; node server.js