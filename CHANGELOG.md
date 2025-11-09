# Changelog

All notable changes to the EngagePlus Drupal module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-11-09

### Added
- Initial release of EngagePlus for Drupal
- OAuth authentication widget integration
- Support for Google, GitHub, Microsoft, LinkedIn, and custom OIDC providers
- Automatic user creation and login
- Block plugin for flexible widget placement
- Admin configuration form for module settings
- Customizable widget appearance (theme, button text, labels)
- User management settings (auto-creation, default roles, username patterns)
- Email verification skip for trusted OAuth providers
- Debug mode for troubleshooting
- Redirect configuration after login
- Multiple widget support with unique container IDs
- Logout button display for authenticated users
- Comprehensive documentation and help text
- Template files for widget and logout display
- CSS styling with dark mode support
- JavaScript integration with EngagePlus widget API
- JWT token handling for user authentication
- Drupal watchdog logging integration

### Security
- HTTPS-only communication with EngagePlus
- JWT token validation
- No user data stored by third parties
- Short-lived access tokens (1 hour expiry)

## [1.0.1] - 2025-11-09

### Added
- Copyable callback URL field in configuration form with one-click copy button
- API Base URL configuration setting for custom EngagePlus instances
- Improved getting started instructions in admin form

### Fixed
- Widget now correctly uses EngagePlus API URL instead of Drupal site URL for API calls
- Widget configuration now includes `baseUrl` parameter to ensure proper API endpoint resolution

### Changed
- Configuration form now displays callback URL prominently with copy functionality
- API Base URL is now configurable (defaults to https://engageplus.id)

## [Unreleased]

### Planned Features
- Multi-language support (i18n)
- Role mapping based on OAuth provider data
- Custom user field mapping
- Webhook support for real-time sync
- Advanced token refresh handling
- Single Sign-On (SSO) integration
- User profile picture sync from OAuth providers
- Custom OAuth scopes configuration
- Integration with Drupal Commerce
- Two-factor authentication support

---

For support and feature requests, please visit [engageplus.id](https://engageplus.id)

