<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Tools\Knowledge;

use Capell\AgentBridge\Support\KnowledgeRepository;
use Laravel\AgentBridge\Response;
use Laravel\AgentBridge\ResponseFactory;
use Laravel\AgentBridge\Server\Attributes\Description;
use Laravel\AgentBridge\Server\Attributes\Name;
use Laravel\AgentBridge\Server\Attributes\Title;
use Laravel\AgentBridge\Server\Tool;
use Laravel\AgentBridge\Server\Tools\Annotations\IsReadOnly;

#[Name('capell-knowledge-list-packages')]
#[Title('List Capell Packages')]
#[Description('List Capell package metadata discovered from capell.json manifests.')]
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
