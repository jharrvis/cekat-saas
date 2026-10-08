<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\KnowledgeBase;
use App\Models\KnowledgeDocument;
use App\Models\Widget;
use App\Services\Billing\PlanLimitService;
use App\Services\TextChunker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * External knowledge sync for /api/v1 (API-key auth, server-to-server).
 *
 * Integrations - the WordPress/WooCommerce plugin first - upsert one
 * knowledge document per external item into the knowledge base of the
 * channel's agent, keyed by (source, external_ref). The integration
 * composes the document text (product name, price, stock, description,
 * link); this endpoint stores, chunks, and activates it exactly like a
 * text document added from the dashboard, and enforces the plan's
 * knowledge_documents limit for new documents.
 */
class KnowledgeSyncController extends V1Controller
{
    /**
     * POST /api/v1/knowledge/documents - create or update one document.
     */
    public function upsert(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request, ['name', 'content']);

        if ($data instanceof JsonResponse) {
            return $data;
        }

        [$widget, $kb] = $this->resolveTarget($request, $data['widget_slug']);

        if ($kb instanceof JsonResponse) {
            return $kb;
        }

        $existing = KnowledgeDocument::where('knowledge_base_id', $kb->id)
            ->where('source', $data['source'])
            ->where('external_ref', $data['external_ref'])
            ->first();

        if (! $existing) {
            $check = app(PlanLimitService::class)->check($request->user(), 'knowledge_documents', [
                'used' => $kb->documents()->count(),
            ]);

            if (! $check['allowed']) {
                return response()->json([
                    'error' => 'plan_limit',
                    'error_code' => 'plan_limit',
                    'message' => app(PlanLimitService::class)->limitMessage($request->user(), 'knowledge_documents'),
                ], 403);
            }
        }

        $chunks = (new TextChunker())->chunk($data['content']);

        $attributes = [
            'name' => $data['name'],
            'type' => 'text',
            'url' => $data['url'] ?? null,
            'content' => $data['content'],
            'chunks' => $chunks,
            'status' => 'completed',
        ];

        if ($existing) {
            $existing->update($attributes);
            $document = $existing;
            $created = false;
        } else {
            $document = $kb->documents()->create($attributes + [
                'source' => $data['source'],
                'external_ref' => $data['external_ref'],
            ]);
            $created = true;
        }

        return response()->json([
            'data' => [
                'id' => $document->id,
                'source' => $document->source,
                'external_ref' => $document->external_ref,
                'name' => $document->name,
                'chunks' => count($chunks),
                'status' => $document->status,
            ],
            'meta' => ['created' => $created],
        ], $created ? 201 : 200);
    }

    /**
     * DELETE /api/v1/knowledge/documents - remove one synced document.
     * Idempotent: deleting an unknown reference is a successful no-op.
     */
    public function remove(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request, []);

        if ($data instanceof JsonResponse) {
            return $data;
        }

        [$widget, $kb] = $this->resolveTarget($request, $data['widget_slug']);

        if ($kb instanceof JsonResponse) {
            return $kb;
        }

        $deleted = KnowledgeDocument::where('knowledge_base_id', $kb->id)
            ->where('source', $data['source'])
            ->where('external_ref', $data['external_ref'])
            ->delete();

        return response()->json([
            'data' => ['deleted' => $deleted > 0],
        ]);
    }

    /**
     * Shared payload rules. Returns the validated subset or a JsonResponse
     * describing the first problem, in the V1 flat error shape.
     *
     * @param  string[]  $extraRequired
     */
    private function validatePayload(Request $request, array $extraRequired): array|JsonResponse
    {
        $data = [
            'widget_slug' => trim((string) $request->input('widget_slug', '')),
            'source' => trim((string) $request->input('source', '')),
            'external_ref' => trim((string) $request->input('external_ref', '')),
            'name' => trim((string) $request->input('name', '')),
            'content' => (string) $request->input('content', ''),
            'url' => $request->input('url'),
        ];

        foreach (array_merge(['widget_slug', 'source', 'external_ref'], $extraRequired) as $field) {
            if ($data[$field] === '') {
                return $this->invalidParam($field, 'Wajib diisi.');
            }
        }

        if (strlen($data['source']) > 50 || strlen($data['external_ref']) > 191 || strlen($data['name']) > 255) {
            return $this->invalidParam('source/external_ref/name', 'Melebihi panjang maksimum.');
        }

        if (strlen($data['content']) > 200000) {
            return $this->invalidParam('content', 'Maksimum 200.000 karakter.');
        }

        if ($data['url'] !== null && (! is_string($data['url']) || strlen($data['url']) > 2000 || ! filter_var($data['url'], FILTER_VALIDATE_URL))) {
            return $this->invalidParam('url', 'Harus URL yang valid.');
        }

        return $data;
    }

    /**
     * Resolve the caller's widget (by slug) and its knowledge base.
     * Returns [widget, KnowledgeBase] or [null, JsonResponse].
     *
     * @return array{0: ?Widget, 1: KnowledgeBase|JsonResponse}
     */
    private function resolveTarget(Request $request, string $widgetSlug): array
    {
        $widget = $request->user()->widgets()->where('slug', $widgetSlug)->first();

        if (! $widget) {
            return [null, $this->notFound('Widget')];
        }

        $kb = KnowledgeBase::where('widget_id', $widget->id)->first()
            ?? ($widget->ai_agent_id
                ? KnowledgeBase::where('ai_agent_id', $widget->ai_agent_id)->first()
                : null);

        if (! $kb) {
            return [null, response()->json([
                'error' => 'no_knowledge_base',
                'error_code' => 'no_knowledge_base',
                'message' => 'Channel ini belum memiliki knowledge base. Tautkan agent ke channel terlebih dahulu.',
            ], 422)];
        }

        return [$widget, $kb];
    }
}
