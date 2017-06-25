<?php declare(strict_types=1);
namespace PhpLang;

class Base65536 {
    protected static $encodeTable = [];
    protected static $decodeTable = [];

    public static function init() {
        static $initialized = false;
        if ($initialized) return;

        $ranges = [
            0x01500 => 0x015FF, // Padding block
            0x03400 => 0x04CFF,
            0x04E00 => 0x09EFF,
            0x0A100 => 0x0A3FF,
            0x0A500 => 0x0A5FF,
            0x10600 => 0x106FF,
            0x12000 => 0x122FF,
            0x13000 => 0x133FF,
            0x14400 => 0x145FF,
            0x16800 => 0x169FF,
            0x20000 => 0x285FF,
        ];

        $block = -1;
        foreach ($ranges as $start => $end) {
            for (;$start < $end; $start += 0x100, ++$block) {
                self::$encodeTable[$block] = $start;
                self::$decodeTable[$start] = $block;
            }
        }
        $initialized = true;
    }

    /**
     * Main encoding algorithm
     *
     * Only deals with two octets of input at a time,
     * yielding exactly one integer (UTF-32) codepoint as output.
     *
     * @param iterable - Data source
     * @yield int - Two bytes of data mapped to Unicode ordinals
     */
    public static function encode_iterable(iterable $str): \Generator {
        $table = static::$encodeTable;
        $cp = null;
        foreach ($str as $chr) {
            if ($cp === null) {
                $cp = ord($chr);
                continue;
            }
            yield $cp | $table[ord($chr)];
            $cp = null;
        }
        if ($cp !== null) {
            yield $cp | $table[-1];
        }
    }

    /**
     * String based convenience wrapper for encode_iterable()
     *
     * What any given user will probably want in reality.
     * Translates a string of input to a string of output.
     * Encoding defaults to CESU8 per the "spec" github.com/qntm/base65536
     * UTF8 would produce smaller encoded output, but meh...
     *
     * @param string - Input string of binary data
     * @param string - Output encoding (default: CESU8)
     *
     * @return string - Base65536 encoded $input
     */
    public static function encode(string $str, string $encoding = 'CESU8'): string {
        $str = (function($str) {
            for ($i = 0; $i < strlen($str); ++$i) {
                yield $str[$i];
            }
        })($str);

        $ret = '';
        foreach (static::encode_iterable($str, $encoding) as $chr) {
            $ret .= \IntlChar::chr($chr);
        }
        return \UConverter::transcode($ret, $encoding, 'UTF8');
    }


    /**
     * Main decode algorithm
     *
     * Accepts numeric codepoints as iterator inputs and produces
     * 1 or 2 bytes of binary data as output.
     *
     * @param iterable - Input data
     * @yields char - One or two octets per input codepoint
     */
    public static function decode_iterable(iterable $str): \Generator {
        $table = static::$decodeTable;
        foreach ($str as $cp) {
            $b2 = $table[$cp & 0xFFFFFF00] ?? null;
            if ($b2 === null) {
                if (\IntlChar::isWhitespace($cp)) continue;
                throw new \InvalidArgumentException(sprintf("U+%04X %s is not a valid base65536 character",
                                                            $cp, \IntlChar::charName($cp)));
            }
            yield chr($cp & 0xFF);
            if ($b2 !== -1) {
                yield chr($b2);
            }
        }
    }

    /**
     * String based convenience wrapper for decode_iterable()
     *
     * What any given user will probably want in reality.
     * Translates an encoded string of input to a string of binary output.
     *
     * @param string - Input string of binary data
     * @param string - Input encoding (default: CESU8)
     *
     * @return string - Binary data
     */
    public static function decode(string $str, string $encoding = 'CESU8'): string {
        $str = \UConverter::transcode($str, 'UTF8', $encoding);
        $str = (function($str) {
            $len = strlen($str);
            for ($i = 0; $i < $len;) {
                $c = ord($str[$i]);
                if (($c & 0x80) == 0x00) { ++$i; } else
                if (($c & 0xE0) == 0xC0) { $c = \IntlChar::ord(substr($str, $i, 2)); $i += 2; } else
                if (($c & 0xF0) == 0xE0) { $c = \IntlChar::ord(substr($str, $i, 3)); $i += 3; } else
                if (($c & 0xF8) == 0xF0) { $c = \IntlChar::ord(substr($str, $i, 4)); $i += 4; } else $c = null;
                if ($c === null) {
                    throw new \InvalidArgumentException("Encountered invalid characters in input");
                }
                yield $c;
            }
        })($str);

        $ret = '';
        foreach (static::decode_iterable($str) as $chr) {
            $ret .= $chr;
        }
        return $ret;
    }
}
Base65536::init();
