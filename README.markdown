## NOT MAINTAINED ##

This extension is no longer maintained and is only left here to point anybody visiting to the successor extension here: https://github.com/colinmollenhour/Cm_Cache_Backend_File

## Old README: ##

This module is intended to improve the performance of the file cache backend
with large cache pools (as fast or slow backend).

There is a post on [MageBase][] about it which contains more details:

[MageBase]: http://magebase.com/magento-tutorials/improving-the-file-cache-backend/

This module is provided with no warranty, you use it at your own risk.
I know it is being used successfully on several sites, both as a primary cache
backend and as a slow backend in combination with APC or memcached.
I invite you to have a look at it and try it out, but please start with a test
instance and not your live store.

If you find bugs or have improvements, please send them in.


According to the [php reference manual][] symlinks works under Linux or since PHP 5.3 under Windows
Vista/Windows Server 2008 or greater (see in the php manual under the section titled Notes).

[php reference manual]: http://php.net/manual/en/function.symlink.php

After installation, clear the cache and check under "System > Tools > Symlink
Cache" for further instructions.
