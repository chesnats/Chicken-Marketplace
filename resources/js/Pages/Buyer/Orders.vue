<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue'; // Add this import
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ myOrders: Array });

// Modal State
const showingDeleteModal = ref(false);
const selectedOrder = ref(null);

const confirmRemove = (item) => {
    selectedOrder.value = item;
    showingDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showingDeleteModal.value = false;
    selectedOrder.value = null;
};

// NEW: Function to mark order as received
const markAsReceived = (id) => {
    if (confirm('Confirm that you have received this order?')) {
        router.post(route('buyer.orders.updateStatus', id), {
            status: 'delivered'
        }, {
            preserveScroll: true
        });
    }
};

const proceedWithDelete = () => {
    if (selectedOrder.value) {
        router.delete(route('buyer.orders.destroy', selectedOrder.value.id), {
            preserveScroll: true,
            onSuccess: () => closeDeleteModal(),
        });
    }
};
</script>

<template>
    <Head title="My Purchases" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">📦 My Purchases</h2>
                <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded-md text-xs font-bold uppercase">
                    {{ myOrders.length }} Total
                </span>
            </div>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border">
                    <div class="p-6 overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500">Chicken</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500">Seller</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500">Price</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500">Status</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500">Action</th>
                                    <th class="p-4 text-xs font-bold uppercase text-gray-500 text-center">Remove</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in myOrders" :key="item.id" class="hover:bg-gray-50 transition">
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center text-xl">🐔</div>
                                            <div>
                                                <p class="font-medium text-gray-900">{{ item.listing?.breed }}</p>
                                                <p class="text-xs text-gray-500">Order #{{ item.id }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <p class="text-sm font-bold">{{ item.listing?.user?.name }}</p>
                                    </td>
                                    <td class="p-4 font-bold text-green-600">₱{{ item.price }}</td>
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
                                        <PrimaryButton 
                                            v-if="item.status === 'on_delivery'"
                                            @click="markAsReceived(item.id)"
                                            class="!text-[10px] !py-1 !px-2 bg-green-600 hover:bg-green-700"
                                        >
                                            Order Received
                                        </PrimaryButton>

                                        <span v-else-if="item.status === 'delivered'" class="text-green-600 font-bold text-xs uppercase">
                                            ✅ Finished
                                        </span>
                                        <span v-else class="text-gray-400 text-[10px] uppercase font-bold italic">
                                            In Progress
                                        </span>
                                    </td>

                                    <td class="p-4 text-center">
                                        <button 
                                            v-if="item.status === 'delivered'"
                                            @click="confirmRemove(item)"
                                            class="text-gray-400 hover:text-red-600 transition"
                                            title="Delete History"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mx-auto inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                        <span v-else class="text-gray-300 text-xs">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showingDeleteModal" @close="closeDeleteModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Remove from history?</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Are you sure you want to remove the record for <strong>{{ selectedOrder?.listing?.breed }}</strong>? This action cannot be undone.
                </p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeDeleteModal"> Cancel </SecondaryButton>
                    <DangerButton class="ms-3" @click="proceedWithDelete"> Confirm Remove </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>