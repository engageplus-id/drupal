# Security Policy

## Credential Safety

This Drupal module is designed with security in mind and does **NOT** contain any hardcoded credentials, API keys, or secrets.

### ✅ What is Safe to Commit

- Configuration schema files (with empty/default values)
- Code files (PHP, JavaScript, CSS)
- Documentation (README, CHANGELOG)
- Template files
- Default settings with empty credentials

### ❌ Never Commit These

- `.env` files with actual credentials
- Database credentials
- API keys or secrets
- OAuth client secrets (only client IDs are public)
- AWS credentials
- SSH private keys
- Personal access tokens

## Configuration Security

All sensitive configuration is stored in Drupal's configuration system and should be:

1. **Client ID** - Public identifier (safe to expose)
2. **API Base URL** - Public URL (safe to expose)
3. **User credentials** - Stored encrypted in Drupal database (never in code)

### EngagePlus Client ID

The EngagePlus `client_id` (e.g., `ep_8p6t8yr68vamh517xqs`) is a **public identifier** and is safe to expose. It is:
- Visible in browser JavaScript
- Used in public API calls
- Similar to OAuth 2.0 client IDs
- **Not a secret** - does not grant access by itself

### What EngagePlus Does NOT Require

- ❌ No client secret (no OAuth client secret needed)
- ❌ No API secret key
- ❌ No private keys
- ❌ No database passwords in code
- ❌ No environment variables with credentials

## Security Best Practices

### For This Module

1. **Configuration** - All sensitive config is stored in Drupal's config system
2. **No Hardcoded Secrets** - All credentials entered via admin UI
3. **HTTPS Required** - Module enforces HTTPS for OAuth
4. **JWT Validation** - User tokens are validated server-side
5. **Session Management** - Proper session cleanup on logout

### For Your Drupal Installation

1. **Protect `settings.php`** - Contains database credentials
2. **Secure `.env` files** - If using environment variables
3. **Restrict file permissions** - Protect config directories
4. **Use `.gitignore`** - Exclude sensitive files from version control
5. **Rotate credentials regularly** - Change passwords periodically

## What We Scanned For

This repository has been scanned and verified to contain NO:

- ✅ AWS credentials (access keys, secret keys)
- ✅ Database credentials
- ✅ API keys or secrets
- ✅ Private keys (.pem, .key files)
- ✅ OAuth client secrets
- ✅ GitHub tokens
- ✅ URLs with embedded credentials
- ✅ Environment variable files (.env)
- ✅ Hardcoded passwords
- ✅ Stripe keys
- ✅ JWT secrets

## .gitignore Protection

The `.gitignore` file is configured to exclude:

- Vendor dependencies (`vendor/`)
- IDE configuration files (`.idea/`, `.vscode/`)
- OS-specific files (`.DS_Store`)
- Build artifacts
- Temporary files

## Reporting Security Issues

If you discover a security vulnerability in this module:

1. **Do NOT** open a public issue
2. Email: support@engageplus.id
3. Include:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

We will respond within 48 hours and work on a fix.

## Security Updates

- Always use the latest version of this module
- Subscribe to security advisories on Drupal.org
- Run `drush updb` after updating
- Clear caches after security updates

## Compliance

This module:

- Stores user data only in YOUR Drupal database
- Does not send credentials to third parties
- Uses HTTPS for all OAuth communications
- Implements short-lived access tokens (1 hour expiry)
- Supports email verification from OAuth providers
- Follows Drupal security best practices

## License

This module is open source and can be audited by anyone. All code is transparent and available for security review.

---

**Last Security Audit:** 2025-11-09  
**Status:** ✅ No credentials or secrets found  
**Next Audit:** Recommended quarterly
