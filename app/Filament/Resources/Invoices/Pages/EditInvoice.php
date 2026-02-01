<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Setting;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $data = $this->data;

        // Save Sender Information as default settings
        if (!empty($data['company_name'])) {
            Setting::setUserValue('invoice_company_name', $data['company_name']);
        }
        if (!empty($data['company_address'])) {
            Setting::setUserValue('invoice_company_address', $data['company_address']);
        }
        if (!empty($data['company_phone'])) {
            Setting::setUserValue('invoice_company_phone', $data['company_phone']);
        }
        if (!empty($data['company_bank_account'])) {
            Setting::setUserValue('invoice_company_bank_account', $data['company_bank_account']);
        }
        if (!empty($data['company_logo'])) {
            Setting::setUserValue('invoice_company_logo', $data['company_logo']);
        }
        if (!empty($data['notes'])) {
            Setting::setUserValue('invoice_notes', $data['notes']);
        }
    }
}
