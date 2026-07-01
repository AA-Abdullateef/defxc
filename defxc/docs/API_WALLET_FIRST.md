# DEFXC Wallet-First API Refactor

## Architecture

Wallet identity is the primary authentication provider. A wallet identity is stored in `wallets` with:

- `mnemonic_hash`
- `fingerprint`
- `public_key`
- nullable `user_id`

Plaintext mnemonics are never stored. The mnemonic is shown only during generation, cached temporarily for challenge verification, and then discarded.

`users` and `profiles` are the account layer. They are created only after the wallet holder completes profile registration. Transactions continue to belong to `users.id`.

## Public / Onboarding Endpoints

- `POST /api/v1/wallet/generate`
- `POST /api/v1/wallet/challenge`
- `POST /api/v1/wallet/verify`
- `POST /api/v1/wallet/import`
- `GET /api/v1/countries`
- `GET /api/v1/methods`
- `GET /api/v1/submethods`
- `POST /api/v1/login` - backup only
- `POST /api/v1/forgot-password`
- `POST /api/v1/verify-reset-otp`
- `POST /api/v1/reset-password`

`POST /api/v1/register-profile` requires a wallet Sanctum token. It creates the user/profile account layer, attaches the wallet, sends the email OTP, revokes the wallet token, and returns a user token.

## Protected User Endpoints

- `GET /api/v1/me`
- `POST /api/v1/profile/photo`
- all dashboard, asset, deposit, withdrawal, transfer, transaction, wallet connection, password, settings, and 2FA routes

Financial routes use `EnsureOnboardingCompleted`. The middleware blocks access when:

- the token is a wallet token without an attached user
- `profile_completed` is false
- `email_verified_at` is null

## Removed Legacy API Surface

- `POST /api/v1/register`
- `POST /api/v1/admin/transactions/complete`
- `POST /api/v1/admin/transactions/update-status`

Admin transaction actions remain in the Blade admin surface.

## Migration Path

1. `2026_06_20_000000_make_users_wallet_first`
   - makes `username`, `email`, and `password` nullable
   - adds `profile_completed`
   - marks existing users with username/email as complete

2. `2026_06_21_000000_harden_wallet_identity_storage`
   - backfills `mnemonic_hash` and `public_key` from the old encrypted mnemonic column
   - removes `wallets.mnemonic`
   - makes `wallets.user_id` nullable

Legacy seeded duplicate mnemonics are not valid wallet identities. Production data should have one mnemonic per wallet identity.
