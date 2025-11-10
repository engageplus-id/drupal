/**
 * @file
 * EngagePlus widget initialization and handling.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Initialize EngagePlus widgets on the page.
   */
  Drupal.behaviors.engagePlusWidget = {
    attach: function (context, settings) {
      const widgetSettings = settings.engageplus || {};
      const debugMode = widgetSettings.debugMode || false;
      
      // Don't initialize the widget on the callback page - let callback.js handle it
      if (widgetSettings.callback) {
        if (debugMode) {
          console.log('EngagePlus: Skipping main widget initialization on callback page');
        }
        return;
      }
      
      const widgets = widgetSettings.widgets || {};
      const callbackUrl = widgetSettings.callbackUrl || '/engageplus/auth/callback';
      const userInfoUrl = widgetSettings.userInfoUrl || '/engageplus/api/user';

      // Load the EngagePlus widget script if not already loaded.
      if (typeof window.EngagePlus === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://engageplus.id/widget.js';
        script.async = true;
        script.onload = function () {
          initializeWidgets();
        };
        script.onerror = function () {
          console.error('EngagePlus: Failed to load widget script');
        };
        document.head.appendChild(script);
      } else {
        // Widget script already loaded, initialize immediately.
        initializeWidgets();
      }

      /**
       * Initialize all widgets on the page.
       */
      function initializeWidgets() {
        Object.keys(widgets).forEach(function (containerId) {
          const container = document.getElementById(containerId);
          
          if (!container) {
            if (debugMode) {
              console.warn('EngagePlus: Container not found:', containerId);
            }
            return;
          }

          // Check if already initialized.
          once('engageplus-widget', container, context).forEach(function (element) {
            const config = widgets[containerId];
            
            if (debugMode) {
              console.log('EngagePlus: Initializing widget', containerId, config);
              console.log('EngagePlus: issuer set to:', config.issuer || 'NOT SET (will default to window.location.origin)');
            }

            // Initialize the widget with all callbacks.
            window.EngagePlus.init({
              ...config,
              // onSuccess is for compatibility, onLogin is the widget's preferred callback
              onSuccess: function (result) {
                handleAuthSuccess(result, config);
              },
              onLogin: function (result) {
                handleAuthSuccess(result, config);
              },
              onLogout: function (user) {
                handleAuthLogout(user, config);
              },
              onError: function (error) {
                handleAuthError(error, config);
              }
            });
            
            // Check if user is already authenticated on page load
            if (window.EngagePlus.isAuthenticated && window.EngagePlus.isAuthenticated()) {
              if (debugMode) {
                const user = window.EngagePlus.getUser();
                console.log('EngagePlus: User already authenticated:', user);
              }
            }
          });
        });
      }

      /**
       * Handle successful authentication.
       */
      function handleAuthSuccess(result, config) {
        if (debugMode) {
          console.log('EngagePlus: Authentication successful', result);
          console.log('EngagePlus: Token structure:', JSON.stringify(result, null, 2));
        }

        // Extract tokens and user data from result object
        // The widget returns {tokens: {access_token, id_token, refresh_token}, user: {...}, provider: '...'}
        var tokens = result.tokens || result;
        var accessToken = tokens.access_token || tokens.accessToken;
        var idToken = tokens.id_token || tokens.idToken;
        var refreshToken = tokens.refresh_token || tokens.refreshToken || null;
        var userData = result.user || null;

        if (!idToken) {
          console.error('EngagePlus: No ID token found in result', result);
          throw new Error('Missing ID token');
        }

        if (!userData || !userData.email) {
          console.error('EngagePlus: No user data found in result', result);
          throw new Error('Missing user data');
        }

        if (debugMode) {
          console.log('EngagePlus: Sending tokens and user data to Drupal backend');
        }

        // Send tokens and user data to Drupal backend to create/login user.
        fetch(userInfoUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            idToken: idToken,
            accessToken: accessToken,
            refreshToken: refreshToken,
            user: userData,
            provider: result.provider || 'unknown',
          }),
        })
          .then(function (response) {
            return response.json();
          })
          .then(function (data) {
            if (data.success) {
              if (debugMode) {
                console.log('EngagePlus: User logged in', data);
              }

              // Show success message.
              showMessage('Successfully logged in as ' + data.username, 'status');

              // Store the access token in session storage.
              sessionStorage.setItem('engageplus_token', tokens.accessToken);

              // Redirect if specified.
              if (data.redirect && data.redirect !== 'current') {
                setTimeout(function () {
                  window.location.href = data.redirect;
                }, 1000);
              } else {
                // Reload the current page to reflect logged-in state.
                setTimeout(function () {
                  window.location.reload();
                }, 1000);
              }
            } else {
              throw new Error(data.error || 'Authentication failed');
            }
          })
          .catch(function (error) {
            console.error('EngagePlus: Error processing authentication', error);
            showMessage('Authentication failed: ' + error.message, 'error');
          });
      }

      /**
       * Handle authentication errors.
       */
      function handleAuthError(error, config) {
        console.error('EngagePlus: Authentication error', error);
        showMessage('Authentication failed: ' + (error.message || 'Unknown error'), 'error');
      }

      /**
       * Handle user logout.
       */
      function handleAuthLogout(user, config) {
        if (debugMode) {
          console.log('EngagePlus: User logged out', user);
        }

        // Clear any Drupal-side session data
        // The EngagePlus.logout() method has already cleared widget session/localStorage
        
        // Optionally reload the page to show logged-out state
        setTimeout(function () {
          window.location.reload();
        }, 500);
      }

      /**
       * Show a message to the user.
       */
      function showMessage(message, type) {
        // Create Drupal-style message.
        const messageDiv = document.createElement('div');
        messageDiv.className = 'messages messages--' + type;
        messageDiv.setAttribute('role', 'contentinfo');
        messageDiv.setAttribute('aria-label', type === 'error' ? 'Error message' : 'Status message');
        messageDiv.innerHTML = message;

        // Find a place to insert the message.
        const mainContent = document.querySelector('main') || document.querySelector('.region-content') || document.body;
        mainContent.insertBefore(messageDiv, mainContent.firstChild);

        // Auto-remove after 5 seconds.
        setTimeout(function () {
          messageDiv.remove();
        }, 5000);
      }
    }
  };

})(Drupal, drupalSettings, once);

