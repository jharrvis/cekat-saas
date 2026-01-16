@extends('layouts.dashboard')

@section('title', 'Integration Management')
@section('page-title', 'Integration Management')

@section('content')
    <div>
        {{-- Header --}}
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold">Integration Management</h2>
                <p class="text-muted-foreground">Manage WordPress plugin and integration documentation</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            {{-- WordPress Plugin Upload --}}
            <div class="bg-card rounded-xl shadow-sm border p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i class="fa-brands fa-wordpress text-2xl text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold">WordPress Plugin</h3>
                        <p class="text-sm text-muted-foreground">Upload plugin zip untuk user download</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="border-2 border-dashed rounded-lg p-6 text-center">
                        <i class="fa-solid fa-cloud-upload-alt text-3xl text-muted-foreground mb-2"></i>
                        <p class="text-sm text-muted-foreground mb-2">Drag & drop plugin zip file here</p>
                        <input type="file" accept=".zip" class="hidden" id="plugin-upload">
                        <label for="plugin-upload" class="cursor-pointer text-primary text-sm font-medium hover:underline">
                            or click to browse
                        </label>
                    </div>

                    <div class="bg-muted/30 rounded-lg p-4">
                        <p class="text-sm font-medium mb-2">Current Plugin:</p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">cekat-chatbot-v1.0.0.zip</span>
                            <span class="text-xs text-muted-foreground">Coming Soon</span>
                        </div>
                    </div>

                    <button disabled
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg transition font-medium disabled:opacity-50">
                        <i class="fa-solid fa-upload mr-2"></i> Upload New Version
                    </button>
                </div>
            </div>

            {{-- Integration Instructions --}}
            <div class="bg-card rounded-xl shadow-sm border p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                        <i class="fa-solid fa-code text-2xl text-purple-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold">JavaScript Embed</h3>
                        <p class="text-sm text-muted-foreground">Kode embed untuk website lain</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="bg-slate-900 text-green-400 rounded-lg p-4 text-sm font-mono overflow-x-auto">
                        <pre>&lt;script src="{{ config('app.url') }}/widget/cekat-widget.js"&gt;&lt;/script&gt;
    &lt;script&gt;
      CekatWidget.init({
        widgetId: 'YOUR_WIDGET_ID',
        apiUrl: '{{ config('app.url') }}/api/chat'
      });
    &lt;/script&gt;</pre>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-sm text-blue-800">
                            <i class="fa-solid fa-info-circle mr-2"></i>
                            User akan melihat kode ini di halaman Integration mereka dengan Widget ID mereka sendiri.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Integration Platforms --}}
        <div class="mt-6 bg-card rounded-xl shadow-sm border p-6">
            <h3 class="text-lg font-bold mb-4">Supported Platforms</h3>
            <div class="grid md:grid-cols-4 gap-4">
                <div class="border rounded-lg p-4 text-center hover:border-primary transition">
                    <i class="fa-brands fa-wordpress text-3xl text-blue-600 mb-2"></i>
                    <p class="font-medium">WordPress</p>
                    <span class="text-xs text-green-600">Ready</span>
                </div>
                <div class="border rounded-lg p-4 text-center opacity-50">
                    <i class="fa-brands fa-shopify text-3xl text-green-600 mb-2"></i>
                    <p class="font-medium">Shopify</p>
                    <span class="text-xs text-muted-foreground">Coming Soon</span>
                </div>
                <div class="border rounded-lg p-4 text-center opacity-50">
                    <i class="fa-brands fa-wix text-3xl text-black mb-2"></i>
                    <p class="font-medium">Wix</p>
                    <span class="text-xs text-muted-foreground">Coming Soon</span>
                </div>
                <div class="border rounded-lg p-4 text-center opacity-50">
                    <i class="fa-solid fa-code text-3xl text-gray-600 mb-2"></i>
                    <p class="font-medium">Custom HTML</p>
                    <span class="text-xs text-green-600">Ready</span>
                </div>
            </div>
        </div>

        {{-- Documentation --}}
        <div class="mt-6 bg-card rounded-xl shadow-sm border p-6">
            <h3 class="text-lg font-bold mb-4">Documentation</h3>
            <p class="text-muted-foreground mb-4">Edit dokumentasi integrasi yang akan ditampilkan ke user</p>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Installation Steps (Markdown)</label>
                    <textarea rows="6"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono text-sm"
                        placeholder="Write installation steps in markdown...">## WordPress Installation

    1. Download the plugin from your Integration page
    2. Go to WordPress Admin > Plugins > Add New
    3. Click "Upload Plugin" and select the zip file
    4. Activate the plugin
    5. Go to Settings > Cekat Chatbot
    6. Enter your Widget ID
    7. Save changes

    Your chatbot will now appear on your website!</textarea>
                </div>

                <button
                    class="bg-primary text-primary-foreground px-6 py-3 rounded-lg hover:bg-primary/90 transition font-medium">
                    <i class="fa-solid fa-save mr-2"></i> Save Documentation
                </button>
            </div>
        </div>
    </div>
@endsection