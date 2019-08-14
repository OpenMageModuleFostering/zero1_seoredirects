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
 * Sitemap edit form
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Zero1_Seoredirects_Block_Adminhtml_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Init form
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('redirection_form');
        $this->setTitle(Mage::helper('adminhtml')->__('Redirection Information'));
    }


    protected function _prepareForm()
    {
        $model = Mage::registry('zero1_seo_redirect');

        $form = new Varien_Data_Form(array(
            'id'        => 'edit_form',
            'action'    => $this->getData('action'),
            'method'    => 'post'
        ));

        $fieldset = $form->addFieldset('add_seo_redirect_form', array('legend' => Mage::helper('zero1_seo_redirects')->__('Seo Redirection')));

        if ($model->getId()) {
            $fieldset->addField('redirection_id', 'hidden', array(
                'name' => 'redirection_id',
            ));
        }

        $fieldset->addField('source', 'hidden', array(
            'name' => 'source',
        ));
        if(!$model->getSource()){
            $model->setSource(Zero1_Seoredirects_Model_Redirection::SOURCE_TYPE_MANUAL_VALUE);
        }

        $fieldset->addField('store_id', 'select', array(
            'label'     => Mage::helper('adminhtml')->__('Store'),
            'title'     => Mage::helper('adminhtml')->__('Store'),
            'name'      => 'store_id',
            'required'  => true,
            'options'   => Mage::helper('zero1_seo_redirects')->getStoreUrls(),
        ));

        $fieldset->addField('from_url_path', 'text', array(
            'label' => Mage::helper('zero1_seo_redirects')->__('From URL'),
            'name'  => 'from_url_path',
            'required' => true,
            'note'  => Mage::helper('zero1_seo_redirects')->__('example: example/b/?foo=bar'),
            'value' => $model->getFromUrlPath()
        ));

        $fieldset->addField('from_type', 'select', array(
            'label'     => Mage::helper('adminhtml')->__('From Type'),
            'title'     => Mage::helper('adminhtml')->__('From Type'),
            'name'      => 'from_type',
            'required'  => true,
            'options'   => Mage::helper('zero1_seo_redirects')->getFromTypes(),
        ));
        if($model->getFromType() == null){
            $model->setFromType(Zero1_Seoredirects_Model_Redirection::FROM_TYPE_FIXED_QUERY_VALUE);
        }

        $hidden = false;
//        if($model->getFromType() == Zero1_Seoredirects_Model_Redirection::FROM_TYPE_FIXED_QUERY_VALUE){
//            $hidden = true;
//        }

        $fieldset->addField('persist_query', 'select', array(
            'label'     => Mage::helper('adminhtml')->__('Persist Query'),
            'title'     => Mage::helper('adminhtml')->__('Persist Query'),
            'name'      => 'persist_query',
            'required'  => false,
            'options'   => array(0 => 'No', 1 => 'Yes'),
            'note'      => 'Only applicable if from type is '.Zero1_Seoredirects_Model_Redirection::FROM_TYPE_OPEN_ENDED_QUERY_LABEL
        ));

        $fieldset->addField('to_url', 'text', array(
            'label' => Mage::helper('zero1_seo_redirects')->__('To URL'),
            'name'  => 'to_url',
            'required' => true,
            'note'  => Mage::helper('zero1_seo_redirects')->__('example: moved/here/?b=a'),
            'value' => $model->getFromUrl()
        ));

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

}
