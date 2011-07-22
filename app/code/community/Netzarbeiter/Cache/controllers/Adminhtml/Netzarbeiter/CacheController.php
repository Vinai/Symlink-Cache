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

class Netzarbeiter_Cache_Adminhtml_Netzarbeiter_CacheController
	extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
	{
		$this->loadLayout();
		$this->_setActiveMenu('system');
		$this->renderLayout();
	}

	public function initSymlinksAction()
	{
		try
		{
			$results = Mage::helper('netzarbeiter_cache')->initTagSymlinks()->getResults();
			$this->_getSession()->addSuccess($this->__('Created %s symlinks', count($results)));
			$this->_getSession()->setResultInfo($results);
		}
		catch(Exception $e)
		{
			$this->_getSession()->addError($e->getMessage());
			Mage::logException($e);
		}
		$this->_redirect('*/*/index');
	}

	protected function _isAllowed()
	{
		return Mage::getSingleton('admin/session')->isAllowed('system/tools/netzarbeiter_cache');
	}
}
