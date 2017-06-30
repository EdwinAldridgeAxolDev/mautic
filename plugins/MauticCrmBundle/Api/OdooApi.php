<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\PluginBundle\Exception\ApiErrorException;

/**
 * @property OdooIntegration $integration
 */
class OdooApi extends CrmApi
{
    /**
     * Formats an external Odoo request and calls makeRequest with the correct parameters.
     *
     * @param string $url
     * @param string $operation
     * @param array  $parameters
     * @param string $method
     * @param string $object
     *
     * @return mixed
     */
    protected function request($url, $operation, $parameters = [], $method = null, $object = null)
    {
        $extraParameters = $parameters;
        $parameters      = [
                'model'     => $object,
                'operation' => $operation,
                'values'    => $extraParameters,
        ];
        $request = $this->integration->makeRequest($url, $parameters);
        if (isset($request['faultCode'])) {
            $message             = $request['faultString'];
            $exceptionMessagePos = strrpos($message, 'Error:');
            $startMessagePos     = strrpos($message, '\n', 0 - $exceptionMessagePos);
            $exeptionMessage     = substr($message, $startMessagePos);
            //$message = 'No result from the server, check your command\'s validity and that you have permission to execute it';
            throw new ApiErrorException($exeptionMessage);
        }

        return $result;
    }

    /**
     * @param array $data
     * @param Lead  $lead
     *
     * @return mixed
     */
    public function createLead(array $data, $lead)
    {
        /*At the minimum, we require the following:
         * some name value - representing the the company/user
         * an email address
        */
        $email = $data['email'];
        //Test for validity
        MailHelper::validateEmail($email);
        $company   = $data['company'];
        $firstName = $data['firstname'];
        $lastName  = $data['lastname'];
        if (is_null($company) && (is_null($firstName) || is_null($lastName))) {
            throw new \Exception('Need at least a company or contact name');
        }
        $formattedLeadData = $this->integration->formatLeadDataForCreateOrUpdate($data, $lead, $updateLink);

        $url       = $this->integration->getApiUrl().'/xmlrpc/2/object';
        $object    = 'crm.lead';
        $operation = 'create';
        $result    = $this->request($url, $operation, $formattedLeadData, null, $object);

        return $result;
    }
}
