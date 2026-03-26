// Import the functions you need from the SDKs you need
import { initializeApp } from "firebase/app";
import { getAnalytics } from "firebase/analytics";
// TODO: Add SDKs for Firebase products that you want to use
// https://firebase.google.com/docs/web/setup#available-libraries

// Your web app's Firebase configuration
// For Firebase JS SDK v7.20.0 and later, measurementId is optional
const firebaseConfig = {
  apiKey: "AIzaSyBJWEAAQ2mfg7FJazXTgNoZxxGYx_Wc4Wk",
  authDomain: "fleur-c-print-34a2d.firebaseapp.com",
  projectId: "fleur-c-print-34a2d",
  storageBucket: "fleur-c-print-34a2d.firebasestorage.app",
  messagingSenderId: "991723369051",
  appId: "1:991723369051:web:dcfafa7681edde8c4e857e",
  measurementId: "G-6C87BXTTVX"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const analytics = getAnalytics(app);