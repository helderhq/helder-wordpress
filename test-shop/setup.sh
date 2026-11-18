#!/bin/sh
# Installs the test shop: WordPress in Dutch, WooCommerce with sample data, and the Helder plugin.
# Runs in the wp-cli container (see compose.yml). Safe to run again: existing data is kept.
set -eu
cd /var/www/html

echo "Waiting for WordPress and the database..."
i=0
until [ -f wp-config.php ] && wp db check >/dev/null 2>&1; do
  i=$((i + 1)); [ "$i" -gt 60 ] && { echo "WordPress did not start." >&2; exit 1; }
  sleep 2
done

# The shop address: SHOP_URL if given, else the quick tunnel's hostname, else localhost.
url="${SHOP_URL:-}"
if [ -z "$url" ] && getent hosts tunnel >/dev/null 2>&1; then
  i=0
  while [ "$i" -lt 30 ]; do
    host=$(curl -fsS http://tunnel:2000/quicktunnel 2>/dev/null | sed -n 's/.*"hostname":"\([^"]*\)".*/\1/p' || true)
    [ -n "$host" ] && { url="https://$host"; break; }
    i=$((i + 1)); sleep 2
  done
fi
[ -z "$url" ] && url="http://localhost:${SHOP_PORT}"
echo "Shop address: $url"

if ! wp core is-installed 2>/dev/null; then
  wp core install --url="$url" --title="Helder test shop" --admin_user=admin \
    --admin_password="$ADMIN_PASSWORD" --admin_email=test@example.com --skip-email
fi
wp option update home "$url" >/dev/null
wp option update siteurl "$url" >/dev/null
# The WordPress image writes the .htaccess with the rewrite rules itself.
wp rewrite structure '/%postname%/' >/dev/null

wp language core install nl_NL --activate >/dev/null 2>&1 || echo "Dutch not installed, continuing in English."
wp option update timezone_string Europe/Amsterdam >/dev/null

wp plugin is-installed woocommerce || wp plugin install woocommerce
wp plugin activate woocommerce helder
wp language plugin install woocommerce nl_NL >/dev/null 2>&1 || true

# Store settings: Netherlands, euro, skip onboarding, cash on delivery.
set_option() { wp option update "$@" >/dev/null 2>&1 || true; }
set_option woocommerce_default_country NL
set_option woocommerce_currency EUR
set_option woocommerce_onboarding_profile '{"skipped":true}' --format=json
set_option woocommerce_task_list_hidden yes
set_option woocommerce_coming_soon no
set_option woocommerce_cod_settings '{"enabled":"yes","title":"Betalen bij levering"}' --format=json

# No mail server in the container: turn WooCommerce emails off.
wp eval 'foreach ( WC()->mailer()->get_emails() as $m ) { update_option( "woocommerce_" . $m->id . "_settings", array_merge( (array) get_option( "woocommerce_" . $m->id . "_settings", array() ), array( "enabled" => "no" ) ) ); }' >/dev/null 2>&1 || true

# Sample data, only the first time.
if [ "$(wp wc product list --user=admin --format=count)" = "0" ]; then
  echo "Creating sample data..."
  p1=$(wp wc product create --user=admin --name="Linnen overhemd" --regular_price=59.95 --sku=OVH-01 \
    --manage_stock=true --stock_quantity=12 --porcelain)
  p2=$(wp wc product create --user=admin --name="Wollen sjaal" --regular_price=34.50 --sku=SJL-02 \
    --manage_stock=true --stock_quantity=0 --porcelain)
  wp wc product create --user=admin --name="Canvas tas" --regular_price=24.00 --sku=TAS-03 \
    --manage_stock=true --stock_quantity=40 --porcelain >/dev/null

  customer=$(wp wc customer create --user=admin --email=klant@example.com --first_name=Sanne \
    --last_name="de Vries" --password=helder-klant --porcelain 2>/dev/null)
  address='{"first_name":"Sanne","last_name":"de Vries","address_1":"Teststraat 1","postcode":"1011 AA","city":"Amsterdam","country":"NL","email":"klant@example.com"}'

  wp wc shop_order create --user=admin --customer_id="$customer" --status=processing \
    --payment_method=cod --payment_method_title="Betalen bij levering" \
    --billing="$address" --shipping="$address" \
    --line_items="[{\"product_id\":$p1,\"quantity\":1}]" --porcelain >/dev/null 2>&1
  wp wc shop_order create --user=admin --customer_id="$customer" --status=completed \
    --payment_method=cod --payment_method_title="Betalen bij levering" \
    --billing="$address" --shipping="$address" \
    --line_items="[{\"product_id\":$p2,\"quantity\":2}]" --porcelain >/dev/null 2>&1
  # A guest order (no account).
  wp wc shop_order create --user=admin --status=on-hold \
    --billing='{"first_name":"Gast","last_name":"Klant","email":"gast@example.com","country":"NL"}' \
    --line_items="[{\"product_id\":$p1,\"quantity\":1}]" --porcelain >/dev/null 2>&1
fi

cat <<DONE

Test shop ready.
  Shop:      $url
  Admin:     $url/wp-admin  (admin / $ADMIN_PASSWORD)
  Customer:  klant@example.com / helder-klant
  Helder:    $HELDER_APP_URL
In the admin, go to Settings, Helder to connect.
DONE
