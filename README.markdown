
This module is intended to improve the performance of the file cache backend
with large cache pools (as fast or slow backend).

This module is provided with no warranty, you use it at your own risk.
I know it is being used successfully on several sites, both as a primary cache
backend and as a slow backend in combination with APC or memcached.
I invite you to have a look at it and try it out, but please start with a test
instance and not your live store.

If you find bugs or have improvements, please send them in.


According to the php reference manual symlinks works under Linux or since PHP 5.3 under Windows
Vista/Windows Server 2008 or greater (see http://php.net/manual/en/function.symlink.php
under the section titled Notes).

After installation, clear the cache and check under "System > Tools > Symlink
Cache" for further instructions.
