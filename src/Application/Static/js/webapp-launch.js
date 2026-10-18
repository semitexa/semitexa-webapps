  // If the companion is active it sets data-semitexa-companion on <html> and
  // strips the framing headers, so the frame just works — no hint needed.
  // Otherwise, after a grace period with no successful load, surface the hint.
  (function () {
    var hint = document.getElementById('hint');
    var frame = document.querySelector('.frame');
    // A linked script can run after the frame already loaded; the page's own
    // load then has fired too (it waits for its frames), so read that as loaded.
    var loaded = document.readyState === 'complete';
    frame.addEventListener('load', function () { loaded = true; });
    setTimeout(function () {
      var companion = document.documentElement.dataset.semitexaCompanion;
      if (!companion && !loaded) { hint.style.display = 'block'; }
    }, 3500);
  })();
