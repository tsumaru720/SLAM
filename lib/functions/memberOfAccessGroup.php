<?php

function memberOfAccessGroup($LDAP_RESOURCE, $LDAP_DOMAIN, $SEARCH_DN, $LDAP_ACCESS_GROUP) {
    $search = ldap_search($LDAP_RESOURCE, $LDAP_DOMAIN, '(distinguishedname='.$LDAP_ACCESS_GROUP.')', array('member'), 0, 1000);
	
    if (ldap_count_entries($LDAP_RESOURCE, $search) > 0) {
        $results = ldap_get_entries($LDAP_RESOURCE, $search);
        foreach ($results as $entry) {
            $groupName = $entry['dn'];
            if ($groupName == $LDAP_ACCESS_GROUP) {
                $groupMembers = $entry['member'];
                foreach ($groupMembers as $memberDN) {
                    if (is_string($memberDN) && $memberDN == $SEARCH_DN) {
                        return true;
                    }
                }
            }
        }
    }
    return false;
}

?>