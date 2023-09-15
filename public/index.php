<?php

use App\Renderer\TemplateRenderer;
use DI\ContainerBuilder;
use Odan\Session\Middleware\SessionStartMiddleware;
use Odan\Session\SessionInterface;
use Psr\Container\ContainerInterface;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/container.php');

AppFactory::setContainer($containerBuilder->build());
$app = AppFactory::create();

$app->add(SessionStartMiddleware::class);
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$app->get('/', function ($request, $response) {
    /* @var $this ContainerInterface */
    $renderer = $this->get(TemplateRenderer::class);
    $renderer->setLayout('layout');

    $session = $this->get(SessionInterface::class);
    $items = $session->get('items', []);

    $data = [
        'items' => $items,
    ];

    return $renderer->render($response, 'todo/todo', $data);
});

$app->post('/todo', function ($request, $response) {
    // Your server-side logic here
    /* @var $this ContainerInterface */

    // sleep(1);
    $session = $this->get(SessionInterface::class);
    $items = $session->get('items', []);
    $items[] = [
        'name' => 'New item',
    ];

    $session->set('items', $items);

    $data = [
        'items' => $items,
    ];

    $renderer = $this->get(TemplateRenderer::class);

    return $renderer->render($response, 'todo/todo_list_items', $data);
});

$app->delete('/todo', function ($request, $response) {
    $session = $this->get(SessionInterface::class);
    $session->set('items', []);

    return $response;
});

$app->run();
