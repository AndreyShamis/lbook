#!/bin/bash -xe

info() {
    echo -e "\033[1;33m[Info]    $1  \033[0m"
}

error() {
    echo -e "\033[1;31m[Error]   $1  \033[0m"
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

# Requirements
#sudo apt install php7.2-ldap php7.2-zip php7.2-xml php7.2-mbstring

save_proxy

info "Print composer info"
composer -V
info "Composer selfupdate"
composer selfupdate || true
info "Print composer info"
composer -V

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
mkdir -p artifacts
composer -vvv update &> artifacts/update.log.txt
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
#php bin/console doctrine:schema:validate -e=prod
#success "Finish Check DataBase"

info "Start Check YAML files"
php bin/console lint:yaml config/
success "Finish Check YAML files"

restore_proxy

info "Start unittests"
./vendor/bin/simple-phpunit --verbose --debug --colors=always --strict-coverage --strict-global-state --testdox-text artifacts/phpunit.txt
success "Finish unittests"