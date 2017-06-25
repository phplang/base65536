<?php

use PHPUnit\Framework\TestCase;
use PhpLang\Base65536 as b64k;

class Base64Test extends TestCase {

    public function testRandom() {
        for ($i = 0; $i < 1000; ++$i) {
            $data = random_bytes(random_int(16, 64));
            $enc = b64k::encode($data);
            $this->assertTrue(strlen($enc) > strlen($data));
            $this->assertSame($data, b64k::decode($enc));
        }
    }

    public function testKnown() {
        $tests = [
            "\x00" => "\u{1500}",
            "\x01" => "\u{1501}",
            "\xFE" => "\u{15FE}",
            "\xFF" => "\u{15FF}",
            "\x00\x00" => "\u{3400}",
            "\x01\x00" => "\u{3401}",
            "\x00\x01" => "\u{3500}",
            "\xFE\xFF" => "\u{285FE}",
            "\xFF\xFF" => "\u{285FF}",
        ];
        foreach ($tests as $bin => $enc) {
            $this->assertSame($enc, b64k::encode($bin, 'UTF8'));
            $this->assertSame($bin, b64k::decode($enc, 'UTF8'));
        }
    }
}
