<?php

function showError($message, $fatal = false) {
    echo $message.'<br>';
    if ($fatal) {
        die();
    }
}

?>