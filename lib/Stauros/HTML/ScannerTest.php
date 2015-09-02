<?php

/*
 * This file is part of Stauros, a fast XSS filtering engine
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace Stauros\HTML;

class ScannerTest extends \PHPUnit_Framework_TestCase {
    
    public static function provideVectors() {
        yield [new Config(["p"=> []]), "<p>Good Text</p>", "<p>Good Text</p>"];
        yield [new Config([]), "<p>Bad Text</p>", "Bad Text"];
        yield [new Config, "<SCRIPT SRC=http://ha.ckers.org/xss.js></SCRIPT>", ""];
        yield [new Config, '<IMG SRC="javascript:alert(\'XSS\');">', "<IMG>"];
        yield [new Config, "<IMG SRC=javascript:alert('XSS')>", "<IMG>"];
        yield [new Config, "<IMG SRC=JaVaScRiPt:alert('XSS')>", "<IMG>"];
        yield [new Config, "<IMG SRC=`javascript:alert(\"RSnake says, 'XSS'\")`>", "<IMG>"];
        yield [new Config, '<a onmouseover="alert(document.cookie)">xss link</a>', '<a>xss link</a>'];
        yield [new Config, '<a onmouseover=alert(document.cookie)>xss link</a>', '<a>xss link</a>'];
        yield [new Config, '<IMG """><SCRIPT>alert("XSS")</SCRIPT>">', '<IMG>alert("XSS")"&gt;'];
        yield [new Config, '<IMG SRC=javascript:alert(String.fromCharCode(88,83,83))>', '<IMG>'];
        yield [new Config, '<IMG SRC=# onmouseover="alert(\'xxs\')">', '<IMG>'];
        yield [new Config, '<IMG SRC= onmouseover="alert(\'xxs\')">', '<IMG>'];
        yield [new Config, '<IMG onmouseover="alert(\'xxs\')">', '<IMG>'];
        yield [new Config, '<IMG SRC=/ onerror="alert(\'xxs\')">', '<IMG>'];
        yield [new Config, '<img src=x onerror="&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058&#0000097&#0000108&#0000101&#0000114&#0000116&#0000040&#0000039&#0000088&#0000083&#0000083&#0000039&#0000041">', '<img>'];
        yield [new Config, '<IMG SRC=&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#88;&#83;&#83;&#39;&#41;>', '<IMG>'];
        yield [new Config, '<IMG SRC=&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058&#0000097&#0000108&#0000101&#0000114&#0000116&#0000040&#0000039&#0000088&#0000083&#0000083&#0000039&#0000041>', '<IMG>'];
        yield [new Config, '<IMG SRC=&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A&#x61&#x6C&#x65&#x72&#x74&#x28&#x27&#x58&#x53&#x53&#x27&#x29>', '<IMG>'];
        yield [new Config, '<IMG SRC="jav   ascript:alert(\'XSS\');">', '<IMG>'];
        yield [new Config, '<IMG SRC="jav&#x0a;ascript:alert(\'XSS\');">', '<IMG>'];
        yield [new Config, '<IMG SRC="jav&#x0D;ascript:alert(\'XSS\');">', '<IMG>'];
        yield [new Config, '<IMG SRC="jav' . chr(0) . 'ascript:alert(\'XSS\');">', '<IMG>'];
        yield [new Config, '<IMG SRC=" &#14; javascript:alert(\'XSS\');">', '<IMG>'];

        yield [new Config, '<SCRIPT/XSS SRC="http://ha.ckers.org/xss.js"></SCRIPT>', ''];
        yield [new Config, '<BODY onload!#$%&()*~+-_.,:;?@[/|\]^`=alert("XSS")>', ''];
        yield [new Config, '<IMG onload!#$%&()*~+-_.,:;?@[/|\]^`=alert("XSS")>', '<IMG>'];

        yield [new Config, '<SCRIPT/SRC="http://ha.ckers.org/xss.js"></SCRIPT>', ''];
        yield [new Config, '<<SCRIPT>alert("XSS");//<</SCRIPT>', 'alert("XSS");//'];
        yield [new Config, '<SCRIPT SRC=http://ha.ckers.org/xss.js?< B >', ''];
        yield [new Config, '<SCRIPT SRC=//ha.ckers.org/.j>', ''];
        yield [new Config, '<IMG SRC="javascript:alert(\'XSS\')"', '<IMG>'];
        yield [new Config, '<iframe src=http://ha.ckers.org/scriptlet.html <', ''];
        yield [new Config, '<!--[if gte IE 4]><SCRIPT>alert(\'XSS\');</SCRIPT><![endif]-->', ''];
        yield [new Config, '<SCRIPT a=">" SRC="http://ha.ckers.org/xss.js"></SCRIPT>', ''];
        yield [new Config, '<SCRIPT =">" SRC="http://ha.ckers.org/xss.js"></SCRIPT>', ''];
        yield [new Config, '<SCRIPT a=">" \'\' SRC="http://ha.ckers.org/xss.js"></SCRIPT>', ''];
        yield [new Config, '<SCRIPT>document.write("<SCRI");</SCRIPT>PT SRC="http://ha.ckers.org/xss.js"></SCRIPT>', 'document.write(">'];

    }

    /**
     * @dataProvider provideVectors
     */
    public function testVectors($rules, $input, $expected) {
        $scanner = new Scanner($rules);
        $scanner->start();
        $output = $scanner->scan($input);
        $output .= $scanner->end();
        $this->assertEquals($expected, $output);
    }

    public function testAttributeCallbackPositive() {
        $called = 0;
        $cb = function() use (&$called) {
            $called++;
            return true;
        };
        $config = new Config;
        $config->addAttributeCallback($cb);
        $scanner = new Scanner($config);
        $scanner->start();
        $output = $scanner->scan("<a href='http://blah.com'>blah</a>");
        $output .= $scanner->end();
        $this->assertEquals(1, $called);
        $this->assertEquals("<a href=\"http://blah.com\">blah</a>", $output);
    }

    public function testAttributeCallback() {
        $called = 0;
        $cb = function() use (&$called) {
            $called++;
            return false;
        };
        $config = new Config;
        $config->addAttributeCallback($cb);
        $scanner = new Scanner($config);
        $scanner->start();
        $output = $scanner->scan("<a href='http://blah.com'>blah</a>");
        $output .= $scanner->end();
        
        $this->assertEquals(1, $called);
        $this->assertEquals("<a>blah</a>", $output);
    }
}