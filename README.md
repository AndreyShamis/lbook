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

#This sets the size of the InnoDB’s redo log files which, in MySQL world, are often called simply transaction logs. 
#And right until MySQL 5.6.8 the default value of innodb_log_file_size=5M was the single biggest InnoDB 
#performance killer. Starting with MySQL 5.6.8, the default was raised to 48M which, for many intensive systems, 
#is still way too low.
#As a rule of thumb you should set this to accommodate ~1-2h worth of writes and if you’re in a hurry, 
#having this set to 1-2G will give you pretty good performance with pretty much any workload.
innodb_log_file_size = 1G

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
### Set timezone
sudo dpkg-reconfigure tzdata

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

## Apache
~~~
<Directory /var/www/lbook/public >
                Options Indexes FollowSymLinks MultiViews
                AllowOverride All
                Order allow,deny
                allow from all
                Allow from All
        <IfModule mod_rewrite.c>
            Options -MultiViews
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule ^(.*)$ index.php [QSA,L]
        </IfModule>
        </Directory>
~~~

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