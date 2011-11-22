Ellipsis - Version 0.2
================================================================================
Written by [Toby Miller](tobius.miller@gmail.com)
Licensed under the [MIT License](http://www.opensource.org/licenses/mit-license.php)

Introduction
--------------------------------------------------------------------------------
Ellipsis is a micro-framework written in PHP5 and it's purpose is to be useful
for experienced PHP developers who create rapid web application prototypes and
beyond. Ellipsis developers will ideally be of the hybrid variety preferring 
results over patterns and able to easily move between procedural and 
object-oriented programming techniques. If you often find yourself writing web
applications from scratch while in between frameworks then you and Ellipsis just
might hit it off. This project is licensed as MIT software so, put quite simply,
you are free to do anything you want with it except claim authorship.

Release Notes
--------------------------------------------------------------------------------
This is the alpha release of Ellipsis and, as such, is expected to have bugs so
please use at your own risk.

Required Software
--------------------------------------------------------------------------------
* PHP 5.3 or higher
* Apache 2.x

Optional Software
--------------------------------------------------------------------------------
* MySQL 5.x or higher (required by MySQL module && Repository module)
* ChromePHP Browser Extension (useful for Ellipsis::debug)
* FirePHP Browser Extension (useful for Ellipsis::debug)
* cURL 7.21.x (required by HTTP module)
* Graphviz 2.28 or higher (used by Docblox to generate Class Inheritance Diagram)

Installation Instructions
--------------------------------------------------------------------------------
Create a local project folder

    mkdir ~/Projects/your_project

Install Ellipsis (this framework)

    cd ~/Projects/your_project
    git clone https://github.com/tobius/Ellipsis.git .

Install Simpletest (to run unit tests)

    mkdir ~/Projects/your_project/utils
    cd ~/Projects/your_project/utils
    svn co https://simpletest.svn.sourceforge.net/svnroot/simpletest/simpletest/trunk simpletest

Install Docblox (to generate php api documentation)

    cd ~/Projects/your_project/utils
    git clone https://github.com/mvriel/Docblox.git docblox

Fix permissions

    cd ~/Projects/your_project
    chmod 755 rundocs
    chmod 755 runtests

Add this project to your Apache configuration, for example:

    <VirtualHost *:80>
        DocumentRoot "/Users/tmiller/Sites/your_project"
        ServerName local.your_project.com
        LogFormat "%V %h %l %u %t \"%r\" %s %b" vcommon
        ErrorLog "logs/vhosts-your_project-error_log"
        CustomLog "logs/vhosts-your_project-access_log" vcommon
        <Directory "/Users/tmiller/Sites/your_project">
            Options Indexes FollowSymLinks ExecCGI Includes
            AllowOverride All
            Order allow,deny
            Allow from all
        </Directory>
    </VirtualHost>

Restart Apache and visit your installation, for example:

    sudo xampp restart
    open http://local.your_project.com

Generate PHP Documentation

    cd ~/Projects/your_project
    ./rundocs
    open http://local.your_project.com/docs/index.html

Run Unit Tests

    cd ~/Projects/your_project
    ./runtests

Documentation
--------------------------------------------------------------------------------
* README.md - this file
* TODO.md - tasks left to do

Known Problems
--------------------------------------------------------------------------------
@todo: still in alpha

