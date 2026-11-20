# SameSite Evidence — Completed

Cross-site comparison against the original (token-less) DVWA Low
handler, attacker on `http://localhost:8084`, target on
`http://127.0.0.1:4280` (different sites). Request type: top-level GET
navigation (auto-submitting form).

## Recorded results

- `lax_cross_site_get_attack_success.mp4` — with `SameSite=Lax`
  explicitly set on the DVWA session cookie, the forged cross-site GET
  still carried the cookie (Lax allows top-level safe-method
  navigations) and DVWA responded "Password Changed." (SS-01)
- `strict_cross_site_get_attack_blocked.mp4` — with `SameSite=Strict`,
  the browser withheld the session cookie on the same cross-site
  navigation and DVWA showed the login page. (SS-02)
- Cookie attribute inspection (Application → Cookies) is shown within
  the recordings. (SS-03)

## Configurations used

The cookie attribute was changed via DVWA's session configuration;
variants are preserved in `../../dvwa-attacks/samesite/backup/`:
`dvwaPage.inc.php.original`, `.lax`, `.strict`. The original
configuration was restored after the experiment.

## Interpretation limits

Lax succeeding here applies only to top-level GET navigation. It does
not generalize to cross-site POSTs or subresource requests (`<img>`,
`fetch`), which Lax blocks. See `report/evaluation-matrix-revised.md`
§2 and §5.