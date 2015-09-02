<?php

/*
 * This file is part of Stauros, a fast XSS filtering engine
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace Stauros;

class Stauros {

    private $blocksize = 8192;
    private $htmlConfig;

    public function __construct(HTML\Config $htmlConfig = null) {
        if ($config) {
            $this->htmlConfig = $htmlConfig;
        }
    }

    /**
     * Scan a string to a string
     */
    public function scanHTML($string) {
        $scanner = new HTML\Scanner($this->htmlConfig);
        return $scanner->scan($string) . $scanner->end();
    }

    /**
     * Scan a stream to a stream
     */
    public function scanHTMLStreamToStream($input, $output) {
        $scanner = new HTML\Scanner($this->htmlConfig);
        while ($string = fread($input, $this->blocksize)) {
            fwrite($output, $scanner->scan($string));
        }
        fwrite($output, $scanner->end());
    }

    /**
     * Scan a stream to a string
     */
    public function scanHTMLStreamToString($input) {
        $output = fopen("php://memory", "w");
        $this->scanHTMLStreamToStream($input, $output);
        return stream_get_contents($output, -1, 0);
    }

}
