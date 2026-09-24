<?php

namespace App\Livewire;

use App\Models\CreditNote;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CreditNoteTable extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', CreditNote::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $creditNotes = CreditNote::query()
            ->with('invoice.customer')
            ->when($this->search !== '', function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(fn ($q) => $q
                    ->where('number', 'like', $term)
                    ->orWhere('reason', 'like', $term)
                    ->orWhereHas('invoice', fn ($i) => $i
                        ->where('number', 'like', $term)
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term))));
            })
            ->latest('issue_date')
            ->latest('id')
            ->paginate(15);

        return view('livewire.credit-note-table', ['creditNotes' => $creditNotes]);
    }
}
