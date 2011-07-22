/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension
 * to newer versions in the future.
 *
 * @category   Netzarbeiter
 * @package    Netzarbeiter_Cache
 * @copyright  Copyright (c) 2011 Vinai Kopp http://netzarbeiter.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

This module is provided with no warranty, you use it at your own risk.
I know it is being used successfully on several sites, both as a primary cache
backend and as a slow backend in combination with APC or memcached.
I invite you to have a look at it and try it out, but please start with a test
instance and not your live store.
You can download the module from Magento Connect or from github
https://github.com/Vinai/Symlink-Cache

If you find bugs or have improvements, please send them in.


According to the php reference manual symlinks works under Linux or since PHP 5.3 under Windows
Vista/Windows Server 2008 or greater (see http://php.net/manual/en/function.symlink.php
under the section titled Notes).
