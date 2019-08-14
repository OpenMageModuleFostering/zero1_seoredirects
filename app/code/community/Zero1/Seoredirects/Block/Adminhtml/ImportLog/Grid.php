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
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml customer grid block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Zero1_Seoredirects_Block_Adminhtml_ImportLog_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('importLogGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        //$this->setVarNameFilter('product_filter');
    }

    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    protected function _prepareCollection()
    {
        /* @var $collection Zero1_Seoredirects_Model_Resource_ImportLog_Collection */
        $collection = Mage::getModel('zero1_seo_redirects/importLog')->getCollection();
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id',
            array(
                'header'=> Mage::helper('catalog')->__('ID'),
                'width' => '50px',
                'type'  => 'number',
                'index' => 'id',
                'is_system' => true,
        ));

        $this->addColumn('scope', array(
            'header'    => Mage::helper('catalog')->__('Scope'),
            'index'     => 'scope',
        ));

        $this->addColumn('scope_id', array(
            'header'    => Mage::helper('catalog')->__('Scope ID'),
            'index'     => 'scope_id',
        ));

        $this->addColumn('severity', array(
            'header'    => Mage::helper('catalog')->__('Severity'),
            'index'     => 'severity',
        ));

        $this->addColumn('line_number', array(
            'header'    => Mage::helper('catalog')->__('Line Number'),
            'index'     => 'line_number',
        ));

        $this->addColumn('message', array(
            'header'    => Mage::helper('catalog')->__('Message'),
            'index'     => 'message',
        ));
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}
