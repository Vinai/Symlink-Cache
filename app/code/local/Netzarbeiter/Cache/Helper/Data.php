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

class Netzarbeiter_Cache_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * The name of the tags directory within the cache base dir.
	 *
	 * @var string
	 * @see Zend_Cache_Backend_File::_getTagDir()
	 */
	protected $_tagDirName = 'tags';

	/**
	 * The cache file name prefix to use
	 *
	 * @var string
	 */
	protected $_fileNamePrefix = 'mage';

	/**
	 * The cache base directory
	 *
	 * @var string
	 */
	protected $_cacheDir = '';

	/**
	 * The full path of the tags directory
	 *
	 * @var string
	 */
	protected $_tagDir = '';

	/**
	 * Array of symlinks created
	 *
	 * @var array
	 */
	protected $_result = array();

	/**
	 * Initialite a few constant values
	 */
	public function  __construct()
	{
		$this->_cacheDir = Mage::getBaseDir('cache');
		$this->_tagDir = $this->_cacheDir . DS . $this->_tagDirName;
	}

	/**
	 * Initialize the cache metadata by tag symlinks
	 *
	 * @return Netzarbeiter_Cache_Helper_Data
	 */
	public function initTagSymlinks()
	{
		$this->_result = array();

		$this->_removeTagLinks();

		$files = $this->getRealMetadataFiles();
		foreach ($files as $file)
		{
			$metadata = $this->_readMetadatas($file);
			if ($metadata && is_array($metadata) && isset($metadata['tags']))
			{
				$this->_createLinks($file, $metadata['tags']);
			}
		}
		return $this;
	}
	/**
	 * Return an array with all metadata files in the file system cache
	 *
	 * @return array
	 */
	public function getRealMetadataFiles()
	{
		$files = array();
		// @hack: assume hashed_directory_level == 1
		$cacheSubDirs = @glob("{$this->_cacheDir}/{$this->_fileNamePrefix}--*");
		if ($cacheSubDirs)
		{
			foreach ($cacheSubDirs as $subDir)
			{
				$glob = @glob("{$subDir}/{$this->_fileNamePrefix}---internal-metadatas---*");
				if ($glob)
				{
					$files = array_merge($files, $glob);
				}
			}
		}
		return $files;
	}

	/**
	 * Return the unserialized contents of a metadata file or false on error.
	 *
	 * @param string $file
	 * @return array|false
	 */
	protected function _readMetadatas($file)
	{
		$metadata = false;
		if (!is_readable($file))
		{
			throw new Exception($this->__("Unable to read metadatas file %s", $file));
		}
		$serialized = file_get_contents($file);
		if (is_string($serialized) && strlen($serialized) > 0)
		{
			$metadata = unserialize($serialized);
		}
		return $metadata;
	}

	/**
	 * Create symlinks to the $target dile in every $tag directory
	 *
	 * @param string $target Target file
	 * @param array $tags
	 * @return bool
	 */
	protected function _createLinks($target, array $tags)
	{
		$basename = basename($target);
		$result = true;
		foreach ($tags as $tag)
		{
			$dir = $this->_tagDir . DS . str_replace(DS, '-', $tag);
			$link = $dir . DS . $basename;
			if (file_exists($link))
			{
				@unlink($link);
			}
			if (! is_dir($dir))
			{
				@mkdir($dir, 0777, true);
			}
			if ($result = $result && @symlink($target, $link))
			{
				$this->_result[] = array(
					'target' => basename($target),
					'tag' => $tag
				);
			}
		}
		return $result;
	}

	/**
	 * Remove all existing tag symlinks
	 *
	 * @return bool
	 */
	protected function _removeTagLinks()
	{
		$result = true;
		if (file_exists($this->_tagDir) && is_dir($this->_tagDir))
		{
			$tags = scandir($this->_tagDir);
			if ($tags)
			{
				foreach ($tags as $tag) {
					$dir = $this->_tagDir . DS . $tag;
					if (! is_dir($dir) || in_array($tag, array('.', '..')))
					{
						continue;
					}
					$links = scandir($dir);
					if ($links)
					{
						foreach ($links as $linkName)
						{
							$link = $dir . DS . $linkName;
							if (is_link($link))
							{
								$result = $result && @unlink($link);
							}
						}
					}
				}
			}
		}
		return $result;
	}

	/**
	 *
	 * @return array
	 */
	public function getResults()
	{
		return $this->_result;
	}
}
