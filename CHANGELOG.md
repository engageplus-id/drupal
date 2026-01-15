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
- Database update hook (8101) to set api_base_url for existing installations
- Enhanced debug logging to show configured issuer URL
- Comprehensive troubleshooting guide in README

### Fixed
- Widget now correctly uses EngagePlus API URL instead of Drupal site URL for API calls
- Widget configuration now uses correct `issuer` parameter (was incorrectly using `baseUrl`)
- Widget configuration now includes `redirectUri` parameter to ensure proper OAuth callback
- Callback page now properly loads and initializes widget.js to process OAuth tokens
- Callback handler now uses correct clientId from Drupal configuration (not placeholder)
- Callback handler now correctly extracts tokens from widget response object structure
- Fixed token extraction to handle both `{tokens: {...}}` and direct token object formats
- Fixed duplicate widget initialization on callback page (now skips main widget init)
- Now sends id_token and user data directly instead of trying to decode access_token as JWT
- Controller now uses user data from widget response instead of decoding tokens
- Access token is opaque, not JWT - using id_token for user data instead
- Prevents CORS errors from duplicate token exchange attempts
- Fallback logic ensures api_base_url always has a value for upgraded installations

### Changed
- Configuration form now displays callback URL prominently with copy functionality
- API Base URL is now configurable (defaults to https://engageplus.id)
- Debug logging now shows issuer parameter value

## [1.1.0] - 2025-11-09

### Added
- Widget customization settings in admin configuration
  * Layout & Sizing: width, max width, padding
  * Colors: background, primary, text, secondary text, button hover
  * Borders & Shadows: border radius, border color, border width, box shadow, button border radius
  * Typography: font family
- Link to EngagePlus widget customization documentation in admin form
- Automatic conversion of settings to widget styles object
- Support for all styling options from EngagePlus widget API

### Fixed
- Removed persistent loading spinner from widget container
- Widget template now renders empty container for widget.js to populate
- Loading class no longer applied by default

### Changed
- Widget Appearance section in config form now collapsed by default
- Organized style settings into collapsible sub-sections (Layout, Colors, Borders, Typography)
- Configuration schema updated to include all style settings

## [1.2.0] - 2025-11-09

### Added
- Full support for EngagePlus widget callbacks per official documentation
  * onLogin callback support (in addition to onSuccess)
  * onLogout callback with automatic page reload
  * Proper integration of all widget lifecycle events
- Authentication mode configuration (redirect vs popup)
- Automatic user state detection on page load using isAuthenticated()
- Enhanced logout functionality
  * Calls EngagePlus.logout() to clear widget session/localStorage
  * Clears all tokens (access_token, id_token, refresh_token)
  * Then performs Drupal logout
- Check for existing authenticated sessions on page load
- Debug logging for authenticated user state

### Changed
- Logout button now properly calls EngagePlus.logout() before Drupal logout
- Widget initialization now checks for existing authentication
- Callback page supports both onSuccess and onLogin callbacks

### Documentation
- Aligned implementation with https://engageplus.id/docs/widget
- Added support for all documented widget methods and callbacks

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

