<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    userRole: String,
    stats: Object,
    charts: Object,
    insights: Object,
});

const monthlyLabels = computed(() => props.charts?.monthly?.labels ?? []);
const monthlyValues = computed(() => props.charts?.monthly?.values ?? []);
const maxMonthlyValue = computed(() => Math.max(...monthlyValues.value, 1));

const monthlyBars = computed(() =>
    monthlyValues.value.map((value, index) => ({
        label: monthlyLabels.value[index] ?? '',
        value,
        height: `${Math.max((value / maxMonthlyValue.value) * 100, value > 0 ? 10 : 4)}%`,
    })),
);

const statusLabels = computed(() => props.charts?.order_status?.labels ?? []);
const statusValues = computed(() => props.charts?.order_status?.values ?? []);
const totalStatus = computed(() => statusValues.value.reduce((sum, value) => sum + value, 0));
const statusPalette = ['#f97316', '#1d4ed8', '#16a34a', '#f59e0b', '#6b7280', '#dc2626'];

const statusSegments = computed(() =>
    statusValues.value.map((value, index) => ({
        label: statusLabels.value[index] ?? `Status ${index + 1}`,
        value,
        color: statusPalette[index % statusPalette.length],
        width: totalStatus.value > 0 ? `${(value / totalStatus.value) * 100}%` : '0%',
    })),
);

const recentBuyerActivity = computed(() => props.insights?.recent_buyer_activity ?? {});
const recentBuyerActivityList = computed(() => (props.insights?.recent_buyer_activity_list ?? []).slice(0, 5));
const recentOrders = computed(() => props.insights?.recent_orders ?? []);
const recentOrdersLimited = computed(() => recentOrders.value.slice(0, 5));
const totalIncomeDisplay = computed(() =>
    Number(props.stats?.total_income ?? 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <div class="w-full space-y-6 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm font-medium uppercase text-gray-500 dark:text-gray-400">Account Type</p>
                        <p class="text-2xl font-bold capitalize text-orange-600">{{ userRole }}</p>
                    </div>

                    <div v-if="userRole === 'seller'" class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm font-medium uppercase text-gray-500 dark:text-gray-400">My Active Listings</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.listings_count }} Chickens</p>
                    </div>

                    <div v-if="userRole === 'buyer'" class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm font-medium uppercase text-gray-500 dark:text-gray-400">Items in Cart</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.cart_count }} Items</p>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm font-medium uppercase text-gray-500 dark:text-gray-400">Member Since</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ new Date($page.props.auth.user.created_at).toLocaleDateString() }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm font-medium uppercase text-gray-500 dark:text-gray-400">
                            {{ userRole === 'seller' ? 'Total Income' : 'Total Spending' }}
                        </p>
                        <p class="text-2xl font-bold text-emerald-600">${{ totalIncomeDisplay }}</p>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm font-medium uppercase text-gray-500 dark:text-gray-400">Registered Buyers</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.registered_buyers ?? 0 }}</p>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm font-medium uppercase text-gray-500 dark:text-gray-400">
                            {{ userRole === 'seller' ? 'Items Sold' : 'Items Bought' }}
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.items_sold ?? 0 }}</p>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="p-8 text-center">
                        <h3 class="mb-2 text-lg font-bold text-gray-900 dark:text-gray-100">Ready to get started?</h3>
                        <p class="mb-6 text-gray-500 dark:text-gray-400">
                            {{ userRole === 'seller'
                                ? 'Manage your poultry inventory and respond to buyer messages.'
                                : 'Browse high-quality breeds from verified sellers in your area.'
                            }}
                        </p>

                        <div class="flex justify-center gap-4">
                            <Link
                                :href="route('listings.index')"
                                class="rounded-lg bg-orange-600 px-8 py-3 font-bold text-white transition hover:bg-orange-700"
                            >
                                {{ userRole === 'seller' ? 'Manage My Listings' : 'Browse Marketplace' }}
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h4 class="mb-1 font-bold text-gray-900 dark:text-gray-100">
                            {{ userRole === 'seller' ? 'Listings Added (6 Months)' : 'Purchases Made (6 Months)' }}
                        </h4>
                        <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">Monthly activity trend</p>

                        <div class="flex h-52 items-end gap-3 border-b border-gray-200 pb-2 dark:border-gray-700">
                            <div
                                v-for="bar in monthlyBars"
                                :key="bar.label"
                                class="flex h-full flex-1 flex-col items-center justify-end"
                            >
                                <span class="mb-1 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ bar.value }}</span>
                                <div
                                    class="w-full max-w-[38px] rounded-t-md bg-orange-500 transition-all duration-300"
                                    :style="{ height: bar.height }"
                                />
                                <span class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ bar.label }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h4 class="mb-1 font-bold text-gray-900 dark:text-gray-100">
                            {{ userRole === 'seller' ? 'Incoming Order Status' : 'My Order Status' }}
                        </h4>
                        <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">Distribution by current order stage</p>

                        <div class="mb-5 flex h-4 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                            <div
                                v-for="segment in statusSegments"
                                :key="segment.label"
                                :style="{ width: segment.width, backgroundColor: segment.color }"
                                class="h-full"
                            />
                        </div>

                        <div v-if="statusSegments.length > 0" class="space-y-3">
                            <div
                                v-for="segment in statusSegments"
                                :key="`legend-${segment.label}`"
                                class="flex items-center justify-between text-sm"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="inline-block h-3 w-3 rounded-full" :style="{ backgroundColor: segment.color }" />
                                    <span class="text-gray-700 dark:text-gray-300">{{ segment.label }}</span>
                                </div>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ segment.value }} ({{ totalStatus > 0 ? Math.round((segment.value / totalStatus) * 100) : 0 }}%)
                                </span>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-500 dark:text-gray-400">No order data yet.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="rounded-xl border border-orange-100 bg-orange-50 p-6 dark:border-orange-900/60 dark:bg-orange-950/30">
                        <h4 class="mb-2 font-bold text-orange-800 dark:text-orange-300">Recent Buyer Activity</h4>
                        <div class="space-y-1 text-sm text-orange-700 dark:text-orange-200">
                            <p>
                                Orders in last 7 days:
                                <span class="font-bold">{{ recentBuyerActivity.orders_last_7_days ?? 0 }}</span>
                            </p>
                            <p v-if="userRole === 'seller'">
                                Unique buyers in last 7 days:
                                <span class="font-bold">{{ recentBuyerActivity.unique_buyers_last_7_days ?? 0 }}</span>
                            </p>
                            <p v-else>
                                Keep checking status updates from sellers in your purchases page.
                            </p>
                        </div>
                        <div v-if="recentBuyerActivityList.length > 0" class="mt-3 max-h-64 space-y-2 overflow-y-auto pe-1 scrollbar-hidden">
                            <div
                                v-for="activity in recentBuyerActivityList"
                                :key="`activity-${activity.id}`"
                                class="rounded-md bg-white/70 px-3 py-2 text-sm text-orange-900 dark:bg-orange-900/30 dark:text-orange-100"
                            >
                                <p class="font-semibold">{{ activity.breed }}</p>
                                <p class="text-xs opacity-90">
                                    {{ userRole === 'seller' ? `Buyer: ${activity.buyer_name}` : 'Activity by you' }}
                                    | {{ activity.status }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-6 dark:border-blue-900/60 dark:bg-blue-950/30">
                        <h4 class="mb-2 font-bold text-blue-800 dark:text-blue-300">Recent Orders</h4>
                        <div v-if="recentOrdersLimited.length > 0" class="max-h-64 space-y-2 overflow-y-auto pe-1 scrollbar-hidden">
                            <div
                                v-for="order in recentOrdersLimited"
                                :key="order.id"
                                class="rounded-md bg-white/70 px-3 py-2 text-sm text-blue-900 dark:bg-blue-900/30 dark:text-blue-100"
                            >
                                <p class="font-semibold">{{ order.breed }} - ${{ order.price }}</p>
                                <p class="text-xs opacity-90">
                                    {{ userRole === 'seller' ? `Buyer: ${order.buyer_name}` : 'Order placed by you' }}
                                    | {{ order.status }}
                                </p>
                            </div>
                        </div>
                        <p v-else class="text-sm text-blue-700 dark:text-blue-200">
                            No sales yet. Your newest orders will appear here once buyers checkout.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
