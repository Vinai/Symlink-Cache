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

class Netzarbeiter_Cache_Block_Adminhtml_Cache extends Mage_Adminhtml_Block_Template
{
	protected $_symlinkCacheClass = 'Netzarbeiter_Cache_Model_Symlink';

	protected function _prepareLayout()
	{
		parent::_prepareLayout();
		if ($this->isSymlinkCacheInUse())
		{
			$this->setChild('initSymlinksButton',
				$this->getLayout()->createBlock('adminhtml/widget_button')
					->setData(array(
						'label' => $this->__('Initialize Tag Symlinks'),
						'onclick' => "window.location.href='" . $this->getUrl('*/*/initSymlinks') . "'",
						'class'  => 'task'
					))
			);
		}
		return $this;
	}

	public function getFastBackend()
	{
		return (string) Mage::getConfig()->getNode('global/cache/backend');
	}

	public function getSlowBackend()
	{
		return (string) Mage::getConfig()->getNode('global/cache/slow_backend');
	}

	public function isSymlinkCacheInUse()
	{
		return $this->getFastBackend() == $this->_symlinkCacheClass || $this->getSlowBackend() == $this->_symlinkCacheClass;
	}

	public function getSymlinkCacheStatus()
	{
		if ($this->isSymlinkCacheInUse())
		{
			return $this->__('enabled');
		}
		return $this->__('disabled');
	}

	public function getResultInfo()
	{
		$data = Mage::getSingleton('adminhtml/session')->getResultInfo(true);
		return (array) $data;
	}

	public function getFastBackendInstructions()
	{
		$string = <<<EOT
<config>
	<global>
		<cache>
			<backend>Netzarbeiter_Cache_Model_Symlink</backend>
			<backend_options>
				<cache_dir>var/cache</cache_dir>
				<hashed_directory_level>1</hashed_directory_level>
				<hashed_directory_umask>0777</hashed_directory_umask>
				<file_name_prefix>mage</file_name_prefix>
			</backend_options>
		</cache>
	</global>
</config>
EOT;
		return $string;
	}

	public function getSlowBackendInstructions()
	{
		$string = <<<EOT
<config>
	<global>
		<cache>
			<slow_backend>Netzarbeiter_Cache_Model_Symlink</slow_backend>
		</cache>
	</global>
</config>
EOT;
		return $string;
	}
}
