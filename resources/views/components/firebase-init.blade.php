{{--
    Initializes Firebase JS once. Exposes:
      window.__firebaseApp
      window.__db
      window.__storage
      window.__teacherUid
      event 'firebase-ready'
--}}
<script type="module">
    import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js";
    import { getFirestore } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";
    import { getStorage } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-storage.js";

    const firebaseConfig = @json(config('services.firebase', []));

    window.__firebaseApp = initializeApp(firebaseConfig);
    window.__db = getFirestore(window.__firebaseApp);
    window.__storage = getStorage(window.__firebaseApp);
    window.__teacherUid = @json(session('auth_uid') ?: session('firebase_uid'));

    window.dispatchEvent(new CustomEvent('firebase-ready'));
</script>
