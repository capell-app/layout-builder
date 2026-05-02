<?php

declare(strict_types=1);

namespace Capell\Mcp\Tools\Knowledge;

use Capell\Mcp\Support\KnowledgeRepository;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('capell-knowledge-list-packages')]
#[Title('List Capell Packages')]
#[Description('List public Capell package metadata discovered from capell.json manifests.')]
#[IsReadOnly]
final class ListKnowledgePackagesTool extends Tool
{
    public function handle(KnowledgeRepository $repository): ResponseFactory
    {
        return Response::structured([
            'packages' => $repository->packages(),
        ]);
    }
}
