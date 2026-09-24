<?php

namespace App\Livewire;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class InvoiceTable extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(as: 'customer', except: '')]
    public string $customerId = '';

    #[Url(except: '')]
    public string $month = '';

    #[Url(except: '')]
    public string $year = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Invoice::class);
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'status', 'customerId', 'month', 'year'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'customerId', 'month', 'year');
        $this->resetPage();
    }

    public function render(): View
    {
        $invoices = Invoice::query()
            ->with('customer')
            ->forCustomer($this->customerId !== '' ? (int) $this->customerId : null)
            ->issuedIn($this->month !== '' ? (int) $this->month : null, $this->year !== '' ? (int) $this->year : null)
            ->when(InvoiceStatus::tryFrom($this->status), fn ($q, InvoiceStatus $status) => $q->where('status', $status))
            ->when($this->search !== '', function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(fn ($q) => $q
                    ->where('number', 'like', $term)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term)));
            })
            ->latest('issue_date')
            ->latest('id')
            ->paginate(15);

        return view('livewire.invoice-table', [
            'invoices' => $invoices,
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'statuses' => InvoiceStatus::cases(),
            'years' => range((int) now()->year, (int) now()->year - 5),
        ]);
    }
}
