<?php

declare(strict_types=1);

return [
    // For multi-site installations this would need to be handled
    // differently. A base name should be set here, and the "real" table name should
    // be constructed inside the register method of a bootstrapper.
    'table' => $GLOBALS['wpdb']->prefix . 'ebooks',
];
