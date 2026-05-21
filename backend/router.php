<?php

use Symfony\Component\HttpFoundation\Request;

$_SERVER["SCRIPT_FILENAME"] = __DIR__ . "/public/index.php";

if (is_file(__DIR__ . "/public" . $_SERVER["REQUEST_URI"])) {
    return false;
}

$_SERVER["SCRIPT_NAME"] = "/index.php";
require __DIR__ . "/public/index.php";
