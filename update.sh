#! /bin/bash
git pull
for arg; do
  case $arg in
    --install)
      /opt/alt/php83/usr/bin/php composer.phar install
      ;;
    --migrate)
      /opt/alt/php83/usr/bin/php bin/console doctrine:schema:update --force
      ;;
    --import)
      /opt/alt/php83/usr/bin/php bin/console doctrine:query:sql "$(< users.sql)"
      ;;
  esac
done
/opt/alt/php83/usr/bin/php bin/console cache:clear --no-warmup --env=prod --no-debug
