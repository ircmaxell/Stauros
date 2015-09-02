<?php

/*
 * This file is part of Stauros, a fast XSS filtering engine
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace Stauros\HTML;

class Scanner {

    const STATE_MASK = 0xFFFFFFFFFFFF00;

    private $buffer = "";
    private $currentTag = null;
    private $currentAttr = null;

    private $tagStartChars = [
        'a'=> 1,'b'=>1,'c'=>1,'d'=>1,'e'=>1,'f'=>1,'g'=>1,'h'=>1,'i'=>1,'j'=>1,'k'=>1,'l'=>1,'m'=>1,'n'=>1,'o'=>1,'p'=>1,'q'=>1,'r'=>1,'s'=>1,'t'=>1,'u'=>1,'v'=>1,'w'=>1,'x'=>1,'y'=>1,'z'=>1,
        'A'=> 1,'B'=>1,'C'=>1,'D'=>1,'E'=>1,'F'=>1,'G'=>1,'H'=>1,'I'=>1,'J'=>1,'K'=>1,'L'=>1,'M'=>1,'N'=>1,'O'=>1,'P'=>1,'Q'=>1,'R'=>1,'S'=>1,'T'=>1,'U'=>1,'V'=>1,'W'=>1,'X'=>1,'Y'=>1,'Z'=>1,
        '0'=> 1,'1'=>1,'2'=>1,'3'=>1,'4'=>1,'5'=>1,'6'=>1,'7'=>1,'8'=>1,'9'=>1,
    ];

    private $state = State::HTML;
    private $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    public function scan($block) {
        $state = $this->state;
        $buffer = $this->buffer;
        $output = '';
        for ($i = 0, $n = strlen($block); $i < $n; $i++) {
            $c = $block[$i];
            switch ($state & 0xFF) {
                case State::HTML:
                    if ($c == '<') {
                        $state = ($state << 8) | State::TAG_START;
                    } elseif ($c === '>') {
                        $output .= "&gt;";
                    } else {
                        $output .= $c;
                    }
                    break;
                case State::TAG_START:
                    if ($c === '/' && $buffer === '') {
                        $state = ($state & self::STATE_MASK) | State::TAG_START_CLOSE;
                        break;
                    } elseif ($buffer === '' && strpos("\x20\f\t\n\r", $c) !== false) { // is whitespace
                        $output .= "&lt;" . $c;
                        $state >>= 8;
                        break;
                    }
                case State::TAG_START_CLOSE:
                    if (isset($this->tagStartChars[$c])) {
                        $buffer .= $c;
                    } elseif ($c === '!' && $buffer === '') {
                        $buffer .= '!';
                    } else {
                        if ($c === '-') {
                            if ($buffer === '!-') {
                                $state = ($state & self::STATE_MASK) | State::IN_COMMENT;
                                break;
                            }
                            $buffer .= $c;
                            break;
                        }
                        // check the tag name
                        $tagName = strtolower($buffer);
                        if (isset($this->config->tagWhiteList[$tagName])) {
                            // tag is valid, flush the opening of the tag to output
                            $this->currentTag = $tagName;
                            $output .= '<';
                            if (($state & 0xFF) === State::TAG_START_CLOSE) {
                                $output .= '/';
                            }
                            $output .= $buffer;
                            $buffer = '';
                            $state = ($state & self::STATE_MASK) | State::TAG_OPEN;
                            goto tag_open;
                        } elseif ($c === '>') {
                            $state >>= 8;
                            $buffer = '';
                        } else {
                            $state = ($state & self::STATE_MASK) | State::TAG_SKIP;
                            $buffer = '';
                            goto tag_skip;
                        }
                    }
                    break;
                case State::TAG_OPEN:
tag_open:
                    if (strpos("\x20\f\t\n\r\0\"'>/=", $c) === false) { // isAttributeName
                        $buffer .= $c;
                        break;
                    } elseif ($buffer !== '') {
                        // attribute definition
                        $lowerAttr = strtolower($buffer);
                        if (isset($this->config->tagWhiteList[$this->currentTag][$lowerAttr])) {
                            // valid attribute
                            $this->currentAttr = $lowerAttr;
                            $buffer = '';
                            $state = ($state << 8) | State::ATTR_START;
                            goto attr_start;
                        } else {
                            $buffer = '';
                            $state = ($state << 8) | State::ATTR_SKIP;
                            goto attr_skip;
                        }
                    } elseif ($c === '>') {
                        $output .= '>';
                        $state >>= 8;
                    }
                    break;
                case State::ATTR_START:
attr_start:
                    if (strpos("\x20\f\t\n\r", $c) !== false) { // is whitespace
                        break;
                    } elseif ($c !== '=') {
                        // next attribute
                        $output .= $this->processValue($this->currentAttr);
                        $state >>= 8;
                        $this->assertState(State::TAG_OPEN, $state);
                        goto tag_open;
                    }
                    $state = ($state & self::STATE_MASK) | State::ATTR_VALUE;
                    break;
                case State::ATTR_VALUE:
                case State::ATTR_SKIP:
attr_skip:
                    if ($c === '"' || $c === "'" || $c === "`") {
                        if ($buffer === '') {
                            $newstate = $c === '"' ? State::DOUBLE_QUOTE : ($c === "'" ? State::SINGLE_QUOTE : State::GRAVE_QUOTE);
                            $state = ($state << 8) | $newstate;
                        } else {
                            if (($state & 0xFF) === State::ATTR_VALUE) {
                                $output .= $this->processValue($this->buffer);
                            }
                            $buffer = '';
                            $state = ($state & self::STATE_MASK) | State::CONSUME_TO_WHITESPACE;
                        }
                    } elseif (strpos("\x20\f\t\n\r", $c) !== false) { // is whitespace
                        if ($buffer !== '') {
                            // end of attribute value\
                            if (($state & 0xFF) === State::ATTR_VALUE) {
                                $output .= $this->processValue($buffer);
                            }
                            $buffer = '';
                            $state >>= 8;
                            goto tag_open;
                        }
                        // skip whitespace
                    } elseif ($c === '>') {
                        if ($buffer !== '') {
                            if (($state & 0xFF) === State::ATTR_VALUE) {
                                $output .= $this->processValue($buffer);
                            }
                            $buffer = '';
                        }
                        $state >>= 8;
                        goto tag_open;
                    } elseif (strpos("\x20\f\t\n\r\0\"'>/=`", $c) !== false) { // invalid unquoted
                        if ($buffer !== '') {
                            if (($state & 0xFF) === State::ATTR_VALUE) {
                                $output .= $this->processValue($buffer);
                            }
                            $buffer = '';
                        }
                        $state = ($state & self::STATE_MASK) | State::CONSUME_TO_WHITESPACE;
                    } else {
                        $buffer .= $c;
                    }
                    break;
                case State::TAG_SKIP:
tag_skip:
                    if ($c === '>') {
                        $state >>= 8;
                        $buffer = '';
                    } elseif ($c == "'") {
                        $state = ($state << 8) | State::SINGLE_QUOTE;
                    } elseif ($c == '"') {
                        $state = ($state << 8) | State::DOUBLE_QUOTE;
                    }
                    break;
                case State::SINGLE_QUOTE:
                case State::DOUBLE_QUOTE:
                case State::GRAVE_QUOTE:
                    $tmp = $state & 0xFF;
                    if (($c === "'" && $tmp === State::SINGLE_QUOTE)
                        || ($c === '"' && $tmp === State::DOUBLE_QUOTE)
                        || ($c === '`' && $tmp === State::GRAVE_QUOTE)) {
                        $state >>= 8;
                        if (($state & 0xFF) === State::ATTR_VALUE) {
                            $output .= $this->processValue($buffer);
                            $state >>= 8;
                        }
                        $buffer = '';
                    } else {
                        $buffer .= $c;
                    }
                    break;
                case State::CONSUME_TO_WHITESPACE:
                    if (strpos("\x20\f\t\n\r", $c) !== false) { // is whitespace
                        $state >>= 8;
                    } elseif($c === '>') {
                        $state >>= 8;
                        goto tag_open;
                    }
                    break;
                case State::IN_COMMENT:
                    $buffer .= $c;
                    if ($c === '>' && isset($buffer[3]) && substr($buffer, -3) === '-->') {
                        $state >>= 8;
                        $buffer = '';
                    }
                    break;
                default:
                    throw new \LogicException("Invalid State: " . ($state & 0xFF));
            }
        }
        $this->buffer = $buffer;
        $this->state = $state;
        return $output;
    }

    public function start() {
        $this->state = State::HTML;
        $this->buffer = '';
    }

    public function end() {
        $state = $this->state;
        $this->state = 0;
        if (($state & 0xFF) != State::HTML && ($state & 0xFF != State::TAG_SKIP)) {
            return '>';
        }
        return '';
    }

    private function processValue($value) {
        $value = html_entity_decode($value, ENT_HTML5 | ENT_QUOTES, $this->config->charset);
        if (isset($this->config->uriAttrs[$this->currentAttr])) {
            $parts = parse_url($value);
            if (!isset($parts['scheme']) || !isset($this->config->uriAllowedSchemes[strtolower($parts['scheme'])])) {
                return '';
            }
        }
        foreach ($this->config->attributeCallbacks as $cb) {
            if (!$cb($this->currentAttr, $value)) {
                return '';
            }
        }
        return sprintf(' %s="%s"', $this->currentAttr, htmlspecialchars($value, ENT_HTML5, $this->config->charset));
    }

    private function assertState($s1, $current) {
        if (($current & 0xFF) !== $s1) {
            throw new \LogicException("Invalid state nesting at state $current");
        }
    }
}

