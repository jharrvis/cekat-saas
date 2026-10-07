<div x-data="{ activeTab: 'company' }">
    {{-- Success/Error Messages --}}
    <x-plan-limit-alert />
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
            <i class="fa-solid fa-check-circle mr-2"></i>{{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            <i class="fa-solid fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    {{-- Tab Navigation: Info Bisnis → FAQs → Dokumen --}}
    <div class="flex gap-2 border-b mb-6">
        <button @click="activeTab = 'company'"
            :class="activeTab === 'company' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground'"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium border-b-2 transition">
            <i class="fa-solid fa-building"></i>
            <span>{{ __('livewire.s.info_bisnis') }}</span>
        </button>
        <button @click="activeTab = 'faqs'"
            :class="activeTab === 'faqs' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground'"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium border-b-2 transition">
            <i class="fa-solid fa-question-circle"></i>
            <span>{{ __('agents.s.faqs') }}</span>
            <span class="px-2 py-0.5 bg-muted rounded-full text-xs">{{ count($faqs) }}</span>
        </button>
        <button @click="activeTab = 'documents'"
            :class="activeTab === 'documents' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground'"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium border-b-2 transition">
            <i class="fa-solid fa-file-lines"></i>
            <span>{{ __('agents.s.dokumen') }}</span>
            <span class="px-2 py-0.5 bg-muted rounded-full text-xs">{{ count($documents) }}</span>
        </button>
    </div>

    {{-- Company Info Tab (First) --}}
    <div x-show="activeTab === 'company'" x-cloak>
        {{-- Subtle Helper Tip --}}
        <div class="flex items-center gap-2 text-sm text-muted-foreground mb-4 px-1">
            <i class="fa-solid fa-info-circle text-primary"></i>
            <span>{{ __('livewire.s.info_bisnis_membantu_ai_memperkenalkan_diri_dan') }}</span>
        </div>

        <div class="bg-card border rounded-lg p-4">
            <form wire:submit.prevent="saveCompanyInfo" class="space-y-4">

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('livewire.s.nama_bisnis_perusahaan') }}</label>
                        <input type="text" wire:model="company_name"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="{{ __('livewire.s.toko_abc') }}">
                        @error('company_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('livewire.s.nama_ai_persona') }}</label>
                        <input type="text" wire:model="persona_name"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="{{ __('livewire.s.chika_maya_support_bot') }}">
                        @error('persona_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">{{ __('livewire.s.deskripsi_bisnis') }}</label>
                    <textarea wire:model="company_description" rows="3"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="{{ __('livewire.s.jelaskan_tentang_bisnis_anda') }}"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">{{ __('livewire.s.sapaan_ke_customer') }}</label>
                    <input type="text" wire:model="customer_greeting"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="{{ __('livewire.s.kak_bos_anda') }}">
                    <p class="text-xs text-muted-foreground mt-1">{{ __('livewire.s.contoh_kak_bos_atau_anda') }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">{{ __('livewire.s.instruksi_tambahan') }}</label>
                    <textarea wire:model="custom_instructions" rows="3"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="{{ __('livewire.s.instruksi_khusus_untuk_ai') }}"></textarea>
                    <p class="text-xs text-muted-foreground mt-1">{{ __('livewire.s.contoh_selalu_tawarkan_produk_promo_atau_arahkan') }}</p>
                </div>

                <button type="submit"
                    class="px-6 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition">
                    <i class="fa-solid fa-save mr-2"></i>{{ __('livewire.s.simpan_info_bisnis') }}</button>
            </form>
        </div>
    </div>

    {{-- FAQs Tab (Second) --}}
    <div x-show="activeTab === 'faqs'" x-cloak>
        <div class="space-y-4">
            {{-- Add FAQ Form --}}
            <div class="bg-card border rounded-lg p-4">
                <h4 class="font-medium mb-3">
                    <i class="fa-solid fa-plus text-primary mr-2"></i>{{ __('livewire.s.tambah_faq_baru') }}</h4>
                <form wire:submit.prevent="addFaq" class="space-y-3">
                    <div>
                        <input type="text" wire:model="newFaqQuestion"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="{{ __('livewire.s.pertanyaan_contoh_apa_metode_pembayaran_yang_ter') }}">
                        @error('newFaqQuestion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <textarea wire:model="newFaqAnswer" rows="2"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="{{ __('livewire.s.jawaban_untuk_pertanyaan_tersebut') }}"></textarea>
                        @error('newFaqAnswer') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition text-sm">
                        <i class="fa-solid fa-plus mr-1"></i>{{ __('livewire.s.tambah_faq') }}</button>
                </form>
            </div>

            {{-- FAQ List --}}
            @if(count($faqs) > 0)
                <div class="space-y-2">
                    @foreach($faqs as $index => $faq)
                        <div class="bg-card border rounded-lg p-4" wire:key="faq-{{ $faq['id'] }}">
                            @if($editingFaqId === $faq['id'])
                                {{-- Edit Mode --}}
                                <form wire:submit.prevent="updateFaq" class="space-y-3">
                                    <input type="text" wire:model="editFaqQuestion"
                                        class="w-full px-3 py-2 border rounded-lg text-sm">
                                    <textarea wire:model="editFaqAnswer" rows="2"
                                        class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                                    <div class="flex gap-2">
                                        <button type="submit" class="px-3 py-1.5 bg-primary text-white rounded text-xs">
                                            <i class="fa-solid fa-check mr-1"></i>{{ __('admin.s.simpan') }}</button>
                                        <button type="button" wire:click="cancelEdit"
                                            class="px-3 py-1.5 bg-muted rounded text-xs">{{ __('agents.s.batal') }}</button>
                                    </div>
                                </form>
                            @else
                                {{-- View Mode --}}
                                <div class="flex gap-3">
                                    <div class="flex flex-col gap-1">
                                        <button wire:click="moveFaqUp({{ $faq['id'] }})"
                                            class="text-muted-foreground hover:text-foreground text-xs"
                                            @if($index === 0) disabled @endif>
                                            <i class="fa-solid fa-chevron-up"></i>
                                        </button>
                                        <button wire:click="moveFaqDown({{ $faq['id'] }})"
                                            class="text-muted-foreground hover:text-foreground text-xs"
                                            @if($index === count($faqs) - 1) disabled @endif>
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </button>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-medium text-sm">{{ __('livewire.s.q') }} {{ $faq['question'] }}</p>
                                        <p class="text-muted-foreground text-sm mt-1">{{ __('livewire.s.a') }} {{ $faq['answer'] }}</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button wire:click="editFaq({{ $faq['id'] }})"
                                            class="text-primary hover:underline text-xs">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                        <button wire:click="deleteFaq({{ $faq['id'] }})"
                                            onclick="return confirm('Yakin hapus FAQ ini?')"
                                            class="text-red-500 hover:underline text-xs">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 border rounded-lg bg-muted/20">
                    <div class="w-16 h-16 mx-auto bg-primary/10 rounded-full flex items-center justify-center mb-4">
                        <i class="fa-solid fa-question-circle text-2xl text-primary"></i>
                    </div>
                    <h4 class="font-semibold mb-2">{{ __('livewire.s.belum_ada_faq') }}</h4>
                    <p class="text-muted-foreground text-sm mb-4 max-w-md mx-auto">{{ __('livewire.s.faq_adalah_cara_tercepat_untuk_melatih_ai_tambah') }}</p>

                    {{-- Example Tip --}}
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 mx-auto max-w-sm text-left">
                        <p class="text-xs font-medium text-amber-700 dark:text-amber-300 mb-1">{{ __('livewire.s.contoh_faq') }}</p>
                        <p class="text-xs text-amber-600 dark:text-amber-400">
                            <strong>{{ __('livewire.s.q') }}</strong> {{ __('livewire.s.apa_metode_pembayaran_yang_tersedia') }}<br>
                            <strong>{{ __('livewire.s.a') }}</strong> {{ __('livewire.s.kami_menerima_transfer_bank_e_wallet_gopay_ovo_d') }}</p>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Documents Tab --}}
    <div x-show="activeTab === 'documents'" x-cloak>
        <div class="space-y-4">
            {{-- Upload Form --}}
            <div class="bg-card border rounded-lg p-4">
                <h4 class="font-medium mb-3">
                    <i class="fa-solid fa-upload text-primary mr-2"></i>{{ __('livewire.s.upload_dokumen') }}</h4>
                <p class="text-sm text-muted-foreground mb-4">{{ __('livewire.s.upload_pdf_docx_atau_txt_untuk_melatih_ai_dengan') }}</p>
                <form wire:submit.prevent="uploadFile" class="flex flex-wrap gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <input type="file" wire:model="uploadedFile"
                            class="w-full px-4 py-2 border rounded-lg text-sm file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:bg-primary file:text-white file:cursor-pointer"
                            accept=".pdf,.docx,.txt">
                        {{-- File upload progress indicator --}}
                        <div wire:loading wire:target="uploadedFile" class="text-xs text-blue-600 mt-1">
                            <i class="fa-solid fa-spinner fa-spin mr-1"></i>{{ __('livewire.s.mengupload_file') }}</div>
                        @error('uploadedFile') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" 
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition text-sm disabled:opacity-50"
                        wire:loading.attr="disabled" wire:target="uploadedFile,uploadFile">
                        <span wire:loading.remove wire:target="uploadFile">
                            <i class="fa-solid fa-upload mr-1"></i>{{ __('admin.s.upload') }}</span>
                        <span wire:loading wire:target="uploadFile">
                            <i class="fa-solid fa-spinner fa-spin mr-1"></i>{{ __('admin.s.processing') }}</span>
                    </button>
                </form>
                <p class="text-xs text-muted-foreground mt-2">Format: PDF, DOCX, TXT (maks {{ $maxFileSizeMb }}MB sesuai paket Anda)</p>
            </div>


            {{-- Web Scrape Form --}}
            <div class="bg-card border rounded-lg p-4">
                <h4 class="font-medium mb-3">
                    <i class="fa-solid fa-globe text-green-500 mr-2"></i>{{ __('livewire.s.scrape_website') }}</h4>
                <p class="text-sm text-muted-foreground mb-4">{{ __('livewire.s.ekstrak_konten_dari_halaman_web_untuk_melatih_ai') }}</p>
                <form wire:submit.prevent="crawlWebsite" class="flex flex-wrap gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <input type="url" wire:model="websiteUrl"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm"
                            placeholder="https://example.com/halaman-faq">
                        @error('websiteUrl') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" 
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm disabled:opacity-50"
                        wire:loading.attr="disabled" wire:target="crawlWebsite">
                        <span wire:loading.remove wire:target="crawlWebsite">
                            <i class="fa-solid fa-spider mr-1"></i>{{ __('livewire.s.crawl') }}</span>
                        <span wire:loading wire:target="crawlWebsite">
                            <i class="fa-solid fa-spinner fa-spin mr-1"></i>{{ __('livewire.s.crawling') }}</span>
                    </button>
                </form>
                <p class="text-xs text-muted-foreground mt-2">{{ __('livewire.s.masukkan_url_halaman_yang_berisi_informasi_tenta') }}</p>
            </div>

            {{-- Document List --}}
            @if(count($documents) > 0)
                <div class="bg-card border rounded-lg divide-y">
                    @foreach($documents as $doc)
                        <div class="p-3 flex items-center gap-3" wire:key="doc-{{ $doc['id'] }}">
                            @php
                                $icons = [
                                    'pdf' => 'fa-file-pdf text-red-500',
                                    'docx' => 'fa-file-word text-blue-500',
                                    'txt' => 'fa-file-alt text-gray-500',
                                    'url' => 'fa-globe text-green-500',
                                ];
                                $icon = $icons[$doc['type']] ?? 'fa-file text-gray-500';
                            @endphp
                            <i class="fa-solid {{ $icon }} text-xl"></i>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-sm truncate">{{ $doc['name'] }}</p>
                                <p class="text-xs text-muted-foreground">
                                    @if($doc['status'] === 'completed')
                                        <span class="text-green-600"><i class="fa-solid fa-check-circle mr-1"></i>{{ __('livewire.s.siap') }}</span>
                                    @elseif($doc['status'] === 'processing')
                                        <span class="text-amber-600"><i class="fa-solid fa-spinner fa-spin mr-1"></i>{{ __('livewire.s.processing') }}</span>
                                    @else
                                        <span class="text-red-600"><i class="fa-solid fa-exclamation-circle mr-1"></i>{{ __('admin.s.gagal') }}</span>
                                    @endif
                                </p>
                            </div>
                            <button wire:click="deleteDocument({{ $doc['id'] }})"
                                onclick="return confirm('Yakin hapus dokumen ini?')"
                                class="text-red-500 hover:text-red-700 text-sm">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 border rounded-lg bg-muted/20">
                    <div class="w-16 h-16 mx-auto bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-4">
                        <i class="fa-solid fa-file-lines text-2xl text-green-600"></i>
                    </div>
                    <h4 class="font-semibold mb-2">{{ __('livewire.s.belum_ada_dokumen') }}</h4>
                    <p class="text-muted-foreground text-sm mb-4 max-w-md mx-auto">{{ __('livewire.s.upload_dokumen_atau_crawl_website_untuk_melatih') }}</p>

                    {{-- Format Tips --}}
                    <div class="flex flex-wrap justify-center gap-3 text-xs">
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-700 rounded-full">
                            <i class="fa-solid fa-file-pdf"></i>{{ __('livewire.s.pdf') }}</span>
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-700 rounded-full">
                            <i class="fa-solid fa-file-word"></i>{{ __('livewire.s.docx') }}</span>
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 text-gray-700 rounded-full">
                            <i class="fa-solid fa-file-alt"></i>{{ __('livewire.s.txt') }}</span>
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-700 rounded-full">
                            <i class="fa-solid fa-globe"></i>{{ __('livewire.s.website') }}</span>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
