# Helper script called by Travis CI

# Run single-site unit tests:
export WP_MULTISITE=0
phpunit --exclude-group=ms-required

# Run Multisite unit tests:
export WP_MULTISITE=1
phpunit --exclude-group=ms-excluded
