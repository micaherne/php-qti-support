<?php

$datadir = dirname(__FILE__)."\data";

if (!file_exists($datadir)) {
    mkdir($datadir, 0644, true);
}

require_once dirname(__FILE__).'/../vendor/autoload.php';