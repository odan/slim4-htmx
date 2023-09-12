<?php

use DI\ContainerBuilder;
use Odan\Session\Middleware\SessionStartMiddleware;
use Odan\Session\PhpSession;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions(
    [
        Mustache_Engine::class => function () {
            $options = [
                // Path to templates
                'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/../templates'),

                // Use UTF-8 html encoding
                'escape' => function ($value) {
                    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                },

                // Enable cache in production
                //'cache' => __DIR__ . '/../tmp/templates',
                //'cache_file_mode' => 0755,
            ];
            return new Mustache_Engine($options);
        },

        SessionManagerInterface::class => function (ContainerInterface $container) {
            return $container->get(SessionInterface::class);
        },

        SessionInterface::class => function () {
            $options = [
                'name' => 'app',
                'lifetime' => 0,
                'path' => null,
                'domain' => null,
                // SameSite-Cookie
                // Protection against cross-site request forgery attacks
                'cookie_samesite' => 'Lax',
                // Protection against session hijacking and session fixation
                // Uses a secure connection (HTTPS) if possible
                'secure' => true,
                'httponly' => true,
                'cache_limiter' => 'nocache',
            ];

            return new PhpSession($options);
        },
    ]
);

final class TemplateRenderer
{
    private string $layout = '';

    private Mustache_Engine $mustache;

    public function __construct(Mustache_Engine $mustache)
    {
        $this->mustache = $mustache;
    }

    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    public function render(
        ResponseInterface $response,
        string $template,
        array $data = []
    ): ResponseInterface {
        if ($this->layout) {
            // Render dynamic partial
            $data['content'] = $this->mustache->render($template, $data);
            $template = $this->layout;
        }

        $html = $this->mustache->render($template, $data);

        $response->getBody()->write($html);

        return $response;
    }
}

AppFactory::setContainer($containerBuilder->build());

$app = AppFactory::create();

$app->add(SessionStartMiddleware::class);

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

    //sleep(1);
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
