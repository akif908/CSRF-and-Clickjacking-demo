# P9 — CSRF & Clickjacking Security Lab

A complete web-security lab demonstrating **Cross-Site Request Forgery (CSRF)**
and **Clickjacking** attacks against [DVWA](https://github.com/digininja/DVWA)
(Damn Vulnerable Web Application) and purpose-built demo applications, followed
by working defenses and verified re-tests showing both attacks blocked.

> All experiments run locally in Docker with loopback-only port bindings
> against intentionally vulnerable lab software. Nothing here targets
> real systems.

---

## 1. Assignment requirements

| Requirement | Status |
|---|---|
| Forge a state-changing request from a malicious page (CSRF) | ✅ Custom app + DVWA |
| Overlay a transparent iframe (clickjacking) | ✅ Custom app + DVWA |
| Defense: anti-CSRF tokens | ✅ Custom app + patched DVWA Low handler |
| Defense: SameSite cookies | ✅ Lax vs Strict cross-site comparison |
| Defense: X-Frame-Options / CSP frame-ancestors | ✅ Custom app + DVWA Apache config |
| Verify both attacks blocked (re-test) | ✅ Screenshots + recordings |
| Tools: Burp Suite (CSRF PoC / Clickbandit) | ✅ With documented Community-edition limitation (§7) |
| Deliverables: PoC pages, header/token fixes, demo | ✅ See evidence index (§8) |

## 2. Lab environment

| Service | URL | Role |
|---|---|---|
| DVWA | `http://127.0.0.1:4280` | Primary target (login `admin`/`password`) |
| Custom CSRF protected app | `http://127.0.0.1:8081` | Token + SameSite defense demo |
| Custom clickjacking protected target | `http://127.0.0.1:8082` | XFO + CSP defense demo |
| Custom CSRF vulnerable app | `http://127.0.0.1:8083` | No token validation |
| Custom CSRF attacker page | `http://127.0.0.1:8084` | Forged POST PoC |
| Custom clickjacking vulnerable target | `http://127.0.0.1:8085` | Frameable target |
| Custom clickjacking attacker page | `http://127.0.0.1:8086` | Iframe overlay PoC |
| DVWA attacker pages (cross-site) | `http://localhost:8084/8087/8088` | Served via `python -m http.server` |

**Why `localhost` vs `127.0.0.1` matters:** different ports on the same host
are *cross-origin but same-site*. `localhost` and `127.0.0.1` are different
**sites**, so attacker pages served from `localhost` produce genuinely
cross-site requests — required to meaningfully test SameSite behavior.

Platform: Windows 11, Docker Desktop (WSL2), Chrome DevTools, Burp Suite
Community 2026.11.2.

## 3. Project layout

```
├── dvwa-source/            DVWA source + Docker compose (upstream clone, vendored)
├── dvwa-attacks/
│   ├── csrf/               csrf-poc.html + token-fix backups (backup/)
│   ├── clickjacking/       dvwa-overlay.html + Apache header fix (.conf)
│   └── samesite/           dvwaPage.inc.php cookie-config variants (backup/)
├── csrf/                   Custom CSRF demos (vulnerable, protected, attacker)
├── clickjacking/           Custom clickjacking demos (vulnerable, protected, attacker)
├── burp/                   Burp HTTP history export, redacted request, edition note
├── clickbandit/            Clickbandit-generated PoC + recording
├── evidence/
│   ├── csrf/               Attack success, token field, 302 rejection, recordings
│   ├── clickjacking/       Overlay attack, CSP block, header captures
│   ├── samesite/           Lax-success vs Strict-blocked recordings
│   └── environment/        Environment + Docker service status
├── report/
│   ├── evaluation-matrix-revised.md   Full 22-case evaluation + scope notes
│   └── evaluation-matrix.md           Original custom-demo matrix (history)
└── README.md
```

## 4. The attacks

### CSRF (against DVWA Low)

DVWA's `vulnerabilities/csrf/source/low.php` accepts a plain GET request
with no CSRF token, no current-password check, and no Origin/Referer
validation:

```
GET /vulnerabilities/csrf/?password_new=X&password_conf=X&Change=Change
```

`dvwa-attacks/csrf/csrf-poc.html` is a malicious page with an
auto-submitting hidden form. A logged-in victim who merely opens it gets
their admin password changed without any interaction. The request is a
**top-level GET navigation**, which is why it succeeds cross-site even
when the session cookie is `SameSite=Lax` (Lax allows top-level
safe-method navigations). Subresource variants (`<img>`, `fetch`) are
blocked by Lax.

### Clickjacking (against DVWA)

DVWA sends no `X-Frame-Options` or CSP `frame-ancestors` headers by
default, so it can be framed. `dvwa-attacks/clickjacking/dvwa-overlay.html`
positions a fully transparent (`opacity: 0`) iframe of DVWA's CSRF page
so a hidden button sits exactly under a decoy "Claim Reward" button.
The victim's click lands on the real DVWA control. A lab-only alignment
toggle (`opacity: 0.35`) is included for calibration.

The custom demos in `clickjacking/` show the same mechanics on a minimal
target, and `csrf/attacker/` shows a forged POST against a token-less app.

## 5. The defenses (and where the fixes live)

### Anti-CSRF tokens
- **Custom app** — `csrf/index.php`: session-bound
  `bin2hex(random_bytes(32))` token, constant-time `hash_equals()`
  validation, **HTTP 403** on invalid/missing token.
- **DVWA** — the Low handler was patched using DVWA's own
  `generateSessionToken()` / `tokenField()` / `checkToken()` helpers
  (as used at Impossible level). Forged requests without `user_token`
  are rejected with **HTTP 302** back to the index; legitimate
  submissions with the token succeed. Patched files are preserved in
  `dvwa-attacks/csrf/backup/` with `docker cp` re-apply instructions.

### SameSite cookies
- Custom apps set `SameSite=Lax; HttpOnly` via `session_set_cookie_params()`.
- DVWA experiment (`dvwa-attacks/samesite/backup/` holds all config
  variants): with **Lax**, the cross-site top-level GET attack still
  succeeded; with **Strict**, the browser withheld the session cookie
  and DVWA showed the login page. Original config restored afterwards.

### Frame-busting headers
- **Custom target** — `clickjacking/target/security.conf`.
- **DVWA** — `dvwa-attacks/clickjacking/dvwa-clickjacking-headers.conf`,
  applied live in the container's Apache config:

```apache
Header always set X-Frame-Options "DENY"
Header always set Content-Security-Policy "frame-ancestors 'none'"
```

Verified live: `curl -I` shows both headers, and the browser console
reports the framing violation while direct access keeps working.

## 6. Results summary

22 test cases across CSRF (10), SameSite (3), and clickjacking (9) —
all passed/verified. Full details, observed results, and interpretation
limits: **[report/evaluation-matrix-revised.md](report/evaluation-matrix-revised.md)**.

Highlights:
- Forged request changed the DVWA admin password with zero victim interaction.
- Patched DVWA rejected the same forged request (302); password unchanged;
  valid-token submission still worked.
- `SameSite=Strict` blocked the cross-site attack that `Lax` allowed.
- Transparent overlay activated hidden DVWA controls; after the header
  fix the browser refused to frame DVWA at all.

## 7. Tooling and edition limitation (transparency note)

- **Burp Suite Community 2026.11.2** proxied the browser traffic
  (`127.0.0.1:8080`). The forged CSRF request was captured and exported
  from HTTP history (`burp/http-history/dvwa-csrf-request.xml`, genuine
  Burp export format).
- The built-in **CSRF PoC generator is Professional-only** and was
  unavailable. An equivalent PoC was manually constructed to match the
  captured request and successfully tested; it is stored at
  `dvwa-attacks/csrf/csrf-poc.html` and is **not** presented as
  Burp-generated output. Full note: `burp/csrf-poc/README.md`.
- **Clickbandit** (available in Community) generated the clickjacking
  PoC in `clickbandit/recordings/dvwa-clickbandit-recording.html`; its
  embedded iframe targets DVWA's CSRF page. The "Burp Suite Professional"
  string inside the generated HTML is fixed template text, not an
  indication of the edition used.

## 8. Evidence index

| Claim | Evidence |
|---|---|
| CSRF attack succeeded (custom) | `evidence/csrf/csrf_attack_success.png` |
| CSRF attack succeeded (DVWA) | `evidence/csrf/dvwa_csrf_attack_success.mp4` |
| Token field present in patched DVWA | `evidence/csrf/dvwa_csrf_token_field.png` |
| Forged request rejected, password unchanged | `evidence/csrf/dvwa_csrf_missing_token_302.png`, `dvwa_csrf_password_unchanged.png` |
| Valid token + new-password login works | `evidence/csrf/[x] dvwa_csrf_new_password_login_success and dvwa_csrf_valid_token_success.mp4` |
| SameSite=Lax: cross-site GET succeeded | `evidence/samesite/lax_cross_site_get_attack_success.mp4` |
| SameSite=Strict: cross-site GET blocked | `evidence/samesite/strict_cross_site_get_attack_blocked.mp4` |
| Clickjacking overlay activated hidden control | `evidence/clickjacking/clickjacking_attack_final.png`, `dvwa_clickjacking_attack_success.mp4` |
| Framing blocked by CSP/XFO (DVWA) | `evidence/clickjacking/dvwa_csp_frame_blocked.png` |
| Security headers present | `evidence/clickjacking/clickjacking_headers_final.txt`, `clickjacking_security_headers.png` |
| Protected page still directly accessible | `evidence/clickjacking/clickjacking_direct_access.png` |
| Burp captured the forged request | `burp/http-history/dvwa-csrf-request.xml`, `dvwa-csrf-request-redacted.txt` |
| Clickbandit PoC + session | `clickbandit/recordings/` |

**Not pushed to GitHub** (kept locally; excluded via `.gitignore`):
`CS-record.mp4` (334 MB full demo), `clickbandit/recordings/clickbandit_recording_complete.mp4`
(117 MB) — both exceed GitHub's 100 MB per-file limit — and
`burp/http-history/dvwa-csrf-request.mp4` (62 MB, excluded by project decision).
All findings they show are also covered by the pushed screenshots, smaller
recordings, and the evaluation matrix.

## 9. How to run

```bash
# 1. DVWA target
cd dvwa-source && docker compose up -d
#    → http://127.0.0.1:4280  (admin/password, set Security = Low)

# 2. Custom CSRF demos
cd ../csrf && docker compose up -d          # :8081 protected, :8083 vulnerable
docker compose -f compose-attacker.yaml up -d   # :8084 attacker

# 3. Custom clickjacking demos
cd ../clickjacking && docker compose up -d  # :8082 protected target
docker compose -f compose-vulnerable.yaml up -d  # :8085 vulnerable
docker compose -f compose-attacker.yaml up -d    # :8086 attacker

# 4. Attacks against DVWA (serve attacker from localhost = truly cross-site)
cd ../dvwa-attacks/csrf && python -m http.server 8084 --bind 127.0.0.1
#    open http://localhost:8084/csrf-poc.html
```

Per-attack walkthroughs and defense re-test steps:
`dvwa-attacks/*/README.md`. Re-applying the DVWA fixes after a container
rebuild is documented there too (`docker cp` from the `backup/` folders).

**Restore DVWA after attacking:** log in with the changed password and
change it back, or use *Setup / Reset DB*.

## 10. Scope and ethics

- Local-only, loopback-bound Docker services; intentionally vulnerable
  lab software; harmless state changes (demo preferences, lab password).
- Demonstrated clickjacking targets were demo controls, not sensitive
  transactions.
- Results are specific to the tested request types and browser behavior;
  the report's scope notes (§5 there) state exactly what each test does
  and does not establish.