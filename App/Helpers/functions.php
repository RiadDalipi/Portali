<?php

function checkAdmin() {
    if (!(isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'])) {
        echo "Ndalohet hyrja / Access denied";
        die();
    }
}

function ifAdmin() {
   return (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']);
}

function checkMember() {
    if (!(isset($_SESSION['isMember']) && $_SESSION['isMember'])) {
        echo "Ndalohet hyrja / Access denied";
        die();
    }
}

function ifMember() {
    return (isset($_SESSION['isMember']) && $_SESSION['isMember']);
}

