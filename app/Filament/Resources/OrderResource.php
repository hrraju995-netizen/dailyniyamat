<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Services\Couriers\PathaoService;
use App\Services\Couriers\SteadfastService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';


    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Orders';

    // ── Form (edit only: status, payment_status, courier fields) ─────────

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending'    => 'Pending',
                                'confirmed'  => 'Confirmed',
                                'processing' => 'Processing',
                                'shipped'    => 'Shipped',
                                'delivered'  => 'Delivered',
                                'cancelled'  => 'Cancelled',
                            ])
                            ->required(),
                        Forms\Components\Select::make('payment_status')
                            ->options([
                                'unpaid' => 'Unpaid',
                                'paid'   => 'Paid',
                                'failed' => 'Failed',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Courier')
                    ->schema([
                        Forms\Components\Select::make('courier')
                            ->options([
                                'pathao'    => 'Pathao Courier',
                                'steadfast' => 'Steadfast Courier',
                                'other'     => 'Other',
                            ])
                            ->placeholder('Select courier'),
                        Forms\Components\TextInput::make('courier_tracking')
                            ->label('Tracking Number / Consignment ID'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Customer Info')
                    ->schema([
                        Forms\Components\TextInput::make('customer_name')->disabled(),
                        Forms\Components\TextInput::make('customer_phone')->disabled(),
                        Forms\Components\TextInput::make('customer_email')->disabled(),
                        Forms\Components\TextInput::make('address_line')->disabled(),
                        Forms\Components\TextInput::make('city')->disabled(),
                        Forms\Components\TextInput::make('thana')->disabled(),
                        Forms\Components\Textarea::make('notes')->disabled()->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Admin Notes')
                    ->schema([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Internal Notes (not visible to customer)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('placed_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Order')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->copyable()
                    ->description(fn (Order $record) => $record->customer_name),

                Tables\Columns\TextColumn::make('total')
                    ->formatStateUsing(fn ($state) => '৳' . number_format($state))
                    ->sortable()
                    ->description(fn (Order $record) => strtoupper($record->payment_method) . ' · ' . ucfirst($record->payment_status)),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'    => 'warning',
                        'confirmed'  => 'primary',
                        'processing' => 'info',
                        'shipped'    => 'success',
                        'delivered'  => 'success',
                        'cancelled'  => 'danger',
                        default      => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('placed_at')
                    ->label('Date')
                    ->dateTime('d M, h:i A')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'    => 'Pending',
                        'confirmed'  => 'Confirmed',
                        'processing' => 'Processing',
                        'shipped'    => 'Shipped',
                        'delivered'  => 'Delivered',
                        'cancelled'  => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid'   => 'Paid',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'cod'        => 'Cash on Delivery',
                        'bkash'      => 'bKash',
                        'sslcommerz' => 'SSLCommerz',
                    ]),
                Tables\Filters\SelectFilter::make('courier')
                    ->options([
                        'pathao'    => 'Pathao',
                        'steadfast' => 'SteadFast',
                    ]),
            ])
            ->actions([
                // ── Courier dropdown (SteadFast + Pathao) ─────
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('sendToSteadfast')
                        ->label('SteadFast')
                        ->icon('heroicon-o-truck')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Send to SteadFast Courier')
                        ->modalDescription(fn (Order $record) => "Order {$record->number} — {$record->customer_name}\n৳" . number_format($record->total) . " ({$record->payment_method})")
                        ->modalSubmitActionLabel('Send Now')
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

                    Tables\Actions\Action::make('sendToPathao')
                        ->label('Pathao')
                        ->icon('heroicon-o-truck')
                        ->color('warning')
                        ->modalHeading('Send to Pathao Courier')
                        ->modalSubmitActionLabel('Send Now')
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
                ])
                    ->label('Courier')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->button()
                    ->visible(fn (Order $record) => empty($record->courier_tracking)
                        && ! in_array($record->status, ['delivered', 'cancelled'])),

                // ── Courier booked badge (shows when already sent) ──
                Tables\Actions\Action::make('courierBooked')
                    ->label(fn (Order $record) => match ($record->courier) {
                        'pathao'    => 'Pathao',
                        'steadfast' => 'SteadFast',
                        default     => 'Courier',
                    })
                    ->icon('heroicon-o-check-circle')
                    ->color(fn (Order $record) => match ($record->courier) {
                        'pathao'    => 'warning',
                        'steadfast' => 'info',
                        default     => 'gray',
                    })
                    ->button()
                    ->tooltip(fn (Order $record) => 'Tracking: ' . $record->courier_tracking)
                    ->visible(fn (Order $record) => ! empty($record->courier_tracking))
                    ->url(fn (Order $record) => match ($record->courier) {
                        'steadfast' => 'https://steadfast.com.bd/t/' . $record->courier_tracking,
                        'pathao'    => 'https://merchant.pathao.com',
                        default     => null,
                    })
                    ->openUrlInNewTab(),

                // ── More actions dropdown ─────────────────────
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('updateStatus')
                        ->label('Change Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('primary')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'pending'    => 'Pending',
                                    'confirmed'  => 'Confirmed',
                                    'processing' => 'Processing',
                                    'shipped'    => 'Shipped',
                                    'delivered'  => 'Delivered',
                                    'cancelled'  => 'Cancelled',
                                ])
                                ->required(),
                        ])
                        ->action(function (Order $record, array $data) {
                            $record->update(['status' => $data['status']]);
                        }),

                    Tables\Actions\Action::make('invoice')
                        ->label('Download Invoice')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('gray')
                        ->url(fn (Order $record): string => route('order.invoice', $record->number))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('packingSlip')
                        ->label('Packing Slip')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->color('gray')
                        ->url(fn (Order $record): string => route('order.packing-slip', $record->number))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('shippingLabel')
                        ->label('Shipping Label')
                        ->icon('heroicon-o-tag')
                        ->color('gray')
                        ->url(fn (Order $record): string => route('order.shipping-label', $record->number))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('cancelOrder')
                        ->label('Cancel Order')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Order')
                        ->modalDescription('This will cancel the order and restore stock. This action cannot be undone.')
                        ->visible(fn (Order $record) => $record->status !== 'cancelled' && $record->status !== 'delivered')
                        ->action(fn (Order $record) => $record->cancelAndRestoreStock()),
                ]),

                Tables\Actions\ViewAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkStatusUpdate')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'pending'    => 'Pending',
                                    'confirmed'  => 'Confirmed',
                                    'processing' => 'Processing',
                                    'shipped'    => 'Shipped',
                                    'delivered'  => 'Delivered',
                                    'cancelled'  => 'Cancelled',
                                ])
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $records->each(fn (Order $order) => $order->update(['status' => $data['status']]));
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('bulkSendSteadfast')
                        ->label('Send to SteadFast')
                        ->icon('heroicon-o-truck')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Send to SteadFast')
                        ->modalDescription('Selected orders will be sent to SteadFast courier. Orders that already have tracking numbers will be skipped.')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $eligible = $records->filter(fn (Order $o) =>
                                empty($o->courier_tracking)
                                && ! in_array($o->status, ['delivered', 'cancelled'])
                            );

                            if ($eligible->isEmpty()) {
                                Notification::make()->title('No eligible orders to send.')->warning()->send();
                                return;
                            }

                            $sent = 0;
                            $failed = 0;
                            $service = app(SteadfastService::class);

                            foreach ($eligible as $order) {
                                try {
                                    $result = $service->createOrder($order);
                                    $tracking = $result['consignment']['tracking_code'] ?? $result['tracking_code'] ?? ($result['consignment_id'] ?? '');
                                    $order->update([
                                        'courier'          => 'steadfast',
                                        'courier_tracking' => $tracking,
                                        'status'           => $order->status === 'pending' ? 'processing' : $order->status,
                                    ]);
                                    $sent++;
                                } catch (\Throwable) {
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->title("SteadFast: {$sent} sent" . ($failed ? ", {$failed} failed" : ''))
                                ->color($failed ? 'warning' : 'success')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    // ── Infolist (View page) ──────────────────────────────────────────────

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Order Overview')
                    ->schema([
                        Infolists\Components\TextEntry::make('number')
                            ->weight(\Filament\Support\Enums\FontWeight::Bold),
                        Infolists\Components\TextEntry::make('status')
                            ->badge(),
                        Infolists\Components\TextEntry::make('payment_method')
                            ->label('Payment Method'),
                        Infolists\Components\TextEntry::make('payment_status')
                            ->label('Payment Status')
                            ->badge(),
                        Infolists\Components\TextEntry::make('payment_ref')
                            ->label('Payment Ref')
                            ->placeholder('—')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('placed_at')
                            ->dateTime('d M Y, h:i A'),
                        Infolists\Components\TextEntry::make('total')
                            ->formatStateUsing(fn ($state) => '৳' . number_format($state)),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Courier & Shipping')
                    ->icon('heroicon-o-truck')
                    ->schema([
                        Infolists\Components\TextEntry::make('courier')
                            ->label('Courier')
                            ->badge()
                            ->formatStateUsing(fn (?string $state) => match ($state) {
                                'pathao'    => 'Pathao',
                                'steadfast' => 'SteadFast',
                                'other'     => 'Other',
                                default     => 'Not Assigned',
                            })
                            ->color(fn (?string $state) => match ($state) {
                                'pathao'    => 'warning',
                                'steadfast' => 'info',
                                default     => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('courier_tracking')
                            ->label('Tracking / Consignment ID')
                            ->copyable()
                            ->placeholder('Not sent to courier yet'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Customer')
                    ->schema([
                        Infolists\Components\TextEntry::make('customer_name'),
                        Infolists\Components\TextEntry::make('customer_phone'),
                        Infolists\Components\TextEntry::make('customer_email'),
                        Infolists\Components\TextEntry::make('address_line'),
                        Infolists\Components\TextEntry::make('city'),
                        Infolists\Components\TextEntry::make('thana'),
                        Infolists\Components\TextEntry::make('notes')->columnSpanFull(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Ordered Items')
                    ->icon('heroicon-o-shopping-bag')
                    ->schema([
                        Infolists\Components\ViewEntry::make('items')
                            ->hiddenLabel()
                            ->view('filament.resources.order.order-items-table'),
                    ]),

                Infolists\Components\Section::make('Financials')
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal')
                            ->formatStateUsing(fn ($state) => '৳' . number_format($state)),
                        Infolists\Components\TextEntry::make('delivery')
                            ->formatStateUsing(fn ($state) => $state === 0 ? 'Free' : '৳' . number_format($state)),
                        Infolists\Components\TextEntry::make('discount')
                            ->formatStateUsing(fn ($state) => $state > 0 ? '−৳' . number_format($state) : '—'),
                        Infolists\Components\TextEntry::make('coupon_code')
                            ->placeholder('None'),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Admin Notes')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Infolists\Components\TextEntry::make('admin_notes')
                            ->hiddenLabel()
                            ->placeholder('No admin notes yet.'),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Return Requests')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('returnRequests')
                            ->hiddenLabel()
                            ->schema([
                                Infolists\Components\TextEntry::make('type')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'return'   => 'warning',
                                        'exchange' => 'info',
                                        'refund'   => 'danger',
                                        default    => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending'   => 'warning',
                                        'approved'  => 'primary',
                                        'rejected'  => 'danger',
                                        'completed' => 'success',
                                        default     => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('reason'),
                                Infolists\Components\TextEntry::make('refund_amount')
                                    ->formatStateUsing(fn ($state) => $state ? '৳' . number_format($state) : '—'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Requested')
                                    ->dateTime('d M Y'),
                            ])
                            ->columns(5),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make('Status History')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('statusHistories')
                            ->hiddenLabel()
                            ->schema([
                                Infolists\Components\TextEntry::make('changed_at')
                                    ->label('Date')
                                    ->dateTime('d M Y, h:i A'),
                                Infolists\Components\TextEntry::make('new_status')
                                    ->label('Order Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => Order::statusLabel($state)),
                                Infolists\Components\TextEntry::make('new_payment_status')
                                    ->label('Payment Status')
                                    ->badge()
                                    ->placeholder('—'),
                                Infolists\Components\TextEntry::make('changed_by')
                                    ->label('Changed By'),
                                Infolists\Components\TextEntry::make('note')
                                    ->placeholder('—'),
                            ])
                            ->columns(5),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit'   => Pages\EditOrder::route('/{record}/edit'),
            'view'   => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
