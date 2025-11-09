# EngagePlus for Drupal

Lightweight OAuth authentication widget that syncs users directly to your Drupal database. No more managing complex identity providers or duplicating user data.

## Features

- **3-Line Integration**: Drop-in OAuth widget that just works
- **Direct Database Sync**: Users stored in YOUR Drupal database, not a third-party service
- **Multiple OAuth Providers**: Google, GitHub, Microsoft, LinkedIn, and custom OIDC providers
- **Automatic User Creation**: Create Drupal accounts automatically on first login
- **Flexible Block Placement**: Place the widget anywhere on your site using Drupal's block system
- **Customizable Appearance**: Control widget theme, button text, and styling
- **No Vendor Lock-in**: Minimal dependencies, maximum control

## Why EngagePlus vs Auth0/Clerk/Firebase?

| Feature | EngagePlus | Auth0/Clerk/Firebase |
|---------|------------|---------------------|
| User data storage | **Your Drupal database only** | Their database (you duplicate) |
| Integration complexity | **3 lines + block placement** | SDK setup, API calls, webhooks |
| Backend required? | **No** (Drupal handles it) | Yes (for user management) |
| Vendor lock-in | **Minimal** (just OAuth widget) | High (user data, APIs) |
| Data ownership | **100% yours** | Shared with provider |
| Cost for 10k users | **$29-99/mo** | $200-1000+/mo |

## Requirements

- Drupal 9.x, 10.x, or 11.x
- PHP 7.4 or higher
- EngagePlus account (free trial available at [engageplus.id](https://engageplus.id))

## Installation

### Using Composer (Recommended)

```bash
composer require drupal/engageplus
```

### Manual Installation

1. Download the module from Drupal.org or GitHub
2. Extract to `/modules/contrib/engageplus`
3. Enable the module:

```bash
drush en engageplus
```

Or enable via the Drupal admin interface at `/admin/modules`

## Configuration

### Step 1: Set Up EngagePlus Account

1. Create an account at [engageplus.id](https://engageplus.id)
2. Configure your OAuth providers (Google, GitHub, Microsoft, LinkedIn) in the EngagePlus dashboard
3. Configure your widget layout and styling
4. Copy your **Client ID** from the dashboard

### Step 2: Configure the Drupal Module

1. Navigate to **Configuration** > **People** > **EngagePlus Settings** (`/admin/config/people/engageplus`)
2. Enter your **Client ID**
3. Configure user creation settings:
   - **Automatically create users**: Enable to create Drupal accounts for new OAuth users
   - **Default role**: Choose which role to assign to new users
   - **Username pattern**: Use `[email]` for email-based usernames or `[name]` for display name
   - **Skip email verification**: Recommended - OAuth providers verify emails
4. Customize widget appearance (optional):
   - Button text
   - Theme (light/dark)
   - Show provider labels
5. Save configuration

### Step 3: Add the Widget to Your Site

1. Navigate to **Structure** > **Block layout** (`/admin/structure/block`)
2. Click **Place block** in the desired region (e.g., Sidebar, Header, Content)
3. Find **EngagePlus Widget** and click **Place block**
4. Configure block settings:
   - **Container ID**: Unique ID for this widget instance (default: `engageplus-widget`)
   - **Button Text**: Custom button text (overrides global setting)
   - **Theme**: Widget theme (overrides global setting)
   - **Hide for authenticated users**: Hide widget when user is logged in
   - **Show logout button**: Show logout button for logged-in users
5. Save block

### Step 4: Configure Redirect URIs in EngagePlus Dashboard

1. Go to your EngagePlus dashboard
2. Add the following URL as a redirect URI:
   ```
   https://yoursite.com/engageplus/auth/callback
   ```
3. Replace `yoursite.com` with your actual domain

## Usage

### Basic Usage

Once configured, the widget will appear in the block region you selected. Users can:

1. Click the widget to choose an OAuth provider
2. Authenticate with the provider (Google, GitHub, etc.)
3. Be automatically logged into your Drupal site
4. Have their account created if it doesn't exist

### Multiple Widgets

You can place multiple widgets on your site. Each widget instance can have its own configuration:

1. Create multiple blocks from the EngagePlus Widget
2. Give each a unique **Container ID**
3. Customize appearance per block

### User Management

#### Automatic User Creation

When enabled, new users are automatically created with:
- Email from OAuth provider
- Username based on your pattern (`[email]` or `[name]`)
- Default role as configured
- Email automatically verified (trusted OAuth providers)

#### Existing Users

If a user already exists with the same email:
- They are automatically logged in
- No duplicate account is created
- Works seamlessly with existing user base

### Customization

#### Widget Appearance

Customize in **EngagePlus Settings** or per-block:
- **Button Text**: Change "Login" to "Sign In", "Get Started", etc.
- **Theme**: Light or dark theme to match your site
- **Show Labels**: Display provider names next to icons

#### Redirect After Login

Configure where users go after login:
- Leave empty to stay on current page
- Use `<front>` for homepage
- Use any path like `/dashboard` or `/user`

#### Custom Styling

Add custom CSS classes to widget blocks and style with your theme:

```css
.my-custom-widget {
  border: 2px solid #3498db;
  border-radius: 8px;
  padding: 20px;
}
```

## Advanced Features

### Debug Mode

Enable debug mode in **EngagePlus Settings** to:
- Log authentication events to Drupal watchdog
- See detailed error messages
- Troubleshoot integration issues

View logs at `/admin/reports/dblog`

### Username Patterns

Control how usernames are generated:
- `[email]`: Use full email address (e.g., `john@example.com`)
- `[name]`: Use display name from OAuth (e.g., `John Doe`)
- Custom patterns: Combine with text (e.g., `user_[name]`)

Usernames are automatically made unique if duplicates exist.

### Email Verification

OAuth providers (Google, GitHub, etc.) verify email addresses. Enable **Skip email verification** to:
- Trust OAuth provider verification
- Allow immediate login
- Skip Drupal's email verification process

### Programmatic Access

#### Access Token

The EngagePlus widget provides a JWT access token stored in session storage:

```javascript
const token = sessionStorage.getItem('engageplus_token');
```

Use this token for authenticated API calls (expires in 1 hour).

#### User Data Hook

Module developers can use hook_engageplus_user_login() to react to successful authentication:

```php
/**
 * Implements hook_engageplus_user_login().
 */
function mymodule_engageplus_user_login($account, $user_data) {
  // React to user login
  \Drupal::logger('mymodule')->info('User logged in via EngagePlus: @email', [
    '@email' => $account->getEmail(),
  ]);
}
```

## Troubleshooting

### Widget Not Appearing

1. **Check Client ID**: Ensure you've entered your Client ID in settings
2. **Check Block Placement**: Verify the block is enabled and in a visible region
3. **Clear Cache**: Run `drush cr` or clear cache via admin interface
4. **Check Permissions**: Ensure block visibility conditions allow display

### Authentication Fails

1. **Check Redirect URI**: Verify the redirect URI in EngagePlus dashboard matches your Drupal site
2. **Check Browser Console**: Look for JavaScript errors
3. **Enable Debug Mode**: Check Drupal logs at `/admin/reports/dblog`
4. **Verify OAuth Config**: Ensure OAuth providers are configured in EngagePlus dashboard

### Users Not Created

1. **Check Auto-Create Setting**: Ensure "Automatically create users" is enabled
2. **Check Permissions**: Verify Drupal allows user registration
3. **Check Logs**: Look for error messages in watchdog
4. **Email Conflicts**: User may already exist with that email

### Widget Shows Loading Forever

1. **Check Widget URL**: Verify the widget script URL is correct (default: `https://engageplus.id/widget.js`)
2. **Check Network**: Look for network errors in browser console
3. **Check JavaScript**: Ensure JavaScript is enabled in browser
4. **Check CDN**: EngagePlus CDN may be temporarily unavailable

### Widget Calls Wrong API URL (localhost instead of engageplus.id)

If you're seeing API calls to your local site (e.g., `http://localhost:8090/api/widget/public`) instead of `https://engageplus.id/api/widget/public`:

1. **Clear Drupal Cache**:
   ```bash
   drush cr
   ```
   Or via admin interface: Configuration > Development > Clear all caches

2. **Run Database Updates**:
   ```bash
   drush updb
   ```
   Or visit `/update.php` in your browser

3. **Verify API Base URL**: Go to Configuration > EngagePlus and check that "API Base URL" is set to `https://engageplus.id`

4. **Enable Debug Mode**: Enable debug mode in EngagePlus settings and check browser console for:
   ```
   EngagePlus: issuer set to: https://engageplus.id
   ```

5. **Re-save Configuration**: If the above doesn't work, re-save the EngagePlus configuration form to ensure the api_base_url is properly saved

### After Updating the Module

When updating from an earlier version:

1. **Always clear cache** after updating
2. **Run database updates**: `drush updb` or visit `/update.php`
3. **Check configuration**: Verify all settings are still correct
4. **Test the widget**: Ensure authentication still works

## Security

### Data Privacy

- User data is stored ONLY in your Drupal database
- No user data is stored by EngagePlus
- OAuth tokens are short-lived (1 hour expiry)
- HTTPS required for all communications

### Best Practices

1. **Use HTTPS**: Always use HTTPS in production
2. **Regular Updates**: Keep the module updated
3. **Limit Roles**: Assign minimal default role to new users
4. **Monitor Logs**: Regularly check authentication logs
5. **Test Changes**: Test OAuth flow after any configuration changes

## Support

- **Documentation**: [engageplus.id/docs](https://engageplus.id/docs)
- **Issue Queue**: [GitHub Issues](https://github.com/engageplus/drupal-module/issues)
- **Email Support**: support@engageplus.id

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This module is licensed under the GPL-2.0+ license. See LICENSE.txt for details.

## Credits

- **Developed by**: EngagePlus Team
- **Maintained by**: [Your Name/Organization]
- **Sponsored by**: [Your Sponsor]

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and updates.

---

Made with ❤️ by the EngagePlus team. Get started at [engageplus.id](https://engageplus.id)

