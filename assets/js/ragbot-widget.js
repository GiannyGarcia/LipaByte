/**
 * Site-wide LipaByte ElevenLabs RAGbot widget.
 * Requires assets/js/ragbot-config.js (window.LIPABYTE_RAGBOT.agentId).
 */
(function () {
  'use strict';

  var PLACEHOLDER = 'PASTE_YOUR_ELEVENLABS_AGENT_ID_HERE';
  var SCRIPT_SRC = 'https://unpkg.com/@elevenlabs/convai-widget-embed';
  var cfg = window.LIPABYTE_RAGBOT || {};
  var agentId = String(cfg.agentId || '').trim();

  window.LIPABYTE_RAGBOT_STATUS = {
    ready: false,
    reason: 'pending',
  };

  function setStatus(ready, reason) {
    window.LIPABYTE_RAGBOT_STATUS = { ready: ready, reason: reason };
    try {
      document.dispatchEvent(new CustomEvent('lipabyte-ragbot-ready', {
        detail: window.LIPABYTE_RAGBOT_STATUS,
      }));
    } catch (err) {
      /* older browsers: ignore */
    }
  }

  if (!agentId || agentId === PLACEHOLDER) {
    setStatus(false, 'missing-agent-id');
    return;
  }

  if (document.querySelector('elevenlabs-convai')) {
    setStatus(true, 'already-present');
    return;
  }

  var widget = document.createElement('elevenlabs-convai');
  widget.setAttribute('agent-id', agentId);
  widget.setAttribute('dismissible', 'true');
  document.body.appendChild(widget);

  if (!document.querySelector('script[data-lipabyte-elevenlabs]')) {
    var script = document.createElement('script');
    script.src = SCRIPT_SRC;
    script.async = true;
    script.type = 'text/javascript';
    script.setAttribute('data-lipabyte-elevenlabs', '1');
    document.body.appendChild(script);
  }

  setStatus(true, 'loaded');
})();
