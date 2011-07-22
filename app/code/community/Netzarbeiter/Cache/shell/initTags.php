<?php
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

/**
 * Initialize all cache tag links.
 * Script needs read/write permissions to access the magento cache files.
 * Usage:
 *		php app/code/local/Netzarbeiter/Cache/shell/initTags.php
 */

// use __FILE__ instead of __DIR__ because php 5.3 isn't available everywhere yet
$sentry = 0;
$abstract = '/../../../../../../shell/abstract.php';
while (! file_exists(dirname(__FILE__) . $abstract) && $sentry < 2) {
	$abstract = '/..' . $abstract;
	$sentry++;
}
require_once dirname(__FILE__) . $abstract;


class initTags extends Mage_Shell_Abstract
{
	/**
	 * Initialitze the tag symlinks
	 *
	 * @return initTags
	 */
	public function run()
	{
		$result = Mage::helper('netzarbeiter_cache')->initTagSymlinks()->getResults();
		foreach ($result as $line) {
			printf('Created symlink to "%s" for tag "%s"', basename($line['target']), $line['tag']);
			echo "\n";
		}

		return $this;
	}

	/**
	 * Return the usage help.
	 *
	 * @return string
	 */
	public function usageHelp()
	{
		return <<<USAGE
This scrit initialize all cache tag symlinks.
The script needs read/write permissions to access the magento cache files.

Usage:  php -f app/code/local/Netzarbeiter/Cache/shell/initTags.php -- [options]

	-h            Short alias for help
	help          This help

USAGE;
	}
}


$init = new initTags();
$init->run();