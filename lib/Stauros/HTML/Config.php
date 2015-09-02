<?php

/*
 * This file is part of Stauros, a fast XSS filtering engine
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace Stauros\HTML;

class Config {

    public $charset = "UTF-8";

    public $tagWhiteList = [
        "a" => [
            "href"  => true,
            "title" => true,
        ],
        "abbr" => [
            "title" => true,
        ],
        "acronym" => [
            "title" => true,
        ],
        "b"          => [],
        "blockquote" => [
            "cite" => true,
        ],
        "cite" => [],
        "code" => [],
        "del"  => [
            "datetime" => true,
        ],
        "em"  => [],
        "i"   => [],
        "img" => [
            "src"   => true,
            "title" => true,
        ],
        "p" => [],
        "q" => [
            "cite" => true,
        ],
        "s"      => [],
        "strike" => [],
        "strong" => [],
    ];

    public $uriAttrs = [
        "href" => 1,
        "src"  => 1,
    ];

    public $uriAllowedSchemes = [
        "http"   => 1,
        "https"  => 1,
        "mailto" => 1,
    ];

    public $attributeCallbacks = [];

    public function __construct(array $tagWhiteList = null, array $uriAttrs = null, array $uriAllowedSchemes = null, $charset = null) {
        if (!is_null($tagWhiteList)) {
            $this->tagWhiteList = $tagWhiteList;
        }
        if (!is_null($uriAttrs)) {
            $this->uriAttrs = $uriAttrs;
        }
        if (!is_null($uriAllowedSchemes)) {
            $this->uriAllowedSchemes = $uriAllowedSchemes;
        }
        if (!is_null($charset)) {
            $this->charset = $charset;
        }
    }

    public function addAttributeCallback(callable $cb) {
        $this->attributeCallbacks[] = $cb;
    }
}