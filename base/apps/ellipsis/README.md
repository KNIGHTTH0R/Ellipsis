
Ellipsis - Version 0.2
================================================================================
Written by [Toby Miller](tobius.miller@gmail.com)
Licensed under the [MIT License](http://www.opensource.org/licenses/mit-license.php)

Introduction
--------------------------------------------------------------------------------
Ellipsis is a micro-framework written in PHP5 and it's purpose is to be useful
for experienced PHP developers who create rapid web application prototypes and
beyond. Ellipsis developers will ideally be of the hybrid variety preferring 
results over patterns and are able to easily move between procedural and 
object-oriented programming techniques. If you often find yourself writing web
applications from scratch while in between frameworks then you and Ellipsis just
might hit it off. This project is licensed as MIT software so, put quite simply,
you are free to do anything you want with it except claim authorship.

Release Notes
--------------------------------------------------------------------------------
This is alpha software and, as such, is expected to have bugs so please use at 
your own risk.

Required Software
--------------------------------------------------------------------------

+ PHP 5.3 or higher
+ Apache 2.x

Optional Software
--------------------------------------------------------------------------------

+ MySQL 5.x or higher (required by MySQL module && Repository module)
+ ChromePHP Browser Extension (useful for Ellipsis::debug)
+ FirePHP Browser Extension (useful for Ellipsis::debug)
+ cURL 7.21.x (required by HTTP module)

Installation
--------------------------------------------------------------------------------

    cd ~ && mkdir ellipsis && cd ellipsis
    curl https://raw.github.com/tobius/Ellipsis/master/quick/install.sh | bash


Directory Structure
--------------------------------------------------------------------------
This directory structure supports multiple applications, multiple versions 
of Ellipsis, and multiple websites in almost any configuration that you 
can imagine.

    ellipsis/
        bootstrap.php
        apps/
            {appname}/
                {appname}.php
                config.php
                routes.php
                lib/
                    ...
                modules/
                    ...
                src/
                    assets/
                        ...
                    htdocs/
                        ...
                    ...
                tests/
                    ...
        websites/
            {sitename}/
                config.php
                routes.php
                cache/
                    expirations/
                        ...
                    objects/
                        ...
                htdocs/
                    .htaccess
                    ...
                logs/
                    ...
                tests/
                    ...


Top-Level Directories
--------------------------------------------------------------------------------
At the top level we have two directories.

+ __apps__: php web applications that get loaded by the bootstrap
+ __websites__: apache website instances that connect users to applications


Application Directories
--------------------------------------------------------------------------------
The directory structure for each application is organized to conform to as many 
php web application architectures as possible without dictating which specific
design patterns a developer should (or should not) use. The intent behind this
directory structure is to encourage one source control repository per web 
application.

+ __{appname}__: name of the application (anything after a hyphen is ignored)
+ __{appname}/lib__: global function library used by the application (files are added to include path)
+ __{appname}/modules__: static php classes used by the application (files are added to autoload path)
+ __{appname}/src__: source documents used by the application to build web responses
+ __{appname}/src/assets__: asset files used by the application not necessarily for building web responses
+ __{appname}/src/htdocs__: web files used by the application to build web responses (also may be used for direct routing)
+ __{appname}/tests__: unit tests used by the application to validate successful PHP operations


Website Directories
--------------------------------------------------------------------------------
The directory structure for each website configuration is organized to allow for
all website configuration files and cached assets to be stored and managed in
one place. The intent behind this directory structure is to encourage one source
control repository per web application as well as one place for the system
administrator to use for configuration backups.

+ __{sitename}__: name of the website (typically a domain name)
+ __{sitename}/cache__: meta and data files used to enable response caching
+ __{sitename}/cache/expirations__: meta files used to store expiration timestamps for cached files
+ __{sitename}/cache/objects__: json files used to store cached php objects
+ __{sitename}/htdocs__: web documents to be served directly by apache (this also serves as the storage folder for cached files)
+ __{sitename}/logs__: log files created by Ellipsis applications
+ __{sitename}/tests__: unit tests used by the application to validate successful web operations


Application Configuration Files
--------------------------------------------------------------------------------
There are four files required by all Ellipsis applications which are used to
define the default behaviors of the application. It is up to the developer as to
how simple or complex these configurations should be.

+ __{appname}.php__: static php class that acts as the application gateway
+ __config.php__: php file that sets application specific environment variables
+ __routes.php__: php file that defines application routes to execute based on specific environmental conditions


Website Configuration Files
--------------------------------------------------------------------------------
There are four files required by all Ellipsis website configurations which are
used to define the default behaviors of the website. Website configurations will
override any duplicate application configurations. Again, it is up to the 
developer as to how simple or complex these configurations should be.

+ __config.php__: php file that sets application specific environment variables
+ __routes.php__: php file that defines application routes to execute based on specific environmental conditions
+ __htdocs/.htaccess__: htaccess file used to bootstrap Ellipsis and define which applications to run in this website



<!--

            ellipsis-latest/
                CHANGELOG.md
                LICENSE.md
                README.md
                TODO.md
                VERSION.md
                config.php
                filters.php
                routes.php
                ellipsis.php
                bin/
                    cli.php
                    ellipsis
                    simpletest/
                        ...
                lib/
                    php.php
                modules/
                    cache.php
                    chromephp.php
                    firephp.php
                    http.php
                    image.php
                    mongo.php
                    mysql.php
                    repository.php
                src/
                    assets/
                        cacert.pm
                        junction.ttf
                        repository/
                            create.sql
                            drop.sql
                            mock.sql
                    htdocs/
                        favicon.ico
                        index.html
                        ...
                    ...
                tests/
                    ...



apigen
http://apigen.org/

nette
http://nette.org/en/download

texy
http://texy.info/

php-token-reflection
https://github.com/Andrewsville/PHP-Token-Reflection

fshl
https://github.com/kukulich/fshl

tokenizer
http://us.php.net/manual/en/tokenizer.installation.php

mbstring
http://us.php.net/manual/en/mbstring.installation.php

iconv
http://us3.php.net/manual/en/iconv.installation.php

zlib
http://us3.php.net/manual/en/zlib.installation.php

bzip2
http://us2.php.net/manual/en/bzip2.installation.php

zip
http://us3.php.net/manual/en/zip.installation.php

-->
