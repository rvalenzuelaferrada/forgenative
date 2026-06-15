# ForgeNative

ForgeNative is a small macOS menu bar app for keeping an eye on Laravel Forge without opening a browser. It connects to one or more Forge API tokens, lists the sites available to each connection, shows the selected organization, displays each site's PHP version, and highlights deployment health directly from the menu bar.

The app is built with Laravel, Inertia, Vue, Tailwind CSS, and NativePHP Desktop.

## What it does

- Runs as a menu bar app instead of a full desktop window.
- Lets you add multiple Laravel Forge connections.
- Validates each API token with Forge before storing it.
- Lists visible Forge organizations and sites for the selected connection.
- Shows site URLs, PHP versions, and deployment health.
- Polls the dashboard every 60 seconds while it is open.
- Scans Forge deployments in the background every minute.
- Updates the menu bar icon and tooltip when deployments are healthy, deploying, or failed.
- Supports English and Spanish interface copy.

## How it works

1. On first launch, choose a language and add a Laravel Forge API token.
2. ForgeNative verifies the token directly with Laravel Forge.
3. The token is stored locally after encryption.
4. The menu bar panel loads your Forge organizations, servers, sites, and latest deployment status.
5. The tray icon changes color based on the deployment health found across your connected accounts.

ForgeNative does not deploy code or modify Forge resources. Its current workflow is focused on visibility and deployment status monitoring.

## Security

ForgeNative is designed to keep Forge tokens local to your machine.

- Tokens are never shown again after being saved.
- Tokens are validated directly against Laravel Forge before storage.
- Duplicate tokens are detected with a SHA-256 fingerprint.
- When running as a native app, tokens are encrypted with the operating system's secure storage through NativePHP/Electron.
- When running in a non-native Laravel environment, tokens fall back to Laravel encrypted strings.
- Forge API calls use bearer token authentication over HTTPS.
- The app only stores connection metadata such as the connection name, Forge user ID, Forge email, encrypted token, token fingerprint, and verification timestamp.

For best results, create a dedicated Forge API token for ForgeNative and give it only the permissions required to read organizations, servers, sites, and deployments.

## Download

Prebuilt macOS artifacts will be published on the GitHub Releases page. Download the build that matches your Mac:

- Apple Silicon: `arm64`
- Intel: `x64`

If macOS blocks the app because it was downloaded from the internet, open it from System Settings > Privacy & Security after confirming you trust the build source.

## Local development

Requirements:

- PHP 8.4 or compatible PHP 8.3+
- Composer
- Node.js 22+
- npm
- SQLite

Install the app:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
```

Run the NativePHP desktop app in development:

```bash
composer run native:dev
```

For regular Laravel/Vite development in a browser:

```bash
composer run dev
```

## Building macOS releases

Build frontend assets first:

```bash
npm run build
```

Build for Apple Silicon:

```bash
php artisan native:build mac arm64 --no-interaction
```

Build for Intel:

```bash
php artisan native:build mac x64 --no-interaction
```

Generated release files are written to:

```text
nativephp/electron/dist
```

Upload the generated macOS files from that directory to the GitHub Releases page.

## Useful commands

```bash
# Run tests
php artisan test --compact

# Run PHP formatting
vendor/bin/pint --dirty --format agent

# Check TypeScript
npm run types:check

# Reset NativePHP build output
php artisan native:reset --no-interaction
```

## Tech stack

- Laravel 13
- Inertia.js 3
- Vue 3
- Tailwind CSS 4
- Laravel Wayfinder
- Laravel Forge SDK
- NativePHP Desktop
- Electron
- Pest

