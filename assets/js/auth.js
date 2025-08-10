import { initializeApp } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-app.js";
import { getAuth, onAuthStateChanged, GoogleAuthProvider, signInWithPopup } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-auth.js";
import { firebaseConfig } from './firebase-config.js';

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);

const googleProvider = new GoogleAuthProvider();

document.getElementById('google-login').addEventListener('click', () => {
    signInWithPopup(auth, googleProvider)
        .then((result) => {
            const user = result.user;
            const idToken = user.getIdToken();

            fetch('/api/firebase-auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ idToken: idToken })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = 'index.php';
                } else {
                    console.error('Backend authentication failed:', data.message);
                }
            });
        }).catch((error) => {
            console.error('Google Sign-In Error:', error);
        });
});

onAuthStateChanged(auth, (user) => {
    if (user) {
        // User is signed in.
    } else {
        // User is signed out.
    }
});