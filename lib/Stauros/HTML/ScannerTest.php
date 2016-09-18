<?php

/*
 * This file is part of Stauros, a fast XSS filtering engine
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace Stauros\HTML;

use Stauros\Stauros;

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

    public function testScanHTML()
    {

        $testArray = array(
            // script is escaped, is it safe?
            'http://vulnerable.info/poc/poc.php?foo=%3Csvg%3E%3Cscript%3E/%3C1/%3Ealert(document.domain)%3C/script%3E%3C/svg%3E' => 'http://vulnerable.info/poc/poc.php?foo=%3Csvg%3E%3Cscript%3E/%3C1/%3Ealert(document.domain)%3C/script%3E%3C/svg%3E',

            // Google XSS in IE | 2015: http://blog.bentkowski.info/2015/04/xss-via-host-header-cse.html
            'Location: https://www.google.com%3a443%2fcse%2ftools%2fcreate_onthefly%3b%3c%2ftextarea%3e%3csvg%2fonload%3dalert%28document%2edomain%29%3e%3b%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f' => 'Location: https://www.google.com%3a443%2fcse%2ftools%2fcreate_onthefly%3b%3c%2ftextarea%3e%3csvg%2fonload%3dalert%28document%2edomain%29%3e%3b%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f%2e%2e%2f',

            // script is escaped, is it safe? | IE11 in IE8 docmode #mxss | https://twitter.com/0x6D6172696F/status/626379000181596160
            'with(document)body.appendChild(createElement(\'iframe onload=&#97&#108&#101&#114&#116(1)>\')),body.innerHTML+=\'\'' => 'with(document)body.appendChild(createElement(\'iframe onload=&#97&#108&#101&#114&#116(1)&gt;\')),body.innerHTML+=\'\'',

            // XSS to attack "pfSense" - https://www.htbridge.com/advisory/HTB23251
            'https://[host]/diag_logs_filter.phpfilterlogentries_submit=1&filterlogentries_qty=%27%22%3E%3Cscript%3Ealert%28%27ImmuniWeb%27%29;%3C/script%3E' => "https://[host]/diag_logs_filter.phpfilterlogentries_submit=1&filterlogentries_qty=%27%22%3E%3Cscript%3Ealert%28%27ImmuniWeb%27%29;%3C/script%3E",

            // show a alert with FF 2015-09 | is it safe??
            '(_=alert,_(1337))' => '(_=alert,_(1337))',

            // script is escaped, is it safe?
            "http://www.amazon.com/s/ref=amb_link_7189562_72/002-2069697-5560831?ie =UTF8&amp;node=&quot;/&gt;&lt;script&gt;alert('XSS');&lt;/script&gt;&a mp;pct-off=25-&amp;hidden-keywords=athletic|outdoor&amp;pf_rd_m=ATVPDK IKX0DER&amp;pf_rd_s=center-5&amp;pf_r" => "http://www.amazon.com/s/ref=amb_link_7189562_72/002-2069697-5560831?ie =UTF8&amp;node=&quot;/&gt;&lt;script&gt;alert('XSS');&lt;/script&gt;&a mp;pct-off=25-&amp;hidden-keywords=athletic|outdoor&amp;pf_rd_m=ATVPDK IKX0DER&amp;pf_rd_s=center-5&amp;pf_r",

            // script is escaped, is it safe?
            'http://www.amazon.com/s/ref=amb_link_7581132_5/102-9803838-3100108?ie= UTF8&amp;node=&quot;/&gt;&lt;script&gt;alert(&quot;XSS&quot;);&lt;/scr ipt&gt;&amp;keywords=Lips&amp;emi=A19ZEOAOKUUP0Q&amp;pf_rd_m=ATVPDKIKX 0DER&amp;pf_rd_s=left-1&amp;pf_rd_r=1JMP7' => 'http://www.amazon.com/s/ref=amb_link_7581132_5/102-9803838-3100108?ie= UTF8&amp;node=&quot;/&gt;&lt;script&gt;alert(&quot;XSS&quot;);&lt;/scr ipt&gt;&amp;keywords=Lips&amp;emi=A19ZEOAOKUUP0Q&amp;pf_rd_m=ATVPDKIKX 0DER&amp;pf_rd_s=left-1&amp;pf_rd_r=1JMP7',

            // no style-tag, is it safe?
            '<style>p[foo=bar{}*{-o-link:\'javascript:alert(1)\'}{}*{-o-link-source:current}*{background:red}]{background:green};</style>' => 'p[foo=bar{}*{-o-link:\'javascript:alert(1)\'}{}*{-o-link-source:current}*{background:red}]{background:green};',

            // no style-tag, is it safe?
            "<STYLE>li {list-style-image: url(\"javascript:alert('XSS')\");}</STYLE></br>" => 'li {list-style-image: url("javascript:alert(\'XSS\')");}',

            // no style-tag, is it safe?
            '<STYLE>BODY{-moz-binding:url("http://ha.ckers.org/xssmoz.xml#xss")}</STYLE>' => 'BODY{-moz-binding:url("http://ha.ckers.org/xssmoz.xml#xss")}',

            // no style-tag, is it safe?
            '<STYLE>.XSS{background-image:url("javascript:alert(\'XSS\')");}</STYLE><A CLASS=XSS></A>' => '.XSS{background-image:url("javascript:alert(\'XSS\')");}<A></A>',

            // JS in ActionScript, safe?
            'getURL("javascript:alert(\'XSS\')")' => 'getURL("javascript:alert(\'XSS\')")',
    );

        $stauros = new Stauros;
        foreach ($testArray as $before => $after) {
            self::assertEquals($after, $stauros->scanHTML($before), 'testing: ' . $before);
        }
    }
}
