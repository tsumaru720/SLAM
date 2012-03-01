<?php

function mysqlConnect($SERVER, $USERNAME, $PASSWORD, $PORT = 3306) {

    $RESOURCE = mysql_connect($SERVER, $USERNAME, $PASSWORD) or die(mysql_error());

    return $RESOURCE;
}

?>