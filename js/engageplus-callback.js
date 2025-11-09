/**
 * @file
 * Handles the EngagePlus authentication callback page.
 */

(function () {
  'use strict';

  // This page is loaded after OAuth redirect from EngagePlus.
  // The EngagePlus widget.js needs to be loaded to process the callback.
  
  console.log('EngagePlus: Callback page loaded');

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
    // We just need to initialize it with a minimal config
    try {
      window.EngagePlus.init({
        clientId: 'callback-handler', // Placeholder - not used in callback mode
        onSuccess: function(tokens) {
          console.log('EngagePlus: Callback successful, processing tokens');
          handleAuthSuccess(tokens);
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
  function handleAuthSuccess(tokens) {
    console.log('EngagePlus: Sending tokens to Drupal backend');
    
    // Send tokens to Drupal backend
    fetch('/engageplus/api/user', {
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

})();

