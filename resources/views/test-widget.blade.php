@extends('layouts.dashboard')

@section('title', __('nav.test_widget'))
@section('page-title', 'Test Your Chatbot')

@section('content')
    <div class="bg-card rounded-xl shadow-sm border p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-bold mb-2">{{ __('general.s.widget_preview') }}</h2>
            <p class="text-muted-foreground">{{ __('general.s.test_your_chatbot_before_publishing_to_your_webs') }}</p>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            {{-- Instructions --}}
            <div class="space-y-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">
                        <i class="fa-solid fa-info-circle mr-2"></i>{{ __('general.s.how_to_test') }}</h3>
                    <ol class="text-sm text-blue-800 dark:text-blue-200 space-y-1 ml-5 list-decimal">
                        <li>{{ __('general.s.click_the_chat_button_on_the_right') }}</li>
                        <li>{{ __('general.s.ask_questions_about_your_knowledge_base') }}</li>
                        <li>{{ __('general.s.test_with_uploaded_documents_and_faqs') }}</li>
                        <li>{{ __('general.s.verify_ai_responses_are_accurate') }}</li>
                    </ol>
                </div>

                <div class="bg-muted/50 rounded-lg p-4">
                    <h3 class="font-semibold mb-3">{{ __('general.s.your_widget_info') }}</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">{{ __('general.s.widget_id') }}</span>
                            <code
                                class="bg-background px-2 py-1 rounded">{{ auth()->user()->widgets()->first()->slug ?? 'N/A' }}</code>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">{{ __('admin.s.status') }}</span>
                            <span class="text-green-600 font-medium">
                                <i class="fa-solid fa-circle text-xs mr-1"></i>{{ __('channels.s.active') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">{{ __('general.s.knowledge_base') }}</span>
                            <span
                                class="font-medium">{{ auth()->user()->widgets()->first()->knowledgeBase->company_name ?? 'Not set' }}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <a href="{{ route('widget-editor') }}" class="inline-flex items-center text-primary hover:underline">
                        <i class="fa-solid fa-paintbrush mr-2"></i>{{ __('general.s.customize_widget_appearance') }}</a>
                </div>
                <div>
                    <a href="{{ route('knowledge-base') }}" class="inline-flex items-center text-primary hover:underline">
                        <i class="fa-solid fa-brain mr-2"></i>{{ __('general.s.edit_knowledge_base') }}</a>
                </div>
            </div>

            {{-- Preview Area --}}
            <div class="bg-slate-50 dark:bg-slate-900 rounded-lg border-2 border-dashed p-8 min-h-[600px] relative">
                <div class="text-center text-muted-foreground mb-4">
                    <i class="fa-solid fa-desktop text-4xl mb-2"></i>
                    <p class="text-sm">{{ __('general.s.your_website_preview') }}</p>
                </div>

                {{-- Mock content --}}
                <div class="space-y-3 opacity-30">
                    <div class="h-6 bg-slate-300 dark:bg-slate-700 rounded w-3/4"></div>
                    <div class="h-4 bg-slate-300 dark:bg-slate-700 rounded w-full"></div>
                    <div class="h-4 bg-slate-300 dark:bg-slate-700 rounded w-5/6"></div>
                    <div class="h-4 bg-slate-300 dark:bg-slate-700 rounded w-4/6"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Load Widget Script --}}
    @php
        $widget = auth()->user()->widgets()->first();
        $widgetSlug = $widget ? $widget->slug : 'default';
        $settings = $widget ? $widget->settings : [];
        $primaryColor = $settings['color'] ?? '#0f172a';
        $greeting = $settings['greeting'] ?? 'Halo! 👋 Ada yang bisa saya bantu?';
        $position = $settings['position'] ?? 'bottom-right';
    @endphp

    <script>
        window.CSAIConfig = {
            widgetId: '{{ $widgetSlug }}',
            apiUrl: '{{ config("app.url") }}/api/chat',
            position: '{{ $position }}',
            primaryColor: '{{ $primaryColor }}',
            greeting: '{{ $greeting }}',
        };
    </script>
    <script src="{{ asset('widget/widget.js') }}"></script>
@endsection