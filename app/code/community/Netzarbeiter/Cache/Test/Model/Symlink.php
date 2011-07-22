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
class Netzarbeiter_Cache_Test_Model_Symlink extends EcomDev_PHPUnit_Test_Case
{

	protected $_options = array();

	public function setUp()
	{
		parent::setUp();
		$this->_options = array(
			'cache_dir' => Mage::getBaseDir('cache'),
			'file_name_prefix' => 'mage',
			'hashed_directory_level' => 1,
			'hashed_directory_umask' => 0777,
			'cache_file_umask' => 0777,
		);
	}

	public function tearDown()
	{
		$backend = new Netzarbeiter_Cache_Model_Symlink($this->_options);
		$backend->clean();
		parent::tearDown();
	}

	protected function _idToFilename($id)
	{
		return $this->_options['file_name_prefix'] . '---' . $id;
	}

	protected function _idToMetadataFilename($id)
	{
		return $this->_options['file_name_prefix'] . '---internal-metadatas---' . $id;
	}

	protected function _idToPath($id)
	{
		return $this->_options['cache_dir'] . DIRECTORY_SEPARATOR . $this->_options['file_name_prefix'] . '--' . substr(hash('adler32', $id), 0, 1);
	}

	protected function _tagToPath($tag)
	{
		return $this->_options['cache_dir'] . DIRECTORY_SEPARATOR . 'tags' . DIRECTORY_SEPARATOR . str_replace(DIRECTORY_SEPARATOR, '-', $tag);
	}

	/**
	 *
	 * @return Netzarbeiter_Cache_Model_Symlink
	 */
	protected function _getBackendModel()
	{
		return new Netzarbeiter_Cache_Model_Symlink($this->_options);
	}

	/**
	 * @test
	 */
	public function backend()
	{
		$backend = $this->_getBackendModel();
		$this->assertInstanceOf('Netzarbeiter_Cache_Model_Symlink', $backend);
		return $backend;
	}

	/**
	 * @test
	 * @depends backend
	 */
	public function save()
	{
		$backend = $this->_getBackendModel();

		$data = '1234567890';
		$id = 'test' . __METHOD__;
		$tags = array('test', 'test2');

		$result = $backend->save($data, $id, $tags);
		$this->assertTrue($result, get_class($backend) . '::save() returned false');

		$filename = $this->_idToFilename($id);
		$metadatafilename = $this->_idToMetadataFilename($id);
		$path = $this->_idToPath($id);

		$this->assertFileExists($path . DIRECTORY_SEPARATOR . $filename);
		$this->assertFileExists($path . DIRECTORY_SEPARATOR . $metadatafilename);

		$expectedHash = md5_file($path . DIRECTORY_SEPARATOR . $metadatafilename);

		foreach ($tags as $tag)
		{
			$path = $this->_tagToPath($tag);
			$symlink = $path . DIRECTORY_SEPARATOR . $metadatafilename;
			$this->assertFileExists($symlink);
			$actualHash = md5_file($symlink);
			$this->assertEquals($expectedHash, $actualHash);
			$this->assertTrue(is_link($symlink), $symlink . " is not a symlink");
		}

		return $backend;
	}

	/**
	 * @test
	 * @depends save
	 */
	public function remove()
	{
		$backend = $this->_getBackendModel();

		$data = '1234567890';
		$id = 'test' . __METHOD__;
		$tags = array('test', 'test2');

		$backend->save($data, $id, $tags);


		$filename = $this->_idToFilename($id);
		$metadatafilename = $this->_idToMetadataFilename($id);
		$path = $this->_idToPath($id);

		$result = $backend->remove($id);
		$this->assertTrue($result, get_class($backend) . '::remove() returned false');

		$result = $backend->load($id);
		$this->assertFalse($result);

		$this->assertFileNotExists($path . DIRECTORY_SEPARATOR . $filename);
		$this->assertFileNotExists($path . DIRECTORY_SEPARATOR . $metadatafilename);

		foreach ($tags as $tag)
		{
			$path = $this->_tagToPath($tag);
			$this->assertFileNotExists($path . DIRECTORY_SEPARATOR . $metadatafilename);
		}

		return $backend;
	}

	/**
	 * @test
	 * @depends remove
	 */
	public function clean()
	{
		$backend = $this->_getBackendModel();

		$data = '1234567890';
		$baseId = 'test' . __METHOD__;
		$tags = array('test', 'test2');
		$creatdIds = array($baseId . 'X', $baseId . 'Y');

		foreach ($creatdIds as $id)
		{
			$backend->save($data, $id, $tags);
		}

		$result = $backend->clean();
		$this->assertTrue($result, get_class($backend) . '::clean() returned false');

		foreach ($creatdIds as $id)
		{
			$filename = $this->_idToFilename($id);
			$metadatafilename = $this->_idToMetadataFilename($id);
			$path = $this->_idToPath($id);

			$result = $backend->load($id);
			$this->assertFalse($result);

			$this->assertFileNotExists($path . DIRECTORY_SEPARATOR . $filename);
			$this->assertFileNotExists($path . DIRECTORY_SEPARATOR . $metadatafilename);

			foreach ($tags as $tag)
			{
				$path = $this->_tagToPath($tag);
				$this->assertFileNotExists($path . DIRECTORY_SEPARATOR . $metadatafilename);
			}
		}
		return $backend;
	}

	/**
	 * @test
	 * @depends clean
	 */
	public function getAllIds()
	{
		$backend = $this->_getBackendModel();

		$data = '1234567890';
		$baseId = 'test' . __METHOD__;
		$tags = array('test', 'test2');

		$creatdIds = array($baseId . 'X', $baseId . 'Y');

		foreach ($creatdIds as $id)
		{
			$backend->save($data, $id, $tags);
		}

		$ids = $backend->getIds();
		$this->assertEquals(count($creatdIds), count($ids));
		foreach ($creatdIds as $id)
		{
			$this->assertTrue(array_search($id, $ids) !== false, 'Cache ID ' . $id . ' was not returned by getIds()');
		}
	}

	/**
	 * @test
	 * @depends clean
	 */
	public function getIdsMatchingTags()
	{
		$backend = $this->_getBackendModel();

		$data = '1234567890';
		$baseId = 'test' . __METHOD__;
		$tags = array('test', 'test2');

		$creatdIds = array($baseId . 'X', $baseId . 'Y');

		foreach ($creatdIds as $id)
		{
			$backend->save($data, $id, $tags);
		}

		$extraId = $baseId . 'Z';
		$extraTag = array('test3');
		$backend->save($data, $extraId, $extraTag);

		$extra2Id = $baseId . 'W';
		$extra2Tag = array('test2');
		$backend->save($data, $extra2Id, $extra2Tag);

		$ids = $backend->getIdsMatchingTags($tags);
		$this->assertEquals(count($creatdIds), count($ids));
		foreach ($creatdIds as $id)
		{
			$this->assertTrue(array_search($id, $ids) !== false, 'Cache ID ' . $id . ' was not returned by getIdsMatchingTags()');
		}
		$this->assertTrue(array_search($extraId, $ids) === false, 'Cache ID ' . $extraId . ' was falsly returned by getIdsMatchingTags()');
		$this->assertTrue(array_search($extra2Id, $ids) === false, 'Cache ID ' . $extra2Id . ' was falsly returned by getIdsMatchingTags()');

		$ids = $backend->getIdsMatchingTags($extraTag);
		$this->assertEquals(array($extraId), $ids);

		$expectedIds = array_merge($creatdIds, array($extra2Id));
		$ids = $backend->getIdsMatchingTags($extra2Tag);
		$this->assertEquals(count($expectedIds), count($ids));
		foreach ($expectedIds as $id)
		{
			$this->assertTrue(array_search($id, $ids) !== false, 'Cache ID ' . $id . ' was not returned by getIdsMatchingTags()');
		}
		$this->assertTrue(array_search($extraId, $ids) === false, 'Cache ID ' . $extraId . ' was falsly returned by getIdsMatchingTags()');
	}

	/**
	 * @test
	 * @depends clean
	 */
	public function getIdsMatchingAnyTags()
	{
		$backend = $this->_getBackendModel();

		$data = '1234567890';
		$baseId = 'test' . __METHOD__;
		$tags = array('test', 'test2');

		$creatdIds = array($baseId . 'X', $baseId . 'Y');

		foreach ($creatdIds as $id)
		{
			$backend->save($data, $id, $tags);
		}

		$extraId = $baseId . 'Z';
		$extraTag = array('test3');
		$backend->save($data, $extraId, $extraTag);

		$extra2Id = $baseId . 'W';
		$extra2Tag = array('test2');
		$backend->save($data, $extra2Id, $extra2Tag);

		$extra3Id = $baseId . 'V';
		$extra3Tag = array('test');
		$backend->save($data, $extra3Id, $extra3Tag);

		$ids = $backend->getIdsMatchingAnyTags($tags);
		$expectedIds = array_merge($creatdIds, array($extra2Id, $extra3Id));
		$this->assertEquals(count($expectedIds), count($ids));
		foreach ($expectedIds as $id)
		{
			$this->assertTrue(array_search($id, $ids) !== false, 'Cache ID ' . $id . ' was not returned by getIdsMatchingAnyTags()');
		}

		$ids = $backend->getIdsMatchingAnyTags($extraTag);
		$expectedIds = array($extraId);
		$this->assertEquals(count($expectedIds), count($ids));
		foreach ($expectedIds as $id)
		{
			$this->assertTrue(array_search($id, $ids) !== false, 'Cache ID ' . $id . ' was not returned by getIdsMatchingAnyTags()');
		}

		$ids = $backend->getIdsMatchingAnyTags($extra2Tag);
		$expectedIds = array_merge($creatdIds, array($extra2Id));
		$this->assertEquals(count($expectedIds), count($ids));
		foreach ($expectedIds as $id)
		{
			$this->assertTrue(array_search($id, $ids) !== false, 'Cache ID ' . $id . ' was not returned by getIdsMatchingAnyTags()');
		}

		$ids = $backend->getIdsMatchingAnyTags($extra3Tag);
		$expectedIds = array_merge($creatdIds, array($extra3Id));
		$this->assertEquals(count($expectedIds), count($ids));
		foreach ($expectedIds as $id)
		{
			$this->assertTrue(array_search($id, $ids) !== false, 'Cache ID ' . $id . ' was not returned by getIdsMatchingAnyTags()');
		}
	}

	/**
	 * @test
	 * @depends clean
	 */
	public function getIdsNotMatchingTags()
	{
		$backend = $this->_getBackendModel();

		$data = '1234567890';
		$baseId = 'test' . __METHOD__;
		$tags = array('test', 'test2');

		$creatdIds = array($baseId . 'X', $baseId . 'Y');

		foreach ($creatdIds as $id)
		{
			$backend->save($data, $id, $tags);
		}

		$extraId = $baseId . 'Z';
		$extraTag = array('test3');
		$backend->save($data, $extraId, $extraTag);

		$extra2Id = $baseId . 'W';
		$extra2Tag = array('test2');
		$backend->save($data, $extra2Id, $extra2Tag);

		$extra3Id = $baseId . 'V';
		$extra3Tag = array('test');
		$backend->save($data, $extra3Id, $extra3Tag);


		$ids = $backend->getIdsNotMatchingTags($tags);
		$expectedIds = array($extraId);
		$this->assertEquals(count($expectedIds), count($ids));
		foreach ($expectedIds as $id)
		{
			$this->assertTrue(array_search($id, $ids) !== false, 'Cache ID ' . $id . ' was not returned by getIdsMatchingAnyTags()');
		}

		$ids = $backend->getIdsNotMatchingTags($extra2Tag);
		$expectedIds = array($extraId, $extra3Id);
		$this->assertEquals(count($expectedIds), count($ids));
		foreach ($expectedIds as $id)
		{
			$this->assertTrue(array_search($id, $ids) !== false, 'Cache ID ' . $id . ' was not returned by getIdsMatchingAnyTags()');
		}

		$ids = $backend->getIdsNotMatchingTags($extra3Tag);
		$expectedIds = array($extraId, $extra2Id);
		$this->assertEquals(count($expectedIds), count($ids));
		foreach ($expectedIds as $id)
		{
			$this->assertTrue(array_search($id, $ids) !== false, 'Cache ID ' . $id . ' was not returned by getIdsMatchingAnyTags()');
		}

		$ids = $backend->getIdsNotMatchingTags(array_merge($tags, $extraTag));
		$expectedIds = array();
		$this->assertEquals(count($expectedIds), count($ids));
		foreach ($expectedIds as $id)
		{
			$this->assertTrue(array_search($id, $ids) !== false, 'Cache ID ' . $id . ' was not returned by getIdsMatchingAnyTags()');
		}
	}

	/**
	 * @test
	 * @depends clean
	 */
	public function clearAnyMatchingTag()
	{
		$backend = $this->_getBackendModel();

		$data = '1234567890';
		$baseId = 'test' . __METHOD__;
		$tags = array('test', 'test2');

		$creatdIds = array($baseId . 'X', $baseId . 'Y');

		foreach ($creatdIds as $id)
		{
			$backend->save($data, $id, $tags);
		}

		$extraId = $baseId . 'Z';
		$extraTag = array('test3');
		$backend->save($data, $extraId, $extraTag);

		$extra2Id = $baseId . 'W';
		$extra2Tag = array('test2');
		$backend->save($data, $extra2Id, $extra2Tag);

		$extra3Id = $baseId . 'V';
		$extra3Tag = array('test');
		$backend->save($data, $extra3Id, $extra3Tag);

		$result = $backend->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $tags);
		$this->assertTrue($result, get_class($backend) . '::clean(' . Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG . ') returned false');

		foreach (array_merge($creatdIds, array($extra2Id, $extra3Id)) as $id)
		{
			$filename = $this->_idToFilename($id);
			$metadatafilename = $this->_idToMetadataFilename($id);
			$path = $this->_idToPath($id);

			$result = $backend->load($id);
			$this->assertFalse($result, 'Loading of id ' . $id . ' successfull after the matching tag was cleaned');

			$this->assertFileNotExists($path . DIRECTORY_SEPARATOR . $filename);
			$this->assertFileNotExists($path . DIRECTORY_SEPARATOR . $metadatafilename);

			foreach ($tags as $tag)
			{
				$path = $this->_tagToPath($tag);
				$this->assertFileNotExists($path . DIRECTORY_SEPARATOR . $metadatafilename);
			}
		}

		$result = $backend->load($extraId);
		$this->assertEquals($data, $result);
	}

	/**
	 * @test
	 * @depends clean
	 */
	public function clearAllMatchingTags()
	{
		$backend = $this->_getBackendModel();

		$data = '1234567890';
		$baseId = 'test' . __METHOD__;
		$tags = array('test', 'test2');

		$creatdIds = array($baseId . 'X', $baseId . 'Y');

		foreach ($creatdIds as $id)
		{
			$backend->save($data, $id, $tags);
		}

		$extraId = $baseId . 'Z';
		$extraTag = array('test3');
		$backend->save($data, $extraId, $extraTag);

		$extra2Id = $baseId . 'W';
		$extra2Tag = array('test2');
		$backend->save($data, $extra2Id, $extra2Tag);

		$extra3Id = $baseId . 'V';
		$extra3Tag = array('test');
		$backend->save($data, $extra3Id, $extra3Tag);

		$result = $backend->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, $tags);
		$this->assertTrue($result, get_class($backend) . '::clean(' . Zend_Cache::CLEANING_MODE_MATCHING_TAG . ') returned false');

		foreach ($creatdIds as $id)
		{
			$filename = $this->_idToFilename($id);
			$metadatafilename = $this->_idToMetadataFilename($id);
			$path = $this->_idToPath($id);

			$result = $backend->load($id);
			$this->assertFalse($result, 'Loading of id ' . $id . ' successfull after the matching tags where cleaned');

			$this->assertFileNotExists($path . DIRECTORY_SEPARATOR . $filename);
			$this->assertFileNotExists($path . DIRECTORY_SEPARATOR . $metadatafilename);

			foreach ($tags as $tag)
			{
				$path = $this->_tagToPath($tag);
				$this->assertFileNotExists($path . DIRECTORY_SEPARATOR . $metadatafilename);
			}
		}

		$result = $backend->load($extraId);
		$this->assertEquals($data, $result);

		$result = $backend->load($extra2Id);
		$this->assertEquals($data, $result);

		$result = $backend->load($extra3Id);
		$this->assertEquals($data, $result);
	}

	/**
	 * @test
	 * @depends clean
	 */
	public function clearNotMatchingTag()
	{
		$backend = $this->_getBackendModel();

		$data = '1234567890';
		$baseId = 'test' . __METHOD__;
		$tags = array('test', 'test2');

		$creatdIds = array($baseId . 'X', $baseId . 'Y');

		foreach ($creatdIds as $id)
		{
			$backend->save($data, $id, $tags);
		}

		$extraId = $baseId . 'Z';
		$extraTag = array('test3');
		$backend->save($data, $extraId, $extraTag);

		$extra2Id = $baseId . 'W';
		$extra2Tag = array('test2');
		$backend->save($data, $extra2Id, $extra2Tag);

		$extra3Id = $baseId . 'V';
		$extra3Tag = array('test');
		$backend->save($data, $extra3Id, $extra3Tag);

		$result = $backend->clean(Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG, $tags);
		$this->assertTrue($result, get_class($backend) . '::clean(' . Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG . ') returned false');

		foreach (array_merge($creatdIds, array($extra2Id, $extra3Id)) as $id)
		{
			$result = $backend->load($id);
			$this->assertEquals($data, $result);
		}

		foreach ($extraTag as $id)
		{
			$filename = $this->_idToFilename($id);
			$metadatafilename = $this->_idToMetadataFilename($id);
			$path = $this->_idToPath($id);

			$result = $backend->load($id);
			$this->assertFalse($result, 'Loading of id ' . $id . ' successfull after the matching tags where cleaned');

			$this->assertFileNotExists($path . DIRECTORY_SEPARATOR . $filename);
			$this->assertFileNotExists($path . DIRECTORY_SEPARATOR . $metadatafilename);

			foreach ($tags as $tag)
			{
				$path = $this->_tagToPath($tag);
				$this->assertFileNotExists($path . DIRECTORY_SEPARATOR . $metadatafilename);
			}
		}
	}

	/**
	 * @test
	 * @depends clean
	 */
	public function clearOld()
	{
		$backend = $this->_getBackendModel();

		$data = '1234567890';
		$baseId = 'test' . __METHOD__;
		$tags = array('test');

		$creatdIds = array($baseId . 'X', $baseId . 'Y');

		foreach ($creatdIds as $id)
		{
			$backend->save($data, $id, $tags, -1);
		}

		$extraId = $baseId . 'Z';
		$extraTag = array('test3');
		$backend->save($data, $extraId, $extraTag, 100);

		$result = $backend->clean(Zend_Cache::CLEANING_MODE_OLD);
		$this->assertTrue($result, get_class($backend) . '::clean(' . Zend_Cache::CLEANING_MODE_OLD . ') returned false');


		foreach ($creatdIds as $id)
		{
			$filename = $this->_idToFilename($id);
			$metadatafilename = $this->_idToMetadataFilename($id);
			$path = $this->_idToPath($id);

			$this->assertFileNotExists($path . DIRECTORY_SEPARATOR . $filename);
			$this->assertFileNotExists($path . DIRECTORY_SEPARATOR . $metadatafilename);
		}

		$filename = $this->_idToFilename($extraId);
		$metadatafilename = $this->_idToMetadataFilename($extraId);
		$path = $this->_idToPath($extraId);
		$this->assertFileExists($path . DIRECTORY_SEPARATOR . $filename);
		$this->assertFileExists($path . DIRECTORY_SEPARATOR . $metadatafilename);

		foreach ($extraTag as $tag)
		{
			$path = $this->_tagToPath($tag);
			$this->assertFileExists($path . DIRECTORY_SEPARATOR . $metadatafilename);
		}
	}

	/**
	 * @test
	 * @depends clean
	 */
	public function load()
	{
		$backend = $this->_getBackendModel();

		$data = '1234567890';
		$id = 'test' . __METHOD__;
		$tags = array();

		$creatdIds = array($baseId . 'X', $baseId . 'Y');

		$backend->save($data, $id, $tags);

		$result = $backend->load($id);
		$this->assertEquals($data, $result);
	}

	/**
	 * @test
	 * @depends clean
	 */
	public function loadExpired()
	{
		$backend = $this->_getBackendModel();
		
		$data = '1234567890';
		$id = 'test' . __METHOD__;
		$tags = array();
		$expires = -1;

		$creatdIds = array($baseId . 'X', $baseId . 'Y');

		$backend->save($data, $id, $tags, $expires);

		$result = $backend->load($id);
		$this->assertFalse($result, 'Loading of id ' . $id . ' successfull after the matching tags where cleaned');
	}

}
