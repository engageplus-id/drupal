/**
 * @file
 * Handles the EngagePlus authentication callback page.
 * New API using OPWidget class from https://auth.engageplus.id/public/pkce.js
 */

(function (drupalSettings) {
  'use strict';

  // This page is loaded after OAuth redirect from EngagePlus.
  // The EngagePlus widget needs to be loaded to process the callback.
  
  console.log('EngagePlus: Callback page loaded');

  // Get configuration from Drupal settings
  var callbackSettings = drupalSettings.engageplus && drupalSettings.engageplus.callback;
  if (!callbackSettings || !callbackSettings.orgId) {
    console.error('EngagePlus: Missing callback configuration');
    showError('Configuration error: missing organization ID');
    return;
  }

  var debugMode = callbackSettings.debugMode || false;

  if (debugMode) {
    console.log('EngagePlus: Callback configuration', callbackSettings);
  }

  // Check if the widget script is already loaded
  if (typeof window.OPWidget === 'undefined') {
    // Load the EngagePlus widget script
    const widgetUrl = callbackSettings.widgetUrl || 'https://auth.engageplus.id/public/pkce.js';
    const script = document.createElement('script');
    script.src = widgetUrl;
    script.async = false; // Load synchronously for callback
    script.onload = function () {
      console.log('EngagePlus: Widget script loaded on callback page');
      initializeCallback();
    };
    script.onerror = function () {
      console.error('EngagePlus: Failed to load widget script on callback page');
      showError('Failed to load authentication widget');
    };
    document.head.appendChild(script);
  } else {
    // Widget already loaded
    initializeCallback();
  }

  /**
   * Initialize the widget in callback mode
   */
  function initializeCallback() {
    try {
      if (debugMode) {
        console.log('EngagePlus: Initializing callback with config', {
          orgId: callbackSettings.orgId,
          redirectUri: callbackSettings.redirectUri
        });
      }

      // Create new OPWidget instance for callback handling
      const widget = new window.OPWidget({
        orgId: callbackSettings.orgId,
        redirectUri: callbackSettings.redirectUri,
        onSuccess: function(tokens) {
          console.log('EngagePlus: Callback successful, processing result');
          handleAuthSuccess(tokens);
        },
        onError: function(error) {
          console.error('EngagePlus: Callback error', error);
          showError(error.description || error.error || error.message || 'Authentication failed');
        }
      });

      // Note: OPWidget automatically handles callback processing
      // No need to mount for callback - it detects callback URL params
      if (debugMode) {
        console.log('EngagePlus: Callback widget initialized');
      }
    } catch (error) {
      console.error('EngagePlus: Failed to initialize callback handler', error);
      showError('Failed to process authentication callback');
    }
  }

  /**
   * Handle successful authentication from callback
   */
  function handleAuthSuccess(tokens) {
    console.log('EngagePlus: Callback received tokens', tokens);
    
    // Extract tokens and user data from result
    var accessToken = tokens.access_token || tokens.accessToken;
    var idToken = tokens.id_token || tokens.idToken;
    var refreshToken = tokens.refresh_token || tokens.refreshToken || null;
    var userData = tokens.user || null;

    if (!idToken) {
      console.error('EngagePlus: No ID token found in callback result', tokens);
      showError('Missing ID token in authentication response');
      return;
    }

    if (!userData || !userData.email) {
      console.error('EngagePlus: No user data found in callback result', tokens);
      showError('Missing user data in authentication response');
      return;
    }

    if (debugMode) {
      console.log('EngagePlus: Sending tokens and user data to Drupal backend');
    }
    
    // Send tokens and user data to Drupal backend
    fetch('/engageplus/api/user', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        idToken: idToken,
        accessToken: accessToken,
        refreshToken: refreshToken,
        user: userData,
        provider: tokens.provider || 'unknown',
      }),
    })
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      if (data.success) {
        console.log('EngagePlus: User logged in successfully', data);
        showSuccess('Successfully logged in as ' + data.username);
        
        // Redirect after short delay
        setTimeout(function () {
          if (data.redirect && data.redirect !== 'current') {
            window.location.href = data.redirect;
          } else {
            window.location.href = '/';
          }
        }, 1000);
      } else {
        throw new Error(data.error || 'Authentication failed');
      }
    })
    .catch(function (error) {
      console.error('EngagePlus: Error processing authentication', error);
      showError('Authentication failed: ' + error.message);
    });
  }

  /**
   * Show error message
   */
  function showError(message) {
    const container = document.getElementById('engageplus-auth-callback');
    if (container) {
      container.innerHTML = '<div class="messages messages--error" role="contentinfo">' +
        '<strong>Error:</strong> ' + message +
        '<br><br><a href="/">Return to home page</a>' +
        '</div>';
    }
  }

  /**
   * Show success message
   */
  function showSuccess(message) {
    const container = document.getElementById('engageplus-auth-callback');
    if (container) {
      container.innerHTML = '<div class="messages messages--status" role="contentinfo">' +
        message + '<br>Redirecting...' +
        '</div>';
    }
  }

})(drupalSettings);
