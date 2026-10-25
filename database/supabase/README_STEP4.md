# Step 4 — Supabase Auth (teacher web)

## Dashboard setup (required)

1. **Authentication → Providers → Google** → Enable  
2. Create OAuth credentials in [Google Cloud Console](https://console.cloud.google.com/):
   - Type: Web application  
   - Authorized JavaScript origins: your app origin (e.g. `http://localhost:8000`)  
   - Authorized redirect URIs: **copy the exact redirect URL shown in the Supabase Google provider panel**  
     (looks like `https://nvgbfiavbgupbwhsliqr.supabase.co/auth/v1/callback`)  
3. Paste Client ID + Client Secret into Supabase Google provider → Save  

4. **Authentication → URL configuration**
   - Site URL: your app (e.g. `http://localhost:8000`)  
   - Redirect URLs: include  
     `http://localhost:8000/auth/supabase/callback`  
     (and production URL when deployed)

5. Run SQL: `database/supabase/002_step4_auth_profiles.sql`  
   (auto-creates `profiles` on signup + basic RLS)

## App behavior

| Action | Backend |
|--------|---------|
| Continue with Google | Supabase OAuth → `/auth/supabase/callback` → Laravel session |
| Email / password | Supabase Auth API via `LoginController` |
| Session keys | `auth_uid` (UUID), `firebase_uid` alias (same value for old middleware) |

## Important

- Teacher **web** Auth is now Supabase.  
- **Firestore data still uses old Firebase UIDs** — class lists may be empty until Step 5 (data layer) / user id mapping.  
- Mobile app still uses Firebase Auth until a later step.
