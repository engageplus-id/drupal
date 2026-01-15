/**
 * @file
 * Handles the EngagePlus authentication callback page.
 */

(function (drupalSettings) {
  'use strict';

  // This page is loaded after OAuth redirect from EngagePlus.
  // The EngagePlus widget.js needs to be loaded to process the callback.
  
  console.log('EngagePlus: Callback page loaded');

  // Get configuration from Drupal settings
  var callbackSettings = drupalSettings.engageplus && drupalSettings.engageplus.callback;
  if (!callbackSettings || !callbackSettings.clientId) {
    console.error('EngagePlus: Missing callback configuration');
    showError('Configuration error: missing client ID');
    return;
  }

  var debugMode = callbackSettings.debugMode || false;

  if (debugMode) {
    console.log('EngagePlus: Callback configuration', callbackSettings);
  }

  // Check if the widget script is already loaded
  if (typeof window.EngagePlus === 'undefined') {
    // Load the EngagePlus widget script
    const script = document.createElement('script');
    script.src = 'https://engageplus.id/widget.js';
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
    // The widget will automatically detect callback mode and process the tokens
    try {
      if (debugMode) {
        console.log('EngagePlus: Initializing callback with config', {
          clientId: callbackSettings.clientId,
          issuer: callbackSettings.issuer,
          redirectUri: callbackSettings.redirectUri
        });
      }

      window.EngagePlus.init({
        clientId: callbackSettings.clientId,
        issuer: callbackSettings.issuer,
        redirectUri: callbackSettings.redirectUri,
        // Support both onSuccess (legacy) and onLogin (preferred)
        onSuccess: function(result) {
          console.log('EngagePlus: Callback successful, processing result');
          handleAuthSuccess(result);
        },
        onLogin: function(result) {
          console.log('EngagePlus: Callback successful via onLogin, processing result');
          handleAuthSuccess(result);
        },
        onLogout: function(user) {
          if (debugMode) {
            console.log('EngagePlus: User logged out during callback', user);
          }
        },
        onError: function(error) {
          console.error('EngagePlus: Callback error', error);
          showError(error.description || error.error || 'Authentication failed');
        }
      });
    } catch (error) {
      console.error('EngagePlus: Failed to initialize callback handler', error);
      showError('Failed to process authentication callback');
    }
  }

  /**
   * Handle successful authentication from callback
   */
  function handleAuthSuccess(result) {
    console.log('EngagePlus: Callback received result', result);
    
    // Extract tokens and user data from result object
    // The widget returns {tokens: {access_token, id_token, refresh_token}, user: {...}, provider: '...'}
    var tokens = result.tokens || result;
    var accessToken = tokens.access_token || tokens.accessToken;
    var idToken = tokens.id_token || tokens.idToken;
    var refreshToken = tokens.refresh_token || tokens.refreshToken || null;
    var userData = result.user || null;

    if (!idToken) {
      console.error('EngagePlus: No ID token found in callback result', result);
      showError('Missing ID token in authentication response');
      return;
    }

    if (!userData || !userData.email) {
      console.error('EngagePlus: No user data found in callback result', result);
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
        provider: result.provider || 'unknown',
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

