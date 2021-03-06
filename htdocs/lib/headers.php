<?php

header("X-Frame-Options: deny");
header("Content-Security-Policy: "
  . "default-src 'self'; "
  . "base-uri 'self'; "
  . "block-all-mixed-content; "
  . "font-src 'self'; "
  . "frame-ancestors 'self'; "
  . "img-src 'self' data:; "
  . "object-src 'none'; "
  . "script-src 'self'; "
  . "script-src-attr 'none'; " // NB: not compatible with Firefox (for now).
  . "style-src 'self'" . (defined('COCOTS_CUSTOM_CSS') || defined('COCOTS_CUSTOM_ADMIN_CSS') ? " 'unsafe-inline'" : '') . "; "
  . "upgrade-insecure-requests; ");
header("Cross-Origin-Embedder-Policy: require-corp");
header("Cross-Origin-Opener-Policy: same-origin");
header("Cross-Origin-Resource-Policy: same-origin");
header("X-Content-Type-Options: nosniff");
