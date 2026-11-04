import axios from 'axios';
import {
    GoogleAuthProvider,
    connectAuthEmulator,
    getAuth,
    inMemoryPersistence,
    isSignInWithEmailLink,
    linkWithPopup,
    sendSignInLinkToEmail,
    setPersistence,
    signInWithEmailLink,
    signInWithPopup,
    signOut,
} from 'firebase/auth';
import { initializeApp } from 'firebase/app';

const meta = name => document.querySelector(`meta[name="${name}"]`)?.content || '';

const firebaseConfig = {
    apiKey: meta('firebase-api-key'),
    authDomain: meta('firebase-auth-domain'),
    projectId: meta('firebase-project-id'),
};
const firebaseReady = Object.values(firebaseConfig).every(Boolean);
const auth = firebaseReady ? getAuth(initializeApp(firebaseConfig)) : null;
if (auth && meta('firebase-auth-emulator-url')) connectAuthEmulator(auth, meta('firebase-auth-emulator-url'), { disableWarnings: true });
const authReady = auth ? setPersistence(auth, inMemoryPersistence) : Promise.resolve();
window.clearHuntRecipesFirebaseAuth = async () => {
    if (!auth) return;
    await authReady;
    await signOut(auth);
};

const showAuthMessage = (selector, message, variant = null) => {
    const element = document.querySelector(selector);
    if (!element) return;
    if (variant) {
        element.classList.remove('alert-danger', 'alert-success', 'alert-info', 'alert-warning');
        element.classList.add(`alert-${variant}`);
    }
    element.textContent = message;
    element.hidden = false;
};

const hideAuthMessages = () => {
    document.querySelectorAll('[data-auth-error], [data-auth-success]').forEach(element => {
        element.hidden = true;
    });
};

const AUTH_ERROR_MESSAGES = {
    'auth/popup-closed-by-user': ['The Google sign-in window was closed before sign-in finished. Please try again when you\'re ready.', 'info'],
    'auth/cancelled-popup-request': ['The Google sign-in window was closed before sign-in finished. Please try again when you\'re ready.', 'info'],
    'auth/user-cancelled': ['Sign-in was cancelled.', 'info'],
    'auth/popup-blocked': ['Your browser blocked the Google sign-in window. Allow pop-ups for this site and try again.', 'warning'],
    'auth/network-request-failed': ['We couldn\'t reach the sign-in service. Check your internet connection and try again.'],
    'auth/too-many-requests': ['Too many sign-in attempts. Please wait a few minutes and try again.'],
    'auth/user-disabled': ['This account has been disabled. Contact us if you think this is a mistake.'],
    'auth/invalid-email': ['That email address doesn\'t look right. Check it and try again.'],
    'auth/invalid-action-code': ['This sign-in link is invalid or has already been used. Request a new one from the sign-in page.'],
    'auth/expired-action-code': ['This sign-in link has expired. Request a new one from the sign-in page.'],
    'auth/account-exists-with-different-credential': ['That email already has a HuntRecipes identity. Use the email link once, then link Google from your account.'],
    'auth/credential-already-in-use': ['That Google account is already linked to a different HuntRecipes account.'],
    'auth/provider-already-linked': ['Google is already linked to this account.', 'info'],
    'auth/unauthorized-domain': ['Sign-in isn\'t available from this web address.'],
    'auth/operation-not-allowed': ['This sign-in method isn\'t enabled right now.'],
};

const showAuthError = (error, fallback) => {
    console.error(error);
    const [message, variant] = AUTH_ERROR_MESSAGES[error?.code]
        || [error?.response?.data?.message || fallback];
    showAuthMessage('[data-auth-error]', message, variant || 'danger');
};

const establishSession = async (user, remember, returnUrl) => {
    const idToken = await user.getIdToken(true);
    await axios.post('/api/v1/auth/session', { id_token: idToken, remember });
    await signOut(auth);
    window.location.assign(returnUrl || '/home');
};

const initializeSignIn = () => {
    const screen = document.querySelector('[data-auth-screen]');
    if (!screen) return;
    if (!auth) {
        showAuthMessage('[data-auth-error]', 'Identity provider is not configured for this environment.');
        return;
    }
    const returnUrl = screen.dataset.returnUrl || '/home';
    const form = screen.querySelector('[data-email-link-form]');
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const email = form.email.value.trim().toLowerCase();
        const remember = form.remember.checked;
        try {
            await authReady;
            await sendSignInLinkToEmail(auth, email, { url: `${window.location.origin}/auth/email/finish`, handleCodeInApp: true });
            localStorage.setItem('huntrecipes.emailForSignIn', email);
            localStorage.setItem('huntrecipes.remember', remember ? '1' : '0');
            localStorage.setItem('huntrecipes.returnUrl', returnUrl);
            showAuthMessage('[data-auth-success]', 'Check your email for your one-time sign-in link.');
        } catch (error) {
            showAuthMessage('[data-auth-success]', 'If that address can sign in, a one-time link is on its way.');
        }
    });
    screen.querySelector('[data-google-sign-in]').addEventListener('click', async () => {
        hideAuthMessages();
        try {
            await authReady;
            const result = await signInWithPopup(auth, new GoogleAuthProvider());
            await establishSession(result.user, form.remember.checked, returnUrl);
        } catch (error) {
            showAuthError(error, 'Google sign-in didn\'t work. Please try again.');
        }
    });
};

const initializeEmailFinish = async () => {
    if (!document.querySelector('[data-email-link-finish]')) return;
    if (!auth || !isSignInWithEmailLink(auth, window.location.href)) {
        showAuthMessage('[data-auth-error]', 'This sign-in link is invalid or expired.');
        return;
    }
    let email = localStorage.getItem('huntrecipes.emailForSignIn');
    if (!email) email = window.prompt('Confirm the email address that received this link:');
    if (!email) {
        showAuthMessage('[data-auth-error]', 'Your email address is required to complete sign-in.');
        return;
    }
    try {
        await authReady;
        const result = await signInWithEmailLink(auth, email, window.location.href);
        const requestedAction = new URL(window.location.href).searchParams.get('mode');
        const providerAction = localStorage.getItem('huntrecipes.providerAction') ||
            (['link-google', 'unlink-google'].includes(requestedAction) ? requestedAction : null);
        if (providerAction === 'link-google') {
            await linkWithPopup(result.user, new GoogleAuthProvider());
        } else if (providerAction === 'unlink-google') {
            const idToken = await result.user.getIdToken(true);
            await axios.delete('/api/v1/me/providers/google', { data: { id_token: idToken } });
            localStorage.removeItem('huntrecipes.emailForSignIn');
            localStorage.removeItem('huntrecipes.remember');
            localStorage.removeItem('huntrecipes.returnUrl');
            localStorage.removeItem('huntrecipes.providerAction');
            await signOut(auth);
            window.location.assign('/sign-in');
            return;
        }
        const remember = localStorage.getItem('huntrecipes.remember') === '1';
        const returnUrl = localStorage.getItem('huntrecipes.returnUrl') || '/home';
        localStorage.removeItem('huntrecipes.emailForSignIn');
        localStorage.removeItem('huntrecipes.remember');
        localStorage.removeItem('huntrecipes.returnUrl');
        localStorage.removeItem('huntrecipes.providerAction');
        await establishSession(result.user, remember, returnUrl);
    } catch (error) {
        showAuthError(error, 'We couldn\'t finish signing you in. Please try again.');
    }
};

const initializeProviderManagement = () => {
    const account = document.querySelector('[data-account-email]');
    if (!account || !auth) return;
    const begin = async action => {
        const message = account.querySelector('[data-provider-message]');
        try {
            await authReady;
            await sendSignInLinkToEmail(auth, account.dataset.accountEmail, {
                url: `${window.location.origin}/auth/email/finish?mode=${encodeURIComponent(action)}`,
                handleCodeInApp: true,
            });
            localStorage.setItem('huntrecipes.emailForSignIn', account.dataset.accountEmail);
            localStorage.setItem('huntrecipes.providerAction', action);
            localStorage.setItem('huntrecipes.returnUrl', '/account?goto=security');
            localStorage.setItem('huntrecipes.remember', '0');
            message.className = 'alert alert-success mt-3';
            message.textContent = 'Check your email to confirm this identity change.';
            message.hidden = false;
        } catch (error) {
            message.className = 'alert alert-danger mt-3';
            message.textContent = 'Unable to begin the identity change. Please try again.';
            message.hidden = false;
        }
    };
    account.querySelector('[data-link-google]')?.addEventListener('click', () => begin('link-google'));
    account.querySelector('[data-unlink-google]')?.addEventListener('click', () => begin('unlink-google'));
    axios.get('/api/v1/me/providers').then(response => {
        const providers = response.data.data.providers || [];
        account.querySelector('[data-link-google]').hidden = providers.includes('google.com');
        account.querySelector('[data-unlink-google]').hidden = !providers.includes('google.com') || providers.length < 2;
    }).catch(() => {});
};

export default function initializeAuth() {
    initializeSignIn();
    initializeEmailFinish();
    initializeProviderManagement();
}
