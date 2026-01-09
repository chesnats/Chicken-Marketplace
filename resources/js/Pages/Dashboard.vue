<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    userRole: String,
    stats: Object
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold leading-tight text-gray-800">
                👋 Welcome back, {{ $page.props.auth.user.name }}!
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <p class="text-sm font-medium text-gray-500 uppercase">Account Type</p>
                        <p class="text-2xl font-bold text-orange-600 capitalize">{{ userRole }}</p>
                    </div>

                    <div v-if="userRole === 'seller'" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <p class="text-sm font-medium text-gray-500 uppercase">My Active Listings</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.listings_count }} Chickens</p>
                    </div>

                    <div v-if="userRole === 'buyer'" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <p class="text-sm font-medium text-gray-500 uppercase">Items in Cart</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.cart_count }} Items</p>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <p class="text-sm font-medium text-gray-500 uppercase">Member Since</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ new Date($page.props.auth.user.created_at).toLocaleDateString() }}
                        </p>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                    <div class="p-8 text-center">
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Ready to get started?</h3>
                        <p class="text-gray-500 mb-6">
                            {{ userRole === 'seller' 
                                ? 'Manage your poultry inventory and respond to buyer messages.' 
                                : 'Browse high-quality breeds from verified sellers in your area.' 
                            }}
                        </p>
                        
                        <div class="flex justify-center gap-4">
                            <Link 
                                :href="route('listings.index')" 
                                class="bg-orange-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-orange-700 transition"
                            >
                                {{ userRole === 'seller' ? 'Manage My Listings' : 'Browse Marketplace' }}
                            </Link>
                            
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-orange-50 p-6 rounded-xl border border-orange-100">
                        <h4 class="font-bold text-orange-800 mb-2">💡 Quick Tip</h4>
                        <p class="text-sm text-orange-700">
                            {{ userRole === 'seller' 
                                ? 'Listings with clear photos of the chicken’s face and feathers sell 3x faster!' 
                                : 'Always check the "Age" of the chicken to ensure it fits your coop requirements.' 
                            }}
                        </p>
                    </div>
                    <div class="bg-blue-50 p-6 rounded-xl border border-blue-100">
                        <h4 class="font-bold text-blue-800 mb-2">📢 Marketplace Update</h4>
                        <p class="text-sm text-blue-700">New Brahmas and Rhode Island Reds were just listed in your area. Check them out!</p>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>