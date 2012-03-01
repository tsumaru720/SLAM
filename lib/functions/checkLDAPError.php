<?php

function checkLDAPError($RESOURCE) {
    $LDAP_ERROR = ldap_errno($RESOURCE);

    return $LDAP_ERROR != 0;
}

?>