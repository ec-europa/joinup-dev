#!/bin/bash

install_codebase () {
    mkdir -p "${SITE_DIR}/modules"
    ln -s "${TRAVIS_BUILD_DIR}" "${SITE_DIR}/modules/sparql_entity_storage"
    composer require --dev drupal/core:${DRUPAL} drupal/core-composer-scaffold:${DRUPAL} phpunit/phpunit:${PHPUNIT} --no-interaction --prefer-dist
}

set -x

case "${TEST_SUITE}" in
    PHPCodeSniffer)
        composer install
        ./vendor/bin/phpcs
        exit $?
        ;;
    PHPStan)
        install_codebase
        ./vendor/bin/phpstan analyse ../testing_site/modules/sparql_entity_storage
        exit $?
        ;;
    PHPUnit)
        install_codebase
        # Virtuoso setup.
        mkdir "${SITE_DIR}/virtuoso"
        docker run --name virtuoso -p 8890:8890 -p 1111:1111 -e SPARQL_UPDATE=true -v "${SITE_DIR}/virtuoso":/data -d tenforce/virtuoso
        # Sleep to ensure that Docker services are available.
        sleep 15
        ./vendor/bin/phpunit --verbose
        exit $?
        ;;
    *)
        echo "Unknown test '${TEST_SUITE}'"
        exit 1
esac
