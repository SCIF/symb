<?php

/**
 * @author Alexander "SCIF" Zhuravlev, https://github.com/SCIF
 * @package Symb tools, https://github.com/symb
 * @license GNU GPL-3
 */

require_once  __DIR__ . '/src/Symb4.php';

function usage() {
    echo "\nUsage: php ./" . basename(__FILE__) . " {e|d} {filepath}\nBoth arguments are mandatory.\n\n";
    exit;
}

$action = (string) isset($argv[1]) ? $argv[1]: '';
$filename = (string) isset($argv[2]) ? $argv[2] : '';

if ('' === $action) {
    usage();
}

$codec = new Symb4($filename);

switch ($action) {
    case 'd':
        $codec->decrypt();
        break;
    case 'e':
        $codec->encrypt();
        break;
    default:
        usage();
}
