<?php

namespace movemegif\domain;

use movemegif\exception\MovemegifException;

/**
 * @author Patrick van Bergen
 */
class FileImageCanvas extends GdCanvas
{
    /**
     * @param $filePath
     * @throws MovemegifException
     */
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct($filePath)
    {
        if (preg_match('#\.(gif|png|jpg|jpeg)$#', $filePath, $matches)) {

            $ext = $matches[1];
			
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $filePath); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // good edit, thanks!
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1); // also, this seems wise considering output is image.
    $data = curl_exec($ch);
    curl_close($ch);

            switch ($ext) {

                case 'gif':
                    $this->resource = imagecreatefromstring($data);
                    $this->transparencyColor = $this->locateTransparentColor($this->resource);
                    break;

                case 'png':
                    $this->resource = imagecreatefromstring($data);
                    imagetruecolortopalette($this->resource, true, 256);
                    $this->transparencyColor = $this->locateTransparentColor($this->resource);
                    break;

                case 'jpg':
                case 'jpeg':
                    $this->resource = imagecreatefromstring($data);
                    imagetruecolortopalette($this->resource, true, 256);
                    break;

            }

        } else {
            throw new MovemegifException('Unknown filetype. Choose one of gif, png or jpg.');
        }

        if (!$this->resource) {
            throw new MovemegifException('Unable to read file: ' . $filePath);
        }
    }

    private function locateTransparentColor($resource)
    {
        $colorIndex = imagecolortransparent($resource);
        if ($colorIndex != -1) {
            $rgb = imagecolorsforindex($resource, $colorIndex);
            $color = ($rgb['red'] << 16) + ($rgb['green'] << 8) + ($rgb['blue']);
        } else {
            $color = null;
        }

        return $color;
    }
}
