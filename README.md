Ellipsis - Version 0.2
================================================================================
Written by [Toby Miller](tobius.miller@gmail.com)
Licensed under the [MIT License](http://www.opensource.org/licenses/mit-license.php)

Introduction
--------------------------------------------------------------------------------
Ellipsis is a micro-framework written in PHP for rapid application development
and prototyping. At its core Ellipsis provides a knowledgeable PHP developer 
with all of the necessary tools to quickly create a web application. This 
project is licensed as MIT software so, put quite simply, you are free to do 
anything you want with it except claim authorship.

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

Clone Ellipsis

    cd ~/Projects/your_project
    git clone https://github.com/tobius/Ellipsis.git

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

@todo: decide what should go on the wiki - https://github.com/tobius/Ellipsis/wiki

Known Problems
--------------------------------------------------------------------------------
@todo: still in alpha

