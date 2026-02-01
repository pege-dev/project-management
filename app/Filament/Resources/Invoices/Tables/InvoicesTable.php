<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('client_name')
                    ->label('Client')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'dp' => 'DP',
                        'pelunasan' => 'Pelunasan',
                        'termin' => 'Termin',
                        'other' => 'Other',
                        default => ucfirst($state),
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'dp' => 'info',
                        'pelunasan' => 'success',
                        'termin' => 'warning',
                        'other' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'info',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        'overdue' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->money('idr')
                    ->sortable()
                    ->weight('bold'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                        'overdue' => 'Overdue',
                    ]),
                SelectFilter::make('type')
                    ->options([
                        'dp' => 'DP',
                        'pelunasan' => 'Pelunasan',
                        'termin' => 'Termin',
                        'other' => 'Other',
                    ]),
            ])
            ->recordActions([
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn(Invoice $record) => route('invoices.pdf', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            // ->bulkActions([
            //     BulkActionGroup::make([
            //         DeleteBulkAction::make(),
            //     ]),
            // ])
            ->defaultSort('created_at', 'desc');
    }
}
