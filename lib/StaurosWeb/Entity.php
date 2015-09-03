<?php

namespace StaurosWeb;

use RandomLib\Generator;

class Entity implements \jsonSerializable {
    public $publicId;
    public $code;
    public $created;
    public $ip;
    public $escaped = '';

    public function __construct($code, $publicId = null, $created = null, $ip = null) {
        $this->code = $code;
        $this->publicId = $publicId;
        $this->created = $created;
        $this->ip = $ip;
    }

    public function jsonSerialize() {
        return [
            'publicId' => $this->publicId,
            'code' => $this->code,
            'escaped' => $this->escaped,
        ];
    }

    public static function create($code) {
        return new Entity(
            $code,
            (new \RandomLib\Factory)->getMediumStrengthGenerator()->generateString(
                10,
                Generator::CHAR_ALNUM
            ),
            new \DateTime("now"),
            $_SERVER['REMOTE_ADDR']
        );
    }
}