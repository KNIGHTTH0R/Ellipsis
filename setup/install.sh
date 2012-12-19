#!/bin/sh

# download and install ellipsis
git clone https://github.com/tobius/Ellipsis.git
# download and install docblox
# download and install simpletest
wget http://downloads.sourceforge.net/project/simpletest/simpletest/simpletest_1.1/simpletest_1.1.0.tar.gz?r=http%3A%2F%2Fsimpletest.org%2Fen%2Fdownload.html&ts=1355948855&use_mirror=hivelocity
tar -xvf simpletest_1.1.0.tar.gz ../build/simpletest

# fix the file/dir permissions
# encourage installer to follow the project for updated versions

