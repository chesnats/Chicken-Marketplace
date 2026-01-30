<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue'; // Import DangerButton for delete
import Modal from '@/Components/Modal.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ incomingOrders: Array });

// Modal States
const showingStatusModal = ref(false);
const showingDeleteModal = ref(false); // New state for delete
const selectedOrder = ref(null);
const pendingStatus = ref('');

// Status Update logic
const confirmStatusUpdate = (item, newStatus) => {
    selectedOrder.value = item;
    pendingStatus.value = newStatus;
    showingStatusModal.value = true;
};

// Deletion logic
const confirmDelete = (item) => {
    selectedOrder.value = item;
    showingDeleteModal.value = true;
};

const closeModal = () => {
    showingStatusModal.value = false;
    showingDeleteModal.value = false;
    selectedOrder.value = null;
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
</script>

<template>
    <Head title="Seller Orders" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">👨‍🌾 Incoming Orders</h2>
                <span class="bg-orange-100 text-orange-700 px-2 py-1 rounded-md text-xs font-bold">
                    {{ incomingOrders.length }} Total
                </span>
            </div>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border">
                    <!-- Desktop table (visible on sm and up) -->
                    <div class="hidden sm:block p-6 overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500">Buyer</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500">Chicken</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500">Address</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500">Payment</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500 text-center">Status</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500 text-center">Action</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500 text-center">Remove</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in incomingOrders" :key="item.id" class="hover:bg-gray-50">
                                    <td class="p-4">
                                        <p class="font-bold text-gray-900">{{ item.order?.user?.name || 'Unknown' }}</p>
                                        <p class="text-sm text-gray-600">📞 {{ item.order?.phone }}</p>
                                    </td>
                                    <td class="p-4">
                                        <p class="font-medium text-gray-900">{{ item.listing?.breed }}</p>
                                        <p class="text-xs text-green-600 font-bold">₱{{ item.price }}</p>
                                    </td>
                                    <td class="p-4">
                                        <p class="text-sm text-gray-700 italic">📍 {{ item.order?.address }}</p>
                                    </td>
                                    <td class="p-4 uppercase text-xs font-bold text-gray-500">
                                        {{ item.order?.payment_method }}
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
                                            ✅ Finished
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
                                        <span v-else class="text-gray-300 text-xs">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile cards (visible under sm) -->
                    <div class="sm:hidden p-4 space-y-4">
                        <div v-for="item in incomingOrders" :key="`mobile-${item.id}`" class="bg-white border rounded-lg p-4 shadow-sm">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-bold text-gray-900">{{ item.order?.user?.name || 'Unknown' }}</p>
                                    <p class="text-sm text-gray-600">📞 {{ item.order?.phone }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">{{ item.order?.payment_method }}</p>
                                    <p class="font-bold text-green-600">₱{{ item.price }}</p>
                                </div>
                            </div>

                            <div class="mt-3">
                                <p class="font-medium text-gray-900">{{ item.listing?.breed }}</p>
                                <p class="text-xs text-gray-500 italic mt-1">📍 {{ item.order?.address }}</p>
                            </div>

                            <div class="mt-3 flex items-center justify-between">
                                <div>
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

                                <div class="ms-3 flex-shrink-0">
                                    <button v-if="item.status === 'delivered'" @click="confirmDelete(item)" class="text-gray-400 hover:text-red-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="mt-3 grid gap-2">
                                <PrimaryButton 
                                    v-if="item.status === 'pending' || !item.status"
                                    @click="confirmStatusUpdate(item, 'accepted')"
                                    class="w-full bg-blue-600 hover:bg-blue-700"
                                >Accept</PrimaryButton>

                                <button 
                                    v-if="item.status === 'accepted'"
                                    @click="confirmStatusUpdate(item, 'on_delivery')"
                                    class="w-full bg-purple-600 text-white px-3 py-2 rounded-md font-bold text-sm uppercase transition hover:bg-purple-700"
                                >Ship Out</button>

                                <button 
                                    v-if="item.status === 'on_delivery'"
                                    @click="confirmStatusUpdate(item, 'delivered')"
                                    class="w-full bg-green-600 text-white px-3 py-2 rounded-md font-bold text-sm uppercase transition hover:bg-green-700"
                                >Mark Delivered</button>

                                <div v-if="item.status === 'delivered'" class="text-green-600 font-bold text-sm uppercase text-center">✅ Finished</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showingStatusModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Update Order Status</h2>
                <p class="mt-1 text-sm text-gray-600">
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
                <h2 class="text-lg font-medium text-gray-900">Delete Order Record</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Are you sure you want to remove the record for <strong>{{ selectedOrder?.listing?.breed }}</strong>? This action cannot be undone.
                </p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal"> Cancel </SecondaryButton>
                    <DangerButton class="ms-3" @click="proceedWithDelete"> Delete Record </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>