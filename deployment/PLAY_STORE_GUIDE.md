# Google Play Store Publishing Guide: Telegram Price Monitor (PWA / TWA)

This application is fully PWA-enabled with a Web App Manifest ([`public/manifest.json`](file:///home/sanjay/Work/Telegram/public/manifest.json)), Service Worker ([`public/sw.js`](file:///home/sanjay/Work/Telegram/public/sw.js)), and vector launcher icon ([`public/icon.svg`](file:///home/sanjay/Work/Telegram/public/icon.svg)).

Because it is a progressive web application, you can publish it directly to the Google Play Store as a **Trusted Web Activity (TWA)** using Google's official **Bubblewrap CLI**. This requires **no Android development knowledge**, produces a genuine `.aab` (Android App Bundle) file, and passes all Google Play Developer Console requirements.

---

## Prerequisites

1. **Public HTTPS Domain**: Host this Laravel project on a VPS (e.g. Ubuntu + Nginx with a free Let's Encrypt SSL certificate) at your domain (e.g. `https://app.yourdomain.com`).
2. **Google Play Console Account**: A one-time \$25 developer account at [play.google.com/console](https://play.google.com/console).
3. **Node.js 18+ and Java JDK 17+** on your local machine.

---

## Step-by-Step Publishing Process

### Step 1: Install Google's Official Bubblewrap CLI
Open your local terminal and install Bubblewrap globally:
```bash
npm install -g @bubblewrap/cli
```

### Step 2: Initialize Android Package from Your Domain
Run the init wizard pointing to your live URL:
```bash
bubblewrap init --manifest="https://app.yourdomain.com/manifest.json"
```
Bubblewrap will automatically download your `manifest.json`, icon, app name, and theme colors. It will prompt you for:
- **Application Name**: Telegram Price Monitor
- **Package ID / Application ID**: e.g. `com.jaykastabhanjan.pricemonitor`
- **Signing Keystore**: Bubblewrap can auto-generate a secure `.keystore` password for signing your app. Save the keystore file and passwords safely!

### Step 3: Build the Android App Bundle (`.aab`)
Run the build command:
```bash
bubblewrap build
```
This produces `app-release-bundle.aab` in your project folder. This is the exact format required by Google Play Console.

### Step 4: Digital Asset Links (Domain Ownership Verification)
To remove the browser address bar and run full-screen like a native app:
1. Bubblewrap will generate an `assetlinks.json` file containing your SHA-256 fingerprint.
2. Place this file on your server at:
   ```
   https://app.yourdomain.com/.well-known/assetlinks.json
   ```
3. Test domain verification:
   ```bash
   bubblewrap validate --url=https://app.yourdomain.com
   ```

### Step 5: Upload to Google Play Console
1. Go to [Google Play Console](https://play.google.com/console) and click **Create App**.
2. Enter your App Name ("Price Monitor"), default language, and select **Free / Paid**.
3. Under **Production** > **Create new release**, upload your `app-release-bundle.aab`.
4. Fill in the Store Listing details (screenshots of the dashboard, short description, and privacy policy link).
5. Submit for Review! Google typically approves initial releases within 24–48 hours.

Once approved, all users worldwide can download and install the app directly from the Google Play Store onto their Android phones and tablets!

