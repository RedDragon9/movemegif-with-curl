<?php

namespace movemegif\data;

/**
 * @author Patrick van Bergen
 */
class ImageData
{
    const BLOCK_TERMINATOR = '0';
    const NUMBER_OF_SPECIAL_CODES = 2;

    public function getContents()
    {
        $colorCount = 4;

        /** @var int $lzwMinimumCodeSize The number of bits required for the initial color index codes, plus 2 special codes (Clear Code and End of Information Code) */
        $lzwMinimumCodeSize = $this->getMinimumCodeSize($colorCount);

        $blockTerminator = chr(self::BLOCK_TERMINATOR);

        $data =
            "1 1 1 1 1 2 2 2 2 2 " .
            "1 1 1 1 1 2 2 2 2 2 " .
            "1 1 1 1 1 2 2 2 2 2 " .
            "1 1 1 0 0 0 0 2 2 2 " .
            "1 1 1 0 0 0 0 2 2 2 " .
            "2 2 2 0 0 0 0 1 1 1 " .
            "2 2 2 0 0 0 0 1 1 1 " .
            "2 2 2 2 2 1 1 1 1 1 " .
            "2 2 2 2 2 1 1 1 1 1 " .
            "2 2 2 2 2 1 1 1 1 1";

        $codes = $this->compressCodes($this->gifLzwCompress(implode('', array_map('chr', explode(' ', $data))), $colorCount), $colorCount);

        $blocks = array_chunk($codes, 255);

        $dataSubBlocks = '';
        foreach ($blocks as $block) {
            $dataSubBlocks .= chr(count($block)) . implode('', $block);
        }

        return chr($lzwMinimumCodeSize) . $dataSubBlocks . $blockTerminator;
    }

    function gifLzwCompress($uncompressedString, $colorIndexCount)
    {
        // the resulting compressed string
        $resultCodes = array();

        // initialize sequence 2 code map
        list($sequence2code, $dictSize, $clearCode, $endOfInformationCode) = $this->createSequence2CodeMap($colorIndexCount);

        // save the initial map
        $savedMap = $sequence2code;
        $savedDictSize = $dictSize;

        // start with a clear code
        $resultCodes[] = $clearCode;

        $previousSequence = "";
        for ($i = 0; $i < strlen($uncompressedString); $i++) {

            $colorIndex = $uncompressedString[$i];
            $sequence = $previousSequence . $colorIndex;

            if (array_key_exists($sequence, $sequence2code)) {

                // sequence found, next run, try to find an even longer sequence
                $previousSequence .= $colorIndex;

            } else {

                // this sequence was not found, store the longest sequence found to the result
                $resultCodes[] = $sequence2code[$previousSequence];

                // the dictionary may hold only 2^12 items
                if ($dictSize == 4096) {

                    // reset the dictionary
                    $sequence2code = $savedMap;
                    $dictSize = $savedDictSize;

                    // insert a clear code
                    $resultCodes[] = $clearCode;
                }

                // store the new sequence to the map
                $sequence2code[$sequence] = $dictSize++;

                // start a new sequence
                $previousSequence = $colorIndex;
            }
        }

        if ($previousSequence !== "") {
            $resultCodes[] = $sequence2code[$previousSequence];
        }

        // end with the end of information code
        $resultCodes[] = $endOfInformationCode;

        return $resultCodes;
    }

    private function createSequence2CodeMap($colorIndexCount)
    {
        // a map of color index sequences to special codes
        $sequence2code = array();

        $dictSize = 0;
        $powerOfTwo = $this->getFirstHigherPowerOfTwo($colorIndexCount);

        // fill up the map with entries up to a power of 2
        for ($colorIndex = 0; $colorIndex < $powerOfTwo; $colorIndex++) {
            $sequence2code[chr($colorIndex)] = $dictSize++;
        }

        // define control codes
        $clearCode = $dictSize++;
        $endOfInformationCode = $dictSize++;
        $sequence2code[chr($clearCode)] = 'Clear Code';
        $sequence2code[chr($endOfInformationCode)] = 'End of Information Code';

        return array($sequence2code, $dictSize, $clearCode, $endOfInformationCode);
    }

    private function compressCodes(array $codes, $colorCount)
    {
        /** @var int $lzwMinimumCodeSize The number of bits required for the initial color index codes, plus 2 special codes (Clear Code and End of Information Code) */
        $lzwMinimumCodeSize = $this->getMinimumCodeSize($colorCount);
        $firstCodeSize = $lzwMinimumCodeSize + 1;
        $currentCodeSize = $firstCodeSize;

        $bytes = array();
        $byte = 0;
        $powerOfTwo = 1;

        $p = 0;
        $bitCombinationCount = pow(2, $currentCodeSize - 1);

        foreach ($codes as $i => $code) {

            $bits = $code;
            for ($b = 0; $b < $currentCodeSize; $b++) {

                if ($powerOfTwo == 256) {

                    // byte full
                    $bytes[] = chr($byte);

                    // new byte
                    $byte = 0;

                    // back to rightmost bit
                    $powerOfTwo = 1;
                }

                // rightmost bit of code = 1?
                if ($bits & 1) {

                    // add it to result
                    $byte += $powerOfTwo;
                }

                $bits >>= 1;
                $powerOfTwo <<= 1;
            }

            // increase code size
            $p++;
            if ($p == $bitCombinationCount) {
                $currentCodeSize++;
                $p = 0;
                $bitCombinationCount *= 2;
            }
        }

        if ($powerOfTwo > 1) {
            $bytes[] = chr($byte);
        }

        return $bytes;
    }

    private function getFirstHigherPowerOfTwo($number)
    {
        return pow(2, $this->getMinimumCodeSize($number));
    }

    private function getMinimumCodeSize($colorCount)
    {
        $size = 0;
        $bits = $colorCount - 1;

        while ($bits > 0) {
            $size++;
            $bits >>= 1;
        }

        // The GIF spec requires a minimum of 2
        return max(2, $size);
    }
}