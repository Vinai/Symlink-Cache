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
class Netzarbeiter_Cache_Model_Symlink extends Zend_Cache_Backend_File
{
	/**
	 * Constructor
	 *
	 * @param  array $options associative array of options
	 * @throws Zend_Cache_Exception
	 * @return void
	 */
	public function __construct(array $options = array())
	{
		// this is ugly, but if no absolute path is set cronjobs and cli scripts
		// might fail. It's ugly because this is the only part of the class that
		// now uses code from the Magento core.
		if (array_key_exists('cache_dir', $options) && substr(strval($options['cache_dir']), 0, 1) !== '/')
		{
			$options['cache_dir'] = Mage::getBaseDir() . DS . $options['cache_dir'];
		}

		parent::__construct($options);
	}

	/**
	 *
	 * @param string $dir
	 * @param string $mode
	 * @param array $tags
	 * @return array | false
	 */
	protected function _get($dir, $mode, $tags = array())
	{
		//print_r(array(__METHOD__, $dir, $mode, $tags));
		if (!is_dir($dir))
		{
			return false;
		}
		$result = array();
		if ('matchingAny' === $mode)
		{
			$glob = $this->_getFilesMatchingAnyTags($tags);
		}
		elseif ('matching' === $mode)
		{
			$glob = $this->_getFilesMatchingAllTags($tags);
		}
		elseif ('notMatching' === $mode)
		{
			$glob = $this->_getFilesNotMatchingTags($tags);
		}
		elseif ('tags' === $mode)
		{
			return $this->_getAllTags();
		}
		else
		{
			$prefix = $this->_options['file_name_prefix'];
			$glob = @glob($dir . $prefix . '--*');
			if ($glob === false)
			{
				// On some systems it is impossible to distinguish between empty match and an error.
				return array();
			}
		}
		foreach ($glob as $file)
		{
			if ($this->_isFile($file))
			{
				$fileName = basename($file);
				$id = $this->_fileNameToId($fileName);
				$metadatas = $this->_getMetadatas($id);
				if ($metadatas === false)
				{
					continue;
				}
				if (time() > $metadatas['expire'])
				{
					continue;
				}
				switch ($mode)
				{
					case 'ids':
					case 'matching':
					case 'notMatching':
					case 'matchingAny':
						$result[] = $id;
						break;
					case 'tags':
						$result = array_unique(array_merge($result, $metadatas['tags']));
						break;
					default:
						Zend_Cache::throwException('Invalid mode for _get() method');
						break;
				}
			}
			elseif ((is_dir($file)) && ($this->_options['hashed_directory_level'] > 0))
			{
				// Recursive call
				$recursiveRs = $this->_get($file . DIRECTORY_SEPARATOR, $mode, $tags);
				if ($recursiveRs === false)
				{
					$this->_log('Zend_Cache_Backend_File::_get() / recursive call : can\'t list entries of "' . $file . '"');
				}
				else
				{
					$result = array_unique(array_merge($result, $recursiveRs));
				}
			}
		}
		return array_unique($result);
	}

	/**
	 * Clean some cache records (protected method used for recursive stuff)
	 *
	 * Available modes are :
	 * Zend_Cache::CLEANING_MODE_ALL (default)    => remove all cache entries ($tags is not used)
	 * Zend_Cache::CLEANING_MODE_OLD              => remove too old cache entries ($tags is not used)
	 * Zend_Cache::CLEANING_MODE_MATCHING_TAG     => remove cache entries matching all given tags
	 *                                               ($tags can be an array of strings or a single string)
	 * Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG => remove cache entries not {matching one of the given tags}
	 *                                               ($tags can be an array of strings or a single string)
	 * Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG => remove cache entries matching any given tags
	 *                                               ($tags can be an array of strings or a single string)
	 *
	 * @param  string $dir  Directory to clean
	 * @param  string $mode Clean mode
	 * @param  array  $tags Array of tags
	 * @throws Zend_Cache_Exception
	 * @return boolean True if no problem
	 */
	protected function _clean($dir, $mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
	{
		if (!is_dir($dir))
		{
			return false;
		}
		$result = true;
		$basenames = array();
		switch ($mode)
		{
			case Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG:
				$files = $this->_getFilesMatchingAnyTags($tags);
				//$f = fopen('var/log/cache.log','a');fwrite($f, print_r('HIT ANY TAG WITH ' . count($files) . ' FILES',1)."\n");fclose($f);
				foreach ($files as $fileName)
				{
					$basename = basename($fileName);
					if (!isset($basenames[$basename]))
					{
						$id = $this->_fileNameToId($basename);
						$result = $this->remove($id) && $result;
						$basenames[$basename] = 1;
					}
				}
				return $result;
				break;
			case Zend_Cache::CLEANING_MODE_MATCHING_TAG:
				$files = $this->_getFilesMatchingAllTags($tags);
				//$f = fopen('var/log/cache.log','a');fwrite($f, print_r('HIT EVERY TAG WITH ' . count($files) . ' FILES',1)."\n");fclose($f);
				foreach ($files as $fileName)
				{
					$basename = basename($fileName);
					if (!isset($basenames[$basename]))
					{
						$id = $this->_fileNameToId($basename);
						$result = $this->remove($id) && $result;
						$basenames[$basename] = 1;
					}
				}
				return $result;
				break;
			case Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG:
				$files = $this->_getFilesNotMatchingTags($tags);
				//$f = fopen('var/log/cache.log','a');fwrite($f, print_r('HIT NOT ANY TAG WITH ' . count($files) . ' FILES',1)."\n");fclose($f);
				foreach ($files as $fileName)
				{
					$basename = basename($fileName);
					if (!isset($basenames[$basename]))
					{
						$id = $this->_fileNameToId($basename);
						$result = $this->remove($id) && $result;
					}
				}
				return $result;
				break;
		}

		// Default: handle clean modes ALL and OLD
		$prefix = $this->_options['file_name_prefix'];
		$glob = @glob($dir . $prefix . '--*');
		if ($glob === false)
		{
			// On some systems it is impossible to distinguish between empty match and an error.
			return true;
		}
		foreach ($glob as $file)
		{
			if ($this->_isFile($file))
			{
				$fileName = basename($file);
				if ($this->_isMetadatasFile($fileName))
				{
					// in CLEANING_MODE_ALL, we drop anything, even remainings old metadatas files
					if ($mode != Zend_Cache::CLEANING_MODE_ALL)
					{
						continue;
					}
				}
				$id = $this->_fileNameToId($fileName);
				$metadatas = $this->_getMetadatas($id);
				if ($metadatas === false)
				{
					$metadatas = array('expire' => 1, 'tags' => array());
				}
				switch ($mode)
				{
					case Zend_Cache::CLEANING_MODE_ALL:
						$res = $this->remove($id);
						if (!$res)
						{
							// in this case only, we accept a problem with the metadatas file drop
							$res = $this->_remove($file);
						}
						$result = $result && $res;
						break;
					case Zend_Cache::CLEANING_MODE_OLD:
						if (time() > $metadatas['expire'])
						{
							$result = $this->remove($id) && $result;
						}
						break;
					default:
						Zend_Cache::throwException('Invalid mode for clean() method');
						break;
				}
			}
			if ((is_dir($file)) && ($this->_options['hashed_directory_level'] > 0))
			{
				// Recursive call
				$result = $this->_clean($file . DIRECTORY_SEPARATOR, $mode, $tags) && $result;
				if ($mode == Zend_Cache::CLEANING_MODE_ALL)
				{
					// if mode=='all', we try to drop the structure too
					@rmdir($file);
				}
			}
		}
		return $result;
	}

	/**
	 * Transform a file name into cache id and return it
	 *
	 * @param  string $fileName File name
	 * @return string Cache id
	 */
	protected function _fileNameToId($fileName)
	{
		$prefix = $this->_options['file_name_prefix'];
		$fileName = str_replace('internal-metadatas---', '', $fileName);
		return preg_replace('~^' . $prefix . '---(.*)$~', '$1', $fileName);
	}

	/**
	 *
	 * @param array $tags
	 * @return array
	 */
	protected function _getFilesMatchingAnyTags(array $tags)
	{
		//$f = fopen('var/log/cache.log','a');fwrite($f, print_r(array(__METHOD__, $tags),1)."\n");fclose($f);
		$files = array();
		$prefix = $this->_options['file_name_prefix'];
		foreach ($tags as $tag)
		{
			$dir = $this->_getTagDir($tag) . DIRECTORY_SEPARATOR;
			if (false !== ($glob = @glob($dir . $prefix . '--*')))
			{
				$files = array_merge($glob, $files);
			}
		}
		return $files;
	}

	/**
	 *
	 * @param array $tags
	 * @return array
	 */
	protected function _getFilesMatchingAllTags(array $tags)
	{
		$files = array();
		$prefix = $this->_options['file_name_prefix'];
		$firstTag = array_pop($tags);
		$firstDir = $this->_getTagDir($firstTag) . DIRECTORY_SEPARATOR;
		if (false !== ($glob = @glob($firstDir . $prefix . '--*')))
		{
			foreach ($glob as $file)
			{
				$basename = basename($file);
				$match = true;
				foreach ($tags as $tag)
				{
					$checkLink = $this->_getTagDir($tag) . DIRECTORY_SEPARATOR . $basename;
					if (!$this->_isFile($checkLink))
					{
						continue 2;
					}
				}
				$files[] = $file;
			}
		}
		return $files;
	}

	/**
	 *
	 * @param array $tags
	 * @return array
	 */
	protected function _getFilesNotMatchingTags(array $tags)
	{
		$files = array();
		$otherTags = array_diff($this->_getAllTags(), $tags);
		$excludeBasenames = $this->_baseNameArray($this->_getFilesMatchingAnyTags($tags));
		$notMatchingFiles = $this->_getFilesMatchingAnyTags($otherTags);

		foreach ($notMatchingFiles as $notMatchingFile)
		{
			$notMatchingBasename = basename($notMatchingFile);
			if (!in_array($notMatchingBasename, $excludeBasenames))
			{
				$files[] = $notMatchingFile;
			}
		}
		return $files;
	}

	/**
	 *
	 * @param array $arrayOfFiles
	 * @return array
	 */
	protected function _baseNameArray(array $arrayOfFiles)
	{
		$files = array();
		foreach ($arrayOfFiles as $file)
		{
			$file = basename($file);
			if (!in_array($file, $files))
			{
				$files[] = $file;
			}
		}
		return $files;
	}

	/**
	 *
	 * @return array
	 */
	protected function _getAllTags()
	{
		$tags = scandir($this->_getTagBaseDir());
		if (!$tags)
		{
			return array();
		}
		foreach (array('..', '.') as $filter)
		{
			if (false !== ($key = array_search($filter, $tags)))
			{
				unset($tags[$key]);
			}
		}
		return $tags;
	}

	/**
	 * Set a metadatas record
	 *
	 * @param  string $id        Cache id
	 * @param  array  $metadatas Associative array of metadatas
	 * @param  boolean $save     optional pass false to disable saving to file
	 * @return boolean True if no problem
	 */
	protected function _setMetadatas($id, $metadatas, $save = true)
	{
		$result = parent::_setMetadatas($id, $metadatas, $save);
		if ($result)
		{
			if (isset($metadatas['tags']) && $metadatas['tags'])
			{
				$this->_createMetadataTagSymlinks($id, $metadatas['tags']);
			}
		}
		return $result;
	}

	/**
	 *
	 * @param string $id
	 * @param array $tags
	 */
	protected function _createMetadataTagSymlinks($id, $tags)
	{
		$metadataFile = $this->_metadatasFile($id);
		foreach ($tags as $tag)
		{
			$link = $this->_getMetadataTagSymlinkFilename($metadataFile, $tag, true);
			if (!file_exists($link))
			{
				@symlink($metadataFile, $link);
			}
		}
	}

	/**
	 *
	 * @param string $metadataFile
	 * @param string $tag
	 * @param bool $createDir
	 * @return string Filename of the link
	 */
	protected function _getMetadataTagSymlinkFilename($metadataFile, $tag, $createDir = false)
	{
		$dir = $this->_getTagDir($tag, $createDir);
		$file = $dir . DIRECTORY_SEPARATOR . basename($metadataFile);
		return $file;
	}

	/**
	 *
	 * @return string
	 */
	protected function _getTagBaseDir()
	{
		return $this->_options['cache_dir'] . 'tags';
	}

	/**
	 *
	 * @param string $tag
	 * @param bool $createDir
	 * @return string
	 */
	protected function _getTagDir($tag, $createDir = false)
	{
		$dir = $this->_getTagBaseDir() . DIRECTORY_SEPARATOR . str_replace(DIRECTORY_SEPARATOR, '-', $tag);
		if ($createDir)
		{
			$partsArray = array(
				$this->_getTagBaseDir(), $dir
			);
			foreach ($partsArray as $part)
			{
				if (!is_dir($part))
				{
					@mkdir($part, $this->_options['hashed_directory_umask']);
					@chmod($part, $this->_options['hashed_directory_umask']); // see #ZF-320 (this line is required in some configurations)
				}
			}
		}
		return $dir;
	}

	/**
	 * Drop a metadata record
	 *
	 * @param  string $id Cache id
	 * @return boolean True if no problem
	 */
	protected function _delMetadatas($id)
	{
		$metadata = $this->_loadMetadatas($id);
		$file = $this->_metadatasFile($id);
		if (isset($metadata['tags']) && is_array($metadata['tags']))
		{
			foreach ($metadata['tags'] as $tag)
			{
				$link = $this->_getMetadataTagSymlinkFilename($file, $tag);
				$this->_remove($link);

				// remove directory if empty
				@rmdir(dirname($link));
			}
		}
		return parent::_delMetadatas($id);
	}

	/**
	 * Remove a file
	 *
	 * If we can't remove the file (because of locks or any problem), we will touch
	 * the file to invalidate it
	 *
	 * @param  string $file Complete file path
	 * @return boolean True if ok
	 */
	protected function _remove($file)
	{
		if (!$this->_isFile($file))
		{
			return false;
		}
		if (!@unlink($file))
		{
			# we can't remove the file (because of locks or any problem)
			$this->_log("Zend_Cache_Backend_File::_remove() : we can't remove $file");
			return false;
		}
		return true;
	}

	/**
	 * Return the file content of the given file
	 *
	 * @param  string $file File complete path
	 * @return string File content (or false if problem)
	 */
	protected function _fileGetContents($file)
	{
		$result = false;
		if (!$this->_isFile($file))
		{
			return false;
		}
		$f = @fopen($file, 'rb');
		if ($f)
		{
			if ($this->_options['file_locking'])
				@flock($f, LOCK_SH);
			$result = stream_get_contents($f);
			if ($this->_options['file_locking'])
				@flock($f, LOCK_UN);
			@fclose($f);
		}
		return $result;
	}

	/**
	 *
	 * @param string $file
	 * @return bool
	 */
	protected function _isFile($file)
	{
		return file_exists($file) && (is_file($file) || is_link($file));
	}

}
