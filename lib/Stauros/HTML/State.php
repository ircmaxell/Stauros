<?php

/*
 * This file is part of Stauros, a fast XSS filtering engine
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace Stauros\HTML;

abstract class State {
    const HTML = 0;
    const TAG_START = 1;
    const TAG_START_CLOSE = 2;
    const TAG_OPEN = 3;
    const TAG_SKIP = 4;
    const ATTR_START = 5;
    const ATTR_SKIP = 6;
    const ATTR_VALUE = 7;
    const SINGLE_QUOTE = 8;
    const DOUBLE_QUOTE = 9;
    const GRAVE_QUOTE = 10;
    const CONSUME_TO_WHITESPACE = 11;
    const IN_COMMENT = 12;
}