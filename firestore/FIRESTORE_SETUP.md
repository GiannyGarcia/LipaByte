# Firestore Setup — LipaByte Device Inquiries (Week 5)

Do these steps in your browser. Cursor cannot log into Firebase for you.

Your MySQL / InfinityFree data is **not** migrated or deleted. This module only uses Firestore collection `inquiries`.

## 1. Create Firebase project
1. Open [Firebase Console](https://console.firebase.google.com/)
2. Click **Add project** (or Create a project)
3. Name it e.g. `LipaByte-IPT102`
4. You can disable Google Analytics for class work
5. Finish project creation

## 2. Register a Web app
1. In the project overview, click the **Web** icon (`</>`)
2. App nickname: `LipaByte Inquiries`
3. Do **not** require Firebase Hosting for this lab
4. Click **Register app**
5. Copy the `firebaseConfig` object values

## 3. Create Cloud Firestore
1. Left menu → **Build** → **Firestore Database**
2. Click **Create database**
3. Start in **test mode** for first-time setup (we will replace rules next)
4. Choose a location (prefer Asia if available, e.g. `asia-southeast1`)
5. Enable

## 4. Paste config into LipaByte
1. Open `assets/js/firebase-config.js`
2. Replace every `PASTE_...` value with your real Firebase web config:
   - `apiKey`
   - `authDomain`
   - `projectId`
   - `storageBucket`
   - `messagingSenderId`
   - `appId`
3. Save the file

Use `assets/js/firebase-config.example.js` only as a format reference.

## 5. Publish Security Rules
1. Firebase Console → **Firestore** → **Rules**
2. Replace the editor contents with the file:
   `firestore/firestore.rules`
3. Click **Publish**

These rules allow CRUD only on `/inquiries/{id}` and deny other paths.

## 6. Test locally
1. Make sure XAMPP Apache + MySQL are running (MySQL still powers login)
2. Log in to LipaByte: `http://localhost/lipabyte/auth/login.php`
3. Open: `http://localhost/lipabyte/inquiries.php`
4. Submit a test inquiry
5. Confirm it appears in Firebase Console → Firestore → `inquiries`
6. Test Edit status, Filter, Sort, and Delete

## 7. Upload to InfinityFree (safe list only)
Upload/replace **only**:

| Path |
|------|
| `inquiries.php` |
| `assets/js/firebase-config.js` *(with real keys)* |
| `assets/js/inquiries.js` |
| `assets/css/style.css` |
| `includes/header.php` |
| `firestore/` *(optional docs/rules for your records)* |

**Do not overwrite** `config/database.local.php` or wipe MySQL tables.

## 8. Live verify
1. Log in on: `https://lipabytewebapp.freedev.app/lipabyte/`
2. Open **Inquiries** in the nav
3. Create / edit / filter / sort / delete one record
4. Confirm Marketplace, Messages, and listings still work (MySQL unchanged)

## Troubleshooting
| Problem | Fix |
|---------|-----|
| Yellow setup alert on Inquiries page | `firebase-config.js` still has `PASTE_...` placeholders |
| Permission denied in console | Publish `firestore.rules` again |
| Login works but Inquiries blank | Hard-refresh (Ctrl+F5); check browser console |
| Afraid of losing rental data | Stop — this feature never writes to MySQL |

## Week 5 demo checklist
- [ ] Firestore database created
- [ ] Web app connected via Firebase SDK
- [ ] Create inquiry works
- [ ] Read/list works
- [ ] Update status/content works
- [ ] Delete with confirm works
- [ ] Filter by status/category works
- [ ] Sort newest/oldest works
- [ ] Existing LipaByte MySQL features still work
