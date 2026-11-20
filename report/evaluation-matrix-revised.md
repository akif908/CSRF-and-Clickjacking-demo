# P9 Security Lab --- Comparative Evaluation

## 1. CSRF Evaluation

  -----------------------------------------------------------------------------------
  Test ID        Security        Test input        Observed result     Status
                 configuration                                         
  -------------- --------------- ----------------- ------------------- --------------
  CSRF-01        Original DVWA   Inspect form and  The original        Passed
                 Low handler     source            inspected handler   
                                                   did not perform     
                                                   application-level   
                                                   CSRF token          
                                                   validation. The     
                                                   original form did   
                                                   not include a token 
                                                   for Low security.   

  CSRF-02        Custom token    Valid session     Request accepted;   Passed
                 defense (port   token             preference updated. 
                 8081)                                                 

  CSRF-03        Custom token    Invalid or        Request rejected    Passed
                 defense (port   missing token     with HTTP 403.      
                 8081)                                                 

  CSRF-04        Custom session  Inspect cookie    HttpOnly enabled;   Verified
                 cookie          attributes        SameSite=Lax.       
                                                   Secure was disabled 
                                                   for the local HTTP  
                                                   demonstration.      

  CSRF-05        Vulnerable      Forged POST       Request accepted;   Passed
                 custom          request without   preference changed  
                 application     token             from Green to Blue. 
                 (port 8083)                                           

  CSRF-06        Protected       Forged POST       Request rejected;   Passed
                 custom          request without   preference remained 
                 application     token             Green.              
                 (port 8081)                                           

  CSRF-07        Modified DVWA   Inspect           A hidden            Verified
                 Low handler     password-change   `user_token` field  
                                 form              was rendered in the 
                                                   Low-security form.  

  CSRF-08        Modified DVWA   Submit forged     DVWA returned HTTP  Passed
                 Low handler     password-change   302 and redirected  
                                 GET request       to `index.php`.     
                                 without                               
                                 `user_token`                          

  CSRF-09        Modified DVWA   Verify account    Login with the      Passed
                 Low handler     after             previously valid    
                                 missing-token     password succeeded, 
                                 request           confirming the      
                                                   forged request did  
                                                   not change the      
                                                   password in this    
                                                   test.               

  CSRF-10        Modified DVWA   Submit password   DVWA displayed      Passed
                 Low handler     change normally   "Password Changed"; 
                                 with the valid    login with the new  
                                 form token        password succeeded. 
  -----------------------------------------------------------------------------------

### CSRF implementation and interpretation

The original DVWA Low-security handler was backed up before
modification. The modified handler uses DVWA's existing
`generateSessionToken()`, `tokenField()`, and `checkToken()` helpers.
The CSRF form was updated to render the hidden token at Low security.

The missing-token test and valid-token test were both performed against
the modified handler. The missing-token request returned a redirect
(HTTP 302), while a normal form submission containing the valid token
successfully changed the password.

The custom application and DVWA use different rejection responses: the
custom application returned HTTP 403 for invalid or missing tokens,
while the modified DVWA handler redirected with HTTP 302. These results
should not be presented as if they came from the same implementation.

## 2. SameSite Cookie Evaluation

The SameSite comparison used the original vulnerable DVWA Low handler
temporarily, so token validation would not mask the effect of cookie
behavior. The original handler and original DVWA session configuration
were restored after the experiment; the token-protected handler was also
restored and verified.

  -----------------------------------------------------------------------------------------
  Test ID        Cookie            Request context           Observed result Status
                 configuration                                               
  -------------- ----------------- ------------------------- --------------- --------------
  SS-01          SameSite=Lax      Cross-site top-level GET  The forged      Passed
                                   navigation from           request reached 
                                   `http://localhost:8084`   the             
                                   to                        authenticated   
                                   `http://127.0.0.1:4280`   DVWA action and 
                                                             displayed       
                                                             "Password       
                                                             Changed."       

  SS-02          SameSite=Strict   Same cross-site top-level DVWA displayed  Passed
                                   GET navigation using the  the login page; 
                                   same attacker page and    the             
                                   target                    authenticated   
                                                             session was not 
                                                             available to    
                                                             the cross-site  
                                                             request.        

  SS-03          SameSite=Lax and  Inspect `PHPSESSID` in    The browser     Verified
                 SameSite=Strict   browser Application →     showed Lax      
                                   Cookies                   during the Lax  
                                                             test and Strict 
                                                             during the      
                                                             Strict test.    
  -----------------------------------------------------------------------------------------

### SameSite test scope and interpretation

The attacker page was served from `localhost` and the DVWA target from
`127.0.0.1`. For this experiment, the browser treated these as different
sites. The request was a cross-site top-level GET navigation.

The observed result is specific to this request type: Lax allowed the
cookie on the top-level safe-method navigation in this test, so the
forged action succeeded. Strict withheld the session cookie in this
cross-site context, and DVWA displayed its login page.

This experiment does not establish that SameSite=Lax permits every
cross-site request. In particular, it should not be generalized to
cross-site POST requests, subresource requests, or all browser contexts.

The default DVWA Low configuration left the SameSite attribute blank.
The experiment temporarily set the attribute explicitly to Lax and then
Strict. The original `dvwaPage.inc.php` configuration was restored
afterward.

## 3. Clickjacking Evaluation

  ----------------------------------------------------------------------------------------------------------------
  Test ID        Security        Test input     Observed result                                     Status
                 configuration                                                                      
  -------------- --------------- -------------- --------------------------------------------------- --------------
  CJ-01          Unprotected     Embed target   Target displayed inside the iframe.                 Passed
                 custom target   in iframe                                                          

  CJ-02          Protected       Inspect HTTP   `X-Frame-Options: DENY` was present.                Verified
                 custom target   response                                                           

  CJ-03          Protected       Inspect HTTP   `Content-Security-Policy: frame-ancestors 'none'`   Verified
                 custom target   response       was present.                                        

  CJ-04          Protected       Embed target   Browser blocked iframe embedding.                   Passed
                 custom target   in iframe                                                          

  CJ-05          Protected       Open target    Page and demo button remained accessible.           Passed
                 custom target   directly                                                           

  CJ-06          Vulnerable      Transparent    Clicking the deceptive button activated the         Passed
                 custom target   iframe overlay underlying target button.                           
                 (port 8085)                                                                        

  CJ-07          Protected       Load protected Browser refused to display the target inside the    Passed
                 custom target   target through iframe.                                             
                 (port 8082)     attacker                                                           
                                 iframe page                                                        

  CJ-08          DVWA target     Transparent    The attacker overlay aligned with DVWA's "Test      Demonstrated
                                 iframe overlay Credentials" button; the clickjacking interaction   
                                                was demonstrated.                                   

  CJ-09          DVWA with       Inspect        `X-Frame-Options: DENY` and CSP                     Passed
                 Apache security response       `frame-ancestors 'none'` were present; the browser  
                 headers         headers and    blocked framing.                                    
                                 attempt iframe                                                     
                                 embedding                                                          
  ----------------------------------------------------------------------------------------------------------------

### Clickjacking scope note

The custom clickjacking demonstration used a non-sensitive demo button.
The DVWA overlay demonstration used the "Test Credentials" control.
These demonstrate the mechanics of deceptive iframe interaction; they
should not be described as proof that a sensitive transaction or account
change was performed through clickjacking.

The protected custom target remained directly accessible while refusing
to be embedded. The DVWA Apache configuration also blocked framing
through both X-Frame-Options and CSP.

## 4. Overall Findings

-   The original inspected DVWA Low-security CSRF handler lacked
    application-level CSRF token validation.
-   The custom CSRF implementation accepted a valid session token and
    rejected invalid or missing tokens with HTTP 403.
-   The vulnerable custom application accepted a forged POST request
    without a token; the protected custom application rejected the
    corresponding request and left the preference unchanged.
-   The modified DVWA Low handler rendered a hidden CSRF token, rejected
    the tested missing-token request with HTTP 302, and accepted a
    legitimate form submission with a valid token.
-   In the tested cross-site top-level GET scenario, SameSite=Lax did
    not prevent the forged request; the attack succeeded.
-   In the same tested scenario, SameSite=Strict withheld the
    authenticated session cookie; DVWA displayed its login page.
-   The vulnerable clickjacking targets could be embedded, and the
    transparent overlay demonstrations activated underlying target
    controls.
-   X-Frame-Options: DENY and CSP `frame-ancestors 'none'` prevented
    iframe embedding of the protected custom target and the configured
    DVWA target.
-   Direct access to the protected custom target remained available.
-   All demonstration applications were hosted locally using Docker with
    loopback-only port bindings.

## 5. Important Test Scope Notes

1.  **Cross-origin is not necessarily cross-site.** The custom CSRF
    attacker and target used different ports on `127.0.0.1`. They were
    cross-origin but same-site. Those tests demonstrate missing-token
    acceptance and token-based rejection; they do not demonstrate a
    cross-site request bypassing SameSite=Lax.
2.  **SameSite comparison used different hostnames.** The SameSite
    experiment used `localhost:8084` as the attacker and
    `127.0.0.1:4280` as the target, with a top-level GET navigation.
3.  **Different token-defense responses.** The custom token defense
    returned HTTP 403; the modified DVWA handler redirected with
    HTTP 302. Both outcomes were observed in their respective
    implementations.
4.  **DVWA Low was modified for the token experiment.** The
    default/original Low handler was preserved as a backup. The
    token-protected handler and modified form were installed for the
    token test. The original vulnerable handler was temporarily restored
    for the SameSite experiment, then the protected handler was
    restored.
5.  **Clickjacking demonstration scope.** The demonstrated target
    controls were demo controls ("Claim Reward" overlay over a custom
    demo button and DVWA's "Test Credentials" button), not sensitive
    financial or account actions.
6.  **Local-only environment.** The applications were exposed on
    loopback addresses for the lab demonstration, not deployed as public
    services.

## 6. Evidence Directory

Evidence is stored under the project `evidence/` directory, organized by
topic:

-   `evidence/csrf/`
-   `evidence/clickjacking/`
-   `evidence/samesite/`
-   `evidence/environment/`

### Key CSRF evidence

-   `evidence/csrf/csrf_valid_token.png`
-   `evidence/csrf/csrf_invalid_token.png`
-   `evidence/csrf/csrf_attack_success.png`
-   `evidence/csrf/csrf_defense_attack_blocked.png`
-   `evidence/csrf/dvwa_csrf_attack_success.mp4`
-   `evidence/csrf/dvwa_csrf_token_field.png`
-   `evidence/csrf/dvwa_csrf_missing_token_302.png`
-   `evidence/csrf/dvwa_csrf_password_unchanged.png`
-   `evidence/csrf/dvwa_csrf_token_field-dvwa_csrf_missing_token_302-dvwa_csrf_password_unchanged.mp4`
-   `evidence/csrf/[x] dvwa_csrf_new_password_login_success and dvwa_csrf_valid_token_success.mp4`

### Key clickjacking evidence

-   `evidence/clickjacking/clickjacking_attack_success.png`
-   `evidence/clickjacking/clickjacking_protected_iframe_blocked.png`
-   `evidence/clickjacking/clickjacking_security_headers.png`
-   `evidence/clickjacking/clickjacking_direct_access.png`
-   `evidence/clickjacking/clickjacking_restart_verification.txt`
-   `evidence/clickjacking/dvwa_csp_frame_blocked.png`
-   `evidence/clickjacking/dvwa_clickjacking_attack_success.mp4`

### Key SameSite evidence

-   `evidence/samesite/lax_cross_site_get_attack_success.mp4`
-   `evidence/samesite/strict_cross_site_get_attack_blocked.mp4`
-   `evidence/samesite/README.md`

The SameSite videos were reported as showing both the cookie
configuration and corresponding attack outcome. The evidence list
therefore references the existing recordings rather than requiring
duplicate screenshots.

### Key tool evidence (Burp Suite / Clickbandit)

-   `burp/http-history/dvwa-csrf-request.xml` — Burp HTTP history export
    (burpVersion 2026.11.2) containing the forged DVWA CSRF GET request.
-   `burp/http-history/dvwa-csrf-request-redacted.txt` — the forged
    request with the session cookie redacted.
-   `burp/http-history/dvwa-csrf-request.mp4` — Burp session recording.
-   `clickbandit/recordings/dvwa-clickbandit-recording.html` —
    Clickbandit-generated clickjacking PoC ("Clickjacking PoC Generated
    by Burp Suite Professional"); the embedded iframe targets
    `http://127.0.0.1:4280/vulnerabilities/csrf/`.
-   `clickbandit/recordings/clickbandit_recording_complete.mp4` — full
    Clickbandit recording session.

### Tool edition limitation

The installed edition was Burp Suite Community Edition. The built-in
CSRF PoC generator (Engagement tools) is Professional-only and was
unavailable. The CSRF request was instead captured and exported from
Burp's HTTP history, and an equivalent PoC was manually constructed to
match the captured request and successfully tested. The manual PoC is
stored at `dvwa-attacks/csrf/csrf-poc.html` and is not presented as
Burp-generated output. See `burp/csrf-poc/README.md`. Clickbandit is
available in Community Edition and was used directly; the
"Burp Suite Professional" string inside its generated HTML is fixed
template text and does not indicate the edition used.

## 7. Conclusion

The lab demonstrated CSRF and clickjacking attack mechanics using local
Docker-based applications and DVWA. It compared vulnerable and protected
CSRF implementations, verified token validation in a modified DVWA
Low-security handler, and examined the effect of SameSite=Lax and
SameSite=Strict on a cross-site top-level GET request. It also
demonstrated iframe-based clickjacking and verified that X-Frame-Options
and CSP frame-ancestors blocked framing while allowing direct access to
protected pages.

The results are limited to the applications, browser, request types, and
local test configuration used in this lab. They should be interpreted as
controlled demonstrations rather than universal claims about all browser
behavior or all CSRF and clickjacking scenarios.
