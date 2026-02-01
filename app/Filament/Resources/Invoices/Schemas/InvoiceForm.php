<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Project;
use App\Models\Setting;
use Filament\Forms\Components\{FileUpload, TextInput, Textarea, Select, Repeater, ToggleButtons, DatePicker};
use Filament\Schemas\Components\{Grid, Section};
use Filament\Schemas\Components\Utilities\{Set, Get};
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        // Column 1 & 2: Main Content
                        Grid::make(1)
                            ->columnSpan(2)
                            ->schema([
                                // Section 1: Sender Information (Auto-Learning)
                                Section::make('Sender Information (My Company)')
                                    ->description('These details will be saved as default for future invoices.')
                                    ->collapsible()
                                    ->collapsed(fn(string $operation) => $operation === 'edit')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                FileUpload::make('company_logo')
                                                    ->label('Logo')
                                                    ->image()
                                                    ->directory('company')
                                                    ->default(fn() => Setting::getUserValue('invoice_company_logo')),

                                                Grid::make(1)
                                                    ->schema([
                                                        TextInput::make('company_name')
                                                            ->label('Company Name')
                                                            ->required()
                                                            ->default(fn() => Setting::getUserValue('invoice_company_name')),

                                                        TextInput::make('company_phone')
                                                            ->label('Phone / Contact')
                                                            ->default(fn() => Setting::getUserValue('invoice_company_phone')),
                                                    ]),
                                            ]),

                                        Textarea::make('company_address')
                                            ->label('Address')
                                            ->rows(3)
                                            ->default(fn() => Setting::getUserValue('invoice_company_address')),

                                        Textarea::make('company_bank_account')
                                            ->label('Bank Account Details')
                                            ->rows(3)
                                            ->placeholder("Bank Name\nAccount Number\nAccount Name")
                                            ->default(fn() => Setting::getUserValue('invoice_company_bank_account')),
                                    ]),

                                // Section 2: Client Information
                                Section::make('Client Information')
                                    ->schema([
                                        Select::make('project_id')
                                            ->relationship('project', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if ($state) {
                                                    $project = Project::find($state);
                                                    if ($project) {
                                                        $set('client_name', $project->client_name);
                                                        $set('client_address', $project->client_address);
                                                    }
                                                }
                                            }),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('client_name')
                                                    ->required(),

                                                Textarea::make('client_address')
                                                    ->rows(3),
                                            ]),
                                    ]),

                                // Section 3: Items
                                Section::make('Invoice Items')
                                    ->schema([
                                        Repeater::make('items')
                                            ->schema([
                                                TextInput::make('description')
                                                    ->required()
                                                    ->columnSpan(4),
                                                TextInput::make('quantity')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::updateItemTotal($set, $get))
                                                    ->columnSpan(2),
                                                TextInput::make('unit_price')
                                                    ->numeric()
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::updateItemTotal($set, $get))
                                                    ->columnSpan(3),
                                                TextInput::make('total')
                                                    ->numeric()
                                                    ->disabled()
                                                    ->dehydrated() // Needed to save to JSON
                                                    ->columnSpan(3),
                                            ])
                                            ->columns(12)
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateTotals($set, $get)),
                                    ]),
                            ]),

                        // Column 3: Totals & Metadata
                        Grid::make(1)
                            ->schema([
                                Section::make('Invoice Details')
                                    ->schema([
                                        TextInput::make('invoice_number')
                                            ->default(fn() => 'INV-' . strtoupper(Str::random(6)))
                                            ->required()
                                            ->unique(ignoreRecord: true),

                                        ToggleButtons::make('type')
                                            ->options([
                                                'dp' => 'DP',
                                                'pelunasan' => 'Pelunasan',
                                                'termin' => 'Termin',
                                                'other' => 'Other',
                                            ])
                                            ->colors([
                                                'dp' => 'info',
                                                'pelunasan' => 'success',
                                                'termin' => 'warning',
                                                'other' => 'gray',
                                            ])
                                            ->inline()
                                            ->default('other')
                                            ->required(),

                                        Select::make('status')
                                            ->options([
                                                'draft' => 'Draft',
                                                'sent' => 'Sent',
                                                'paid' => 'Paid',
                                                'cancelled' => 'Cancelled',
                                                'overdue' => 'Overdue',
                                            ])
                                            ->default('draft')
                                            ->required(),

                                        DatePicker::make('issue_date')
                                            ->default(now())
                                            ->required(),

                                        DatePicker::make('due_date')
                                            ->default(now()->addDays(14))
                                            ->required(),
                                    ]),

                                Section::make('Financials')
                                    ->schema([
                                        TextInput::make('subtotal')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->readOnly(),

                                        TextInput::make('tax_rate')
                                            ->label('Tax Rate (%)')
                                            ->numeric()
                                            ->default(0)
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateTotals($set, $get)),

                                        TextInput::make('tax_amount')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->readOnly(),

                                        TextInput::make('total_amount')
                                            ->label('Grand Total')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->readOnly()
                                            ->extraInputAttributes(['class' => 'text-xl font-bold']),
                                    ]),

                                Section::make('Notes')
                                    ->schema([
                                        Textarea::make('notes')
                                            ->rows(4)
                                            ->default(fn() => Setting::getUserValue('invoice_notes', "Terima kasih atas kepercayaan Anda.\nHarap melakukan pembayaran sebelum tanggal jatuh tempo.")),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    protected static function updateItemTotal(Set $set, Get $get): void
    {
        $qty = (float) $get('quantity');
        $price = (float) $get('unit_price');
        $set('total', $qty * $price);
    }

    protected static function calculateTotals(Set $set, Get $get): void
    {
        $items = $get('items') ?? [];
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += (float) ($item['total'] ?? 0);
        }

        $taxRate = (float) $get('tax_rate');
        $taxAmount = $subtotal * ($taxRate / 100);
        $totalAmount = $subtotal + $taxAmount;

        $set('subtotal', $subtotal);
        $set('tax_amount', $taxAmount);
        $set('total_amount', $totalAmount);
    }
}
