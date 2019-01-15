#!/usr/bin/env bash

function get_abs_filename () {
  # $1 : relative filename
  echo "$(cd "$1" && pwd)"
}

BASE_DIR="$(get_abs_filename "${BASH_SOURCE%/*}/../")"
DNSMASQ_CONF=/usr/local/etc/dnsmasq.conf
DNSMASQ_CONF_LINE="conf-file=${BASE_DIR}/conf/dnsmasq.conf"

if (brew list | grep -q dnsmasq); then
	echo "dnsmasq is already installed. Ensuring process is stopped..."
	brew services stop dnsmasq || true
	sudo brew services stop dnsmasq || true
else
	echo "Installing dnsmasq"
	brew install dnsmasq
fi

if grep -Fxq "$DNSMASQ_CONF_LINE" "$DNSMASQ_CONF"; then
	echo "dnsmasq configuration in place. Moving on..."
else
	echo "Adding relevant config to ${DNSMASQ_CONF}"
	echo "$DNSMASQ_CONF_LINE" >> "$DNSMASQ_CONF"
fi

echo "Creating /etc/resolver"
sudo mkdir -p /etc/resolver

echo "Linking ${BASE_DIR}/conf/resolver-hmdocker to /etc/resolver/hmdocker"
sudo ln -sf "${BASE_DIR}/conf/resolver-hmdocker" /etc/resolver/hmdocker

echo "Clearing DNS cache"
sudo killall -HUP mDNSResponder
sudo killall mDNSResponderHelper
sudo dscacheutil -flushcache

echo "Registering dnsmasq to start at boot and starting"
# dnsmasq is started with sudo to ensure it can use port 53
sudo brew services start dnsmasq
