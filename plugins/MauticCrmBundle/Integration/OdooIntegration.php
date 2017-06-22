<?php

/*
 * @copyright	2017 Mautic Contributors. All rights reserved
 * @author		Axol Bioscience
 * 
 * @link		http://axolbio.com
 * 
 * @license		GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Integration;

use Auke\Ripcord\Client;
use Auke\Ripcord\ripcord;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Integration\AbstractIntegration;

$url = 'https://www.axolbio.com/web';
$db = '';
$username = 'admin';
$password = '';


/**
 * Class OdooIntegration
 */
class OdooIntegration extends CrmAbstractIntegration
{
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Mautic\PluginBundle\Integration\AbstractIntegration::getName()
	 * @return string
	 */
	public function getName()
	{
		return 'Odoo';
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Mautic\PluginBundle\Integration\AbstractIntegration::getRequiredKeyFields()
	 * @return string[]
	 */
	public function getRequiredKeyFields()
	{
		return [
			'username'	=> 'mautic.integration.keyfield.username',
			'password'	=> 'mautic.integration.keyfield.password',
			'database'	=> 'mautic.integration.keyfield.odoo.database',
			'hostUrl'	=> 'mautic.integration.keyfield.odoo.url'
		];
	}
	
	/**
	 * Get the URL for the hosted database
	 * @return string
	 */
	public function getApiUrl()
	{
		//return 'https://www.axolbio.com/web';
		return $this->keys['hostUrl'];
	}
	
	/**
	 * Get the name of the database that the integration connects to
	 * @return string
	 */
	public function getDatabase()
	{
		return $this->keys['database'];
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Mautic\PluginBundle\Integration\AbstractIntegration::getSecretKeys()
	 * @return string[]
	 */
	public function getSecretKeys()
	{
		return [
			'password'
		];
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration::getClientIdKey()
	 * @return string
	 */
	public function getClientIdKey(){
		return 'username';
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration::getClientSecretKey()
	 * @return string
	 */
	public function getClientSecretKey(){
		return 'password';
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration::getAuthenticationType()
	 * @return string
	 */
	public function getAuthenticationType()
	{
		return 'rest';
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Mautic\PluginBundle\Integration\AbstractIntegration::getAuthTokenKey()
	 * @return string
	 */
	public function getAuthTokenKey()
	{
		return 'uid';
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Mautic\PluginBundle\Integration\AbstractIntegration::makeRequest()
	 * @return mixed
	 */
	public function makeRequest($url, $parameters = [], $method = null, $settings = [])
	{
		$db = $this->getDatabase();
		$password = $this->keys[$this->getClientSecretKey()];
		$uid;
		//Ensure that Mautic has authenticated itself before trying to make a request
		if(!$this->isAuthorized())
		{
			$username = $this->keys[$this->getClientIdKey()];
			$common = ripcord::client($this->getApiUrl().'/xmlrpc/2/common');
			//Attempt to login
			$uid = $common->authenticate($db,$username,$password,array());
			//If the login attempt fails, just return the failure
			if(strpos($uid,"LOGIN FAIL:") == false){
				return $uid;
			} else{
				$this->keys[$this->getAuthTokenKey()] = $uid;
			}
		} else
		{
			$uid = $this->keys[$this->getAuthTokenKey()];
		}
		
		//Dispatch an event to inform all listeners that a request is being made and modify parameters/headers appropriately
		if (empty($settings['ignore_event_dispatch'])) {
			$event = $this->dispatcher->dispatch(
					PluginEvents::PLUGIN_ON_INTEGRATION_REQUEST,
					new PluginIntegrationRequestEvent($this, $url, $parameters, $headers, $method, $settings, $authType)
					);
			
			$parameters = $event->getParameters();
		}
		
		$models = Ripcord\ripcord::client($url);
		$result = $models->execute_kw($db, $uid, $password,
				$parameters['model'], $parameters['operation'],$parameters['values']
		);
		return $id;
	}
	
	/**
	 * Formats lead data from Mautix to be usable with Odoo
	 * @param mixed[] $data
	 * @return array
	 */
	public function formatLeadDataForCreateOrUpdate($data)
	{
		$formattedData = array();
		
		//The individual's name
		$contact_name = null;
		if(isset($data['firstname']))
		{
			$contact_name = $data['firstname'];
			if(isset($data['lastname']))
			{
				$contact_name .= " " . $data['lastname'];
			}
		} else
		{
			$contact_name = $data['lastname'];
		}
		$formattedData[] = array('contact_name' => $contact_name);
		//The company's name
		$partner_name = null;
		if(isset($data['company']))
		{
			$partner_name = $data['company'];
		}
		$formattedData[] = array('partner_name' => $partner_name);
		//Collated customer name
		$partner_id = null;
		if(!is_null($contact_name))
		{
			$partner_id = $contact_name;
			if(!is_null($partner_name))
			{
				$partner_id .= ", $partner_name";
			}
		} else if(!is_null($partner_name))
		{
			$partner_id = $partner_name;
		}
		$formattedData[] = array('partner_id' => $partner_id);
		
		//Identifier in the database
		$name = "$contact_name - Recommended by Mautic";
		$formattedData[] = array('name' => $name);
		
		//email address
		$email_from = $data['email'];
		$formattedData[] = array('email_from' => $email_from);
		//phone number
		$phone = isset($data['phone']) ? $data['phone'] : null;
		$formattedData[] = array('phone' => $phone);
		//country
		$country_id = isset($data['country']) ? $data['country'] : null;
		$formattedData[] = array('country_id' => $country_id);
		//what stage the opportunity is at
		$stage_id = 'new';
		$formattedData[] = array('stage_id' => $stage_id);
		//approximate viability
		$probability = 0.25;
		$formattedData[] = array('probability' => $probability);
		//referral source
		$referred = 'Mautic';
		$formattedData[] = array('referred' => $referred);
		//whether active or not
		$active = true;
		$formattedData[] = array('active' => $active);
		//what should be done with the opportunity
		$title_action = 'Contact to investigate as potential client';
		$formattedData[] = array('title_action' => $title_action);
		//When the action should be performed by
		$time = strtotime("+7 days");
		$date_action = date('d/m/Y', $time);
		$formattedData[] = array('data_action' => $date_action);
		//How the opportunity should be prioritised
		$priority = 0.25;
		$formattedData[] = array('priority' => $priority);
		
		return $formattedData;
	}
}