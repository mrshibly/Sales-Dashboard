// Import the functions you need from the SDKs you need
import { initializeApp } from "firebase/app";
import { getAnalytics } from "firebase/analytics";
// TODO: Add SDKs for Firebase products that you want to use
// https://firebase.google.com/docs/web/setup#available-libraries

// Your web app's Firebase configuration
// For Firebase JS SDK v7.20.0 and later, measurementId is optional
const firebaseConfig = {
  apiKey: "AIzaSyCR2VH7JOGI-_zr14SqgJ-k-9Vq30isfbU",
  authDomain: "srms-87697.firebaseapp.com",
  projectId: "srms-87697",
  storageBucket: "srms-87697.firebasestorage.app",
  messagingSenderId: "821176450677",
  appId: "1:821176450677:web:8975c5cd6db1c08f0e423c",
  measurementId: "G-DFES6KEC37"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const analytics = getAnalytics(app);