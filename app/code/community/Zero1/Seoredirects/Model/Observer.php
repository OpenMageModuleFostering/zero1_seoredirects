<?php
/**
 * Class Zero1_Seoredirects_Model_Observer
 */
class Zero1_Seoredirects_Model_Observer
{
    /**
     *  The default licence limit
     */
    const DEFAULT_LIMIT = 50;

    /**
     * Stores the current Google URL to download the document from
     *
     * @var string
     */
    protected $_url;

    /**
     * Stores the current file to import from
     *
     * @var string
     */
    protected $_file;

    /**
     * Strip "http://www.domain.com/" from the URL
     *
     * @var bool
     */
    protected $_strip_domain;

    /**
     * Strip "/" from the URL
     *
     * @var bool
     */
    protected $_strip_slash;

    /**
     * @param Varien_Event_Observer $observer
     */
    protected function checkForRedirection(Varien_Event_Observer $observer)
	{
		$request = $observer->getFront()->getRequest();
		
		if($request->getActionName() != 'noRoute')
			return; // There is a valid route, nothing to do
		
		// First search for the URI with query strings
		$requestUri = preg_replace('/^'.preg_quote($request->getBasePath(), '/').'/', '', $request->getRequestUri());
		$requestUri = str_replace('?', '_', $requestUri);	// Zend	does not support "?", replace them with "_" wildcard and use LIKE
		$redirections = Mage::getModel('seoredirects/redirection')->getCollection();
		$redirections->addFieldToFilter('store', Mage::app()->getStore()->getId());
		$redirections->addFieldToFilter('redirect_from', array('like' => $requestUri));
        
		foreach($redirections as $redirection)
		{
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: '.$request->getBasePath().$redirection->getRedirectTo());
			die();
		}
		
		// Then search for the URI without query strings		
		$requestUri = preg_replace('/^'.preg_quote($request->getBasePath(), '/').'/', '', $request->getRequestString());
		$redirections = Mage::getModel('seoredirects/redirection')->getCollection();
		$redirections->addFieldToFilter('store', Mage::app()->getStore()->getId());
		$redirections->addFieldToFilter('redirect_from', $requestUri);
        
		foreach($redirections as $redirection)
		{
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: '.$request->getBasePath().$redirection->getRedirectTo());
			die();
		}
		
		// No redirections
		return;
	}

    /**
     * @param Varien_Event_Observer $observer
     */
    public function controller_front_send_response_before(Varien_Event_Observer $observer)
	{
		$this->checkForRedirection($observer);
	}

    /**
     * @param Varien_Event_Observer $observer
     */
    public function controller_front_send_response_after(Varien_Event_Observer $observer)
	{
		$this->checkForRedirection($observer);
	}

    /**
     * Clean the URL bu removing domain and trailing slashes
     *
     * @param $url
     * @return string
     */
    protected function _cleanUrl($url)
    {
        // Strip the domain name from the FROM / TO URL's if it exists
        if($this->_strip_domain) {
            $url = preg_replace('/^[^:]+:\\/\\/[^\\/]+(.*)$/si', '$1', $url);
        }

        // Strip the prefix slash from the FROM / TO URL's if it exists
        if($this->_strip_slash) {
            $url = preg_replace('/^\\/(.*)$/si', '$1', $url);
        }

        return $url;
    }

    /**
     *  Update the redirection(s) from Google and import files
     *
     */
    public function updateRedirectionsCronJob()
	{
        clearstatcache();
		
		$stores = Mage::getResourceModel('core/store_collection');		
		$results = array();
		
		foreach($stores as $store) {
            $this->_url = Mage::getStoreConfig('seoredirects/settings/url', $store->getId());
            $this->_strip_domain = Mage::getStoreConfig('seoredirects/settings/strip_domain', $store->getId());
            $this->_strip_slash = Mage::getStoreConfig('seoredirects/settings/strip_slash', $store->getId());
            $this->_file = Mage::getStoreConfig('seoredirects/settings/file', $store->getId());

			$results[$store->getId()] = array(
					'updated' => 0,
					'deleted' => 0,
					'added' => 0,
					'loops' => 0,
					'limitation' => '',
				);
			
			$redirections = Mage::getModel('seoredirects/redirection')->getCollection();
			$redirections->addFieldToFilter('store', $store->getId());
				
			if(!Mage::getStoreConfig('seoredirects/settings/enabled', $store->getId()))
			{
				// Redirects are disabled for this store, delete them all
				foreach($redirections as $redirection)
				{
					$redirection->delete();
					$results[$store->getId()]['deleted']++;
				}
		
				continue; // Do not import anything now
			}

            $redirects = array();

            ////////////////////////////////////////////////
            // Load redirection(s) from Google
			if(!empty($this->_url)) {
                // Get the content from Google and save it in to a file
                $client = new Zend_Http_Client($this->_url);
                $lines = explode(PHP_EOL, $client->request()->getBody());
                foreach($lines as $line) {
                    $data = str_getcsv($line);
                    if(is_array($data) && count($data) >= 2) {
                        $redirects[$this->_cleanUrl($data[0])] = $this->_cleanUrl($data[1]);
                    }
                }
            }

            ////////////////////////////////////////////////
            // Load redirection(s) from files
            $import_path = Mage::getBaseDir('var').'/seoredirects/import';
            if(is_dir($import_path)) {
                if(is_file($import_path.DS.$this->_file) && is_readable($import_path.DS.$this->_file)) {
                    $fp = fopen($import_path.DS.$this->_file, 'r');
                    while(($data = fgetcsv($fp, 4096)) !== false) {
                        if(is_array($data) && count($data) >= 2) {
                            $redirects[$this->_cleanUrl($data[0])] = $this->_cleanUrl($data[1]);
                        }
                    }
                    fclose($fp);
                }
            } else {
                mkdir($import_path, 0777, true);
            }
		
			// Detect the limit for this store
			$license_data = array();
			if(Mage::helper('seoredirects/license')->isValid($store))
				$license_data = Mage::helper('seoredirects/license')->getData($store);
			
			if(isset($license_data['limit']))
			{
				$limit = $license_data['limit'];
			} else {
				$limit = self::DEFAULT_LIMIT;
			}
			
			if($limit > 0)
			{
				$redirects = array_slice($redirects, 0, $limit);
				$results[$store->getId()]['limitation'] = 'Limited to '.$limit.' rows by your current license. <a href="'.Mage::helper('seoredirects/license')->getRequestURL($store).'" target="_blank">Click here to increase this limit</a>';
			}
			
			// Add / update the redirection(s)
			foreach($redirects as $from => $to)
			{
				$found = false;
				
				foreach($redirections as $redirection)
				{
					if($redirection->getRedirectFrom() == $from)
					{
						// Existing redirection, update
						$redirection->setRedirectTo($to);
						$redirection->setRedirectFrom($from);
						$redirection->setStore($store->getId());
						$redirection->save();
						$found = true;
						$results[$store->getId()]['updated']++;
			    
						//echo 'Updated <b>'.$from.'</b> = <b>'.$to.'</b> into store ID <b>'.$store->getId().'</b><br/>';
					}
				}
				
				if(!$found)
				{
					// New redirection
					$redirection = Mage::getModel('seoredirects/redirection');
					$redirection->setRedirectTo($to);
					$redirection->setRedirectFrom($from);
					$redirection->setStore($store->getId());
					$redirection->save();
					$results[$store->getId()]['added']++;
		
					//echo 'Added <b>'.$from.'</b> = <b>'.$to.'</b> into store ID <b>'.$store->getId().'</b><br/>';
				}
			}
			
			// Remove all the unused redirection(s)
			$redirections = Mage::getModel('seoredirects/redirection')->getCollection();
			$redirections->addFieldToFilter('store', $store->getId());
			foreach($redirections as $redirection)
			{
				if(!array_key_exists($redirection->getRedirectFrom(), $redirects))
				{
					//echo 'Removed <b>'.$redirection->getRedirectFrom().'</b> from store ID <b>'.$store->getId().'</b><br/>';
					$redirection->delete();
					$results[$store->getId()]['deleted']++;
				}
			}
				
			// Make sure there are no redirection(s) loops
			$redirections = Mage::getModel('seoredirects/redirection')->getCollection();
			$redirections->addFieldToFilter('store', $store->getId());
			foreach($redirections as $redirectionA)
			{
				foreach($redirections as $redirectionB)
				{
					if(!$redirectionA || !$redirectionB)
						continue;	// Invalid rediection, been deleted?
					
					if($redirectionA->getRedirectTo() == $redirectionB->getRedirectFrom())
					{
						//echo 'Removed loop <b>'.$redirection->getRedirectFrom().'</b> from store ID <b>'.$store->getId().'</b><br/>';
						$redirectionA->delete();
						$results[$store->getId()]['loops']++;
					}
				}
			}
		}

		Mage::register('seoredirects_results', $results);
	}
}