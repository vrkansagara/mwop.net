<?php
namespace Mwop\Factory;

use Mwop\ErrorHandler as Middleware;

class ErrorHandler
{
    public function __invoke($services)
    {
        return new Middleware(
            $services->get('Zend\Expressive\Template\TemplateInterface'),
            $services->get('Config')['debug']
        );
    }
}
