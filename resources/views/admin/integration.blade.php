@extends('layouts.dashboard')

@section('title', __('nav.integration_management'))
@section('page-title', __('nav.integration_management'))

@section('content')
    <div>
        {{-- Header --}}
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold">{{ __('admin.s.integration_management') }}</h2>
                <p class="text-muted-foreground">{{ __('admin.s.manage_wordpress_plugin_and_integration_document') }}</p>
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
                        <h3 class="text-lg font-bold">{{ __('admin.s.wordpress_plugin') }}</h3>
                        <p class="text-sm text-muted-foreground">{{ __('admin.s.upload_plugin_zip_untuk_user_download') }}</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="border-2 border-dashed rounded-lg p-6 text-center">
                        <i class="fa-solid fa-cloud-upload-alt text-3xl text-muted-foreground mb-2"></i>
                        <p class="text-sm text-muted-foreground mb-2">{{ __('admin.s.drag_drop_plugin_zip_file_here') }}</p>
                        <input type="file" accept=".zip" class="hidden" id="plugin-upload">
                        <label for="plugin-upload" class="cursor-pointer text-primary text-sm font-medium hover:underline">{{ __('admin.s.or_click_to_browse') }}</label>
                    </div>

                    <div class="bg-muted/30 rounded-lg p-4">
                        <p class="text-sm font-medium mb-2">{{ __('admin.s.current_plugin') }}</p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">{{ __('admin.s.cekat_chatbot_v1_0_0_zip') }}</span>
                            <span class="text-xs text-muted-foreground">{{ __('admin.s.coming_soon') }}</span>
                        </div>
                    </div>

                    <button disabled
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg transition font-medium disabled:opacity-50">
                        <i class="fa-solid fa-upload mr-2"></i>{{ __('admin.s.upload_new_version') }}</button>
                </div>
            </div>

            {{-- Integration Instructions --}}
            <div class="bg-card rounded-xl shadow-sm border p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                        <i class="fa-solid fa-code text-2xl text-purple-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold">{{ __('admin.s.javascript_embed') }}</h3>
                        <p class="text-sm text-muted-foreground">{{ __('admin.s.kode_embed_untuk_website_lain') }}</p>
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
                            <i class="fa-solid fa-info-circle mr-2"></i>{{ __('admin.s.user_akan_melihat_kode_ini_di_halaman_integratio') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Integration Platforms --}}
        <div class="mt-6 bg-card rounded-xl shadow-sm border p-6">
            <h3 class="text-lg font-bold mb-4">{{ __('admin.s.supported_platforms') }}</h3>
            <div class="grid md:grid-cols-4 gap-4">
                <div class="border rounded-lg p-4 text-center hover:border-primary transition">
                    <i class="fa-brands fa-wordpress text-3xl text-blue-600 mb-2"></i>
                    <p class="font-medium">{{ __('admin.s.wordpress') }}</p>
                    <span class="text-xs text-green-600">{{ __('admin.s.ready') }}</span>
                </div>
                <div class="border rounded-lg p-4 text-center opacity-50">
                    <i class="fa-brands fa-shopify text-3xl text-green-600 mb-2"></i>
                    <p class="font-medium">{{ __('admin.s.shopify') }}</p>
                    <span class="text-xs text-muted-foreground">{{ __('admin.s.coming_soon') }}</span>
                </div>
                <div class="border rounded-lg p-4 text-center opacity-50">
                    <i class="fa-brands fa-wix text-3xl text-black mb-2"></i>
                    <p class="font-medium">{{ __('admin.s.wix') }}</p>
                    <span class="text-xs text-muted-foreground">{{ __('admin.s.coming_soon') }}</span>
                </div>
                <div class="border rounded-lg p-4 text-center opacity-50">
                    <i class="fa-solid fa-code text-3xl text-gray-600 mb-2"></i>
                    <p class="font-medium">{{ __('admin.s.custom_html') }}</p>
                    <span class="text-xs text-green-600">{{ __('admin.s.ready') }}</span>
                </div>
            </div>
        </div>

        {{-- Documentation --}}
        <div class="mt-6 bg-card rounded-xl shadow-sm border p-6">
            <h3 class="text-lg font-bold mb-4">{{ __('admin.s.documentation') }}</h3>
            <p class="text-muted-foreground mb-4">{{ __('admin.s.edit_dokumentasi_integrasi_yang_akan_ditampilkan') }}</p>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">{{ __('admin.s.installation_steps_markdown') }}</label>
                    <textarea rows="6"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono text-sm"
                        placeholder="{{ __('admin.s.write_installation_steps_in_markdown') }}">## WordPress Installation

    1. Download the plugin from your Integration page
    2. Go to WordPress Admin > Plugins > Add New
    3. Click "Upload Plugin" and select the zip file
    4. Activate the plugin
    5. Go to Settings >{{ __('admin.s.cekat_chatbot_6_enter_your_widget_id_7_save_chan') }}</textarea>
                </div>

                <button
                    class="bg-primary text-primary-foreground px-6 py-3 rounded-lg hover:bg-primary/90 transition font-medium">
                    <i class="fa-solid fa-save mr-2"></i>{{ __('admin.s.save_documentation') }}</button>
            </div>
        </div>
    </div>
@endsection