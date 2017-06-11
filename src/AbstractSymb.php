<?php

/**
 * @author Alexander "SCIF" Zhuravlev, https://github.com/SCIF
 * @package Symb tools, https://github.com/symb
 * @license GNU GPL-3
 */

abstract class AbstractSymb
{
    /** @var int  */
    protected $offset;

    /** @var int */
    protected $size;

    /** @var int */
    protected $destinationSize;

    /** @var resource  */
    protected $handler;

    /** @var string  */
    protected $output;

    /** @var string  */
    protected $filename;

    const COLOR_BYTESIZE = 1;
    
    abstract public function decrypt();
    abstract public function encrypt();

    public function __construct($filename)
    {
        $fileHandler = fopen($filename, 'rb');

        if (false === $fileHandler) {
            throw new RuntimeException('Cannot open to read file ' . $filename);
        }

        $this->offset   = 0;
        $this->filename = realpath($filename);
        $this->output   = '';
        $this->handler  = $fileHandler;
        $this->size     = filesize($filename);
    }

    protected function getExpectedSize()
    {
        return unpack('L', $this->readBinaryString(4))[1];
    }

    /**
     * @return string
     */
    protected function getNextColor()
    {
        return $this->readBinaryString(static::COLOR_BYTESIZE);
    }

    /**
     * @param int $length
     * @return string
     */
    protected function getNextColors($length)
    {
        return $this->readBinaryString($length * static::COLOR_BYTESIZE);
    }

    /**
     * @return int|null
     */
    protected function getNextByteAsInt()
    {
        $string = $this->readBinaryString(1);

        if ('' === $string) {
            return null;
        }

        return unpack('C1byte', $string)['byte'];
    }

    /**
     * @return int|null
     */
    protected function getNextColorAsInt()
    {
        $string = $this->readBinaryString(static::COLOR_BYTESIZE);

        if ('' === $string) {
            return null;
        }

        return unpack(
            ((static::COLOR_BYTESIZE === 1) ? 'C' : 'S') .  '1byte',
            $string
       )['byte'];
    }

    /**
     * @param int $colorInt
     *
     * @return string
     */
    protected function convertColorFromInt($colorInt)
    {
        return pack((static::COLOR_BYTESIZE === 1) ? 'C' : 'S', $colorInt);
    }

    /**
     * @param int $times
     */
    protected function cloneNextColor($times)
    {
        $this->output .= str_repeat($this->getNextColor(), $times);
    }

    /**
     * @param int $length
     */
    protected function copyNextColors($length)
    {
        $length = $length * static::COLOR_BYTESIZE;
        $string = $this->readBinaryString($length);

        if ('' === $string) {
            throw new RuntimeException('Cannot copy colors, probably pointer reached end of file');
        }

        $this->output .= $string;
    }

    /**
     * @param $length
     *
     * @return string Binary string
     */
    protected function readBinaryString($length)
    {
        $result = fread($this->handler, $length);

        if (false !== $result) {
            $this->offset += $length;
        }

        return (string) $result;
    }

    /**
     * @param string $action
     */
    protected function saveFile($action)
    {
        fclose($this->handler);

        $suffix      = strtolower(substr($action, 0, 3));
        $actualSize  = strlen($this->output);
        $newFilename = "{$this->filename}.{$suffix}";

        if (is_file($newFilename)) {
            $newFilename =  tempnam(dirname($newFilename), strtolower($action));
        }

        file_put_contents($newFilename, $this->output);

        echo "{$action} {$actualSize} bytes into file {$newFilename}\n";
    }

    protected function writeSize()
    {
        $this->output .= pack('l', $this->size);
    }

    protected function alignOutput() {
        $alignByBytes = 4;
        $addCount = $alignByBytes - (strlen($this->output) % $alignByBytes);

        if (0 !== $addCount && 4 !== $addCount) {
            $this->output .= str_repeat("\0", $addCount);
            echo "Aligned with {$alignByBytes} byte conventions. Added {$addCount} bytes.\n";
        }
    }

    protected function rewindToStart()
    {
        $this->offset = 0;
        rewind($this->handler);
    }
}