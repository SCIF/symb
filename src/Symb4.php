<?php

/**
 * @author Alexander "SCIF" Zhuravlev, https://github.com/SCIF
 * @package Symb tools, https://github.com/symb
 * @license GNU GPL-3
 */

require_once __DIR__ . '/AbstractSymb.php';

class Symb4 extends AbstractSymb
{
    public function decrypt()
    {
        $this->destinationSize = $this->getExpectedSize();

        while ($this->offset < $this->size && $this->destinationSize > strlen($this->output)) {
            $byte = $this->getNextByteAsInt();

            if ($byte <= 127) {
                $this->copyNextColors($byte + 1);
            } else {
                $this->cloneNextColor(257 - $byte);
            }
        }

        $actualSize = strlen($this->output);

        if ($actualSize !== $this->destinationSize) {
            throw new RuntimeException("Decoded and expected sizes are different: {$actualSize} vs {$this->destinationSize} for file '{$this->filename}'");
        }

        $this->saveFile('Decoded');
    }

    public function encrypt()
    {
        $this->writeSize();
        $this->compressBody();
        $this->alignOutput();
        $this->saveFile('Encoded');
    }

    private function compressBody()
    {
        $lastBlock = '';
        $previous = '';
        $count = 0;

        while ($this->offset < $this->size) {
            $color = $this->getNextColor();

            if ($previous === $color && $count !== 127) {
                if ($lastBlock !== $previous) {
                    $this->dumpBlock(substr($lastBlock, 0, - static::COLOR_BYTESIZE));
                    $lastBlock = $color;
                }
                $count++;
            } elseif ($count > 0) {
                $this->flushCompressed($previous, $count);
                $lastBlock = $color;
                $count = 0;
            } else {
                $lastBlock .= $color;
            }

            $previous = $color;
        }

        if ($count > 0) {
            $this->flushCompressed($previous, $count);
        } else {
            $this->dumpBlock($lastBlock);
        }
    }

    /**
     * @param string $block Last color MUST be ignored
     */
    protected function dumpBlock($block)
    {
        $count = (strlen($block)/static::COLOR_BYTESIZE);

        do {
            $restCount = ($count > 127) ? 128 : $count;
            $count -= 128;

            $this->output .= pack('C', $restCount - 1); // -1 is simulate base of 0
            $this->output .= substr($block, 0, $restCount * static::COLOR_BYTESIZE);
            $block = substr($block, $restCount * static::COLOR_BYTESIZE);
        } while ($count > 0);
    }

    /**
     * @param string $color
     * @param int    $count
     */
    protected function flushCompressed($color, $count)
    {
        do {
            $restCount = ($count > 127) ? 127 : $count;
            $count -= 128;

            $this->output .= pack('C', 256 - $restCount);
            $this->output .= $color;
        } while ($count > 0);
    }
}