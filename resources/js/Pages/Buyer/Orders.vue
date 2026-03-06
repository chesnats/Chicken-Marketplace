<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ myOrders: Array });

const showingDeleteModal = ref(false);
const showingReceivedModal = ref(false);
const showingListingModal = ref(false);
const selectedOrder = ref(null);
const selectedListing = ref(null);
const showingHistory = ref(false);
const searchQuery = ref('');
const statusFilter = ref('all');

const historyOrders = computed(() =>
    (props.myOrders || []).filter((item) => item.status === 'delivered')
);

const activeOrders = computed(() =>
    (props.myOrders || []).filter((item) => item.status !== 'delivered')
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
            item.listing?.breed,
            item.listing?.user?.name,
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

const confirmRemove = (item) => {
    selectedOrder.value = item;
    showingDeleteModal.value = true;
};

const confirmReceived = (item) => {
    selectedOrder.value = item;
    showingReceivedModal.value = true;
};

const openListingOverview = (item) => {
    if (!item?.listing) return;
    selectedListing.value = item.listing;
    showingListingModal.value = true;
};

const closeModals = () => {
    showingDeleteModal.value = false;
    showingReceivedModal.value = false;
    showingListingModal.value = false;
    selectedOrder.value = null;
    selectedListing.value = null;
};

const proceedWithMarkReceived = () => {
    if (selectedOrder.value) {
        router.post(route('buyer.orders.updateStatus', selectedOrder.value.id), {
            status: 'delivered'
        }, {
            preserveScroll: true,
            onSuccess: () => closeModals(),
        });
    }
};

const proceedWithDelete = () => {
    if (selectedOrder.value) {
        router.delete(route('buyer.orders.destroy', selectedOrder.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModals(),
        });
    }
};

const getListingImage = (listing) => {
    return listing?.image ? `/storage/${listing.image}` : '/chickenlogo.png';
};
</script>

<template>
    <Head title="My Purchases" />
    <AuthenticatedLayout>
        <template #nav_title_suffix>
            <span class="bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 px-2 py-1 rounded-md text-xs font-bold uppercase">
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
                        class="w-full sm:w-52 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-100 placeholder:text-gray-400 px-3 py-1.5 focus:ring-2 focus:ring-orange-400 focus:border-orange-400"
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

        <div class="bg-gray-50 dark:bg-gray-800 h-[calc(100vh-14.5rem)] md:h-[calc(100vh-12.5rem)] overflow-hidden shadow-sm sm:rounded-xl border border-gray-200 dark:border-gray-700 p-2">
            <div class="w-full sm:px-6 lg:px-8 h-full">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl border border-gray-200 dark:border-gray-700 h-full">
                    <div class="hidden sm:block p-6 h-full">
                        <div class="h-full overflow-y-auto overflow-x-auto pe-1 custom-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b bg-gray-50 dark:bg-gray-600 dark:border-gray-500">
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500 dark:text-white">Chicken</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500 dark:text-white">Seller</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500 dark:text-white">Price</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500 dark:text-white">Status</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500 dark:text-white">Action</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500 text-center dark:text-white">Remove</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in filteredOrders" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    <td class="p-4">
                                        <div
                                            class="flex items-center gap-3 cursor-pointer"
                                            v-if="item.listing"
                                            @click="openListingOverview(item)"
                                        >
                                            <img :src="getListingImage(item.listing)" alt="Listing image" class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-700" />
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ item.listing?.breed }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-300">Order #{{ item.id }}</p>
                                            </div>
                                        </div>
                                        <div v-else class="flex items-center gap-3">
                                            <img :src="getListingImage(item.listing)" alt="Listing image" class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-700" />
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ item.listing?.breed }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-300">Order #{{ item.id }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ item.listing?.user?.name }}</p>
                                    </td>
                                    <td class="p-4 font-bold text-green-600">PHP {{ item.price }}</td>
                                    <td class="p-4">
                                        <span
                                            :class="{
                                                'bg-orange-100 text-orange-700': item.status === 'pending',
                                                'bg-blue-100 text-blue-700': item.status === 'accepted',
                                                'bg-purple-100 text-purple-700': item.status === 'on_delivery',
                                                'bg-green-100 text-green-700': item.status === 'delivered'
                                            }"
                                            class="px-3 py-1 rounded-full font-bold uppercase text-[10px]"
                                        >
                                            {{ (item.status || 'pending').replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex flex-col gap-2">
                                            <PrimaryButton
                                                v-if="item.status === 'on_delivery'"
                                                @click="confirmReceived(item)"
                                                class="!text-[10px] !py-1 !px-2 bg-green-600 hover:bg-green-700"
                                            >
                                                Order Received
                                            </PrimaryButton>

                                            <span v-else-if="item.status === 'delivered'" class="text-green-600 font-bold text-xs uppercase">
                                                Finished
                                            </span>
                                            <span v-else class="text-gray-400 text-[10px] uppercase font-bold italic">
                                                In Progress
                                            </span>
                                        </div>
                                    </td>

                                    <td class="p-4 text-center">
                                        <button
                                            v-if="!item.listing || item.status === 'delivered' || item.status === 'pending'"
                                            @click="confirmRemove(item)"
                                            class="transition"
                                            :class="item.status === 'pending' ? 'text-orange-400 hover:text-orange-600' : 'text-gray-400 hover:text-red-600'"
                                            :title="!item.listing ? 'Listing Deleted' : (item.status === 'pending' ? 'Cancel Order' : 'Delete History')"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mx-auto inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                        <span v-else class="text-gray-300 text-xs">-</span>
                                    </td>
                                </tr>
                                <tr v-if="filteredOrders.length === 0">
                                    <td colspan="6" class="p-6 text-center text-sm text-gray-500 dark:text-gray-300">
                                        {{ showingHistory ? 'No finished orders yet.' : 'No active orders.' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>

                    <div class="sm:hidden p-4 space-y-4 h-full overflow-y-auto pe-1 custom-scrollbar">
                        <div v-for="item in filteredOrders" :key="`mobile-${item.id}`" class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg p-4 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div
                                    class="flex items-center gap-3 cursor-pointer"
                                    v-if="item.listing"
                                    @click="openListingOverview(item)"
                                >
                                    <img :src="getListingImage(item.listing)" alt="Listing image" class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-700" />
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ item.listing?.breed }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-300">Order #{{ item.id }}</p>
                                    </div>
                                </div>
                                <div v-else class="flex items-center gap-3">
                                    <img :src="getListingImage(item.listing)" alt="Listing image" class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-700" />
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ item.listing?.breed }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-300">Order #{{ item.id }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ item.listing?.user?.name }}</p>
                                    <p class="font-bold text-green-600">PHP {{ item.price }}</p>
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-between">
                                <div>
                                    <span
                                        :class="{
                                            'bg-orange-100 text-orange-700': item.status === 'pending',
                                            'bg-blue-100 text-blue-700': item.status === 'accepted',
                                            'bg-purple-100 text-purple-700': item.status === 'on_delivery',
                                            'bg-green-100 text-green-700': item.status === 'delivered'
                                        }"
                                        class="px-3 py-1 rounded-full font-bold uppercase text-[11px]"
                                    >
                                        {{ (item.status || 'pending').replace('_', ' ') }}
                                    </span>
                                </div>

                                <div class="ms-3">
                                    <button
                                        v-if="!item.listing || item.status === 'delivered' || item.status === 'pending'"
                                        @click="confirmRemove(item)"
                                        class="transition"
                                        :class="item.status === 'pending' ? 'text-orange-400 hover:text-orange-600' : 'text-gray-400 hover:text-red-600'"
                                        :title="!item.listing ? 'Listing Deleted' : (item.status === 'pending' ? 'Cancel Order' : 'Delete History')"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="mt-3 grid gap-2">
                                <PrimaryButton
                                    v-if="item.status === 'on_delivery'"
                                    @click="confirmReceived(item)"
                                    class="w-full bg-green-600 hover:bg-green-700"
                                >
                                    Order Received
                                </PrimaryButton>

                                <div v-else-if="item.status === 'delivered'" class="text-green-600 font-bold text-sm uppercase text-center">Finished</div>

                                <div v-else class="text-gray-400 text-sm uppercase font-bold italic text-center">In Progress</div>
                            </div>
                        </div>
                        <div v-if="filteredOrders.length === 0" class="text-center text-sm text-gray-500 dark:text-gray-300 py-6">
                            {{ showingHistory ? 'No finished orders yet.' : 'No active orders.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showingDeleteModal" @close="closeModals">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ !selectedOrder?.listing ? 'Remove Unavailable Record?' : (selectedOrder?.status === 'pending' ? 'Cancel Purchase?' : 'Remove from history?') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    <span v-if="!selectedOrder?.listing">
                        This listing is no longer available in the marketplace. You can remove this record from your purchase history.
                    </span>
                    <span v-else-if="selectedOrder?.status === 'pending'">
                        Are you sure you want to cancel your order for <strong>{{ selectedOrder?.listing?.breed }}</strong>? This will notify the seller and remove the item from your purchases.
                    </span>
                    <span v-else>
                        Are you sure you want to remove the record for <strong>{{ selectedOrder?.listing?.breed }}</strong>? This action cannot be undone.
                    </span>
                </p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModals"> Back </SecondaryButton>
                    <DangerButton class="ms-3" @click="proceedWithDelete">
                        {{ selectedOrder?.status === 'pending' ? 'Yes, Cancel Order' : 'Confirm Remove' }}
                    </DangerButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showingReceivedModal" @close="closeModals">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Confirm Receipt</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Have you successfully received your <strong>{{ selectedOrder?.listing?.breed }}</strong> from the seller?
                    This will mark the order as finished.
                </p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModals"> Not Yet </SecondaryButton>
                    <PrimaryButton class="ms-3 bg-green-600 hover:bg-green-700" @click="proceedWithMarkReceived">
                        Yes, I Received It
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showingListingModal" @close="closeModals" maxWidth="lg">
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
                    <SecondaryButton @click="closeModals">Close</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>


