@php
    $order = $getRecord();
    $order->loadMissing(['items.product', 'items.combo']);
@endphp

<div class="space-y-4">
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 shadow-sm">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/10 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                <tr>
                    <th scope="col" class="py-3 px-4 w-16 text-center">Image</th>
                    <th scope="col" class="py-3 px-4">Product / Item</th>
                    <th scope="col" class="py-3 px-4 text-center">Weight / Variant</th>
                    <th scope="col" class="py-3 px-4 text-center">Qty</th>
                    <th scope="col" class="py-3 px-4 text-right">Unit Price</th>
                    <th scope="col" class="py-3 px-4 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse($order->items as $item)
                    @php
                        $imagePath = null;
                        if ($item->product && $item->product->image) {
                            $imagePath = $item->product->image;
                        } elseif ($item->combo && $item->combo->image) {
                            $imagePath = $item->combo->image;
                        }

                        $imageUrl = null;
                        if ($imagePath) {
                            $cleaned = ltrim(preg_replace('#^storage/#', '', $imagePath), '/');
                            if (\Illuminate\Support\Str::startsWith($imagePath, ['http://', 'https://'])) {
                                $imageUrl = $imagePath;
                            } else {
                                $imageUrl = asset('storage/' . $cleaned);
                            }
                        }

                        $productUrl = null;
                        if ($item->product_id) {
                            $productUrl = route('product', $item->product_id);
                        } elseif ($item->combo_id && $item->combo) {
                            $productUrl = route('combo', $item->combo->slug);
                        }
                    @endphp
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-white/5 transition-colors">
                        {{-- Image --}}
                        <td class="py-3 px-4 text-center align-middle">
                            <div class="flex items-center justify-center">
                                @if($imageUrl)
                                    <img src="{{ $imageUrl }}" 
                                         alt="{{ $item->name }}" 
                                         class="w-12 h-12 object-cover rounded-lg border border-gray-200 dark:border-white/10 shadow-sm"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="hidden w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-800 items-center justify-center text-gray-400">
                                        <x-heroicon-o-photo class="w-6 h-6" />
                                    </div>
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400">
                                        <x-heroicon-o-photo class="w-6 h-6" />
                                    </div>
                                @endif
                            </div>
                        </td>

                        {{-- Item Name --}}
                        <td class="py-3 px-4 align-middle">
                            <div class="font-semibold text-gray-900 dark:text-white">
                                @if($productUrl)
                                    <a href="{{ $productUrl }}" target="_blank" class="hover:underline hover:text-primary-600 dark:hover:text-primary-400 inline-flex items-center gap-1">
                                        {{ $item->name }}
                                        <x-heroicon-m-arrow-top-right-on-square class="w-3.5 h-3.5 text-gray-400" />
                                    </a>
                                @else
                                    {{ $item->name }}
                                @endif
                            </div>
                            @if($item->combo_id)
                                <span class="inline-flex items-center px-2 py-0.5 mt-1 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                    Combo Package
                                </span>
                            @endif
                        </td>

                        {{-- Variant / Weight --}}
                        <td class="py-3 px-4 text-center align-middle">
                            @if($item->weight)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    {{ $item->weight }}
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        {{-- Quantity --}}
                        <td class="py-3 px-4 text-center align-middle font-medium text-gray-700 dark:text-gray-200">
                            {{ $item->qty }}
                        </td>

                        {{-- Unit Price --}}
                        <td class="py-3 px-4 text-right align-middle text-gray-600 dark:text-gray-400">
                            ৳{{ number_format($item->price) }}
                        </td>

                        {{-- Line Total --}}
                        <td class="py-3 px-4 text-right align-middle font-bold text-gray-900 dark:text-white">
                            ৳{{ number_format($item->line_total) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-gray-400">
                            No items found in this order.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Order Summary Calculation Box --}}
    <div class="flex justify-end">
        <div class="w-full sm:w-80 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-white/5 p-4 space-y-2 text-sm">
            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                <span>Subtotal</span>
                <span class="font-semibold text-gray-900 dark:text-white">৳{{ number_format($order->subtotal) }}</span>
            </div>
            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                <span>Delivery Charge</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ $order->delivery === 0 ? 'Free' : '৳' . number_format($order->delivery) }}</span>
            </div>
            @if($order->discount > 0)
                <div class="flex justify-between text-emerald-600 dark:text-emerald-400">
                    <span>Discount {{ $order->coupon_code ? '(' . $order->coupon_code . ')' : '' }}</span>
                    <span class="font-semibold">−৳{{ number_format($order->discount) }}</span>
                </div>
            @endif
            <div class="pt-2 border-t border-gray-200 dark:border-white/10 flex justify-between text-base font-bold text-gray-900 dark:text-white">
                <span>Grand Total</span>
                <span class="text-primary-600 dark:text-primary-400">৳{{ number_format($order->total) }}</span>
            </div>
        </div>
    </div>
</div>

