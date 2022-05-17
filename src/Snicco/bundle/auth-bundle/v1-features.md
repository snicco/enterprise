# Features

## Technical

- custom tables for sessions
- split token (if possible)
- uses defuse encryption, not some weirdo self backed stuff
- 100% test coverage against all PHP and WP versions
- 100% Psalm 
- absolute lifetime, rotation (if possible) and idle timeouts
- login with email links (one time usage)
- side channel safe (if possible in custom session implementation)
- auth confirmation for customizable pages
- 2FA
- support for third-party login forms (wp_authenticate)
- password policies and secure password hashing (paragonie password hasher)
- Fail2Ban integration for all actions
- removes username enumeration
- custom tables for everything
- PHP7.4+ only, no legacy stuff
- conditional loading of the plugin