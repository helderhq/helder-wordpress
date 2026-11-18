# Test shop

A throwaway WooCommerce shop in Docker to test the plugin, including the WooCommerce module, against
Helder over https. Includes WordPress, WooCommerce, this plugin (mounted from the repo, so your changes
show up straight away), three products, a customer with two orders and a guest order.

Never use this for a real shop: the passwords are fixed and the database is not backed up.

## Start

```sh
cd test-shop
docker compose --profile tunnel up -d   # WordPress, database and a Cloudflare quick tunnel
docker compose run --rm setup            # installs WordPress, WooCommerce and sample data, prints the address
```

The setup prints the shop address (`https://<random>.trycloudflare.com`), the admin login
(`admin` / `helder-test`) and the customer login (`klant@example.com` / `helder-klant`).

Then, in the shop's admin, go to Settings, Helder:

1. **Connect with Helder** and pick a workspace.
2. **Give Helder access** to WooCommerce and approve on the WooCommerce screen.
3. In Helder, check that the integration WooCommerce is active, and ask the chat about order #14 with
   `klant@example.com`.
4. **Revoke access** and **Disconnect** in the plugin, and check that the key under WooCommerce,
   Settings, Advanced, REST API is gone.

The WooCommerce integration is behind the flag `test_koppelingen`: turn it on for the workspace you test with.

## Notes

- **The quick tunnel address changes on every start** of the `tunnel` container. Run
  `docker compose run --rm setup` again to update the shop address, then connect again in the plugin
  (Helder ties a connection to the site's host).
- **Fixed address:** create a tunnel in your own Cloudflare account (Zero Trust, Networks, Tunnels) with a
  public hostname pointing to `http://wordpress:80`, and put this in `test-shop/.env`:
  ```sh
  TUNNEL_TOKEN=...
  SHOP_URL=https://woo-test.example.com
  ```
  Start with `docker compose --profile named up -d` instead of `--profile tunnel`.
- **Another Helder environment:** set `HELDER_APP_URL` and `HELDER_WIDGET_URL` in `.env`. WooCommerce sends
  the keys to `HELDER_APP_URL`, so it must be https and reachable from the shop.
- **Without a tunnel** the shop runs on http://localhost:8080 (`docker compose up -d`). That is enough to
  look around, but Helder only connects sites on https.
- **Start over:** `docker compose --profile tunnel down -v` removes the shop and its database.
