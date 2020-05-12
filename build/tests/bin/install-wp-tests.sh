#!/usr/bin/env bash

if [[ $# -lt 3 ]]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-stable}

WP_DEVELOP_DIR=${WP_DEVELOP_DIR-/tmp/wordpress/}

set -ex

install_wp() {
	rm -rf ${WP_DEVELOP_DIR}
	mkdir -p ${WP_DEVELOP_DIR}

	git clone --depth=1 --quiet git://develop.git.wordpress.org/ ${WP_DEVELOP_DIR}/
	cd ${WP_DEVELOP_DIR}

	if [[ ${WP_VERSION} == 'stable' ]]; then
		git fetch --tags
		export WP_VERSION=$(git tag | sort -n | tail -1)
	fi

	git checkout ${WP_VERSION}
}

create_db() {
	# parse DB_HOST for port or socket references
	local PARTS=(${DB_HOST//\:/ })
	local DB_HOSTNAME=${PARTS[0]};
	local DB_SOCK_OR_PORT=${PARTS[1]};
	local EXTRA=""

	if ! [[ -z ${DB_HOSTNAME} ]] ; then
		if [[ $(echo ${DB_SOCK_OR_PORT} | grep -e '^[0-9]\{1,\}$') ]]; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
		elif ! [[ -z ${DB_SOCK_OR_PORT} ]] ; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		elif ! [[ -z ${DB_HOSTNAME} ]] ; then
			EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
		fi
	fi

	# create database
	mysql -e "DROP DATABASE IF EXISTS $DB_NAME; CREATE DATABASE $DB_NAME;" --user="$DB_USER" --password="$DB_PASS"${EXTRA}
}

config_test_suite() {
	local opts='-i'

	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local opts='-i .bak'
	fi

    cp wp-tests-config-sample.php wp-tests-config.php

	sed ${opts} "s/youremptytestdbnamehere/$DB_NAME/" wp-tests-config.php
	sed ${opts} "s/yourusernamehere/$DB_USER/" wp-tests-config.php
	sed ${opts} "s/yourpasswordhere/$DB_PASS/" wp-tests-config.php
	sed ${opts} "s|localhost|${DB_HOST}|" wp-tests-config.php
}

install_wp
create_db
config_test_suite
