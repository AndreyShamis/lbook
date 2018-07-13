#!/bin/bash -xe

info() {
    echo -e "\033[1;33m[Info]    $1  \033[0m"
}

error() {
    echo -e "\033[1;31m[Error]   $1  \033[0m"
}

fail() {
    echo -e "\033[41m[Fail] $1 \033[0m"
}

success() {
    echo -e "\033[1;32m[Success] $1 \033[0m"
}

save_proxy(){
    info "Save HTTPS and https ENVs"
    TMP_HTTPS_PROXY_SMALL=${https_proxy}
    TMP_HTTPS_PROXY_BIG=${HTTPS_PROXY}
    export https_proxy=""
    export HTTPS_PROXY=""
    success " ----> Proxy ENVs saved <----"
}

restore_proxy(){
    info "Restore proxy ENVs"
    export https_proxy=${TMP_HTTPS_PROXY_SMALL}
    export HTTPS_PROXY=${TMP_HTTPS_PROXY_BIG}
    success "Proxy restored"
}

export sym="php bin/console"
mkdir -p artifacts

# Requirements
#sudo apt install php7.2-ldap php7.2-zip php7.2-xml php7.2-mbstring php7.2-mysql

save_proxy

info "Print composer info"
composer -V
info "Composer selfupdate"
composer selfupdate || true
info "Print composer info"
composer -V
success "Composer update. Composer version."

success "Composer about: "
composer about
success "Composer Shows information about packages: "
composer show
success "Shows a list of locally modified packages: "
composer status

# TODO Enable
#info "Validates a composer.json and composer.lock: "
#composer -vvv validate
#success "Validates a composer.json and composer.lock: "

#"cd /usr/share; ln -s php/data ."
success "***************************** Composer validation finished *****************************"
ls -l
success "ls"
#----------------------------------------------------------------------------------------------------------------------------

composer -vvv --profile update &> artifacts/update.log.txt
success "Composer update finished"

info "Get installed vendors after update"
composer show --installed > artifacts/vendors.txt
success "Versions finished"


info "Check that the composer.json for different errors, like autoload issue:"
#composer validate --no-check-all
#composer validate --no-check-all --strict  # TODO
success " ----> Check that the composer.json for different errors, like autoload issue <----"

# TODO
#info "Check the composer.lock for security issues"
#php vendor/bin/security-checker security:check

# Disable require unit test - should be in composer
#info "Adding symfony/phpunit-bridge"
#composer require  symfony/phpunit-bridge

#info "Start Check DataBase"
#${sym} doctrine:schema:validate -e=prod
#success "Finish Check DataBase"

info "Start Check YAML files"
${sym} lint:yaml config/
success "Finish Check YAML files"

STEP="Verify that Doctrine is properly configured for a production environment"
info "${STEP}"
${sym} doctrine:ensure-production-settings --env=prod -vvv --complete  || fail "Failed to validate production environment"
success "Finish - ${STEP}"

STEP="Get mapping info"
info "${STEP}"
${sym} doctrine:mapping:info -n -vvv
success "Finish - ${STEP}"

restore_proxy
./bin/console ca:cl
info "Start unittests"
./vendor/bin/simple-phpunit --verbose --debug --colors=always --strict-coverage --strict-global-state --testdox-text artifacts/phpunit.txt
success "Finish unittests"