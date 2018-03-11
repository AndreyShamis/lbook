# LogBOOK
LogBook is a tool which allows easy store logs and then show them on the web. 
For upload can be used curl with post log file.

# Settings
## Apache config
- max_upload_file_size
- max_execution_time

## MySQL

# A larger buffer pool requires less disk I/O to access the same table data more than once.
# On a dedicated database server, you might set the buffer pool size to 80% of the machine's
# physical memory size. Be aware of the following potential issues when configuring buffer pool size,
# and be prepared to scale back the size of the buffer pool if necessary.
~~~
innodb_buffer_pool_size     = 16G

# For MyISAM tables
tmpdir                      = /var/mysqltmp

~~~
~~~ bash
# For MyISAM tables
mkdir /var/mysqltmp
id -u mysql
id -g mysql
~~~
Edit /etc/fstab
~~~
tmpfs           /var/mysqltmp                   tmpfs rw,gid=125,uid=117,size=16G,nr_inodes=10k,mode=0700 0 0
~~~

## LDAP (optional)

# Requirements
## PHP>=7.2
* sudo apt install php7.2-ldap
* sudo apt install php7.2-zip
* sudo apt install php7.2-xml
* sudo apt install php7.2-mbstring

## Template
* http://ace.jeka.by

## Release
Releases and pre-releases can be found here https://github.com/AndreyShamis/lbook/releases

## Code review
For code review used https://review.gerrithub.io
Used **jenkins** server

## Continuous Integration
[![Build Status](https://travis-ci.org/AndreyShamis/lbook.svg?branch=master)](https://travis-ci.org/AndreyShamis/lbook)

## Author
[Andrey Shamis](https://github.com/AndreyShamis) lolnik@gmail.com
@AndreyShamis


## PSR-12
https://github.com/php-fig/fig-standards/blob/master/proposed/extended-coding-style-guide.md
~~~php
<?php

declare(strict_types=1);

namespace Vendor\Package;

use Vendor\Package\{ClassA as A, ClassB, ClassC as C};
use Vendor\Package\SomeNamespace\ClassD as D;

use function Vendor\Package\{functionA, functionB, functionC};
use const Vendor\Package\{ConstantA, ConstantB, ConstantC};

class Foo extends Bar implements FooInterface
{
    public function sampleFunction(int $a, int $b = null): array
    {
        if ($a === $b) {
            bar();
        } elseif ($a > $b) {
            $foo->bar($arg1);
        } else {
            BazClass::bar($arg2, $arg3);
        }
    }

    final public static function bar()
    {
        // method body
    }
}
~~~