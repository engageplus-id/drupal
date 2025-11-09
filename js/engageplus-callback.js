/**
 * @file
 * Handles the EngagePlus authentication callback page.
 */

(function () {
  'use strict';

  // This page is loaded after OAuth redirect.
  // The EngagePlus widget handles the callback automatically,
  // but we provide a fallback to close the window if it's a popup.
  
  // Check if this is a popup/modal window.
  if (window.opener || window !== window.top) {
    // This is a popup or iframe, close it after a short delay.
    setTimeout(function () {
      if (window.opener) {
        window.close();
      }
    }, 2000);
  } else {
    // This is a regular page load, redirect to home.
    setTimeout(function () {
      window.location.href = '/';
    }, 2000);
  }

})();

