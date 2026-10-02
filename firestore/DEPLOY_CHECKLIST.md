# Week 5 Deploy Checklist — Firestore Inquiries

Use with `firestore/FIRESTORE_SETUP.md`.

## Your steps (manual)
1. Create Firebase project + web app
2. Create Firestore database
3. Paste config into `assets/js/firebase-config.js`
4. Publish rules from `firestore/firestore.rules`
5. Test on localhost: `/lipabyte/inquiries.php`
6. Upload only the safe file list below
7. Test live Inquiries CRUD + confirm Marketplace still works

## Safe InfinityFree upload list
- `inquiries.php`
- `assets/js/firebase-config.js`
- `assets/js/inquiries.js`
- `assets/css/style.css`
- `includes/header.php`
- `includes/footer.php`
- `firestore/firestore.rules` (reference; apply in Firebase Console)
- `firestore/FIRESTORE_SETUP.md` (optional)

## Do not touch
- `config/database.local.php`
- MySQL / phpMyAdmin tables
- Existing rental, listing, message, or review data

## Done when
- [ ] Create / Read / Update / Delete work on Inquiries
- [ ] Filter + sort work
- [ ] Firebase Console shows `inquiries` documents
- [ ] Login, Marketplace, Messages still work from MySQL
