<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApiLookupRequest;
use App\Models\Widget;
use App\Models\WidgetApiLookup;
use App\Services\Chat\ApiLookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * CRUD definisi API Lookup per channel + endpoint "Uji".
 * Semua aksi butuh izin update pada channel (pemilik channel).
 */
class ApiLookupController extends Controller
{
    protected function channel(string $channelId): Widget
    {
        $channel = auth()->user()->widgets()->findOrFail($channelId);
        Gate::authorize('update', $channel);

        return $channel;
    }

    protected function scopedLookup(Widget $channel, string $lookupId): WidgetApiLookup
    {
        return $channel->apiLookups()->findOrFail($lookupId);
    }

    public function store(ApiLookupRequest $request, string $channel)
    {
        $channel = $this->channel($channel);
        $lookup = $channel->apiLookups()->create($request->normalized());

        return redirect()
            ->route('channels.edit.tab', ['channel' => $channel->id, 'tab' => 'webhook'])
            ->with('success', 'API Lookup "'.$lookup->name.'" ditambahkan.');
    }

    public function update(ApiLookupRequest $request, string $channel, string $lookup)
    {
        $channel = $this->channel($channel);
        $row = $this->scopedLookup($channel, $lookup);
        $row->update($request->normalized($row));

        return redirect()
            ->route('channels.edit.tab', ['channel' => $channel->id, 'tab' => 'webhook'])
            ->with('success', 'API Lookup "'.$row->name.'" diperbarui.');
    }

    public function destroy(string $channel, string $lookup)
    {
        $channel = $this->channel($channel);
        $row = $this->scopedLookup($channel, $lookup);
        $name = $row->name;
        $row->delete();

        return redirect()
            ->route('channels.edit.tab', ['channel' => $channel->id, 'tab' => 'webhook'])
            ->with('success', 'API Lookup "'.$name.'" dihapus.');
    }

    /**
     * Uji lookup dengan parameter contoh. Mengembalikan struktur detail
     * (URL aman-log, status HTTP, mapping, balasan akhir) sebagai JSON.
     */
    public function test(Request $request, string $channel, string $lookup, ApiLookupService $service)
    {
        $channel = $this->channel($channel);
        $row = $this->scopedLookup($channel, $lookup);

        $params = $request->input('test_params', []);

        return response()->json(
            $service->executeForTest($row, is_array($params) ? $params : [])
        );
    }
}
