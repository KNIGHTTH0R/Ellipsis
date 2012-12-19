# requires git, 7-zip
Function untar ($targetFile, $destinationFolder) {        
        $z ="7z.exe"

        $destinationFolder = (Get-Item $destinationFolder).fullname

        $tarbz2Source = $targetFile
        & "$z" x -y $tarbz2Source

        $tarSource = (get-item $targetFile).basename
        & "$z" x -y $tarSource -o $destinationFolder

        Remove-Item $tarSource
}


# download and install ellipsis
git clone https://github.com/tobius/Ellipsis.git
# download and install docblox
# download and install simpletest
$client = new-object System.Net.WebClient
$client.DownloadFile("http://downloads.sourceforge.net/project/simpletest/simpletest/simpletest_1.1/simpletest_1.1.0.tar.gz?r=http%3A%2F%2Fsimpletest.org%2Fen%2Fdownload.html&ts=1355948855&use_mirror=hivelocity",".\..\build\simpletest.tgz")
untar('.\..\build\simpletest.tgz','.\..\build\simpletest')

# fix the file/dir permissions
# encourage installer to follow the project for updated versions

