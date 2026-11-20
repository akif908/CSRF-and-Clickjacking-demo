# Burp Suite — CSRF Evidence

This folder holds evidence that Burp Suite was used, as required by P9
("Tools: Burp Suite (CSRF PoC / Clickbandit)"). All files come from a
real Burp session — nothing here is pre-fabricated.

## Edition limitation (important)

The installed edition was **Burp Suite Community Edition**. The
built-in CSRF PoC generator (Engagement tools → Generate CSRF PoC) is
Professional-only and was therefore unavailable. Instead, the CSRF
request was captured in Burp's HTTP history and exported, and an
equivalent PoC was manually constructed and tested
(`../dvwa-attacks/csrf/csrf-poc.html`). See `csrf-poc/README.md` for
the full limitation note. Clickbandit, used for the clickjacking PoC,
**is** available in Community Edition (see `../clickbandit/`).

## How the evidence was produced

1. Burp's proxy (127.0.0.1:8080) was placed in the browser's path and
   DVWA (`http://127.0.0.1:4280`, security Low) was exercised through it.
2. The forged password-change request appeared in **Proxy → HTTP
   history** and was exported (select rows → right-click → Save items)
   as XML into `http-history/`.
3. A redacted copy of the request was saved as text alongside it.
4. The session was recorded on video.

## Contents

- `csrf-poc/` — edition-limitation note (no Burp-generated HTML; the
  tested manual PoC lives in `../dvwa-attacks/csrf/`).
- `http-history/` — Burp XML export, redacted request text, session video.
- `screenshots/` — optional still captures; the session video covers
  the same material.