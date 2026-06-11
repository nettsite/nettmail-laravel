<?php

use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Nettsite\NettMail\Core\Domain\Campaigns\MergeTag;
use Nettsite\NettMail\Core\Domain\Templates\MergeTagRenderer;
use Nettsite\NettMail\Core\Domain\Templates\MissingUnsubscribeLinkException;
use Nettsite\NettMail\Core\Domain\Templates\TemplateCompiler;
use Nettsite\NettMail\Core\Domain\Templates\TemplateType;
use NettSite\NettMail\Mail\TemplatePreviewMail;
use NettSite\NettMail\Models\Sender;
use NettSite\NettMail\Models\Template;

new
#[Layout('nettmail::layouts.admin')]
#[Title('Template')]
class extends Component
{
    public Template $template;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|in:transactional,broadcast')]
    public string $type = 'broadcast';

    #[Validate('nullable|string|max:255')]
    public string $subject = '';

    #[Validate('nullable|string')]
    public string $html = '';

    public string $testEmail = '';

    public function mount(): void
    {
        $this->name = $this->template->name;
        $this->type = $this->template->type->value;
        $this->subject = $this->template->subject ?? '';
        $this->html = $this->template->html ?? '';
    }

    public function save(): void
    {
        $this->validate();

        $plainText = $this->template->plain_text;

        try {
            $compiled = (new TemplateCompiler)->compile($this->html, TemplateType::from($this->type));
            $plainText = $compiled->plainText;
        } catch (MissingUnsubscribeLinkException) {
            // Saved as a draft; the unsubscribe-block warning below explains
            // why this template can't yet be used in a broadcast campaign.
        }

        $this->template->update([
            'name' => $this->name,
            'type' => $this->type,
            'subject' => $this->subject,
            'html' => $this->html,
            'plain_text' => $plainText,
        ]);

        session()->flash('nettmail-status', 'Template saved.');
    }

    public function sendTest(): void
    {
        $this->validate(['testEmail' => 'required|email']);

        $sender = Sender::query()->first();

        $mail = new TemplatePreviewMail('[Test] '.$this->subject, $this->renderedHtml());

        if ($sender !== null) {
            $mail->from($sender->from_email, $sender->from_name);
        }

        Mail::to($this->testEmail)->send($mail);

        session()->flash('nettmail-status', "Test email sent to {$this->testEmail}.");
    }

    public function getMissingUnsubscribeLinkProperty(): bool
    {
        return $this->type === 'broadcast' && ! (new TemplateCompiler)->hasUnsubscribeLink($this->html);
    }

    public function getPreviewHtmlProperty(): string
    {
        return $this->renderedHtml();
    }

    private function renderedHtml(): string
    {
        $sampleValues = [
            'first_name' => 'Jamie',
            'last_name' => 'Doe',
            'email' => 'jamie@example.test',
            'company' => 'Acme Inc.',
            'unsubscribe_url' => '#',
        ];

        return (new MergeTagRenderer)->render($this->html, $sampleValues);
    }

    /** @return array<int, MergeTag> */
    public function getMergeTagsProperty(): array
    {
        return MergeTag::defaults();
    }

    public function mergeTagPlaceholder(string $key): string
    {
        return '{{'.$key.'}}';
    }
};
?>

<div>
    <h2>{{ $template->name }}</h2>

    @if (session('nettmail-status'))
        <div class="nettmail-card" style="border-color: #16a34a;">{{ session('nettmail-status') }}</div>
    @endif

    @if ($this->missingUnsubscribeLink)
        <div class="nettmail-card" style="border-color: #dc2626;">
            Broadcast templates must include the <code>{{ $this->mergeTagPlaceholder('unsubscribe_url') }}</code> merge tag before they can be used in a campaign.
        </div>
    @endif

    <div class="nettmail-card">
        <form wire:submit="save">
            <div class="nettmail-field">
                <label>Name</label>
                <input type="text" class="nettmail-input" wire:model="name">
                @error('name') <div class="nettmail-error">{{ $message }}</div> @enderror
            </div>
            <div class="nettmail-field">
                <label>Type</label>
                <select class="nettmail-select" wire:model="type">
                    <option value="broadcast">Broadcast</option>
                    <option value="transactional">Transactional</option>
                </select>
            </div>
            <div class="nettmail-field">
                <label>Subject</label>
                <input type="text" class="nettmail-input" wire:model="subject">
            </div>

            <div class="nettmail-field">
                <label>Merge tags</label>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    @foreach ($this->mergeTags as $tag)
                        <button
                            type="button"
                            class="nettmail-btn nettmail-btn-secondary"
                            data-merge-tag="{{ $this->mergeTagPlaceholder($tag->key) }}"
                            x-on:click="
                                const field = $refs.html;
                                const tag = $el.dataset.mergeTag;
                                const start = field.selectionStart;
                                const end = field.selectionEnd;
                                $wire.html = field.value.slice(0, start) + tag + field.value.slice(end);
                                $nextTick(() => { field.focus(); field.selectionStart = field.selectionEnd = start + tag.length; });
                            "
                        >{{ $tag->label }}</button>
                    @endforeach
                </div>
            </div>

            <div class="nettmail-field">
                <label>HTML</label>
                <textarea x-ref="html" class="nettmail-textarea" wire:model.live="html" rows="14"></textarea>
                @error('html') <div class="nettmail-error">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="nettmail-btn">Save</button>
        </form>
    </div>

    <div class="nettmail-card">
        <h3>Preview</h3>
        <iframe srcdoc="{{ $this->previewHtml }}" style="width: 100%; height: 400px; border: 1px solid #cbd5e1;"></iframe>
    </div>

    <div class="nettmail-card">
        <h3>Send test email</h3>

        <form wire:submit="sendTest">
            <div class="nettmail-field">
                <label>Email address</label>
                <input type="email" class="nettmail-input" wire:model="testEmail">
                @error('testEmail') <div class="nettmail-error">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="nettmail-btn">Send test</button>
        </form>
    </div>
</div>
