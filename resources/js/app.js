import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import invoiceEditor from './invoice-editor';

// Livewire ships Alpine; register our components on that single instance.
Alpine.data('invoiceEditor', invoiceEditor);

Livewire.start();
