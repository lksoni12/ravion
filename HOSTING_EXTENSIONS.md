# Ravion Pharma Admin - PHP Extension Requirements

## Required (must be enabled)

- `mysqli` — required for app database connection (`DBDriver=MySQLi`).
- `fileinfo` — required for secure file upload MIME handling.

If either is missing, app startup now shows a clear error from `app/Support/runtime_compat.php`.

## Optional (auto fallback applied)

- `mbstring` — app loads `app/Support/mbstring_polyfill.php` when extension is not available.
- `intl` — app loads `app/Support/intl_polyfill.php` when extension is not available.

`intl` fallback includes compatibility for:
- `Locale`
- `Normalizer`
- `idn_to_ascii`
- `idn_to_utf8`

## Recommended to enable in hosting anyway

Even with fallback, enabling real extensions gives better correctness/performance:
- `mbstring`
- `intl`

## cPanel quick path

- Open **Select PHP Version** (or **PHP Extensions**)
- Enable: `mysqli`, `fileinfo`, `mbstring`, `intl`
- Save and restart PHP handler (if hosting provides option)
