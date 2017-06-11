<?php

/**
 * @author Alexander "SCIF" Zhuravlev, https://github.com/SCIF
 * @package Symb tools, https://github.com/symb
 * @license GNU GPL-3
 */

require_once __DIR__ . '/src/Symb5.php';
require_once __DIR__ . '/src/Symb3.php';

function usage() {
    echo "\nPretty stupid testing tools for internal usage only.\n
    Usage: php ./" . basename(__FILE__) . " {e|d} {filepath}\nBoth arguments are mandatory.\n\n";
    exit;
}

function get_expected_size($content) {
    return unpack('L', substr($content, 0, 4))[1];
}

$directory = (string) isset($argv[1]) ? $argv[1]: '';

if ('' === $directory) {
    usage();
}

$directory = rtrim($directory, '/');

$files = glob(realpath($directory) . '/*_comp');

echo count($files) . " compressed files found in directory\n";

$statDecodedFails = [];
$statDecodedSuccesses = [];
$statEncodedFails = [];
$statEncodedSuccesses = [];

foreach ($files as $file) {
    $extractedFilename = "{$file}.out";

    if (!is_file($extractedFilename)) {
        echo "File {$extractedFilename} is absent, so {$file} skipped\n";
        continue;
    }

    $originalData   = file_get_contents($file);
    $expectedSize   = get_expected_size($originalData);
    $sizeDifference = filesize($extractedFilename) - $expectedSize;

    if ($sizeDifference > 3) {
        echo "File {$extractedFilename} looks incorrect because his size more than 3 bytes (just aligned). Skipped.\n";
        continue;
    } elseif ($sizeDifference < 0) {
        echo "File {$extractedFilename} has size less than expected. Skipped.\n";
        continue;
    }

    /** @var string $extractedData Decoded data by reference decoder. Assume it's correct */
    $extractedData = file_get_contents($extractedFilename, false, null, 0, $expectedSize);
    $filenameData = explode('_', basename($file));

    if (count($filenameData) !== 3 || !is_numeric($filenameData[0]) || !is_numeric($filenameData[1])) {
        echo "File '{$file}' does not follow naming schema {id}_{formatversion}_comp. Skipped";
        continue;
    }

    $version = (int) $filenameData[1];
    $decodedFilename = "{$file}.dec";

    $class = "Symb{$version}";

    if (is_file($decodedFilename)) {
        unlink($decodedFilename);
    }

    /** @var Symb2|Symb4 $symb */
    $symb = new $class($file);
    $symb->decrypt();

    if (filesize($decodedFilename) !== $expectedSize) {
        echo "Decoded file '{$decodedFilename}' has incorrect filesize.\n";
        continue;
    }

    $decodedData = file_get_contents($decodedFilename, false, null, 0, $expectedSize);

    if ($decodedData !== $extractedData) {
        $statDecodedFails[] = $decodedFilename;
        echo "File '{$decodedFilename}' does not equal reference.\n";
    } else {
        $statDecodedSuccesses[] = $decodedFilename;
    }

    $encodedFilename = "{$decodedFilename}.enc";
    /** @var Symb2|Symb4 $symb */
    $symb = new $class($decodedFilename);
    $symb->encrypt();

    $encodedContent = file_get_contents($encodedFilename);
    if ($encodedContent !== $originalData) {
        $statEncodedFails[] = $encodedFilename;
        echo "File '{$encodedFilename}' does not equal original file";
    } else {
        $statEncodedSuccesses[] = $encodedFilename;
    }

    unlink($decodedFilename);
    unlink($encodedFilename);
}
echo 'Successfully decoded: ' . count($statDecodedSuccesses) . "\n";
echo 'Failed to decode: ' . count($statDecodedFails) . "\n";
if (count($statDecodedFails) > 0) {
	echo implode($statDecodedFails, ', ');
	echo "\n";
}
echo 'Successfully encoded: ' . count($statEncodedSuccesses) . "\n";
echo 'Failed to encode: ' . count($statEncodedFails) . "\n";

if (count($statEncodedFails) > 0) {
	echo implode($statEncodedFails, ', ');
	echo "\n";
}
