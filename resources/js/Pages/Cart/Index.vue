<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    cartItems: Array
});

// --- State Management ---
const confirmingItemRemoval = ref(false);
const itemIdToRemove = ref(null);
const confirmingCheckout = ref(false);
const confirmingOrderFinal = ref(false); 

const checkoutForm = useForm({
    payment_method: 'cod',
    address: '',
    phone: '',
});

// --- Functions ---
const confirmItemRemoval = (id) => {
    itemIdToRemove.value = id;
    confirmingItemRemoval.value = true;
};

const closeModal = () => {
    confirmingItemRemoval.value = false;
    confirmingCheckout.value = false;
    confirmingOrderFinal.value = false;
    checkoutForm.clearErrors();
};

const removeItem = () => {
    router.delete(route('cart.destroy', itemIdToRemove.value), {
        onSuccess: () => closeModal(),
    });
};

const calculateTotal = () => {
    return props.cartItems.reduce((total, item) => total + parseFloat(item.listing.price), 0).toFixed(2);
};

const triggerFinalConfirmation = () => {
    // Basic validation check
    if (!checkoutForm.address || !checkoutForm.phone) {
        checkoutForm.post(route('checkout.store')); // Trigger server errors
        return;
    }
    confirmingCheckout.value = false;
    confirmingOrderFinal.value = true;
};

const processCheckout = () => {
    checkoutForm.post(route('checkout.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // 1. Reset state
            confirmingOrderFinal.value = false;
            checkoutForm.reset();
            
            // 2. Immediate automatic redirect
            router.visit(route('buyer.orders.index'));
        },
        onError: () => {
            confirmingOrderFinal.value = false;
            confirmingCheckout.value = true;
        }
    });
};
</script>

<template>
    <Head title="My Cart" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">🛒 My Shopping Cart</h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div v-if="cartItems.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-xl border">
                    <div class="p-6 space-y-4">
                        <div v-for="item in cartItems" :key="item.id" class="flex items-center justify-between border-b pb-4 last:border-0">
                            <div class="flex items-center gap-4">
                                <img v-if="item.listing.image" :src="'/storage/' + item.listing.image" class="w-16 h-16 object-cover rounded-lg" />
                                <div v-else class="w-16 h-16 bg-orange-50 rounded-lg flex items-center justify-center text-2xl">🐔</div>
                                <div>
                                    <h4 class="font-bold text-gray-900">{{ item.listing.breed }}</h4>
                                    <p class="text-sm text-gray-500">📍 {{ item.listing.location }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-green-600">₱{{ item.listing.price }}</p>
                                <button @click="confirmItemRemoval(item.id)" class="text-xs text-red-500 hover:underline">Remove</button>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t flex justify-between items-center">
                            <p class="text-lg font-bold">Total: ₱{{ calculateTotal() }}</p>
                            <button @click="confirmingCheckout = true" class="bg-orange-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-orange-700 transition">
                                Proceed to Checkout
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-20 bg-white rounded-xl border-2 border-dashed">
                    <p class="text-4xl mb-4">🐣</p>
                    <h3 class="text-lg font-bold">Your cart is empty</h3>
                    <Link :href="route('listings.index')" class="text-orange-600 font-bold hover:underline">Go find some chickens!</Link>
                </div>
            </div>
        </div>

        <Modal :show="confirmingItemRemoval" @close="closeModal" maxWidth="sm">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Remove from cart?</h2>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal"> Cancel </SecondaryButton>
                    <DangerButton class="ms-3" @click="removeItem"> Remove </DangerButton>
                </div>
            </div>
        </Modal>

        <Modal :show="confirmingCheckout" @close="closeModal" maxWidth="md">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 text-center">Shipping Details</h2>
                <form @submit.prevent="triggerFinalConfirmation" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Delivery Address</label>
                        <input v-model="checkoutForm.address" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500" :class="{'border-red-500': checkoutForm.errors.address}" placeholder="Street, City, Province" />
                        <div v-if="checkoutForm.errors.address" class="text-red-500 text-xs mt-1">{{ checkoutForm.errors.address }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input v-model="checkoutForm.phone" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500" :class="{'border-red-500': checkoutForm.errors.phone}" placeholder="09123456789" />
                        <div v-if="checkoutForm.errors.phone" class="text-red-500 text-xs mt-1">{{ checkoutForm.errors.phone }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                        <div class="grid grid-cols-1 gap-2">
                            <label v-for="method in ['cod', 'gcash', 'paymaya', 'bank_transfer', 'otc']" :key="method"
                                class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                :class="{'border-orange-500 bg-orange-50 ring-1 ring-orange-500': checkoutForm.payment_method === method}">
                                <input type="radio" v-model="checkoutForm.payment_method" :value="method" class="hidden" />
                                <span class="text-xl mr-3">{{ method === 'cod' ? '🚚' : (method === 'gcash' ? '🔵' : (method === 'bank_transfer' ? '🏦' : '🏪')) }}</span>
                                <span class="font-bold text-sm uppercase">{{ method.replace('_', ' ') }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-6 flex flex-col gap-2">
                        <PrimaryButton class="w-full justify-center py-3 bg-orange-600">Confirm Details</PrimaryButton>
                        <SecondaryButton class="w-full justify-center" @click="closeModal">Cancel</SecondaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="confirmingOrderFinal" @close="confirmingOrderFinal = false" maxWidth="sm">
            <div class="p-6 text-center">
                <h2 class="text-lg font-bold text-gray-900">Place this order?</h2>
                <p class="mt-2 text-sm text-gray-600">Total: <span class="font-bold text-green-600">₱{{ calculateTotal() }}</span></p>
                <div class="mt-6 flex flex-col gap-2">
                    <PrimaryButton class="w-full justify-center bg-orange-600 hover:bg-orange-700" @click="processCheckout" :disabled="checkoutForm.processing">
                        Yes, Place Order
                    </PrimaryButton>
                    <SecondaryButton class="w-full justify-center" @click="confirmingOrderFinal = false">No, Edit Details</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>