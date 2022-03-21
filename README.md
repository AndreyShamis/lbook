# LogBOOK
LogBook is a tool which allows easy store logs and then show them on the web. 
For upload can be used curl with post log file.

# Settings
## Apache config
Need to change 

`sudo vi /etc/php/7.2/apache2/php.ini`

Optional `sudo vi /etc/php/7.2/cli/php.ini`

- **post_max_size** = 200M
- **upload_max_filesize** = 100M
- **max_upload_file_size**
- **max_execution_time** = 4096
- **max_input_time** = 160
- **memory_limit** = 4096M
- 

~~~ bash
sudo a2enmod rewrite
sudo systemctl restart apache2
~~~



Add this to your /etc/apache/sites-avaliable/site.name.conf
~~~ apacheconf
<Directory /var/www/logbook/public >
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

## MySQL

>A larger buffer pool requires less disk I/O to access the same table data more than once. On a dedicated database server, you might set the buffer pool size to 80% of the machine's physical memory size. Be aware of the following potential issues when configuring buffer pool size, and be prepared to scale back the size of the buffer pool if necessary.

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
* sudo apt install php7.2-ldap php7.2-zip php7.2-xml php7.2-mbstring php7.2-sqlite3

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

~~~ php
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

## Internal How To
### Install and enable APCu
~~~ bash
sudo apt install php7.2-dev
pecl install apcu
~~~
The edit /etc/php/7.2/apache2/php.ini add **extension=apcu.so**

### Change permissions
sudo chown -R www-data:www-data /var/www/lbook* ; sudo chmod -R g=u /var/www/lbook*

### Change MySql Password on new machine
[Source of manual](https://linuxconfig.org/how-to-reset-root-mysql-password-on-ubuntu-18-04-bionic-beaver-linux) 
~~~ bash
$ sudo mysql_secure_installation
$ sudo service mysql stop

$ sudo mkdir -p /var/run/mysqld
$ sudo chown mysql:mysql /var/run/mysqld

$ sudo /usr/sbin/mysqld --skip-grant-tables --skip-networking &

$ mysql -u root

mysql> FLUSH PRIVILEGES;
Query OK, 0 rows affected (0.00 sec)

mysql> USE mysql; 
Database changed
mysql> UPDATE user SET authentication_string=PASSWORD("NEWPASS") WHERE User='root';
Query OK, 0 rows affected, 1 warning (0.00 sec)
Rows matched: 1  Changed: 0  Warnings: 1

mysql> UPDATE user SET plugin="mysql_native_password" WHERE User='root';
Query OK, 0 rows affected (0.00 sec)
Rows matched: 1  Changed: 0  Warnings: 0

mysql> quit                                                                                                                                                                                    


$ sudo pkill mysqld                                                                                                                                                        

$ sudo service mysql start

~~~
###  Upload file
~~~ bash
curl --noproxy "127.0.0.1" --max-time 120 --form SETUP_NAME=DELL-KUBUNTU --form 'UPTIME_START=1.73 2.68' --form 'UPTIME_END=3.73 4.68' --form NIC=TEST --form DUTIP=172.17.0.1 --form PlatformName=Platf --form k_ver= --form Kernel=4.4 --form testCaseName=sa --form testSetName=sa --form build=Build --form testCount=2  --form file=@results-03-network_WiFi_Perf.ht40/debug/autoserv.DEBUG --form setup='SUPER SETUP3' --form token=144224564212603434  http://127.0.0.1:8080/upload/new_cli
~~~

#### Disable file upload 
~~~ bash
# in .env
DISABLE_TEST_UPLOAD=true
~~~

### session storage In mysql
~~~ bash
CREATE TABLE `lbk_customer_session` (
    `guid` VARBINARY(128) NOT NULL PRIMARY KEY,
    `sess_data` BLOB NOT NULL,
    `sess_lifetime` INTEGER UNSIGNED NOT NULL,
    `sess_time` INTEGER UNSIGNED NOT NULL,
    INDEX `sessions_sess_lifetime_idx` (`sess_lifetime`)
) COLLATE utf8mb4_bin, ENGINE = InnoDB;
~~~
### crontab
Run crontab -e
Add next lines:
~~~ bash
*/10 * * * *    wget --no-proxy -O- http://logbook.com/bot/delete_cycles >> /tmp/bot_cycle_delete.log
*/2  * * * *    wget --no-proxy -O- http://logbook.com/bot/find_cycles_for_delete >> /tmp/bot_find_cycles_for_delete.>
*/3  * * * *    wget --no-proxy -O- http://logbook.com/bot/cycle_event_delete >> /tmp/bot_cycle_event_delete.log
*/40 * * * *    wget --no-proxy -O- http://logbook.com/bot/setups/clean > /dev/null
*/3 * * * *     wget --no-proxy -O- http://logbook.com/bot/setups/count_cycles > /dev/null
*/13 * * * *    wget --no-proxy -O- http://logbook.com/reports/auto/create > /dev/null
*/1 * * * *     wget --no-proxy -O- http://logbook.com/api/send_emails > /dev/null
*/3 * * * *     sleep 15; wget --no-proxy -O- http://logbook.com/api/cycle/auto/cycle_close   > /dev/null 2>&1
*/2  * * * *    sleep 15; wget --no-proxy -O- http://logbook.com/bot/cycle_event_delete >> /tmp/bot_cycle_event_delet>
* */12 * * *    sleep 15; wget --no-proxy -O- http://logbook.com/build/clean_not_used   > /dev/null 2>&1
* */6 * * *     sleep 15; wget --no-proxy -O- http://logbook.com/bot/setups/clean  >> /tmp/logbook.setup.clean_not_us>
3 */12 * * *    sleep 15; wget --no-proxy -O- http://logbook.com/suites/calculate/3  > /dev/null 2>&1
2 */6 * * *     sleep 15; wget --no-proxy -O- http://logbook.com/suites/calculate/2  > /dev/null 2>&1
1 */2 * * *     sleep 15; wget --no-proxy -O- http://logbook.com/suites/calculate/1  > /dev/null 2>&1
* */20 * * *    sleep 15; wget --no-proxy -O- http://logbook.com/suites/close_unclosed/5  > /dev/null 2>&1
4 */3 * * *     sleep 15; wget --no-proxy -O- http://logbook.com/suites/close_unclosed/3  > /dev/null 2>&1
~~~

### Tweak your Swap Settings
https://www.digitalocean.com/community/tutorials/how-to-add-swap-on-ubuntu-14-04
~~~ bash
cat /proc/sys/vm/swappiness
~~~
The swappiness parameter configures how often your system swaps data out of RAM to the swap space. This is a value between 0 and 100 that represents a percentage.

With values close to zero, the kernel will not swap data to the disk unless absolutely necessary. Remember, interactions with the swap file are “expensive” in that they take a lot longer than interactions with RAM and they can cause a significant reduction in performance. Telling the system not to rely on the swap much will generally make your system faster.

Values that are closer to 100 will try to put more data into swap in an effort to keep more RAM space free. Depending on your applications’ memory profile or what you are using your server for, this might be better in some cases.