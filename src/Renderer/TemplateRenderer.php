<?php

namespace App\Renderer;

use Mustache_Engine;
use Psr\Http\Message\ResponseInterface;

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