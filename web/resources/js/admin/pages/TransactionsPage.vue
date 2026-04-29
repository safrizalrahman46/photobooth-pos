<script setup>
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
        <section class="relative overflow-hidden rounded-2xl px-6 py-5" style="background: linear-gradient(130deg, #1E1B4B 0%, #312E81 58%, #4338CA 100%); box-shadow: 0 6px 24px rgba(49,46,129,0.28);">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -left-10 -top-10 h-36 w-36 rounded-full" style="background: rgba(199,210,254,0.2);"></div>
                <div class="absolute right-8 top-5 h-9 w-9 rounded-full" style="background: rgba(224,231,255,0.2);"></div>
            </div>
            <div class="relative flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-[1.35rem] font-bold text-white">Transactions</h2>
                    <p class="text-sm" style="color: rgba(255,255,255,0.76);">Recent payment history and cashier performance snapshot.</p>
                </div>
                <a :href="panelTransactionsUrl" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold" style="color: #312E81;">Open Full Module</a>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border p-4" style="border-color: #E0E7FF; background: #FFFFFF; box-shadow: 0 1px 3px rgba(49,46,129,0.08), 0 8px 20px rgba(49,46,129,0.08);">
                <p class="text-xs text-[#94A3B8]">Total Transactions</p>
                <p class="mt-1 text-2xl font-bold text-[#1F2937]">{{ normalizedRecentTransactions.length }}</p>
            </div>
            <div class="rounded-2xl border p-4" style="border-color: #E0E7FF; background: #FFFFFF; box-shadow: 0 1px 3px rgba(49,46,129,0.08), 0 8px 20px rgba(49,46,129,0.08);">
                <p class="text-xs text-[#94A3B8]">Today Revenue</p>
                <p class="mt-1 text-xl font-bold text-[#059669]">{{ transactionTodayTotal }}</p>
            </div>
            <div class="rounded-2xl border p-4" style="border-color: #E0E7FF; background: #FFFFFF; box-shadow: 0 1px 3px rgba(49,46,129,0.08), 0 8px 20px rgba(49,46,129,0.08);">
                <p class="text-xs text-[#94A3B8]">Paid</p>
                <p class="mt-1 text-2xl font-bold text-[#059669]">{{ normalizedRecentTransactions.filter((item) => item.status === 'paid').length }}</p>
            </div>
            <div class="rounded-2xl border p-4" style="border-color: #E0E7FF; background: #FFFFFF; box-shadow: 0 1px 3px rgba(49,46,129,0.08), 0 8px 20px rgba(49,46,129,0.08);">
                <p class="text-xs text-[#94A3B8]">Unpaid / Partial</p>
                <p class="mt-1 text-2xl font-bold text-[#D97706]">{{ normalizedRecentTransactions.filter((item) => item.status !== 'paid').length }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border" style="border-color: #E0E7FF; background: #FFFFFF; box-shadow: 0 1px 3px rgba(49,46,129,0.08), 0 8px 20px rgba(49,46,129,0.08);">
            <table class="w-full">
                <thead>
                    <tr style="border-bottom: 1px solid #E0E7FF; background: #EEF2FF;">
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Transaction</th>
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Customer</th>
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Cashier</th>
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Method</th>
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Amount</th>
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(transaction, index) in normalizedRecentTransactions" :key="`transaction-module-row-${transaction.id}-${index}`" style="border-bottom: 1px solid #F8FAFC;">
                        <td class="px-5 py-3.5">
                            <p class="text-sm font-semibold text-[#2563EB]">{{ transaction.id }}</p>
                            <p class="text-xs text-[#94A3B8]">{{ transaction.time }}</p>
                        </td>
                        <td class="px-5 py-3.5 text-sm text-[#1F2937]">{{ transaction.customer }}</td>
                        <td class="px-5 py-3.5 text-sm text-[#64748B]">{{ transaction.cashier }}</td>
                        <td class="px-5 py-3.5"><span class="rounded px-2 py-0.5 text-xs" :style="{ background: resolveMethodStyle(transaction.method).bg, color: resolveMethodStyle(transaction.method).color }">{{ transaction.method }}</span></td>
                        <td class="px-5 py-3.5 text-sm font-semibold text-[#1F2937]">{{ transaction.amountText }}</td>
                        <td class="px-5 py-3.5"><span class="rounded-full px-2 py-0.5 text-xs" :style="{ background: resolveTransactionStatus(transaction.status).bg, color: resolveTransactionStatus(transaction.status).color }">{{ resolveTransactionStatus(transaction.status).label }}</span></td>
                    </tr>
                    <tr v-if="!normalizedRecentTransactions.length">
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-[#94A3B8]">No recent transactions.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
