<?php

namespace Codebender\LibraryBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class DefaultControllerFunctionalTest
 * @package Codebender\LibraryBundle\Tests\Controller
 * @SuppressWarnings(PHPMD)
 */
class DefaultControllerFunctionalTest extends WebTestCase
{
    public function testStatus()
    {
        $client = static::createClient();

        $client->request('GET', '/status');

        $this->assertEquals('{"success":true,"status":"OK"}', $client->getResponse()->getContent());

    }

    public function testInvalidMethod()
    {
        $client = static::createClient();

        $authorizationKey = $client->getContainer()->getParameter('authorizationKey');

        $client->request('GET', "/$authorizationKey/v1");

        $this->assertEquals(405, $client->getResponse()->getStatusCode());

    }

    public function testList()
    {
        $client = static::createClient();

        $authorizationKey = $client->getContainer()->getParameter('authorizationKey');

        $client = $this->postApiRequest($client, $authorizationKey, '{"type":"list"}');

        $response = $client->getResponse()->getContent();
        $response = json_decode($response, true);

        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);

        $this->assertArrayHasKey('categories', $response);
        $categories = $response['categories'];

        $this->assertArrayHasKey('Examples', $categories);
        $this->assertNotEmpty($categories['Examples']);

        $this->assertArrayHasKey('Builtin Libraries', $categories);
        $this->assertNotEmpty($categories['Builtin Libraries']);

        $this->assertArrayHasKey('External Libraries', $categories);
        $this->assertNotEmpty($categories['External Libraries']);

        $basicExamples = $categories['Examples']['01.Basics']['examples'];


        // Check for a specific, known example
        $foundExample = array_filter($basicExamples, function($element) {
            if ($element['name'] == 'AnalogReadSerial') {
                return true;
            }
        });

        $foundExample = array_values($foundExample);

        // Make sure the example was found
        $this->assertEquals('AnalogReadSerial', $foundExample[0]['name']);
    }


    public function testGetFixtureLibrary()
    {
        $client = static::createClient();

        $authorizationKey = $client->getContainer()->getParameter('authorizationKey');

        $client = $this->postApiRequest($client, $authorizationKey, '{"type":"fetch","library":"default"}');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertTrue($response['success']);

        $filenames = array_column($response['files'], 'filename');

        $this->assertContains('default.cpp', $filenames);
        $this->assertContains('default.h', $filenames);

        $baseLibraryPath = $client->getKernel()->locateResource('@CodebenderLibraryBundle/Resources/library_files/default');

        $contents = array_column($response['files'], 'content');

        $this->assertContains(file_get_contents($baseLibraryPath . '/default.cpp'), $contents);

        $this->assertContains(file_get_contents($baseLibraryPath . '/default.h'), $contents);

    }

    public function testGetExampleCode()
    {
        $client = static::createClient();

        $authorizationKey = $client->getContainer()->getParameter('authorizationKey');

        $client = $this->postApiRequest(
            $client,
            $authorizationKey,
            '{"type":"getExampleCode","library":"EEPROM","example":"eeprom_read"}'
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertTrue($response['success']);
        $this->assertEquals('eeprom_read.ino', $response['files'][0]['filename']);
        $this->assertContains('void setup()', $response['files'][0]['code']);
    }

    public function testGetExamples()
    {
        $client = static::createClient();

        $authorizationKey = $client->getContainer()->getParameter('authorizationKey');

        $client = $this->postApiRequest($client, $authorizationKey, '{"type":"getExamples","library":"EEPROM"}');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('eeprom_clear', $response['examples']);
        $this->assertArrayHasKey('eeprom_read', $response['examples']);
        $this->assertArrayHasKey('eeprom_write', $response['examples']);
    }

    public function testFetchLibrary()
    {
        $client = static::createClient();

        $authorizationKey = $client->getContainer()->getParameter('authorizationKey');

        $client = $this->postApiRequest($client, $authorizationKey, '{"type":"fetch","library":"EEPROM"}');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertTrue($response['success']);
        $this->assertEquals('Library found', $response['message']);

        $filenames = array_column($response['files'], 'filename');
        $this->assertContains('EEPROM.cpp', $filenames);
        $this->assertContains('EEPROM.h', $filenames);
        $this->assertContains('keywords.txt', $filenames);

    }

    public function testCheckGithubUpdates()
    {
        $client = static::createClient();

        $authorizationKey = $client->getContainer()->getParameter('authorizationKey');

        $client = $this->postApiRequest($client, $authorizationKey, '{"type":"checkGithubUpdates"}');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertTrue($response['success']);
        $this->assertEquals('1 external libraries need to be updated', $response['message']);
        /*
         * DynamicArrayHelper library's last commit is not the same as its origin.
         */
        $this->assertEquals('DynamicArrayHelper', $response['libraries'][0]['Machine Name']);

        /*
         * Disabling the library should make it not be returned in the list.
         */
        $client->request('POST', '/' . $authorizationKey . '/toggleStatus/DynamicArrayHelper');
        $client = $this->postApiRequest($client, $authorizationKey, '{"type":"checkGithubUpdates"}');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertTrue($response['success']);
        $this->assertEquals('No external libraries need to be updated', $response['message']);

    }

    public function testGetKeywords()
    {
        $client = static::createClient();

        $authorizationKey = $client->getContainer()->getParameter('authorizationKey');

        $client = $this->postApiRequest($client, $authorizationKey, '{"type":"getKeywords","library":"EEPROM"}');

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(true, $response['success']);
        $this->assertArrayHasKey('keywords', $response);
        $this->assertArrayHasKey('KEYWORD1', $response['keywords']);
        $this->assertEquals('EEPROM', $response['keywords']['KEYWORD1'][0]);
    }

    /**
     * Use this method for library manager API requests with POST data
     *
     * @param Client $client
     * @param string $authKey
     * @param string $data
     * @return Client
     */
    private function postApiRequest(Client $client, $authKey, $data)
    {
        $client->request(
            'POST',
            '/' . $authKey . '/v1',
            [],
            [],
            [],
            $data,
            true
        );

        return $client;
    }

}
