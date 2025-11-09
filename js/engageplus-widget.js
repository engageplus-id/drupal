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
      const widgets = widgetSettings.widgets || {};
      const callbackUrl = widgetSettings.callbackUrl || '/engageplus/auth/callback';
      const userInfoUrl = widgetSettings.userInfoUrl || '/engageplus/api/user';
      const debugMode = widgetSettings.debugMode || false;

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
            }

            // Initialize the widget with success/error handlers.
            window.EngagePlus.init({
              ...config,
              onSuccess: function (tokens) {
                handleAuthSuccess(tokens, config);
              },
              onError: function (error) {
                handleAuthError(error, config);
              }
            });
          });
        });
      }

      /**
       * Handle successful authentication.
       */
      function handleAuthSuccess(tokens, config) {
        if (debugMode) {
          console.log('EngagePlus: Authentication successful', tokens);
        }

        // Send user data to Drupal backend to create/login user.
        fetch(userInfoUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            accessToken: tokens.accessToken,
            refreshToken: tokens.refreshToken || null,
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

