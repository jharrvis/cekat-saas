@extends('layouts.dashboard')

@section('title', 'Test Widget')
@section('page-title', 'Test Your Chatbot')

@section('content')
    <div class="bg-card rounded-xl shadow-sm border p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-bold mb-2">Widget Preview</h2>
            <p class="text-muted-foreground">Test your chatbot before publishing to your website.</p>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            {{-- Instructions --}}
            <div class="space-y-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">
                        <i class="fa-solid fa-info-circle mr-2"></i>How to Test
                    </h3>
                    <ol class="text-sm text-blue-800 dark:text-blue-200 space-y-1 ml-5 list-decimal">
                        <li>Click the chat button on the right</li>
                        <li>Ask questions about your knowledge base</li>
                        <li>Test with uploaded documents and FAQs</li>
                        <li>Verify AI responses are accurate</li>
                    </ol>
                </div>

                <div class="bg-muted/50 rounded-lg p-4">
                    <h3 class="font-semibold mb-3">Your Widget Info</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Widget ID:</span>
                            <code
                                class="bg-background px-2 py-1 rounded">{{ auth()->user()->widgets()->first()->slug ?? 'N/A' }}</code>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Status:</span>
                            <span class="text-green-600 font-medium">
                                <i class="fa-solid fa-circle text-xs mr-1"></i>Active
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Knowledge Base:</span>
                            <span
                                class="font-medium">{{ auth()->user()->widgets()->first()->knowledgeBase->company_name ?? 'Not set' }}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <a href="{{ route('widget-editor') }}" class="inline-flex items-center text-primary hover:underline">
                        <i class="fa-solid fa-paintbrush mr-2"></i>
                        Customize Widget Appearance
                    </a>
                </div>
                <div>
                    <a href="{{ route('knowledge-base') }}" class="inline-flex items-center text-primary hover:underline">
                        <i class="fa-solid fa-brain mr-2"></i>
                        Edit Knowledge Base
                    </a>
                </div>
            </div>

            {{-- Preview Area --}}
            <div class="bg-slate-50 dark:bg-slate-900 rounded-lg border-2 border-dashed p-8 min-h-[600px] relative">
                <div class="text-center text-muted-foreground mb-4">
                    <i class="fa-solid fa-desktop text-4xl mb-2"></i>
                    <p class="text-sm">Your website preview</p>
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