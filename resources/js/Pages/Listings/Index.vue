<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
// 1. Added Link to the imports
import { Head, useForm, router, Link } from '@inertiajs/vue3'; 
import { computed, ref, watch } from 'vue';

const showingDeleteModal = ref(false);
const listingToDelete = ref(null);
const imagePreview = ref(null);
const showImageModal = ref(false);
const activeImage = ref(null);
const fileInput = ref(null);

const props = defineProps({ 
    listings: Object,
    canPost: Boolean,
    filters: Object,
    userRole: String, 
    cartCount: Number
});

const listingItems = computed(() => props.listings?.data ?? []);
const paginationLinks = computed(() => props.listings?.links ?? []);

const openImage = (image) => {
    activeImage.value = image;
    showImageModal.value = true;
};

const closeImage = () => {
    showImageModal.value = false;
    activeImage.value = null;
};

const searchTerm = ref(props.filters?.search || '');
watch(searchTerm, (value) => {
    router.get(route('listings.index'), { search: value }, { 
        preserveState: true, 
        replace: true 
    });
});

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    form.image = file;

    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
    }

    imagePreview.value = URL.createObjectURL(file);
};

const removeImage = () => {
    // clear form data
    form.image = null;

    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
        imagePreview.value = null;
    }

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const confirmDeleteListing = (chicken) => {
    listingToDelete.value = chicken;
    showingDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showingDeleteModal.value = false;
    listingToDelete.value = null;
};

const proceedWithDelete = () => {
    if (listingToDelete.value) {
        router.delete(route('listings.destroy', listingToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => closeDeleteModal(),
        });
    }
};

const form = useForm({
    breed: '',
    price: '',
    age_weeks: '',
    location: '',
    description: '',
    image: null,
});

const submit = () => {
    form.post(route('listings.store'), {
        onSuccess: () => {
            form.reset();
            removeImage();
        },
    });
};

// --- RESTORED: ADD TO CART FUNCTION ---
const addToCart = (id) => {
    router.post(route('cart.store'), { 
        listing_id: id 
    }, {
        preserveScroll: true, // Stops the page from jumping to top after adding
        onSuccess: () => {
            // The cart count in AuthenticatedLayout updates automatically 
            // via the HandleInertiaRequests middleware
            console.log("Chicken added to cart!");
        },
        onError: (errors) => {
            console.error("Failed to add to cart:", errors);
        }
    });
};

const showingMessageModal = ref(false);
const messageForm = useForm({
    receiver_id: null,
    listing_id: null,
    content: '',
});

const openChat = (chicken) => {
    messageForm.receiver_id = chicken.user_id;
    messageForm.listing_id = chicken.id; // <--- Make sure this isn't messageForm.id
    showingMessageModal.value = true;
};

const sendMessage = () => {
    messageForm.post(route('messages.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showingMessageModal.value = false;
            messageForm.reset();
            router.visit(route('listings.index'), {
                preserveScroll: false,
            });
        },
        onError: (errors) => {
            // This will tell you EXACTLY which field is failing validation
            console.log("Validation Failed:", errors);
        }
    });
};
// Function to delete the listing
const deleteListing = (id) => confirm('Delete this listing?') && router.delete(route('listings.destroy', id));

// Function to toggle availability (Mark as Unavailable/Available)
const toggleAvailability = (chicken) => router.patch(route('listings.update', chicken.id), { is_available: !chicken.is_available });
</script>

<template>
    <Head title="Marketplace" />
 
    <AuthenticatedLayout>
    <template #header>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            
            <!-- Title + Role Badge -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:gap-3">
                <h2 class="font-semibold text-lg sm:text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    Chicken Marketplace
                </h2>

                <span
                    v-if="userRole === 'buyer'"
                    class="w-fit text-xs bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300 px-2 py-1 rounded-full uppercase tracking-wider"
                >
                    Buyer Mode
                </span>

                <span
                    v-if="userRole === 'seller'"
                    class="w-fit text-xs bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-300 px-2 py-1 rounded-full uppercase tracking-wider"
                >
                    Seller Mode
                </span>
            </div>

            <!-- Search -->
            <div class="w-full sm:w-64">
                <input
                    v-model="searchTerm"
                    type="text"
                    placeholder="Search breeds..."
                    class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-orange-500 focus:border-orange-500 text-sm"
                >
            </div>
        </div>
    </template>

        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <div v-if="userRole === 'seller'" class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">List a Chicken for Sale</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-300">Provide details and a photo to reach local buyers.</p>
                    </div>
                    
                    <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-1">Breed</label>
                            <input v-model="form.breed" type="text" placeholder="e.g. Brahma" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-orange-500">
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-1">Price ($)</label>
                            <input v-model="form.price" type="number" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-orange-500">
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-1">Age (Weeks)</label>
                            <input v-model="form.age_weeks" type="number" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-orange-500">
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-1">Location</label>
                            <input v-model="form.location" type="text" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-orange-500">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-1">
                                Chicken Photo
                            </label>

                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/*"
                            @change="handleImageChange"
                            class="w-full text-sm text-gray-900 dark:text-gray-100
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-full file:border-0
                                file:text-sm file:font-semibold
                                file:bg-orange-50 file:text-orange-700
                                hover:file:bg-orange-100 dark:border-gray-600 bg-white dark:bg-gray-800 rounded-lg shadow-sm focus:ring-orange-500 focus:border-orange-500"
                                
                        />

                            <!-- Image Preview -->
                            <div v-if="imagePreview" class="mt-4">
                                <p class="text-xs text-gray-500 mb-2">Preview</p>
                                <div class="w-full max-w-xs rounded-lg overflow-hidden border border-gray-200 shadow-sm">
                                    <img
                                        :src="imagePreview"
                                        alt="Chicken preview"
                                        class="w-full h-full object-fit"
                                    />
                                </div>
                            </div>
                            <button
                                v-if="imagePreview"
                                type="button"
                                @click="removeImage"
                                class="text-xs text-red-500 hover:underline mt-2"
                            >
                                Remove image
                            </button>
                       </div>
                       <div class="md:col-span-4">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-1">Description</label>
                            <textarea 
                                v-model="form.description" 
                                rows="2" 
                                placeholder="Describe health, behavior..." 
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-500 focus:border-orange-500 text-gray-900 dark:text-gray-100 dark:bg-gray-800 dark:border-gray-600"
                                :class="{'border-red-500': form.errors.description}"
                            ></textarea>
                            
                            <div v-if="form.errors.description" class="text-red-500 text-xs mt-1 font-semibold">
                                {{ form.errors.description }}
                            </div>
                        </div>
                        <div class="md:col-span-4 flex justify-end">
                            <button type="submit" :disabled="form.processing" class="bg-orange-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-orange-700 transition">
                                🚀 Post Listing
                            </button>
                        </div>
                    </form>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <div v-for="chicken in listingItems" :key="chicken.id" class="bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-300 border border-gray-100 dark:border-gray-700 flex flex-col">
                        <div class="h-48 w-full bg-gray-200 dark:bg-gray-700 relative"> <img v-if="chicken.image" :src="'/storage/' + chicken.image" @click="openImage(chicken.image)" class="w-full h-full object-cover transition-transform duration-300 hover:scale-105" />
                                <div v-else class="flex items-center justify-center h-full text-gray-400 bg-orange-50 text-4xl">🐔</div>                     
                                <div v-if="!chicken.is_available" class="absolute top-3 left-3 bg-red-600 text-white text-[10px] font-bold px-2 rounded uppercase shadow-md z-10">
                                    Not Available
                                </div>
                            </div>

                        <div class="p-5 flex flex-col flex-1">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="text-xl font-black text-gray-900 dark:text-gray-100 truncate mr-2">{{ chicken.breed }}</h4>
                                <span class="bg-green-100 text-green-700 text-lg font-bold px-3 py-1 rounded-full border border-green-200">${{ chicken.price }}</span>
                            </div>
                            
                            <div class="flex items-center gap-2 text-gray-500 text-xs font-medium mb-4">
                                <span class="bg-gray-100 px-2 py-1 rounded">📍 {{ chicken.location }}</span>
                                <span class="bg-gray-100 px-2 py-1 rounded">⏳ {{ chicken.age_weeks }} Weeks</span>
                            </div>

                            <p class="text-gray-600 dark:text-gray-300 text-sm italic mb-6 flex-1 line-clamp-2">"{{ chicken.description }}"</p>

                            <div class="pt-4 border-t border-gray-100 dark:border-gray-700 space-y-2">
                                
                                <template v-if="userRole === 'buyer'">
                                <button 
                                    @click="addToCart(chicken.id)" 
                                    :disabled="!chicken.is_available"
                                    class="w-full py-2 rounded-lg font-bold transition flex items-center justify-center gap-2"
                                    :class="chicken.is_available 
                                        ? 'bg-blue-600 text-white hover:bg-blue-700' 
                                        : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
                                >
                                    <span v-if="chicken.is_available">🛒 Add to Cart</span>
                                    <span v-else>🚫 Out of Stock</span>
                                </button>

                                <button @click="openChat(chicken)" class="w-full border border-orange-600 text-orange-600 py-2 rounded-lg font-bold hover:bg-orange-50 transition">
                                    💬 Message Seller
                                </button>
                            </template>

                            <template v-else-if="$page.props.auth.user && $page.props.auth.user.id === chicken.user_id">
                                <div class="flex gap-2">
                                    <button @click="toggleAvailability(chicken)" class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 py-2 rounded-lg font-bold text-xs hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                        {{ chicken.is_available ? '🚫 Mark Unavailable' : '✅ Mark Available' }}
                                    </button>
                                    
                                    <button @click="confirmDeleteListing(chicken)" class="px-3 bg-red-50 text-red-600 py-2 rounded-lg font-bold text-xs hover:bg-red-100 transition">
                                        🗑️
                                    </button>
                                </div>
                            </template>

                            <Modal :show="showingDeleteModal" @close="closeDeleteModal">
                                <div class="p-6">
                                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                        Delete Listing
                                    </h2>

                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                        Are you sure you want to delete the listing for <strong>{{ listingToDelete?.breed }}</strong>? 
                                        This action will permanently remove the chicken from the marketplace.
                                    </p>

                                    <div class="mt-6 flex justify-end">
                                        <SecondaryButton @click="closeDeleteModal">
                                            Cancel
                                        </SecondaryButton>

                                        <button
                                            class="ms-3 inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                            @click="proceedWithDelete"
                                        >
                                            Delete Listing
                                        </button>
                                    </div>
                                </div>
                            </Modal>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="listingItems.length === 0" class="col-span-full flex flex-col items-center justify-center py-20 bg-white dark:bg-gray-800 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700">
                    <p class="text-4xl mb-4">🐣</p>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">No chickens found</h3>
                </div>

                <div
                    v-if="listingItems.length > 0 && paginationLinks.length > 3"
                    class="col-span-full flex flex-wrap items-center justify-center gap-2 pt-4"
                >
                    <Link
                        v-for="(link, index) in paginationLinks"
                        :key="index"
                        :href="link.url || '#'"
                        preserve-scroll
                        class="px-3 py-1.5 rounded-md text-sm border transition"
                        :class="[
                            link.active
                                ? 'bg-orange-600 text-white border-orange-600'
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600',
                            !link.url ? 'opacity-50 pointer-events-none' : 'hover:bg-orange-50 dark:hover:bg-gray-700'
                        ]"
                    >
                        <span v-html="link.label" />
                    </Link>
                </div>
            </div>
        </div>
        <Modal :show="showImageModal" @close="closeImage">
            <div class="p-4 bg-black/90 flex items-center justify-center">
                <div class="relative max-w-3xl w-full">
                    
                    <!-- Close button -->
                    <button
                        @click="closeImage"
                        class="absolute -top-10 right-0 text-white text-2xl hover:text-gray-300"
                    >
                        ✕
                    </button>

                    <!-- Full Image -->
                    <img
                        v-if="activeImage"
                        :src="'/storage/' + activeImage"
                        class="w-full max-h-[80vh] object-contain rounded-lg shadow-lg bg-white"
                    />
                </div>
            </div>
        </Modal>
        <Modal :show="showingMessageModal" @close="showingMessageModal = false">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-2xl">✉️</span>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        Inquire about this Listing
                    </h2>
                </div>

                <p class="text-sm text-gray-500 dark:text-gray-300 mb-4">
                    Your message will be sent directly to the seller. You can coordinate pickup and payment details here.
                </p>

                <div class="mt-4">
                    <label class="block text-xs font-bold uppercase text-gray-400 dark:text-gray-300 mb-1">Your Message</label>
                    <textarea 
                        v-model="messageForm.content" 
                        class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-orange-500 focus:border-orange-500" 
                        placeholder="Hi! Is this chicken still available? I'm interested..."
                        rows="4"
                    ></textarea>
                    <div v-if="messageForm.errors.content" class="text-red-500 text-xs mt-1">
                        {{ messageForm.errors.content }}
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showingMessageModal = false">
                        Cancel
                    </SecondaryButton>

                    <button 
                        class="inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700 focus:bg-orange-700 active:bg-orange-900 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        :class="{ 'opacity-25': messageForm.processing }"
                        :disabled="messageForm.processing"
                        @click="sendMessage"
                    >
                        Send Message
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
