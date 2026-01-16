<div>
    {{-- Success/Error Messages --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
            {{ session('error') }}
        </div>
    @endif

    {{-- Company Info Section --}}
    <div class="bg-card rounded-xl shadow-sm border p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Company Information</h3>

        <form wire:submit.prevent="saveCompanyInfo" class="space-y-4">
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Company Name *</label>
                    <input type="text" wire:model="company_name"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="e.g., Cekat.biz.id">
                    @error('company_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Persona Name *</label>
                    <input type="text" wire:model="persona_name"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="e.g., Chika">
                    @error('persona_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Company Description</label>
                <textarea wire:model="company_description" rows="3"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    placeholder="Brief description of your company..."></textarea>
                @error('company_description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Persona Tone</label>
                <select wire:model="persona_tone"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="friendly">Friendly & Casual</option>
                    <option value="professional">Professional</option>
                    <option value="enthusiastic">Enthusiastic</option>
                    <option value="helpful">Helpful & Supportive</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Custom Instructions</label>
                <textarea wire:model="custom_instructions" rows="4"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    placeholder="Additional instructions for the AI..."></textarea>
                @error('custom_instructions') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <button type="submit"
                class="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:bg-primary/90 transition">
                <i class="fa-solid fa-save mr-2"></i> Save Company Info
            </button>
        </form>
    </div>

    {{-- Documents Upload Section --}}
    <div class="bg-card rounded-xl shadow-sm border p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Documents & Website</h3>

        <div class="grid md:grid-cols-2 gap-4 mb-4">
            {{-- File Upload --}}
            <div>
                <label class="block text-sm font-medium mb-2">Upload File (PDF, DOCX, TXT)</label>
                <input type="file" wire:model="uploadedFile" accept=".pdf,.docx,.txt"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">

                {{-- Upload Progress --}}
                <div wire:loading wire:target="uploadedFile" class="mt-2 text-sm text-blue-600">
                    <i class="fa-solid fa-spinner fa-spin mr-1"></i> Uploading file...
                </div>

                @error('uploadedFile') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

                <button wire:click="uploadFile" wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed" wire:target="uploadedFile,uploadFile"
                    type="button"
                    class="mt-2 bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition text-sm">
                    <span wire:loading.remove wire:target="uploadFile"><i class="fa-solid fa-upload mr-2"></i>Upload
                        File</span>
                    <span wire:loading wire:target="uploadFile"><i
                            class="fa-solid fa-spinner fa-spin mr-2"></i>Processing...</span>
                </button>
            </div>

            {{-- Website URL --}}
            <div>
                <label class="block text-sm font-medium mb-2">Website URL</label>
                <input type="url" wire:model="websiteUrl"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    placeholder="https://example.com">
                @error('websiteUrl') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                <button wire:click="crawlWebsite" wire:loading.attr="disabled" type="button"
                    class="mt-2 bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition text-sm">
                    <span wire:loading.remove wire:target="crawlWebsite"><i class="fa-solid fa-globe mr-2"></i> Crawl
                        Website</span>
                    <span wire:loading wire:target="crawlWebsite"><i class="fa-solid fa-spinner fa-spin mr-2"></i>
                        Crawling...</span>
                </button>
            </div>
        </div>

        {{-- Documents List --}}
        <div class="mt-6">
            <h4 class="font-medium mb-3">Uploaded Documents ({{ count($documents) }})</h4>
            <div class="space-y-2">
                @forelse($documents as $doc)
                    <div class="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/30 transition">
                        <div class="flex items-center gap-3">
                            <i
                                class="fa-solid {{ $doc['type'] === 'pdf' ? 'fa-file-pdf text-red-500' : ($doc['type'] === 'docx' ? 'fa-file-word text-blue-500' : ($doc['type'] === 'url' ? 'fa-globe text-green-500' : 'fa-file-text text-gray-500')) }} text-xl"></i>
                            <div>
                                <p class="font-medium text-sm">{{ $doc['name'] }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ ucfirst($doc['status']) }} •
                                    {{ \Carbon\Carbon::parse($doc['created_at'])->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        <button wire:click="deleteDocument({{ $doc['id'] }})" wire:confirm="Delete this document?"
                            class="text-red-500 hover:text-red-700 p-2">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                @empty
                    <p class="text-center text-muted-foreground py-4">No documents uploaded yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- FAQs Section --}}
    <div class="bg-card rounded-xl shadow-sm border p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Frequently Asked Questions</h3>
            <span class="text-sm text-muted-foreground">{{ count($faqs) }} FAQs</span>
        </div>

        {{-- Add New FAQ Form --}}
        @if(!$editingFaqId)
            <div class="bg-muted/50 rounded-lg p-4 mb-4">
                <h4 class="font-medium mb-3">Add New FAQ</h4>
                <form wire:submit.prevent="addFaq" class="space-y-3">
                    <div>
                        <input type="text" wire:model="newFaqQuestion"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Question">
                        @error('newFaqQuestion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <textarea wire:model="newFaqAnswer" rows="3"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Answer"></textarea>
                        @error('newFaqAnswer') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit"
                        class="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition text-sm">
                        <i class="fa-solid fa-plus mr-2"></i> Add FAQ
                    </button>
                </form>
            </div>
        @endif

        {{-- Edit FAQ Form --}}
        @if($editingFaqId)
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                <h4 class="font-medium mb-3 text-blue-900 dark:text-blue-100">Edit FAQ</h4>
                <form wire:submit.prevent="updateFaq" class="space-y-3">
                    <div>
                        <input type="text" wire:model="editFaqQuestion"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Question">
                        @error('editFaqQuestion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <textarea wire:model="editFaqAnswer" rows="3"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Answer"></textarea>
                        @error('editFaqAnswer') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex gap-2">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition text-sm">
                            <i class="fa-solid fa-save mr-2"></i> Update FAQ
                        </button>
                        <button type="button" wire:click="cancelEdit"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition text-sm">
                            <i class="fa-solid fa-times mr-2"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- FAQs List --}}
        <div class="space-y-3">
            @forelse($faqs as $index => $faq)
                <div class="border rounded-lg p-4 hover:bg-muted/30 transition">
                    <div class="flex justify-between items-start gap-4">
                        <div class="flex-1">
                            <p class="font-medium mb-1">{{ $faq['question'] }}</p>
                            <p class="text-sm text-muted-foreground">{{ $faq['answer'] }}</p>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            @if($index > 0)
                                <button wire:click="moveFaqUp({{ $faq['id'] }})"
                                    class="text-muted-foreground hover:text-foreground p-2" title="Move up">
                                    <i class="fa-solid fa-arrow-up"></i>
                                </button>
                            @endif
                            @if($index < count($faqs) - 1)
                                <button wire:click="moveFaqDown({{ $faq['id'] }})"
                                    class="text-muted-foreground hover:text-foreground p-2" title="Move down">
                                    <i class="fa-solid fa-arrow-down"></i>
                                </button>
                            @endif
                            <button wire:click="editFaq({{ $faq['id'] }})" class="text-blue-500 hover:text-blue-700 p-2"
                                title="Edit">
                                <i class="fa-solid fa-edit"></i>
                            </button>
                            <button wire:click="deleteFaq({{ $faq['id'] }})" wire:confirm="Delete this FAQ?"
                                class="text-red-500 hover:text-red-700 p-2" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-center text-muted-foreground py-8">No FAQs yet. Add your first FAQ above!</p>
            @endforelse
        </div>
    </div>
</div>