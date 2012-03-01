<?php

function ldapConnect($SERVER, $PORT = 389) {
    $RESOURCE = ldap_connect($SERVER, $PORT);

    ldap_set_option($RESOURCE, LDAP_OPT_NETWORK_TIMEOUT, 1);
    ldap_set_option($RESOURCE, LDAP_OPT_PROTOCOL_VERSION, 3);
    return $RESOURCE;
}

?>