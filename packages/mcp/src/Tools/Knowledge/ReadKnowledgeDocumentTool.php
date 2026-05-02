<?php

declare(strict_types=1);

namespace Capell\Mcp\Tools\Knowledge;

use Capell\Mcp\Support\KnowledgeRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('capell-knowledge-read-document')]
#[Title('Read Capell Document')]
#[Description('Read an allowed Capell Markdown document by repository-relative path.')]
#[IsReadOnly]
final class ReadKnowledgeDocumentTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()->description('Repository-relative Markdown path from the knowledge document list.')->required(),
        ];
    }

    public function handle(Request $request, KnowledgeRepository $repository): Response
    {
        $data = $request->validate([
            'path' => ['required', 'string'],
        ]);

        $content = $repository->readDocument((string) $data['path']);

        if ($content === null) {
            return Response::error('Document not found or not allowed.');
        }

        return Response::text($content);
    }
}
