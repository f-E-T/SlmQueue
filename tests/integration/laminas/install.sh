#!/usr/bin/sh

rm -rf lib
rm -f composer.lock

# copy code to `lib` directory. This prevents Composer from symlinking the root directory,
# which would cause PHPUnit to crash. The integration projects' own `lib` copies are skipped, so they don't nest.
rsync -av --progress ../../../ lib/ --exclude=".*"  --exclude="vendor/" --exclude="/tests/integration/*/lib/"
composer install
