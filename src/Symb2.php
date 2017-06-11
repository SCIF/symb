<?php

/**
 * @author Alexander "SCIF" Zhuravlev, https://github.com/SCIF
 * @package Symb tools, https://github.com/symb
 * @license GNU GPL-3
 */

require_once __DIR__ . '/AbstractSymb.php';

class Symb2 extends AbstractSymb
{
    const DEFAULT_BITMASK = 0x80000000;

    public function decrypt()
    {
        $this->destinationSize = $this->getExpectedSize();
        $bitmaskLength         = floor(($this->destinationSize + 31) / 32); // number of bytes aligned
        $bitmasks              = $this->readBinaryString($bitmaskLength * 4);
        $defaultColor          = $this->getNextColor();

        $maskArray = str_split($bitmasks, 4);

        foreach ($maskArray as $i => $maskByte) {
            $mask = static::DEFAULT_BITMASK;

            for ($j = 0; ($j < 32) && (strlen($this->output) < $this->destinationSize); $j++, $mask >>= 1) {
                if (unpack('L', $maskByte)[1] & $mask) {
                    $this->output .= $this->getNextColor();
                } else {
                    $this->output .= $defaultColor;
                }
            }
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
        $colors = [];

        // ищем самый частовстречаемый цвет и это будет defaultColor, а если несколько одинаково часто, то последний
        while (null !== ($color = $this->getNextColorAsInt())) {
            $colors[$color] = isset($colors[$color]) ? $colors[$color] + 1 : 1;
        }

        $this->rewindToStart();

        $max          = max($colors);
        $defaultColor = array_keys($colors, $max, true);

        if (is_array($defaultColor)) {
            $defaultColor = end($defaultColor);
        }

        $colorsString = '';

        $mask          = static::DEFAULT_BITMASK;
        $bitmask       = 0;
        $bitmaskString = '';

        $bitmaskLength = ceil($this->size + 31);

        for ($i = 0; $i < $bitmaskLength; $i++) {
            $color = $this->getNextColorAsInt();

            if (null !== $color && $color !== $defaultColor) {
                $bitmask      += $mask;
                $colorsString .= $this->convertColorFromInt($color);
            }

            $mask >>= 1;

            if ($mask === 0) {
                $bitmaskString .= pack('L', $bitmask);
                $mask          = static::DEFAULT_BITMASK;
                $bitmask       = 0;
            }
        }

        $this->output .= $bitmaskString;
        $this->output .= $this->convertColorFromInt($defaultColor);
        $this->output .= $colorsString;
    }
}