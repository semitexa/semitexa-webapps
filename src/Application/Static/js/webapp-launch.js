  // If the companion is active it sets data-semitexa-companion on <html> and
  // strips the framing headers, so the frame just works — no hint needed.
  // Otherwise the hint shows after a grace period that gives the companion
  // time to mark the page.
  //
  // Whether the app refused framing cannot be told from here: a frame blocked
  // by X-Frame-Options or CSP still fires load, and a cross-origin frame cannot
  // be looked into. A load listener used to count as "embedded", so the hint
  // never appeared for the very case it describes; it is now a strip along the
  // bottom, worded for either outcome, instead of a card over the app.
  (function () {
    // The handler places this script inside the launch .wrap.
    var me = document.currentScript;
    var root = me && me.parentElement && me.parentElement.closest('.wrap');
    if (!root) return;
    setTimeout(function () {
      var companion = document.documentElement.dataset.semitexaCompanion;
      var hint = root.querySelector('#hint');
      if (!companion && hint) { hint.style.display = 'block'; }
    }, 3500);
  })();
