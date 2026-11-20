# CSRF Attack Against DVWA (Low Security)

Forged password-change request against the real DVWA target, closing the
"target = DVWA" requirement of P9.

## Target

- DVWA: `http://127.0.0.1:4280` (from `dvwa-source/compose.yml`)
- Endpoint: `GET /vulnerabilities/csrf/?password_new=...&password_conf=...&Change=Change`
- `vulnerabilities/csrf/source/low.php` performs no token validation,
  no current-password check, and no Origin/Referer check.

## Run steps

1. Start DVWA: `docker compose up -d` inside `dvwa-source/`.
2. Log in at `http://127.0.0.1:4280` (`admin` / `password`), set
   **DVWA Security = Low**.
3. Serve this folder from a *different site* to make the request truly
   cross-site (localhost is a different site than 127.0.0.1):

   ```
   python -m http.server 8087 --bind 127.0.0.1
   ```

4. In the same browser, open `http://localhost:8087/csrf-poc.html`.
5. The form auto-submits. DVWA replies **"Password Changed."** — the
   admin password is now `9083` without the victim touching DVWA.
6. Capture the result into `../../evidence/csrf/` (e.g.
   `dvwa_csrf_attack_success.png`).

## Why it works despite SameSite=Lax

The forged request is a **top-level GET navigation** (form submission),
which SameSite=Lax explicitly allows to carry cookies. Subresource
variants (`<img src=...>`, `fetch`) are blocked by Lax — demonstrating
that difference is a good extra test for `evidence/samesite/`.

## Re-test with defenses (Milestone 3)

- Set **DVWA Security = Impossible** and reload the PoC. The handler
  (`impossible.php`) requires a session-bound `user_token` and the
  current password, so the forged request is rejected/redirected to
  login. Capture as `dvwa_csrf_defense_blocked.png`.

## Restore

Log in with `9083` and change the password back, or use
**Setup / Reset DB** in DVWA.



- Target: DVWA
- Security: Low
- Attack origin: http://localhost:8084
- Target endpoint: /vulnerabilities/csrf/
- Method: GET
- Result: Password changed without victim interaction
- New lab password: 9083

## Applied token fix and re-applying after a container rebuild

The token defense was applied live inside the DVWA container using
DVWA's own `generateSessionToken()` / `tokenField()` / `checkToken()`
helpers. Preserved in `backup/`:

- `low.php.original` / `index.php.original` — untouched DVWA files
- `low.php.protected` — token-validating Low handler (currently live)
- `index.php` — CSRF form rendering the hidden `user_token` at Low

If the DVWA container is recreated, re-apply with:

```bash
docker cp backup/low.php.protected dvwa-source-dvwa-1:/var/www/html/vulnerabilities/csrf/source/low.php
docker cp backup/index.php dvwa-source-dvwa-1:/var/www/html/vulnerabilities/csrf/index.php
```

Verified live results: `../../evidence/csrf/dvwa_csrf_token_field.png`,
`dvwa_csrf_missing_token_302.png` (forged GET → 302),
`dvwa_csrf_password_unchanged.png`.
