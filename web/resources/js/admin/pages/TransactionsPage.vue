<script setup>
import { ChevronDown, ReceiptText, WalletCards } from 'lucide-vue-next';

defineProps({
    panelTransactionsUrl: { type: String, default: '/admin/transactions' },
    normalizedRecentTransactions: { type: Array, default: () => [] },
    transactionTodayTotal: { type: String, default: 'Rp 0' },
    resolveMethodStyle: { type: Function, required: true },
    resolveTransactionStatus: { type: Function, required: true },
});
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-3xl px-6 py-5" style="background: linear-gradient(135deg, #1E1B4B 0%, #312E81 52%, #4338CA 100%); box-shadow: 0 8px 28px rgba(49,46,129,0.28);">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -left-10 -top-10 h-36 w-36 rounded-full" style="background: rgba(199,210,254,0.2);"></div>
                <div class="absolute right-8 top-5 h-9 w-9 rounded-full" style="background: rgba(224,231,255,0.2);"></div>
            </div>
            <div class="relative flex flex-wrap items-start justify-between gap-3 text-white">
                <div>
                    <h2 class="text-[1.35rem] font-bold">Transactions</h2>
                    <p class="text-sm text-white/75">Riwayat transaksi dengan rincian item dan pembayaran.</p>
                </div>
                <span class="rounded-xl bg-white px-4 py-2 text-sm font-semibold" style="color: #312E81;">Baris bisa dibuka per transaksi</span>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border p-4" style="border-color: #E0E7FF; background: #FFFFFF; box-shadow: 0 1px 3px rgba(49,46,129,0.08), 0 8px 20px rgba(49,46,129,0.08);">
                <p class="text-xs text-[#94A3B8]">Total Transactions</p>
                <p class="mt-1 text-2xl font-bold text-[#1F2937]">{{ normalizedRecentTransactions.length }}</p>
            </div>
            <div class="rounded-3xl border p-4" style="border-color: #E0E7FF; background: #FFFFFF; box-shadow: 0 1px 3px rgba(49,46,129,0.08), 0 8px 20px rgba(49,46,129,0.08);">
                <p class="text-xs text-[#94A3B8]">Visible Paid Amount</p>
                <p class="mt-1 text-xl font-bold text-[#059669]">{{ transactionTodayTotal }}</p>
            </div>
            <div class="rounded-3xl border p-4" style="border-color: #E0E7FF; background: #FFFFFF; box-shadow: 0 1px 3px rgba(49,46,129,0.08), 0 8px 20px rgba(49,46,129,0.08);">
                <p class="text-xs text-[#94A3B8]">Paid</p>
                <p class="mt-1 text-2xl font-bold text-[#059669]">{{ normalizedRecentTransactions.filter((item) => item.status === 'paid').length }}</p>
            </div>
            <div class="rounded-3xl border p-4" style="border-color: #E0E7FF; background: #FFFFFF; box-shadow: 0 1px 3px rgba(49,46,129,0.08), 0 8px 20px rgba(49,46,129,0.08);">
                <p class="text-xs text-[#94A3B8]">Partial / Unpaid</p>
                <p class="mt-1 text-2xl font-bold text-[#D97706]">{{ normalizedRecentTransactions.filter((item) => item.status !== 'paid').length }}</p>
            </div>
        </div>

        <div class="space-y-4">
            <details v-for="transaction in normalizedRecentTransactions" :key="`transaction-module-row-${transaction.record_id || transaction.id}`" class="group overflow-hidden rounded-3xl border bg-white" style="border-color: #E0E7FF; box-shadow: 0 1px 3px rgba(49,46,129,0.08), 0 8px 20px rgba(49,46,129,0.08);">
                <summary class="list-none cursor-pointer px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-base font-bold text-[#312E81]">{{ transaction.id }}</p>
                                <span class="rounded-full px-2.5 py-1 text-[0.68rem] font-semibold" :style="{ background: resolveTransactionStatus(transaction.status).bg, color: resolveTransactionStatus(transaction.status).color }">
                                    {{ resolveTransactionStatus(transaction.status).label }}
                                </span>
                                <span class="rounded-full px-2.5 py-1 text-[0.68rem] font-semibold" :style="{ background: resolveMethodStyle(transaction.method).bg, color: resolveMethodStyle(transaction.method).color }">
                                    {{ transaction.method }}
                                </span>
                                <span class="rounded-full bg-[#F8FAFC] px-2.5 py-1 text-[0.68rem] font-semibold text-[#64748B]">{{ transaction.items.length }} item</span>
                                <span class="rounded-full bg-[#F8FAFC] px-2.5 py-1 text-[0.68rem] font-semibold text-[#64748B]">{{ transaction.payments.length }} payment</span>
                            </div>
                            <p class="mt-2 text-sm font-semibold text-[#0F172A]">{{ transaction.customer }}</p>
                            <p class="mt-1 text-xs text-[#64748B]">{{ transaction.branchName || '-' }} · {{ transaction.cashier }}</p>
                            <p class="mt-1 text-xs text-[#94A3B8]">{{ transaction.time }}</p>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="text-right">
                                <p class="text-xs text-[#94A3B8]">Paid / Total</p>
                                <p class="mt-1 text-sm font-bold text-[#0F172A]">{{ transaction.paidText }} / {{ transaction.totalText }}</p>
                                <p class="mt-1 text-xs text-[#64748B]">Sisa {{ transaction.remainingText }}</p>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#EEF2FF] text-[#4338CA] transition-transform group-open:rotate-180">
                                <ChevronDown class="h-4 w-4" />
                            </div>
                        </div>
                    </div>
                </summary>

                <div class="border-t px-5 py-5" style="border-color: #EEF2FF; background: #F8FAFC;">
                    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.95fr)]">
                        <section class="rounded-3xl border bg-white p-4" style="border-color: #E2E8F0;">
                            <div class="mb-4 flex items-center gap-2 text-sm font-semibold text-[#0F172A]">
                                <ReceiptText class="h-4 w-4 text-[#2563EB]" />
                                Item Transaksi
                            </div>
                            <div class="max-h-[360px] space-y-3 overflow-y-auto pr-1">
                                <div v-for="(item, itemIndex) in transaction.items" :key="`transaction-item-${transaction.id}-${itemIndex}`" class="flex items-start justify-between gap-3 rounded-2xl bg-[#F8FAFC] px-4 py-3">
                                    <div>
                                        <p class="text-sm font-semibold text-[#0F172A]">{{ item.itemName }}</p>
                                        <p class="mt-1 text-xs text-[#64748B]">{{ item.itemType }} · {{ item.qty }} x {{ item.unitPriceText }}</p>
                                    </div>
                                    <p class="text-sm font-semibold text-[#1F2937]">{{ item.lineTotalText }}</p>
                                </div>
                                <p v-if="!transaction.items.length" class="rounded-2xl bg-[#F8FAFC] px-4 py-8 text-center text-sm text-[#94A3B8]">Tidak ada item pada transaksi ini.</p>
                            </div>
                        </section>

                        <section class="space-y-4">
                            <article class="rounded-3xl border bg-white p-4" style="border-color: #E2E8F0;">
                                <div class="mb-4 flex items-center gap-2 text-sm font-semibold text-[#0F172A]">
                                    <WalletCards class="h-4 w-4 text-[#059669]" />
                                    Riwayat Pembayaran
                                </div>
                                <div class="max-h-[260px] space-y-3 overflow-y-auto pr-1">
                                    <div v-for="(payment, paymentIndex) in transaction.payments" :key="`transaction-payment-${transaction.id}-${paymentIndex}`" class="rounded-2xl bg-[#F8FAFC] px-4 py-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-[#0F172A]">{{ payment.paymentCode }}</p>
                                                <p class="mt-1 text-xs text-[#64748B]">{{ payment.method }} · {{ payment.cashierName }}</p>
                                                <p class="mt-1 text-xs text-[#94A3B8]">{{ payment.paidAtText }}</p>
                                                <p v-if="payment.referenceNo" class="mt-1 text-xs text-[#94A3B8]">Ref: {{ payment.referenceNo }}</p>
                                            </div>
                                            <p class="text-sm font-semibold text-[#059669]">{{ payment.amountText }}</p>
                                        </div>
                                    </div>
                                    <p v-if="!transaction.payments.length" class="rounded-2xl bg-[#F8FAFC] px-4 py-8 text-center text-sm text-[#94A3B8]">Belum ada pembayaran pada transaksi ini.</p>
                                </div>
                            </article>

                            <article class="rounded-3xl border bg-white p-4" style="border-color: #E2E8F0;">
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <p class="text-xs text-[#94A3B8]">Phone</p>
                                        <p class="mt-1 font-medium text-[#0F172A]">{{ transaction.customerPhone || '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-[#94A3B8]">Change</p>
                                        <p class="mt-1 font-medium text-[#0F172A]">{{ transaction.changeText }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-[#94A3B8]">Notes</p>
                                        <p class="mt-1 font-medium text-[#0F172A]">{{ transaction.notes || '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-[#94A3B8]">Remaining</p>
                                        <p class="mt-1 font-medium text-[#0F172A]">{{ transaction.remainingText }}</p>
                                    </div>
                                </div>
                            </article>
                        </section>
                    </div>
                </div>
            </details>

            <div v-if="!normalizedRecentTransactions.length" class="rounded-3xl border bg-white px-4 py-14 text-center text-sm text-[#94A3B8]" style="border-color: #E0E7FF; box-shadow: 0 1px 3px rgba(49,46,129,0.08), 0 8px 20px rgba(49,46,129,0.08);">
                No recent transactions.
            </div>
        </div>
    </div>
</template>
