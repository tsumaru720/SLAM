<?php
function initPage() {
    header("HTTP/1.0 404 Not Found");
}

function doHeader() {
    ?><link rel='stylesheet' type='text/css' href='css/center.css'>
<?php
}

function getPageTitle() {
    return '404 Page not found';
}

function showPageBody() {
    ?>
    <div id="container">
        <div id="body">
            <h2>404</h2>
            <p>NOT FOUND</p>
    </div>
    </div>
    <?php
}
?>

