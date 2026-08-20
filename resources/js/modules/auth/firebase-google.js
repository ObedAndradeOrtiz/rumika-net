import { initializeApp } from 'firebase/app';
import { getAnalytics, isSupported } from 'firebase/analytics';
import { getAuth, GoogleAuthProvider, signInWithPopup } from 'firebase/auth';

const googleButtons = document.querySelectorAll('[data-firebase-google]');

if (googleButtons.length) {
    const firebaseConfig = {
        apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
        authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
        projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
        storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
        messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
        appId: import.meta.env.VITE_FIREBASE_APP_ID,
        measurementId: import.meta.env.VITE_FIREBASE_MEASUREMENT_ID,
    };

    const app = initializeApp(firebaseConfig);
    const auth = getAuth(app);
    const provider = new GoogleAuthProvider();

    provider.setCustomParameters({
        prompt: 'select_account',
    });

    isSupported().then((supported) => {
        if (supported && firebaseConfig.measurementId) {
            getAnalytics(app);
        }
    });

    googleButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const errorTarget = document.querySelector(button.dataset.errorTarget || '[data-google-error]');
            const originalText = button.querySelector('[data-google-label]')?.textContent;

            button.disabled = true;
            button.classList.add('is-loading');

            if (button.querySelector('[data-google-label]')) {
                button.querySelector('[data-google-label]').textContent = 'Conectando con Google...';
            }

            if (errorTarget) {
                errorTarget.hidden = true;
                errorTarget.textContent = '';
            }

            try {
                const credential = await signInWithPopup(auth, provider);
                const idToken = await credential.user.getIdToken();
                const response = await window.axios.post(button.dataset.authUrl, {
                    id_token: idToken,
                });

                window.location.href = response.data.redirect || '/dashboard';
            } catch (error) {
                const message = error.response?.data?.message
                    || Object.values(error.response?.data?.errors || {})?.flat()?.[0]
                    || 'No se pudo iniciar sesion con Google. Intenta de nuevo.';

                if (errorTarget) {
                    errorTarget.hidden = false;
                    errorTarget.textContent = message;
                }

                button.disabled = false;
                button.classList.remove('is-loading');

                if (button.querySelector('[data-google-label]') && originalText) {
                    button.querySelector('[data-google-label]').textContent = originalText;
                }
            }
        });
    });
}
