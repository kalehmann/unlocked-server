<?php

declare(strict_types=1);

namespace KaLehmann\UnlockedServer\Action;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class HelloAction extends AbstractController
{
    public function __invoke(): Response
    {
        return new Response('Hello!', Response::HTTP_OK);
    }
}
