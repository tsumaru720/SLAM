<?php
function initPage() {
    
}

function doHeader() {
    ?><link rel='stylesheet' type='text/css' href='css/center.css'>
<?php
    
}

function getPageTitle() {
    return 'Config not found';
}

function showPageBody() {
    ?>
    <div id="container">
        <div id="body">
            <h2>No Config</h2>
            <p>Please make sure you have configured your config file correctly.</p>
        </div>
    </div>
    <?php
}
?>

