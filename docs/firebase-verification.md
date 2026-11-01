# Firebase authentication verification

Authentication is verified in three layers. Each layer covers what the layer before it cannot.

## 1. Mocked unit/feature tests: `composer test`

These tests use a mocked Firebase SDK. They cover:

- Local account resolution and provisioning
- Rejection of unverified, stale, disabled and revoked tokens
- Cookie flags, including `Secure` in production

## 2. Auth Emulator tests: `composer test:firebase-emulator`

These tests run `tests/Feature/FirebaseEmulatorTest.php` against a real Firebase Auth Emulator. They also run in CI as the `firebase-emulator` job. Nothing is mocked: the emulator issues the tokens, creates the session cookies, links providers and revokes sessions. Laravel receives the raw `Set-Cookie` value exactly as a browser would store it. They need Node 22 or newer and no credentials or Java.

They cover:

- Email-link sign-in, single-use codes, and a link redeemed for a different address
- Google sign-in, Google linking and unlinking, and refusing to unlink the last provider
- The 14-day remembered session and the 12-hour browser-session cookie
- Rejection of unverified emails and stale sign-ins
- Revocation, disabled users and deleted users, each rejected on the next request and the cookie cleared
- Sign-out

The emulator **does not sign tokens**, so signature verification is only exercised against a real project (see below). For this reason, the app refuses to boot in production when `FIREBASE_AUTH_EMULATOR_HOST` is set.

## 3. Real-project checks (manual, per environment)

Run these once against the **development** Firebase project over HTTPS, using a tunnel or a staging host. Then run them again against **production** after deploy. Record the date, the environment and who checked each item.

### Firebase console

- [ ] **Authentication → Sign-in method**: Google is enabled. Email/Password is enabled with **Email link (passwordless sign-in)** turned on.
- [ ] **Authentication → Settings → Authorized domains**: lists the production host, the staging host if there is one, and nothing stale. Remove `localhost` from production.
- [ ] **Authentication → Settings → User account linking**: set to *Link accounts that use the same email*, which the Google-collision message relies on.
- [ ] The Google provider's OAuth consent screen is published, not in *Testing*, with the production domain listed.
- [ ] The service account in `FIREBASE_CREDENTIALS` belongs to the same project as `FIREBASE_PROJECT_ID`. The development and production projects are separate.

### Sign-in flows (incognito window each time)

- [ ] **Google, new user**: a local account is created with the Google name and photo, and you land on `/home`.
- [ ] **Google, existing email-link user** (no Google linked, non-Gmail address): sign-in is refused with the "use the email link once, then link Google" message. Gmail addresses may be auto-linked by Firebase, because Google is the trusted provider for them. Confirm that the result is still a single local account.
- [ ] **Email link, same device**: the email arrives within about 1 minute, is not in spam, comes from the expected sender, and its link points at `https://<host>/auth/email/finish`. Clicking it signs you in without asking for the email address.
- [ ] **Email link, different device or browser**: opening the link prompts for the email address and then signs in.
- [ ] **Email link replay**: opening the same link a second time shows "invalid or expired".
- [ ] **Email link expiry**: an old, unused link is refused. Record the window you observe; it is set by Firebase, not the app.
- [ ] **Remember me**: with it checked, the session survives a browser restart. Without it, the session ends when the browser closes.

### Session cookie (DevTools → Application → Cookies)

- [ ] The `__session` cookie is `HttpOnly`, `Secure` (production), `SameSite=Lax` and `Path=/`, with no `Domain` broader than the host.
- [ ] Without "Remember me" the cookie is a session cookie. With it, the cookie expires in 14 days.
- [ ] `/api/v1/me` returns 200 with the cookie, and returns 401 after the cookie is deleted.
- [ ] Sign-out removes the cookie, and `/account` then redirects to sign-in.

### Account management

- [ ] **Link Google** from `/account` (re-authenticates by email link first): providers then list both.
- [ ] **Unlink Google**: you are signed out everywhere, and an old session in another browser gets 401 on its next request.

### Revocation and disabling (another browser stays signed in throughout)

- [ ] **Revoke tokens** (for example, `$auth->revokeRefreshTokens($uid)` in `php artisan tinker`): the other browser is signed out on its next request.
- [ ] **Disable the user** in the Firebase console: the other browser is signed out on its next request, and signing in again is refused.
- [ ] **Re-enable the user**: signing in works again.
- [ ] **Delete the user** in the Firebase console: the other browser is signed out on its next request.

### Production configuration

- [ ] `APP_ENV=production` and `FIREBASE_AUTH_EMULATOR_HOST` is **unset**. The app refuses to boot otherwise.
- [ ] `FIREBASE_AUTH_EMULATOR_URL` is unset. Even if it is set, it is never rendered in production.
- [ ] The site is served only over HTTPS, and HTTP redirects to HTTPS.
