  // If the companion is active it sets data-semitexa-companion on <html> and
  // strips the framing headers, so the frame just works — no hint needed.
  // Otherwise, after a grace period with no successful load, surface the hint.
  (function () {
    // The handler places this script inside the launch .wrap, BEFORE the
    // iframe: it runs while the frame is not parsed yet, so a capturing load
    // listener on the root is in place before the frame can start loading. A
    // listener added after the frame (the old placement) missed a load that
    // fired while this file was still downloading and flashed a false hint.
    var me = document.currentScript;
    var root = me && me.parentElement && me.parentElement.closest('.wrap');
    if (!root) return;
    var loaded = false;
    // load does not bubble, but it does pass through ancestors while capturing.
    root.addEventListener('load', function (e) {
      if (e.target && e.target.classList && e.target.classList.contains('frame')) loaded = true;
    }, true);
    setTimeout(function () {
      var companion = document.documentElement.dataset.semitexaCompanion;
      var hint = root.querySelector('#hint');
      if (!companion && !loaded && hint) { hint.style.display = 'block'; }
    }, 3500);
  })();
