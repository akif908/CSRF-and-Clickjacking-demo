# Clickjacking Attack Against DVWA

Transparent-iframe overlay on the real DVWA target. DVWA sends no
`X-Frame-Options` or CSP `frame-ancestors` headers (verified by grepping
`dvwa-source/dvwa/includes/`), so any page can be framed.

## Run steps

1. Start DVWA (`docker compose up -d` in `dvwa-source/`), log in as
   `admin` / `password`, set **DVWA Security = Low**, and open the CSRF
   module once so the session is on that page context.
2. Serve this folder: `python -m http.server 8088 --bind 127.0.0.1`
3. Open `http://localhost:8088/overlay.html` in the same browser.
4. Use **Toggle alignment view** to verify the transparent DVWA
   "Change" button sits exactly under the decoy "Claim Reward" button;
   adjust the `.overlay` `top`/`left` offsets in `overlay.html` if your
   window size differs. Toggle back to `opacity: 0` for the attack.
5. Click **Claim Reward**. The click lands on DVWA's hidden **Change**
   button and submits the password-change form.
6. Capture before/after into `../../evidence/clickjacking/`
   (`dvwa_clickjacking_attack_success.png`).

Note: with both password fields left empty, DVWA Low still performs a
state change (sets the password to the hash of an empty string) — a
real, demonstrable effect. Restore via **Setup / Reset DB** afterwards.

## Re-test with defenses (Milestone 3)

Add frame-busting headers to DVWA's Apache config, e.g. in
`dvwa-source/` create an override that enables `mod_headers` and sets:

```
Header always set X-Frame-Options "DENY"
Header always set Content-Security-Policy "frame-ancestors 'none'"
```

Reload `overlay.html`: the browser refuses to render DVWA in the
iframe. Capture headers (`curl -I`) and the blocked frame as
`dvwa_clickjacking_defense_blocked.png` / `.txt`.

The custom protected target in `../../clickjacking/target/security.conf`
already demonstrates exactly this fix and can be referenced as the
working header configuration.

## Applied fix and re-applying after a container rebuild

The header fix was applied live inside the DVWA container as
`/etc/apache2/conf-available/dvwa-clickjacking-headers.conf` and is
saved in this folder as `dvwa-clickjacking-headers.conf`. The live
result is verified in `../../evidence/clickjacking/dvwa_csp_frame_blocked.png`
(console: framing blocked by `frame-ancestors 'none'`).

If the DVWA container is recreated, re-apply with:

```bash
docker cp dvwa-clickjacking-headers.conf dvwa-source-dvwa-1:/etc/apache2/conf-available/
docker exec dvwa-source-dvwa-1 sh -c "a2enconf dvwa-clickjacking-headers && a2enmod headers && apache2ctl graceful"
```