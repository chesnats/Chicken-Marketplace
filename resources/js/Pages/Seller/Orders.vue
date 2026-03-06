<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ incomingOrders: Array });

const showingStatusModal = ref(false);
const showingDeleteModal = ref(false);
const showingListingModal = ref(false);
const selectedOrder = ref(null);
const selectedListing = ref(null);
const pendingStatus = ref('');
const showingHistory = ref(false);
const searchQuery = ref('');
const statusFilter = ref('all');

const historyOrders = computed(() =>
    (props.incomingOrders || []).filter((item) => item.status === 'delivered')
);

const activeOrders = computed(() =>
    (props.incomingOrders || []).filter((item) => item.status !== 'delivered')
);

const visibleOrders = computed(() => (showingHistory.value ? historyOrders.value : activeOrders.value));
const filteredOrders = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return visibleOrders.value.filter((item) => {
        const status = item.status || 'pending';
        const matchesStatus = showingHistory.value || statusFilter.value === 'all' || status === statusFilter.value;

        if (!matchesStatus) return false;
        if (!query) return true;

        const searchable = [
            item.id,
            item.order?.user?.name,
            item.order?.phone,
            item.order?.address,
            item.order?.payment_method,
            item.listing?.breed,
            status.replace('_', ' ')
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return searchable.includes(query);
    });
});

const setView = (mode) => {
    showingHistory.value = mode === 'history';
    statusFilter.value = 'all';
};

const confirmStatusUpdate = (item, newStatus) => {
    selectedOrder.value = item;
    pendingStatus.value = newStatus;
    showingStatusModal.value = true;
};

const confirmDelete = (item) => {
    selectedOrder.value = item;
    showingDeleteModal.value = true;
};

const openListingOverview = (listing) => {
    if (!listing) return;
    selectedListing.value = listing;
    showingListingModal.value = true;
};

const closeModal = () => {
    showingStatusModal.value = false;
    showingDeleteModal.value = false;
    showingListingModal.value = false;
    selectedOrder.value = null;
    selectedListing.value = null;
    pendingStatus.value = '';
};

const proceedWithUpdate = () => {
    if (selectedOrder.value && pendingStatus.value) {
        router.post(route('seller.orders.updateStatus', selectedOrder.value.id), {
            status: pendingStatus.value
        }, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const proceedWithDelete = () => {
    if (selectedOrder.value) {
        router.delete(route('seller.orders.destroy', selectedOrder.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const formatPaymentMethod = (method) => {
    if (!method) return 'N/A';
    return method.replace('_', ' ').toUpperCase();
};

const getListingImage = (listing) => {
    return listing?.image ? `/storage/${listing.image}` : '/chickenlogo.png';
};
</script>

<template>
    <Head title="Seller Orders" />
    <AuthenticatedLayout>
        <template #nav_title_suffix>
            <span class="bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 px-2 py-1 rounded-md text-xs font-bold">
                {{ filteredOrders.length }} {{ showingHistory ? 'History' : 'Active' }}
            </span>
        </template>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center justify-end">
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search orders..."
                        class="w-full sm:w-56 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-100 placeholder:text-gray-400 px-3 py-1.5 focus:ring-2 focus:ring-orange-400 focus:border-orange-400"
                    />
                    <div class="inline-flex rounded-md border border-gray-300 dark:border-gray-600 overflow-hidden">
                        <button
                            type="button"
                            class="text-xs px-3 py-1.5 font-bold transition"
                            :class="!showingHistory ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'"
                            @click="setView('active')"
                        >
                            Active ({{ activeOrders.length }})
                        </button>
                        <button
                            type="button"
                            class="text-xs px-3 py-1.5 font-bold border-s border-gray-300 dark:border-gray-600 transition"
                            :class="showingHistory ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'"
                            @click="setView('history')"
                        >
                            History ({{ historyOrders.length }})
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div class="bg-gray-50 dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl border border-gray-200 dark:border-gray-700 h-[calc(100vh-14.5rem)] md:h-[calc(100vh-12.5rem)] p-2">
            <div class="w-full sm:px-6 lg:px-8 h-full">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl border border-gray-200 dark:border-gray-700 h-full">
                    <div class="hidden sm:block p-6 h-full border-gray-200 dark:border-gray-700 dark:bg-gray-800">
                        <div class="h-full overflow-y-auto overflow-x-auto pe-1 custom-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b bg-gray-50 dark:bg-gray-600 dark:border-gray-500">
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500 dark:text-white">Buyer</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500 dark:text-white">Chicken</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500 dark:text-white">Address</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500 dark:text-white">Payment</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500 dark:text-white text-center">Status</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500 dark:text-white text-center">Action</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500 text-center dark:text-white">Remove</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in filteredOrders" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="p-4">
                                        <p class="font-bold text-gray-900 dark:text-gray-100">{{ item.order?.user?.name || 'Unknown' }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ item.order?.phone }}</p>
                                    </td>
                                    <td class="p-4">
                                        <div
                                            v-if="item.listing"
                                            class="flex items-center gap-3 cursor-pointer"
                                            @click="openListingOverview(item.listing)"
                                        >
                                            <img :src="getListingImage(item.listing)" alt="Listing image" class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-700" />
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ item.listing?.breed }}</p>
                                                <p class="text-xs text-green-600 font-bold">PHP {{ item.price }}</p>
                                            </div>
                                        </div>
                                        <div v-else class="flex items-center gap-3">
                                            <img :src="getListingImage(item.listing)" alt="Listing image" class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-700" />
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ item.listing?.breed }}</p>
                                                <p class="text-xs text-green-600 font-bold">PHP {{ item.price }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <p class="text-sm text-gray-700 dark:text-gray-300 italic">{{ item.order?.address }}</p>
                                    </td>
                                    <td class="p-4 uppercase text-xs font-bold text-gray-500">
                                        {{ formatPaymentMethod(item.order?.payment_method) }}
                                    </td>
                                    <td class="p-4 text-center">
                                        <span
                                            :class="{
                                                'bg-orange-100 text-orange-700': item.status === 'pending' || !item.status,
                                                'bg-blue-100 text-blue-700': item.status === 'accepted',
                                                'bg-purple-100 text-purple-700': item.status === 'on_delivery',
                                                'bg-green-100 text-green-700': item.status === 'delivered'
                                            }"
                                            class="px-2 py-1 rounded-full font-bold uppercase text-[10px]"
                                        >
                                            {{ (item.status || 'pending').replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="grid gap-2">
                                            <PrimaryButton
                                                v-if="item.status === 'pending' || !item.status"
                                                @click="confirmStatusUpdate(item, 'accepted')"
                                                class="bg-blue-600 hover:bg-blue-700 !text-xs w-full justify-center"
                                            >
                                                Accept
                                            </PrimaryButton>
                                            <button
                                                v-if="item.status === 'accepted'"
                                                @click="confirmStatusUpdate(item, 'on_delivery')"
                                                class="bg-purple-600 text-white px-3 py-2 rounded-md font-bold text-[10px] uppercase w-full transition hover:bg-purple-700"
                                            >
                                                Ship Out
                                            </button>
                                            <button
                                                v-if="item.status === 'on_delivery'"
                                                @click="confirmStatusUpdate(item, 'delivered')"
                                                class="bg-green-600 text-white px-3 py-2 rounded-md font-bold text-[10px] uppercase w-full transition hover:bg-green-700"
                                            >
                                                Mark Delivered
                                            </button>
                                            <div v-if="item.status === 'delivered'" class="text-green-600 font-bold text-xs uppercase">
                                                Finished
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 text-center">
                                        <button
                                            v-if="item.status === 'delivered'"
                                            @click="confirmDelete(item)"
                                            class="text-gray-400 hover:text-red-600 transition"
                                            title="Delete History"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                        <span v-else class="text-gray-300 text-xs">-</span>
                                    </td>
                                </tr>
                                <tr v-if="filteredOrders.length === 0">
                                    <td colspan="7" class="p-6 text-center text-sm text-gray-500 dark:text-gray-300">
                                        {{ showingHistory ? 'No finished orders yet.' : 'No active incoming orders.' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>

                    <div class="sm:hidden p-4 space-y-4 h-full overflow-y-auto pe-1 custom-scrollbar">
                        <div v-for="item in filteredOrders" :key="`mobile-${item.id}`" class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-gray-100">{{ item.order?.user?.name || 'Unknown' }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ item.order?.phone }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500 dark:text-gray-300">{{ formatPaymentMethod(item.order?.payment_method) }}</p>
                                    <p class="font-bold text-green-600">PHP {{ item.price }}</p>
                                </div>
                            </div>

                            <div
                                v-if="item.listing"
                                class="mt-3 flex items-center gap-3 cursor-pointer"
                                @click="openListingOverview(item.listing)"
                            >
                                <img :src="getListingImage(item.listing)" alt="Listing image" class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-700" />
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ item.listing?.breed }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-300 italic">{{ item.order?.address }}</p>
                                </div>
                            </div>
                            <div v-else class="mt-3 flex items-center gap-3">
                                <img :src="getListingImage(item.listing)" alt="Listing image" class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-700" />
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ item.listing?.breed }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-300 italic">{{ item.order?.address }}</p>
                                </div>
                            </div>

                            <div class="mt-3">
                                <span
                                    :class="{
                                        'bg-orange-100 text-orange-700': item.status === 'pending' || !item.status,
                                        'bg-blue-100 text-blue-700': item.status === 'accepted',
                                        'bg-purple-100 text-purple-700': item.status === 'on_delivery',
                                        'bg-green-100 text-green-700': item.status === 'delivered'
                                    }"
                                    class="px-2 py-1 rounded-full font-bold uppercase text-[11px]"
                                >
                                    {{ (item.status || 'pending').replace('_', ' ') }}
                                </span>
                            </div>

                            <div class="mt-3 grid gap-2">
                                <PrimaryButton
                                    v-if="item.status === 'pending' || !item.status"
                                    @click="confirmStatusUpdate(item, 'accepted')"
                                    class="w-full bg-blue-600 hover:bg-blue-700"
                                >
                                    Accept
                                </PrimaryButton>
                                <button
                                    v-if="item.status === 'accepted'"
                                    @click="confirmStatusUpdate(item, 'on_delivery')"
                                    class="w-full bg-purple-600 text-white px-3 py-2 rounded-md font-bold text-sm uppercase transition hover:bg-purple-700"
                                >
                                    Ship Out
                                </button>
                                <button
                                    v-if="item.status === 'on_delivery'"
                                    @click="confirmStatusUpdate(item, 'delivered')"
                                    class="w-full bg-green-600 text-white px-3 py-2 rounded-md font-bold text-sm uppercase transition hover:bg-green-700"
                                >
                                    Mark Delivered
                                </button>
                                <button
                                    v-if="item.status === 'delivered'"
                                    @click="confirmDelete(item)"
                                    class="w-full border border-red-200 text-red-600 px-3 py-2 rounded-md font-bold text-sm uppercase transition hover:bg-red-50"
                                >
                                    Delete Record
                                </button>
                            </div>
                        </div>
                        <div v-if="filteredOrders.length === 0" class="text-center text-sm text-gray-500 dark:text-gray-300 py-6">
                            {{ showingHistory ? 'No finished orders yet.' : 'No active incoming orders.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showingStatusModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Update Order Status</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Are you sure you want to change the status to
                    <span class="font-bold uppercase text-orange-600">{{ pendingStatus.replace('_', ' ') }}</span>?
                </p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal"> Cancel </SecondaryButton>
                    <PrimaryButton class="ms-3" @click="proceedWithUpdate"> Confirm Update </PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showingDeleteModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Delete Order Record</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Are you sure you want to remove the record for <strong>{{ selectedOrder?.listing?.breed }}</strong>? This action cannot be undone.
                </p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal"> Cancel </SecondaryButton>
                    <DangerButton class="ms-3" @click="proceedWithDelete"> Delete Record </DangerButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showingListingModal" @close="closeModal" maxWidth="lg">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">Listing Overview</h2>
                <div v-if="selectedListing" class="mt-4 grid gap-4 sm:grid-cols-2">
                    <img :src="getListingImage(selectedListing)" alt="Listing image" class="w-full h-48 object-cover rounded-lg border border-gray-200 dark:border-gray-700" />
                    <div class="space-y-2 text-sm">
                        <p><span class="font-bold text-gray-700 dark:text-gray-200">Breed:</span> {{ selectedListing.breed || 'N/A' }}</p>
                        <p><span class="font-bold text-gray-700 dark:text-gray-200">Price:</span> PHP {{ selectedListing.price ?? '0.00' }}</p>
                        <p><span class="font-bold text-gray-700 dark:text-gray-200">Location:</span> {{ selectedListing.location || 'N/A' }}</p>
                        <p><span class="font-bold text-gray-700 dark:text-gray-200">Age:</span> {{ selectedListing.age || 'N/A' }}</p>
                        <p class="text-gray-600 dark:text-gray-300"><span class="font-bold text-gray-700 dark:text-gray-200">Description:</span> {{ selectedListing.description || 'No description provided.' }}</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Close</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>


