<?php
//Domain FQDN
$LDAP['SERVER'] = 'domain.local';

//OU to look for users who try to log in
$LDAP['DOMAIN'] = "OU=Users,DC=domain,DC=local";

//Only allows users to log in from this group
$LDAP['ACCESS_GROUP_DN'] = 'CN=Access_Group,OU=Users,DC=domain,DC=local';
$LDAP['ADMIN_GROUP_DN'] = 'CN=Admin_Group,OU=Users,DC=domain,DC=local';

//Full path to root of application as viewed from a browser
$GENERAL['URL_HOME'] = 'http://am.example.com';

//Full host path to Knowledge base file store
$GENERAL['KB_PATH'] = '/var/www/asset/kb/';

//Hostname for MySQL Server
$SQL['HOST'] = 'localhost';

//Port for MySQL Server
$SQL['PORT'] = 3306;

//Database to use for MySQL Server
$SQL['DATABASE'] = 'asset_tracker';

//Username for MySQL Server
$SQL['USERNAME'] = 'asset_tracker';

//Password for MySQL Server
$SQL['PASSWORD'] = 'Pa55w0rd_G0es_Here!';

?>