<?php

namespace movemegif\domain;

/**
 * @author Patrick van Bergen
 */
class Frame
{
    private $pixels = null;

    /** @var bool  */
    private $useLocalColorTable = false;

    /** @var int  */
    private $duration = 0;

    /**
     * Enter this image's data as a string of indexes and a indexed color table.
     *
     * For example:
     *
     * $pixels = "
     *     1 1 2 2
     *     1 0 0 2
     *     2 2 1 1
     * ";
     *
     * $colors = array(
     *     0 => 0xFFFFFF,
     *     1 => 0xFF0000,
     *     2 => 0x0000FF
     * )
     *
     * Each color is coded as 0x00RRGGBB (unused, red, green, blue bytes)
     *
     * @param string $pixelIndexes A whitespace separated string of color indexes.
     * @param int[] $colorTable An index 2 RGB color map.
     * @return $this
     */
    public function setPixelsAndColors($pixelIndexes, array $colorTable)
    {
        $array = array();

        preg_match_all('/(\d+)/', $pixelIndexes, $matches);

        foreach ($matches[1] as $match) {

            $index = $match[0];

            if (array_key_exists($index, $colorTable)) {
                $array[] = $colorTable[$index];
            } else {
#todo
            }

        }

        $this->pixels = $array;

        return $this;
    }

    /**
     * @param boolean $useLocalColorTable
     * @return $this
     */
    public function setUseLocalColorTable($useLocalColorTable)
    {
        $this->useLocalColorTable = $useLocalColorTable;
        return $this;
    }

    /**
     * @param int $duration The time this frame is visible (in 1/100 seconds).
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return int
     */
    public function getduration()
    {
        return $this->duration;
    }

    public function getPixels()
    {
        if ($this->pixels === null) {

            $this->setPixelsAndColors("
                1 1 1 1 1 2 2 2 2 2
                1 1 1 1 1 2 2 2 2 2
                1 1 1 1 1 2 2 2 2 2
                1 1 1 0 0 0 0 2 2 2
                1 1 1 0 0 0 0 2 2 2
                2 2 2 0 0 0 0 1 1 1
                2 2 2 0 0 0 0 1 1 1
                2 2 2 2 2 1 1 1 1 1
                2 2 2 2 2 1 1 1 1 1
                2 2 2 2 2 1 1 1 1 1",

                array(
                    '0' => 0xFFFFFF,
                    '1' => 0xFF0000,
                    '2' => 0x0000FF,
                    '3' => 0x000000
                )
            );

        }

        return $this->pixels;
    }

    /**
     * @return boolean
     */
    public function usesLocalColorTable()
    {
        return $this->useLocalColorTable;
    }
}