<div x-data="{ activeSubTab: 'company' }">
    {{-- Success/Error Messages --}}
    <x-plan-limit-alert />
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
            <i class="fa-solid fa-check-circle mr-2"></i>{{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
            <i class="fa-solid fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    {{-- Sub-tabs Navigation --}}
    <div class="flex gap-2 mb-6 border-b">
        <button @click="activeSubTab = 'company'"
            :class="activeSubTab === 'company' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground'"
            class="px-4 py-3 font-medium text-sm border-b-2 transition flex items-center gap-2">
            <i class="fa-solid fa-building"></i>
            <span>{{ __('livewire.s.company_info') }}</span>
        </button>
        <button @click="activeSubTab = 'documents'"
            :class="activeSubTab === 'documents' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground'"
            class="px-4 py-3 font-medium text-sm border-b-2 transition flex items-center gap-2">
            <i class="fa-solid fa-file-alt"></i>
            <span>{{ __('livewire.s.documents') }}</span>
            <span class="px-2 py-0.5 bg-muted rounded-full text-xs">{{ count($documents) }}</span>
        </button>
        <button @click="activeSubTab = 'faqs'"
            :class="activeSubTab === 'faqs' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground'"
            class="px-4 py-3 font-medium text-sm border-b-2 transition flex items-center gap-2">
            <i class="fa-solid fa-question-circle"></i>
            <span>{{ __('agents.s.faqs') }}</span>
            <span class="px-2 py-0.5 bg-muted rounded-full text-xs">{{ count($faqs) }}</span>
        </button>
    </div>

    {{-- Company Info Sub-Tab --}}
    <div x-show="activeSubTab === 'company'" x-cloak>
        <div class="bg-card rounded-xl shadow-sm border p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                    <i class="fa-solid fa-building"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">{{ __('livewire.s.company_information') }}</h3>
                    <p class="text-sm text-muted-foreground">{{ __('livewire.s.ai_akan_menggunakan_informasi_ini_untuk_menjawab') }}</p>
                </div>
            </div>

            <form wire:submit.prevent="saveCompanyInfo" class="space-y-4">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('livewire.s.company_name') }}</label>
                        <input type="text" wire:model="company_name"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="{{ __('livewire.s.cth_toko_kopi_senja') }}">
                        @error('company_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('livewire.s.persona_name') }}</label>
                        <input type="text" wire:model="persona_name"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="{{ __('livewire.s.e_g_chika_maya_support_bot') }}">
                        @error('persona_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">{{ __('livewire.s.company_description') }}</label>
                    <textarea wire:model="company_description" rows="3"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="{{ __('livewire.s.jelaskan_tentang_bisnis_anda') }}"></textarea>
                    @error('company_description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('livewire.s.persona_tone') }}</label>
                        <select wire:model="persona_tone"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="friendly">{{ __('livewire.s.friendly_casual') }}</option>
                            <option value="professional">{{ __('livewire.s.professional') }}</option>
                            <option value="enthusiastic">{{ __('livewire.s.enthusiastic') }}</option>
                            <option value="helpful">{{ __('livewire.s.helpful_supportive') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('livewire.s.customer_greeting') }}</label>
                        <input type="text" wire:model="customer_greeting"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="{{ __('livewire.s.e_g_kak_bapak_ibu') }}">
                        <p class="text-xs text-muted-foreground mt-1">{{ __('livewire.s.sapaan_untuk_customer') }}</p>
                        @error('customer_greeting') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">{{ __('agents.s.custom_instructions') }}</label>
                    <textarea wire:model="custom_instructions" rows="3"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="{{ __('livewire.s.instruksi_khusus_untuk_ai_opsional') }}"></textarea>
                    <p class="text-xs text-muted-foreground mt-1">{{ __('livewire.s.contoh_selalu_tawarkan_diskon_10_untuk_customer') }}</p>
                    @error('custom_instructions') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <button type="submit"
                    class="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:bg-primary/90 transition">
                    <i class="fa-solid fa-save mr-2"></i>{{ __('livewire.s.save_company_info') }}</button>
            </form>
        </div>
    </div>

    {{-- Documents Sub-Tab --}}
    <div x-show="activeSubTab === 'documents'" x-cloak>
        <div class="bg-card rounded-xl shadow-sm border p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-green-100 text-green-600 flex items-center justify-center">
                    <i class="fa-solid fa-file-alt"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">{{ __('livewire.s.documents_website') }}</h3>
                    <p class="text-sm text-muted-foreground">{{ __('livewire.s.upload_dokumen_atau_crawl_website_untuk_training') }}</p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mb-6">
                {{-- File Upload --}}
                <div class="bg-muted/30 rounded-xl p-4">
                    <h4 class="font-medium mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-upload text-primary"></i>{{ __('livewire.s.upload_file') }}</h4>
                    <input type="file" wire:model="uploadedFile" accept=".pdf,.docx,.txt"
                        class="w-full px-3 py-2 border rounded-lg text-sm">

                    <div wire:loading wire:target="uploadedFile" class="mt-2 text-sm text-blue-600">
                        <i class="fa-solid fa-spinner fa-spin mr-1"></i>{{ __('admin.s.uploading') }}</div>

                    @error('uploadedFile') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

                    <button wire:click="uploadFile" wire:loading.attr="disabled" wire:target="uploadedFile,uploadFile"
                        type="button"
                        class="mt-3 w-full bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition text-sm">
                        <span wire:loading.remove wire:target="uploadFile"><i
                                class="fa-solid fa-upload mr-2"></i>{{ __('livewire.s.process_file') }}</span>
                        <span wire:loading wire:target="uploadFile"><i
                                class="fa-solid fa-spinner fa-spin mr-2"></i>{{ __('admin.s.processing') }}</span>
                    </button>
                    <p class="text-xs text-muted-foreground mt-2">Format: PDF, DOCX, TXT (maks {{ $maxFileSizeMb }}MB sesuai paket Anda)</p>
                </div>

                {{-- Website URL --}}
                <div class="bg-muted/30 rounded-xl p-4">
                    <h4 class="font-medium mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-globe text-primary"></i>{{ __('livewire.s.crawl_website') }}</h4>
                    <input type="url" wire:model="websiteUrl" class="w-full px-3 py-2 border rounded-lg text-sm"
                        placeholder="https://example.com">
                    @error('websiteUrl') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

                    <button wire:click="crawlWebsite" wire:loading.attr="disabled" type="button"
                        class="mt-3 w-full bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition text-sm">
                        <span wire:loading.remove wire:target="crawlWebsite"><i
                                class="fa-solid fa-spider mr-2"></i>{{ __('livewire.s.crawl_website') }}</span>
                        <span wire:loading wire:target="crawlWebsite"><i
                                class="fa-solid fa-spinner fa-spin mr-2"></i>{{ __('livewire.s.crawling') }}</span>
                    </button>
                    <p class="text-xs text-muted-foreground mt-2">{{ __('livewire.s.ai_akan_extract_konten_dari_halaman') }}</p>
                </div>
            </div>

            {{-- Documents List --}}
            <div>
                <h4 class="font-medium mb-3 flex items-center justify-between">
                    <span>{{ __('livewire.s.uploaded_documents') }}</span>
                    <span class="text-sm text-muted-foreground">{{ count($documents) }} files</span>
                </h4>

                @if(count($documents) > 0)
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @foreach($documents as $doc)
                            <div class="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/30 transition">
                                <div class="flex items-center gap-3">
                                    <i
                                        class="fa-solid {{ $doc['type'] === 'pdf' ? 'fa-file-pdf text-red-500' : ($doc['type'] === 'docx' ? 'fa-file-word text-blue-500' : ($doc['type'] === 'url' ? 'fa-globe text-green-500' : 'fa-file-text text-gray-500')) }} text-xl"></i>
                                    <div>
                                        <p class="font-medium text-sm truncate max-w-xs">{{ $doc['name'] }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            <span
                                                class="px-1.5 py-0.5 rounded text-xs {{ $doc['status'] === 'completed' ? 'bg-green-100 text-green-700' : ($doc['status'] === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                                {{ ucfirst($doc['status']) }}
                                            </span>
                                            • {{ \Carbon\Carbon::parse($doc['created_at'])->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                                <button wire:click="deleteDocument({{ $doc['id'] }})" wire:confirm="Delete this document?"
                                    class="text-red-500 hover:text-red-700 p-2">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-muted-foreground">
                        <i class="fa-solid fa-file-circle-plus text-4xl mb-3 opacity-50"></i>
                        <p>{{ __('livewire.s.no_documents_uploaded_yet') }}</p>
                        <p class="text-sm">{{ __('livewire.s.upload_file_atau_crawl_website_untuk_mulai') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- FAQs Sub-Tab --}}
    <div x-show="activeSubTab === 'faqs'" x-cloak>
        <div class="bg-card rounded-xl shadow-sm border p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                        <i class="fa-solid fa-question-circle"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">{{ __('livewire.s.frequently_asked_questions') }}</h3>
                        <p class="text-sm text-muted-foreground">{{ __('livewire.s.tambahkan_faq_untuk_training_ai') }}</p>
                    </div>
                </div>
                <span class="px-3 py-1 bg-muted rounded-full text-sm">{{ count($faqs) }} {{ __('agents.s.faqs') }}</span>
            </div>

            {{-- Add New FAQ Form --}}
            @if(!$editingFaqId)
                <div class="bg-muted/30 rounded-xl p-4 mb-4">
                    <h4 class="font-medium mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-plus-circle text-primary"></i>{{ __('livewire.s.add_new_faq') }}</h4>
                    <form wire:submit.prevent="addFaq" class="space-y-3">
                        <div>
                            <input type="text" wire:model="newFaqQuestion"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="{{ __('livewire.s.question_e_g_berapa_harga_produk_x') }}">
                            @error('newFaqQuestion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <textarea wire:model="newFaqAnswer" rows="2"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="{{ __('livewire.s.answer_e_g_harga_produk_x_adalah_rp_100_000') }}"></textarea>
                            @error('newFaqAnswer') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit"
                            class="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition text-sm">
                            <i class="fa-solid fa-plus mr-2"></i>{{ __('livewire.s.add_faq') }}</button>
                    </form>
                </div>
            @endif

            {{-- Edit FAQ Form --}}
            @if($editingFaqId)
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-4">
                    <h4 class="font-medium mb-3 text-blue-900 dark:text-blue-100 flex items-center gap-2">
                        <i class="fa-solid fa-edit"></i>{{ __('livewire.s.edit_faq') }}</h4>
                    <form wire:submit.prevent="updateFaq" class="space-y-3">
                        <div>
                            <input type="text" wire:model="editFaqQuestion"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="{{ __('livewire.s.question') }}">
                            @error('editFaqQuestion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <textarea wire:model="editFaqAnswer" rows="2"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="{{ __('livewire.s.answer') }}"></textarea>
                            @error('editFaqAnswer') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition text-sm">
                                <i class="fa-solid fa-save mr-2"></i>{{ __('livewire.s.update') }}</button>
                            <button type="button" wire:click="cancelEdit"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition text-sm">
                                <i class="fa-solid fa-times mr-2"></i>{{ __('channels.s.cancel') }}</button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- FAQs List --}}
            @if(count($faqs) > 0)
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @foreach($faqs as $index => $faq)
                        <div class="border rounded-lg p-4 hover:bg-muted/30 transition">
                            <div class="flex justify-between items-start gap-4">
                                <div class="flex-1">
                                    <p class="font-medium text-sm mb-1">
                                        <i class="fa-solid fa-q text-primary mr-1"></i>{{ $faq['question'] }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        <i class="fa-solid fa-a text-green-500 mr-1"></i>{{ Str::limit($faq['answer'], 150) }}
                                    </p>
                                </div>
                                <div class="flex gap-1 shrink-0">
                                    @if($index > 0)
                                        <button wire:click="moveFaqUp({{ $faq['id'] }})"
                                            class="text-muted-foreground hover:text-foreground p-1.5 rounded hover:bg-muted"
                                            title="{{ __('livewire.s.move_up') }}">
                                            <i class="fa-solid fa-arrow-up text-xs"></i>
                                        </button>
                                    @endif
                                    @if($index < count($faqs) - 1)
                                        <button wire:click="moveFaqDown({{ $faq['id'] }})"
                                            class="text-muted-foreground hover:text-foreground p-1.5 rounded hover:bg-muted"
                                            title="{{ __('livewire.s.move_down') }}">
                                            <i class="fa-solid fa-arrow-down text-xs"></i>
                                        </button>
                                    @endif
                                    <button wire:click="editFaq({{ $faq['id'] }})"
                                        class="text-blue-500 hover:text-blue-700 p-1.5 rounded hover:bg-blue-50" title="{{ __('agents.s.edit') }}">
                                        <i class="fa-solid fa-edit text-xs"></i>
                                    </button>
                                    <button wire:click="deleteFaq({{ $faq['id'] }})" wire:confirm="Delete this FAQ?"
                                        class="text-red-500 hover:text-red-700 p-1.5 rounded hover:bg-red-50" title="{{ __('admin.s.delete') }}">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-muted-foreground">
                    <i class="fa-solid fa-comments text-4xl mb-3 opacity-50"></i>
                    <p>{{ __('livewire.s.no_faqs_yet') }}</p>
                    <p class="text-sm">{{ __('livewire.s.tambahkan_faq_pertama_anda_di_atas') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>