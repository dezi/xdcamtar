xdcamtar
========

XDCAM Processing

The following config needs to be added to apache/httpd.conf, where "/Users/dezi" needs to be the real path to install:

DocumentRoot "/Users/dezi/xdcamtar/server/www"

\<Directory "/Users/dezi/xdcamtar/server/www"\>

    Options Indexes FollowSymLinks MultiViews
    Order allow,deny
    Allow from all
    
    \<Limit GET POST PUT DELETE HEAD OPTIONS>
        Order allow,deny
        Allow from all
    \</Limit\>

\</Directory\>

\<Directory "/Users/dezi/xdcamtar/server/php"\>

    AllowOverride None
    Options None
    Order allow,deny
    Allow from all
    
\</Directory\>

\<IfModule alias_module\>

    AliasMatch ^/(status) "/Users/dezi/xdcamtar/server/php/$1.php"
    AliasMatch ^/(tarman) "/Users/dezi/xdcamtar/server/php/$1.php"
    AliasMatch ^/(output) "/Users/dezi/xdcamtar/server/php/$1.php"
    AliasMatch ^/(getjob) "/Users/dezi/xdcamtar/server/php/$1.php"

\</IfModule\>
