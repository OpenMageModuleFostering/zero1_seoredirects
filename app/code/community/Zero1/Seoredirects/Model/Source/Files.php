<?php
class Zero1_Seoredirects_Model_Source_Files
{
    /**
     * Retrieve options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();

        $options[] = array(
            'label' => 'None',
            'value' => '',
        );

        $import_path = Mage::getBaseDir('var').DS.'seoredirects'.DS.'import';
        if(is_dir($import_path)) {
            foreach(glob($import_path.DS.'*.csv') as $filename) {
                $filename = str_replace($import_path.DS, '', $filename);
                $options[] = array(
                    'label' => $filename,
                    'value' => $filename,
                );
            }
        } else {
            mkdir($import_path, 0777, true);
        }

        return $options;
    }
}
