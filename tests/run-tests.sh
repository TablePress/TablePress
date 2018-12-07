# Helper script called by Travis CI

# Run single-site unit tests:
export WP_MULTISITE=0
./vendor/bin/phpunit --exclude-group=ms-required

# Run Multisite unit tests:
export WP_MULTISITE=1
./vendor/bin/phpunit --exclude-group=ms-excluded
