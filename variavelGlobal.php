<?php
    $globalVar=5;
    function teste(){
    global $globalVar;
    echo $globalVar;
    }
    teste();
?>