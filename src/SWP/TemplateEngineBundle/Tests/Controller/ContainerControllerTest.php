<?php

/**
 * This file is part of the Superdesk Web Publisher Web Renderer Bundle.
 *
 * Copyright 2015 Sourcefabric z.u. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2015 Sourcefabric z.ú.
 * @license http://www.superdesk.org/license
 */
namespace SWP\WebRendererBundle\Tests\Controller;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use SWP\ContentBundle\Document\Article;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class ContainerControllerTest extends WebTestCase
{
    protected $router;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        self::bootKernel();

        $this->loadFixtureFiles([
            '@SWPFixturesBundle/DataFixtures/ORM/Test/Container.yml',
            '@SWPFixturesBundle/DataFixtures/ORM/Test/Widget.yml'
        ]);

        $this->router = $manager = $this->getContainer()->get('router');

        $this->runCommand('doctrine:phpcr:init:dbal', ['--force' => true, '--env' => 'test'], true);
        $this->runCommand('doctrine:phpcr:repository:init', ['--env' => 'test'], true);
        $this->runCommand('theme:setup', ['--env' => 'test', '--force' => true, 'name' => 'theme_test'], true);
    }

    // public function testListContainersApi()
    // {
    //     $client = static::createClient();
    //     $client->request('GET', $this->router->generate('swp_api_templates_list_containers'));
    //
    //     $this->assertEquals(200, $client->getResponse()->getStatusCode());
    //     $this->assertEquals($client->getResponse()->getContent(), '{"page":1,"limit":10,"pages":1,"total":2,"_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/containers\/?page=1&limit=10"},"first":{"href":"http:\/\/localhost\/api\/v1\/templates\/containers\/?page=1&limit=10"},"last":{"href":"http:\/\/localhost\/api\/v1\/templates\/containers\/?page=1&limit=10"}},"_embedded":{"_items":[{"id":1,"type":1,"name":"Simple Container 1","width":300,"height":400,"styles":"color: #00000","css_class":"col-md-12","visible":true,"data":[],"widgets":[],"_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/containers\/1"}}},{"id":2,"type":1,"name":"Simple Container 2","width":400,"height":500,"styles":"border: 1px solid red;","css_class":"col-md-6","visible":true,"data":[],"widgets":[],"_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/containers\/2"}}}]}}');
    // }
    //
    // public function testSingleContainerApi()
    // {
    //     $client = static::createClient();
    //     $client->request('GET', $this->router->generate('swp_api_templates_get_container', ['id' => 1]));
    //
    //     $this->assertEquals(200, $client->getResponse()->getStatusCode());
    //     $this->assertEquals($client->getResponse()->getContent(), '{"id":1,"type":1,"name":"Simple Container 1","width":300,"height":400,"styles":"color: #00000","css_class":"col-md-12","visible":true,"data":[],"widgets":[],"_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/containers\/1"}}}');
    // }
    //
    // public function testUpdateContainerApi()
    // {
    //     $client = static::createClient();
    //     $client->request('PATCH', $this->router->generate('swp_api_templates_update_container', ['id' => 1]), [
    //         'container' => [
    //             'name' => 'Simple Container 1',
    //             'height' => '301',
    //             'width' => '401',
    //             'styles' => 'color: #00001',
    //             'visible' => 0,
    //             'cssClass' => 'col-md-11',
    //         ]
    //     ]);
    //
    //     $this->assertEquals(200, $client->getResponse()->getStatusCode());
    //     $this->assertEquals($client->getResponse()->getContent(), '{"id":1,"type":1,"name":"Simple Container 1","width":401,"height":301,"styles":"color: #00001","css_class":"col-md-11","visible":false,"data":[],"widgets":[],"_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/containers\/1"}}}');
    // }
    //
    // public function testUpdateSingleContainerPropertyApi()
    // {
    //     $client = static::createClient();
    //     $client->request('PATCH', $this->router->generate('swp_api_templates_update_container', ['id' => 1]), [
    //         'container' => [
    //             'width' => '402',
    //         ]
    //     ]);
    //
    //     $this->assertEquals(200, $client->getResponse()->getStatusCode());
    //     $this->assertEquals($client->getResponse()->getContent(), '{"id":1,"type":1,"name":"Simple Container 1","width":402,"height":400,"styles":"color: #00000","css_class":"col-md-12","visible":true,"data":[],"widgets":[],"_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/containers\/1"}}}');
    // }

    // public function testLinkingWidgetToContainerApi()
    // {
    //     $client = static::createClient();
    //     $client->request('LINK', $this->router->generate('swp_api_templates_link_container', ['id' => 1]), [], [], [
    //         'HTTP_LINK' => '</api/v1/templates/widgets/1; rel="widget">'
    //     ]);
    //     $this->assertEquals(201, $client->getResponse()->getStatusCode());
    //     $this->assertEquals(
    //         '{"id":1,"type":1,"name":"Simple Container 1","width":300,"height":400,"styles":"color: #00000","css_class":"col-md-12","visible":true,"data":[],"widgets":[{"id":1,"widget":{"id":1,"type":"\\\\SWP\\\TemplatesSystem\\\Gimme\\\Widget\\\HtmlWidget","name":"HtmlWidget number 1","visible":true,"parameters":"{\"html_body\": \"sample widget with <span style=\\\\\\"color:red;\\\\\\">html<\/span>\"}","_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/widgets\/1"}}},"position":"0"}],"_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/containers\/1"}}}',
    //         $client->getResponse()->getContent()
    //     );
    // }
    //
    // public function testUnlinkingWidgetToContainerApi()
    // {
    //     $client = static::createClient();
    //     $client->enableProfiler();
    //     $client->request('LINK', $this->router->generate('swp_api_templates_link_container', ['id' => 1]), [], [], [
    //         'HTTP_LINK' => '</api/v1/templates/widgets/1; rel="widget">'
    //     ]);
    //     $client->request('UNLINK', $this->router->generate('swp_api_templates_link_container', ['id' => 1]), [], [], [
    //         'HTTP_LINK' => '</api/v1/templates/widgets/1; rel="widget">'
    //     ]);
    //
    //     $this->assertEquals(201, $client->getResponse()->getStatusCode());
    //     $this->assertEquals($client->getResponse()->getContent(), '{"id":1,"type":1,"name":"Simple Container 1","width":300,"height":400,"styles":"color: #00000","css_class":"col-md-12","visible":true,"data":[],"widgets":[],"_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/containers\/1"}}}');
    // }

    public function testLinkingOnExactPositionApi()
    {
        $client = static::createClient();
        $client->request('LINK', $this->router->generate('swp_api_templates_link_container', ['id' => 1]), [], [], [
            'HTTP_LINK' => '</api/v1/templates/widgets/1; rel="widget">'
        ]);
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $this->assertEquals(
            '{"id":1,"type":1,"name":"Simple Container 1","width":300,"height":400,"styles":"color: #00000","css_class":"col-md-12","visible":true,"data":[],"widgets":[{"id":1,"widget":{"id":1,"type":"\\\\SWP\\\TemplatesSystem\\\Gimme\\\Widget\\\HtmlWidget","name":"HtmlWidget number 1","visible":true,"parameters":"{\"html_body\": \"sample widget with <span style=\\\\\\"color:red;\\\\\\">html<\/span>\"}","_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/widgets\/1"}}},"position":"0"}],"_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/containers\/1"}}}',
            $client->getResponse()->getContent()
        );

        // Move widget 2 on position 1
        $client->request('LINK', $this->router->generate('swp_api_templates_link_container', ['id' => 1]), [], [], [
            'HTTP_LINK' => '</api/v1/templates/widgets/2; rel="widget">,<1; rel="widget-position">'
        ]);
        $this->assertEquals(
            '{"id":1,"type":1,"name":"Simple Container 1","width":300,"height":400,"styles":"color: #00000","css_class":"col-md-12","visible":true,"data":[],"widgets":[{"id":1,"widget":{"id":1,"type":"\\\\SWP\\\TemplatesSystem\\\Gimme\\\Widget\\\HtmlWidget","name":"HtmlWidget number 1","visible":true,"parameters":"{\"html_body\": \"sample widget with <span style=\\\\\\"color:red;\\\\\\">html<\/span>\"}","_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/widgets\/1"}}},"position":"0"},{"id":2,"widget":{"id":2,"type":"\\\SWP\\\TemplatesSystem\\\Gimme\\\Widget\\\HtmlWidget","name":"HtmlWidget number 2","visible":true,"parameters":"{\"html_body\": \"sample widget with html 2\"}","_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/widgets\/2"}}},"position":"1"}],"_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/containers\/1"}}}',
            $client->getResponse()->getContent()
        );

        // Move widget 2 on position 0
        $client->request('LINK', $this->router->generate('swp_api_templates_link_container', ['id' => 1]), [], [], [
            'HTTP_LINK' => '</api/v1/templates/widgets/2; rel="widget">,<0; rel="widget-position">'
        ]);
        $this->assertEquals(
            '{"id":1,"type":1,"name":"Simple Container 1","width":300,"height":400,"styles":"color: #00000","css_class":"col-md-12","visible":true,"data":[],"widgets":[{"id":1,"widget":{"id":1,"type":"\\\\SWP\\\TemplatesSystem\\\Gimme\\\Widget\\\HtmlWidget","name":"HtmlWidget number 1","visible":true,"parameters":"{\"html_body\": \"sample widget with <span style=\\\\\\"color:red;\\\\\\">html<\/span>\"}","_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/widgets\/1"}}},"position":"1"},{"id":2,"widget":{"id":2,"type":"\\\SWP\\\TemplatesSystem\\\Gimme\\\Widget\\\HtmlWidget","name":"HtmlWidget number 2","visible":true,"parameters":"{\"html_body\": \"sample widget with html 2\"}","_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/widgets\/2"}}},"position":"0"}],"_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/containers\/1"}}}',
            $client->getResponse()->getContent()
        );

        // Move widget to on last position (1) with parameter: -1
        $client->request('LINK', $this->router->generate('swp_api_templates_link_container', ['id' => 1]), [], [], [
            'HTTP_LINK' => '</api/v1/templates/widgets/2; rel="widget">,<-1; rel="widget-position">'
        ]);
        $this->assertEquals(
            '{"id":1,"type":1,"name":"Simple Container 1","width":300,"height":400,"styles":"color: #00000","css_class":"col-md-12","visible":true,"data":[],"widgets":[{"id":1,"widget":{"id":1,"type":"\\\\SWP\\\TemplatesSystem\\\Gimme\\\Widget\\\HtmlWidget","name":"HtmlWidget number 1","visible":true,"parameters":"{\"html_body\": \"sample widget with <span style=\\\\\\"color:red;\\\\\\">html<\/span>\"}","_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/widgets\/1"}}},"position":"0"},{"id":2,"widget":{"id":2,"type":"\\\SWP\\\TemplatesSystem\\\Gimme\\\Widget\\\HtmlWidget","name":"HtmlWidget number 2","visible":true,"parameters":"{\"html_body\": \"sample widget with html 2\"}","_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/widgets\/2"}}},"position":"1"}],"_links":{"self":{"href":"http:\/\/localhost\/api\/v1\/templates\/containers\/1"}}}',
            $client->getResponse()->getContent()
        );
    }
}
