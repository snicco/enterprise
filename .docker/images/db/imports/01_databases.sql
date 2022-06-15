#
# =================================================================
# Create test tables
# =================================================================
#
# We need three database for our development setup.
# 1) For development purposes
# 2) For unit/integration tests. This database is always
#    recreated between tests by wp-browser.
# 3) E2E test database. This database needs to exist
#    and it needs to be a valid WordPress database.
#
CREATE DATABASE IF NOT EXISTS snicco_enterprise;
CREATE DATABASE IF NOT EXISTS snicco_enterprise_testing;
CREATE DATABASE IF NOT EXISTS snicco_enterprise_e2e_testing;