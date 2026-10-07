@extends('layouts.dashboard')

@section('title', __('channels.s.buat_channel'))
@section('page-title', __('agents.s.buat_channel_baru'))

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-card rounded-xl shadow-sm border p-8">
            <div class="flex items-center gap-4 mb-6">
                <a href="{{ route('channels.index') }}" class="text-muted-foreground hover:text-foreground">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h2 class="text-2xl font-bold">{{ __('agents.s.buat_channel_baru') }}</h2>
                    <p class="text-muted-foreground">{{ __('channels.s.beri_nama_dan_deskripsi_untuk_channel_web_widget') }}</p>
                </div>
            </div>

            <form action="{{ route('channels.store') }}" method="POST" class="space-y-6">
                @csrf

                @if (session()->has('error'))
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                        {{ session('error') }}
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium mb-2">{{ __('channels.s.nama_channel') }}</label>
                    <input type="text" name="display_name" value="{{ old('display_name') }}"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="{{ __('channels.s.e_g_customer_support_bot') }}" required>
                    @error('display_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">{{ __('channels.s.description_optional') }}</label>
                    <textarea name="description" rows="3"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="{{ __('channels.s.brief_description_of_what_this_chatbot_does') }}">{{ old('description') }}</textarea>
                    @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                {{-- AI Agent Selector --}}
                <div class="border-t pt-6">
                    <label class="block text-sm font-medium mb-2">
                        <i class="fa-solid fa-brain text-primary mr-1"></i> {{ __('channels.s.hubungkan_ke_ai_agent') }}
                    </label>
                    
                    @if($aiAgents->count() > 0)
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-muted/30 transition">
                                <input type="radio" name="ai_agent_id" value="" {{ !old('ai_agent_id') ? 'checked' : '' }}
                                    class="w-4 h-4 text-primary focus:ring-primary">
                                <div>
                                    <span class="font-medium">{{ __('channels.s.tanpa_ai_agent') }}</span>
                                    <p class="text-xs text-muted-foreground">{{ __('channels.s.widget_akan_memiliki_knowledge_base_sendiri') }}</p>
                                </div>
                            </label>
                            
                            @foreach($aiAgents as $agent)
                                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-muted/30 transition">
                                    <input type="radio" name="ai_agent_id" value="{{ $agent->id }}" 
                                        {{ old('ai_agent_id') == $agent->id ? 'checked' : '' }}
                                        class="w-4 h-4 text-primary focus:ring-primary">
                                    <div class="flex-1">
                                        <span class="font-medium">{{ $agent->name }}</span>
                                        <p class="text-xs text-muted-foreground">
                                            {{ ucfirst($agent->personality) }} • 
                                            {{ $agent->widgets()->count() }} widget terhubung
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 text-xs bg-primary/10 text-primary rounded">
                                        {{ Str::afterLast($agent->ai_model, '/') }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                            <p class="text-sm text-amber-800 dark:text-amber-200">
                                <i class="fa-solid fa-info-circle mr-1"></i>
                                {{ __('channels.s.belum_ada_ai_agent') }} 
                                <a href="{{ route('agents.create') }}" class="text-primary font-medium hover:underline">
                                    {{ __('channels.s.buat_ai_agent_dulu') }}
                                </a> {{ __('channels.s.untuk_menggunakan_satu_brain_di_banyak_widget') }}
                            </p>
                        </div>
                        <input type="hidden" name="ai_agent_id" value="">
                    @endif
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        <i class="fa-solid fa-lightbulb mr-2"></i>
                        <strong>{{ __('channels.s.tip') }}</strong> {{ __('channels.s.dengan_ai_agent_satu_otak_bisa_dipakai_banyak_wi') }}
                    </p>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit"
                        class="flex-1 bg-primary text-primary-foreground px-6 py-3 rounded-lg hover:bg-primary/90 transition font-medium">
                        <i class="fa-solid fa-plus mr-2"></i> {{ __('channels.s.buat_channel') }}
                    </button>
                    <a href="{{ route('channels.index') }}"
                        class="px-6 py-3 border rounded-lg hover:bg-muted/30 transition font-medium">
                        {{ __('channels.s.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
