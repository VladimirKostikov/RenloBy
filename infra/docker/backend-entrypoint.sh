#!/bin/bash
set -e

cd /var/www/backend

wait_for_postgres() {
  until php -r '
    $url = getenv("DATABASE_URL");
    if (!$url) { exit(1); }
    $parts = parse_url($url);
    $dsn = sprintf(
      "pgsql:host=%s;port=%s;dbname=%s",
      $parts["host"] ?? "postgres",
      $parts["port"] ?? 5432,
      ltrim($parts["path"] ?? "/renlo", "/")
    );
    new PDO($dsn, $parts["user"] ?? "renlo", $parts["pass"] ?? "renlo");
  ' 2>/dev/null; do
    sleep 2
  done
}

users_table_is_empty() {
  php -r '
    $url = getenv("DATABASE_URL");
    if (!$url) { exit(0); }
    $parts = parse_url($url);
    $dsn = sprintf(
      "pgsql:host=%s;port=%s;dbname=%s",
      $parts["host"] ?? "postgres",
      $parts["port"] ?? 5432,
      ltrim($parts["path"] ?? "/renlo", "/")
    );
    try {
      $pdo = new PDO($dsn, $parts["user"] ?? "renlo", $parts["pass"] ?? "renlo", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      ]);
      $count = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
      exit($count === 0 ? 0 : 1);
    } catch (Throwable) {
      exit(0);
    }
  '
}

if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi

mkdir -p var/cache var/log
chown -R www-data:www-data var
chmod -R ug+rwX var

wait_for_postgres

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

if users_table_is_empty; then
  php bin/console doctrine:fixtures:load --no-interaction
fi

php bin/console cache:clear --no-warmup
php bin/console cache:warmup
chown -R www-data:www-data var

exec apache2-foreground
