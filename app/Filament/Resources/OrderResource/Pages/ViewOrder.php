<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\Couriers\PathaoService;
use App\Services\Couriers\SteadfastService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sendToSteadfast')
                ->label('Send to SteadFast')
                ->icon('heroicon-o-truck')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Send to SteadFast Courier')
                ->modalDescription(fn (Order $record) => "Order {$record->number} — {$record->customer_name}, ৳" . number_format($record->total) . " ({$record->payment_method})")
                ->modalSubmitActionLabel('Send Now')
                ->visible(fn (Order $record) => empty($record->courier_tracking)
                    && ! in_array($record->status, ['delivered', 'cancelled']))
                ->action(function (Order $record) {
                    $service = app(SteadfastService::class);
                    if (! $service->isConfigured()) {
                        Notification::make()
                            ->title('SteadFast Not Configured')
                            ->body('Set STEADFAST_API_KEY and STEADFAST_API_SECRET in .env file.')
                            ->danger()
                            ->send();
                        return;
                    }
                    try {
                        $result = $service->createOrder($record);
                        $tracking = $result['consignment']['tracking_code'] ?? $result['tracking_code'] ?? ($result['consignment_id'] ?? '');
                        $record->update([
                            'courier'          => 'steadfast',
                            'courier_tracking' => $tracking,
                            'status'           => $record->status === 'pending' ? 'processing' : $record->status,
                        ]);
                        Notification::make()
                            ->title('SteadFast Courier Booked!')
                            ->body("Tracking: {$tracking}")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('SteadFast Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('sendToPathao')
                ->label('Send to Pathao')
                ->icon('heroicon-o-truck')
                ->color('warning')
                ->modalHeading('Send to Pathao Courier')
                ->modalSubmitActionLabel('Send Now')
                ->visible(fn (Order $record) => empty($record->courier_tracking)
                    && ! in_array($record->status, ['delivered', 'cancelled']))
                ->form([
                    Forms\Components\Select::make('store_id')
                        ->label('Pickup Store')
                        ->options(function () {
                            try {
                                return collect(app(PathaoService::class)->getStores())
                                    ->pluck('store_name', 'store_id');
                            } catch (\Throwable) {
                                return [];
                            }
                        })
                        ->required()
                        ->searchable(),
                    Forms\Components\Select::make('recipient_city_id')
                        ->label('City')
                        ->options(function () {
                            try {
                                return collect(app(PathaoService::class)->getCities())
                                    ->pluck('city_name', 'city_id');
                            } catch (\Throwable) {
                                return [];
                            }
                        })
                        ->required()
                        ->searchable()
                        ->live(),
                    Forms\Components\Select::make('recipient_zone_id')
                        ->label('Zone')
                        ->options(function (Get $get) {
                            $cityId = $get('recipient_city_id');
                            if (! $cityId) return [];
                            try {
                                return collect(app(PathaoService::class)->getZones((int) $cityId))
                                    ->pluck('zone_name', 'zone_id');
                            } catch (\Throwable) {
                                return [];
                            }
                        })
                        ->required()
                        ->searchable()
                        ->live(),
                    Forms\Components\Select::make('recipient_area_id')
                        ->label('Area (Optional)')
                        ->options(function (Get $get) {
                            $zoneId = $get('recipient_zone_id');
                            if (! $zoneId) return [];
                            try {
                                return collect(app(PathaoService::class)->getAreas((int) $zoneId))
                                    ->pluck('area_name', 'area_id');
                            } catch (\Throwable) {
                                return [];
                            }
                        })
                        ->searchable(),
                    Forms\Components\Select::make('delivery_type')
                        ->label('Delivery Type')
                        ->options([
                            48  => 'Normal (48 Hours)',
                            12  => 'On Demand (12 Hours)',
                        ])
                        ->default(48)
                        ->required(),
                ])
                ->action(function (Order $record, array $data) {
                    $service = app(PathaoService::class);
                    if (! $service->isConfigured()) {
                        Notification::make()
                            ->title('Pathao Not Configured')
                            ->body('Set PATHAO_CLIENT_ID, PATHAO_CLIENT_SECRET, PATHAO_USERNAME, PATHAO_PASSWORD in .env file.')
                            ->danger()
                            ->send();
                        return;
                    }
                    try {
                        $result = $service->createOrder($record, $data);
                        $tracking = $result['consignment_id'] ?? '';
                        $record->update([
                            'courier'          => 'pathao',
                            'courier_tracking' => $tracking,
                            'status'           => $record->status === 'pending' ? 'processing' : $record->status,
                        ]);
                        Notification::make()
                            ->title('Pathao Courier Booked!')
                            ->body("Consignment ID: {$tracking}")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Pathao Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('downloadInvoice')
                ->label('Download Invoice (PDF)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn (Order $record): string => route('order.invoice', $record->number))
                ->openUrlInNewTab(),

            Actions\Action::make('packingSlip')
                ->label('Packing Slip')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('gray')
                ->url(fn (Order $record): string => route('order.packing-slip', $record->number))
                ->openUrlInNewTab(),

            Actions\EditAction::make(),
        ];
    }
}
