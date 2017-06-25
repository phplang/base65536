# base65536

Base65536 is a binary encoding optimised for UTF-32-encoded text and Twitter. This PHP composer package, `phplang/base65536`, is loosely based on [qntm/base65536](https://github.com/qntm/base65536).

## Usage

```php
use \PhpLang\Base65536;

$buf = 'hello world';
$str = Base65536::encode($buf);
echo $str; // 6 codes points, '驨ꍬ啯𒁷ꍲᕤ'

var_dump($buf === Base65536::decode($str)); // bool(true)
```

#### Note

Per the spec, the default encoding used for input to `decode()` and output from `encode()` is [CESU-8](https://en.wikipedia.org/wiki/CESU-8), a variant of `UTF-8` which encodes split `UTF-16` surrogate pairs.  If you want true `UTF-8` output, you must specify so using the second parameter to `encode()` and `decode()`.

## License

MIT, to match the generous licensing of the original. :D
