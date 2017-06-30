<?php

namespace MauticPlugin\MauticCrmBundle\Tests\OdooApi;

include __DIR__.'/../autoload.php';

/* require_once "plugins/MauticCrmBundle/Api/OdooApi.php";
require_once "plugins/MauticCrmBundle/Integration/OdooIntegration.php"; */

class OdooApiTest extends \PHPUnit_Framework_TestCase
{
    public function testAttributes()
    {
        $this->assertClassHasAttribute('integration', 'OdooApi');
        $this->assertAttributeInstanceOf('OdooIntegration', 'integration', 'OdooApi');
    }

    /* public function testRequest()
    {
        $testIntegration = new OdooIntegration();
        $testApi = new OdooApi($testIntegration);

        $actual = $testApi->
    } */

    public function testCreateLead()
    {
        $testIntegration = new OdooIntegration();
        $testApi         = new OdooApi($testIntegration);
        $data            = [
            'email'     => 'joe.bloggs@testcompany.com',
            'firstname' => 'Joe',
            'lastname'  => 'Bloggs',
        ];

        $actual = $testApi->createLead($data, $lead);

        $this->assertInstanceOf('integer', $actual);
    }
}
