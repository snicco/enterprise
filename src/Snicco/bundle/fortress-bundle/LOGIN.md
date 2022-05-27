## Authentication module behaviour

### External login forms / wp-login.php

- Hook into wp_signon()
- If valid => Create 2FA challenge and redirect user to custom route (If valid => redirect to value of "redirect_to" or HTTP_REFERER as fallback).
- If not valid => Do nothing.

### Custom default login page

- Redirect all GET requests to wp-login.php
- Optionally disable all POST requests to wp-login.php (expect)
- Disable pw resets if configured

### View responses in the bundle

Return only a view object for READ requests

All other endpoints should return json only.

