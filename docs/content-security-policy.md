# Content Security Policy

The global middleware always sends an enforced `Content-Security-Policy` header.
There is no report-only mode and no report collection endpoint: blocked loads
show up as warnings in the browser console.

After deployment, confirm the header on a page response and check:

- Google sign-in, email-link sign-in, and linking/unlinking Google.
- Account tabs, Google avatars, and profile/recipe photo selection and cropping.
- The donation button and its Cookie font.

Verify `FIREBASE_AUTH_DOMAIN` matches the Firebase project's configured auth domain.
Only that domain is allowed for the Firebase authentication iframe.

The policy allows Firebase's API/helper hosts and the current donation button's
script and font hosts. It permits Google-hosted profile images, local uploads,
and data/blob previews. Browser checks remain necessary after policy changes;
server rendering tests cannot prove that third-party integrations work in-browser.
