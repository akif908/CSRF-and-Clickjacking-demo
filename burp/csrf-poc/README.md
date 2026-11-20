# CSRF PoC — Edition Limitation Note

## Why this folder contains no Burp-generated PoC HTML

- The P9 assignment specified Burp Suite's CSRF PoC generator
  (Engagement tools → Generate CSRF PoC).
- The installed edition was **Burp Suite Community Edition**.
- The built-in CSRF PoC generator is a **Professional-only** feature
  and was unavailable in this environment.
- An equivalent PoC was manually constructed to match the request
  captured in Burp's HTTP history (same endpoint, method, and
  parameters) and was successfully tested against DVWA.
- The manually created PoC is stored separately at
  `../../dvwa-attacks/csrf/csrf-poc.html`. It is **not** labeled or
  presented as Burp-generated output.

## Supporting evidence that Burp was used

- `../http-history/dvwa-csrf-request.xml` — genuine Burp HTTP history
  export (burpVersion 2026.11.2) containing the forged CSRF GET.
- `../http-history/dvwa-csrf-request-redacted.txt` — the captured
  state-changing request with the session cookie redacted.
- `../http-history/dvwa-csrf-request.mp4` — Burp session recording.

The manual PoC and the Burp-captured request are consistent: both
target `GET /vulnerabilities/csrf/?password_new=...&password_conf=...&Change=Change`
on `http://127.0.0.1:4280`.