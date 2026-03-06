<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3'; 
import { computed, ref, watch } from 'vue';

const showingDeleteModal = ref(false);
const listingToDelete = ref(null);
const showingCreateListingModal = ref(false);
const mediaPreviews = ref([]);
const selectedMediaFiles = ref([]);
const showMediaModal = ref(false);
const activeMedia = ref(null);
const fileInput = ref(null);
const currentMediaIndexes = ref({});
const toasts = ref([]);
let toastCounter = 0;

const props = defineProps({ 
    listings: Object,
    filters: Object,
    userRole: String, 
    defaultSellerLocation: String,
    defaultSellerContactNumber: String,
});

const listingItems = computed(() => props.listings?.data ?? []);
const paginationLinks = computed(() => props.listings?.links ?? []);
const DEFAULT_SELLER_CONTACT_NUMBER = computed(() => props.defaultSellerContactNumber || '09491735243');

const CHICKEN_TYPE_OPTIONS = [
    { value: 'not_fighting_cock', label: 'Not Fighting Cock' },
    { value: 'fighting_cock', label: 'Fighting Cock' },
];

const BREEDS_BY_TYPE = {
    not_fighting_cock: [
        'Rhode Island Red',
        'Black Australorp',
        'Barred Plymouth Rock',
        'White Leghorn',
        'SASSO',
        'Dominant CZ',
    ],
    fighting_cock: [
        'Sweater',
        'Hatch',
        'Roundhead',
        'Claret',
        'Whitehackle',
        'Kelso',
        'Radio',
        'Brown Red',
        'Lemon 84',
    ],
};

const CHICKEN_CONDITION_OPTIONS = [
    { value: 'live', label: 'Live' },
    { value: 'processed', label: 'Processed' },
    { value: 'frozen', label: 'Frozen' },
];

const DELIVERY_OPTIONS = [
    { value: 'pickup_only', label: 'Pickup Only' },
    { value: 'local_delivery', label: 'Local Delivery' },
    { value: 'shipping', label: 'Shipping' },
];

const CATEGORY_TAG_OPTIONS = [
    { value: 'organic', label: 'Organic' },
    { value: 'free_range', label: 'Free-range' },
    { value: 'antibiotic_free', label: 'Antibiotic-free' },
];

const getListingMedia = (listing) => {
    if (Array.isArray(listing?.media) && listing.media.length > 0) {
        return listing.media.map((item) => ({
            id: item.id,
            type: item.type || 'image',
            path: item.path,
            url: `/storage/${item.path}`,
        }));
    }

    if (listing?.image) {
        return [{
            id: `legacy-${listing.id}`,
            type: 'image',
            path: listing.image,
            url: `/storage/${listing.image}`,
        }];
    }

    return [];
};

const getMediaIndex = (listing) => {
    const total = getListingMedia(listing).length;
    if (total === 0) return 0;

    const current = currentMediaIndexes.value[listing.id] ?? 0;
    if (current >= total) return 0;
    if (current < 0) return total - 1;
    return current;
};

const getActiveMedia = (listing) => {
    const mediaList = getListingMedia(listing);
    if (mediaList.length === 0) return null;
    return mediaList[getMediaIndex(listing)];
};

const setMediaIndex = (listing, index) => {
    const total = getListingMedia(listing).length;
    if (total === 0) {
        currentMediaIndexes.value[listing.id] = 0;
        return;
    }

    const normalized = (index + total) % total;
    currentMediaIndexes.value[listing.id] = normalized;
};

const nextMedia = (listing) => {
    setMediaIndex(listing, getMediaIndex(listing) + 1);
};

const previousMedia = (listing) => {
    setMediaIndex(listing, getMediaIndex(listing) - 1);
};

const openMedia = (media) => {
    activeMedia.value = media;
    showMediaModal.value = true;
};

const closeMedia = () => {
    showMediaModal.value = false;
    activeMedia.value = null;
};

const searchTerm = ref(props.filters?.search || '');
const chickenTypeFilter = ref(props.filters?.chicken_type || '');
const breedFilter = ref(props.filters?.breed || '');
const categoryTagFilters = ref(Array.isArray(props.filters?.category_tags) ? props.filters.category_tags : []);
const selectedCategoryTag = computed({
    get: () => categoryTagFilters.value[0] || '',
    set: (value) => {
        categoryTagFilters.value = value ? [value] : [];
    },
});

const filteredBreedOptions = computed(() => {
    if (!chickenTypeFilter.value) return [];
    return BREEDS_BY_TYPE[chickenTypeFilter.value] ?? [];
});

watch(chickenTypeFilter, () => {
    if (!filteredBreedOptions.value.includes(breedFilter.value)) {
        breedFilter.value = '';
    }
});

watch([searchTerm, chickenTypeFilter, breedFilter, categoryTagFilters], ([search, chickenType, breed, categoryTags]) => {
    router.get(route('listings.index'), {
        search,
        chicken_type: chickenType || undefined,
        breed: breed || undefined,
        category_tags: categoryTags?.length ? categoryTags : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
});

const clearSelectedMedia = () => {
    selectedMediaFiles.value = [];
    form.media = [];
    mediaPreviews.value.forEach((preview) => URL.revokeObjectURL(preview.url));
    mediaPreviews.value = [];

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const handleMediaChange = (event) => {
    const files = Array.from(event.target.files || []);
    if (!files.length) return;

    clearSelectedMedia();
    selectedMediaFiles.value = files.slice(0, 8);
    form.media = selectedMediaFiles.value;
    mediaPreviews.value = selectedMediaFiles.value.map((file) => ({
        name: file.name,
        type: file.type.startsWith('video/') ? 'video' : 'image',
        url: URL.createObjectURL(file),
    }));
};

const removeSelectedMedia = (index) => {
    const preview = mediaPreviews.value[index];
    if (preview) {
        URL.revokeObjectURL(preview.url);
    }

    mediaPreviews.value.splice(index, 1);
    selectedMediaFiles.value.splice(index, 1);
    form.media = [...selectedMediaFiles.value];
};

const confirmDeleteListing = (chicken) => {
    listingToDelete.value = chicken;
    showingDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showingDeleteModal.value = false;
    listingToDelete.value = null;
};

const pushToast = (message, type = 'success') => {
    const id = ++toastCounter;
    toasts.value.push({ id, message, type });

    setTimeout(() => {
        toasts.value = toasts.value.filter((toast) => toast.id !== id);
    }, 2800);
};

const proceedWithDelete = () => {
    if (listingToDelete.value) {
        router.delete(route('listings.destroy', listingToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
                pushToast('Listing deleted successfully.');
            },
        });
    }
};

const form = useForm({
    chicken_type: '',
    breed: '',
    price: '',
    quantity: 1,
    weight_kg: '',
    size_label: '',
    chicken_condition: 'live',
    delivery_option: 'pickup_only',
    contact_preference: 'call',
    category_tags: [],
    age_weeks: '',
    location: props.defaultSellerLocation || '',
    media: [],
});

const toggleFormCategoryTag = (tag) => {
    if (form.category_tags.includes(tag)) {
        form.category_tags = form.category_tags.filter((value) => value !== tag);
        return;
    }

    form.category_tags = [...form.category_tags, tag];
};

const formatOptionLabel = (value) => value ? value.replace(/_/g, ' ') : 'N/A';

const createBreedOptions = computed(() => {
    if (!form.chicken_type) return [];
    return BREEDS_BY_TYPE[form.chicken_type] ?? [];
});

watch(() => form.chicken_type, () => {
    if (!createBreedOptions.value.includes(form.breed)) {
        form.breed = '';
    }
});

const submit = () => {
    form.post(route('listings.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            clearSelectedMedia();
            form.location = props.defaultSellerLocation || '';
            form.contact_preference = 'call';
            showingCreateListingModal.value = false;
            pushToast('Listing created successfully.');
        },
        onError: (errors) => {
            const firstError = Object.values(errors || {}).find(Boolean)
                || 'Unable to create listing. Please check the form fields.';
            pushToast(firstError, 'error');
        },
    });
};

const mediaError = computed(() => form.errors.media || form.errors['media.0'] || null);

// --- RESTORED: ADD TO CART FUNCTION ---
const addToCart = (id) => {
    router.post(route('cart.store'), { 
        listing_id: id 
    }, {
        preserveScroll: true, // Stops the page from jumping to top after adding
        onSuccess: () => {
            pushToast('Added to cart successfully.');
        },
        onError: (errors) => {
            pushToast(errors?.listing_id || 'Unable to add item to cart.', 'error');
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
            pushToast('Message sent to seller.');
            router.visit(route('listings.index'), {
                preserveScroll: false,
            });
        },
        onError: (errors) => {
            pushToast(errors?.content || 'Failed to send message.', 'error');
        }
    });
};
// Function to toggle availability (Mark as Unavailable/Available)
const toggleAvailability = (chicken) => {
    const willBeAvailable = !chicken.is_available;

    router.patch(route('listings.update', chicken.id), { is_available: willBeAvailable }, {
        preserveScroll: true,
        onSuccess: () => {
            pushToast(willBeAvailable ? 'Listing marked as available.' : 'Listing marked as unavailable.');
        },
        onError: () => {
            pushToast('Failed to update listing availability.', 'error');
        },
    });
};
</script>

<template>
    <Head title="Marketplace" />
 
    <AuthenticatedLayout>
    <div class="fixed top-20 right-4 z-50 space-y-2 w-[min(90vw,24rem)]">
        <div
            v-for="toast in toasts"
            :key="toast.id"
            class="rounded-lg border px-4 py-3 text-sm font-semibold shadow-lg transition"
            :class="toast.type === 'error'
                ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-700 dark:bg-red-950/80 dark:text-red-200'
                : 'border-green-200 bg-green-50 text-green-700 dark:border-green-700 dark:bg-green-950/80 dark:text-green-200'"
        >
            {{ toast.message }}
        </div>
    </div>
    <template #header>
        <div class="flex justify-end items-center gap-2">
            <select
                v-model="chickenTypeFilter"
                class="w-32 sm:w-40 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-100 placeholder:text-gray-400 px-3 py-1.5 focus:ring-2 focus:ring-orange-400 focus:border-orange-400">
                <option value="">All Chicken Types</option>
                <option v-for="option in CHICKEN_TYPE_OPTIONS" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>
            <select
                v-model="breedFilter"
                :disabled="!chickenTypeFilter"
                class="w-32 sm:w-40 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-100 placeholder:text-gray-400 px-3 py-1.5 focus:ring-2 focus:ring-orange-400 focus:border-orange-400"
            >
                <option value="">All Breeds</option>
                <option v-for="breed in filteredBreedOptions" :key="breed" :value="breed">
                    {{ breed }}
                </option>
            </select>
            <select
                v-model="selectedCategoryTag"
                class="w-32 sm:w-40 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-100 placeholder:text-gray-400 px-3 py-1.5 focus:ring-2 focus:ring-orange-400 focus:border-orange-400"
            >
                <option value="">All Category Tags</option>
                <option v-for="tag in CATEGORY_TAG_OPTIONS" :key="tag.value" :value="tag.value">
                    {{ tag.label }}
                </option>
            </select>
            <input
                v-model="searchTerm"
                type="text"
                placeholder="Search breeds..."
                class="w-76 sm:w-56 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-100 placeholder:text-gray-400 px-3 py-1.5 focus:ring-2 focus:ring-orange-400 focus:border-orange-400"
            >
        </div>
    </template>

        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="w-full sm:px-6 lg:px-8 space-y-8">
                
                <div v-if="userRole === 'seller'" class="flex justify-end">
                    <button
                        type="button"
                        class="bg-orange-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-orange-700 transition"
                        @click="showingCreateListingModal = true"
                    >
                        + List a Chicken for Sale
                    </button>
                </div>

                <Modal v-if="userRole === 'seller'" :show="showingCreateListingModal" @close="showingCreateListingModal = false" maxWidth="2xl">
                <div class="p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">List a Chicken for Sale</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-300">Provide details and upload images/videos to reach local buyers.</p>
                    </div>
                    
                    <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-1">Chicken Type</label>
                            <select
                                v-model="form.chicken_type"
                                class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-orange-500"
                                :class="{'border-red-500': form.errors.chicken_type}"
                            >
                                <option value="">Select chicken type</option>
                                <option v-for="option in CHICKEN_TYPE_OPTIONS" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <div v-if="form.errors.chicken_type" class="text-red-500 text-xs mt-1 font-semibold">
                                {{ form.errors.chicken_type }}
                            </div>
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-1">Breed</label>
                            <select
                                v-model="form.breed"
                                :disabled="!form.chicken_type"
                                class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-orange-500 disabled:opacity-60"
                                :class="{'border-red-500': form.errors.breed}"
                            >
                                <option value="">Select breed</option>
                                <option v-for="breed in createBreedOptions" :key="breed" :value="breed">
                                    {{ breed }}
                                </option>
                            </select>
                            <div v-if="form.errors.breed" class="text-red-500 text-xs mt-1 font-semibold">
                                {{ form.errors.breed }}
                            </div>
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-1">Price ($)</label>
                            <input v-model="form.price" type="number" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-orange-500">
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-1">Quantity</label>
                            <input v-model="form.quantity" type="number" min="1" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-orange-500">
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-1">Weight (kg)</label>
                            <input v-model="form.weight_kg" type="number" min="0" step="0.01" placeholder="Optional" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-orange-500">
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-1">Size</label>
                            <input v-model="form.size_label" type="text" placeholder="Optional" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-orange-500">
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-1">Age (Weeks)</label>
                            <input v-model="form.age_weeks" type="number" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-orange-500">
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-1">Condition</label>
                            <select v-model="form.chicken_condition" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-orange-500">
                                <option v-for="option in CHICKEN_CONDITION_OPTIONS" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-1">Delivery</label>
                            <select v-model="form.delivery_option" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:ring-orange-500">
                                <option v-for="option in DELIVERY_OPTIONS" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-1">Contact Number</label>
                            <input :value="DEFAULT_SELLER_CONTACT_NUMBER" type="text" readonly class="w-full border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm cursor-not-allowed">
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-1">Default Seller Location</label>
                            <input v-model="form.location" type="text" readonly class="w-full border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg shadow-sm cursor-not-allowed">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-2">Category Tags</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="tag in CATEGORY_TAG_OPTIONS"
                                    :key="tag.value"
                                    type="button"
                                    class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                                    :class="form.category_tags.includes(tag.value)
                                        ? 'border-orange-600 bg-orange-100 text-orange-700'
                                        : 'border-gray-300 bg-white text-gray-600 hover:border-orange-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300'"
                                    @click="toggleFormCategoryTag(tag.value)"
                                >
                                    {{ tag.label }}
                                </button>
                            </div>
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-300 mb-1">
                                Listing Media (Images or Videos)
                            </label>

                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/*,video/*"
                            multiple
                            @change="handleMediaChange"
                            class="w-full text-sm text-gray-900 dark:text-gray-100
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-full file:border-0
                                file:text-sm file:font-semibold
                                file:bg-orange-50 file:text-orange-700
                                hover:file:bg-orange-100 dark:border-gray-600 bg-white dark:bg-gray-800 rounded-lg shadow-sm focus:ring-orange-500 focus:border-orange-500"
                                
                        />
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-300">Up to 8 files, max 20MB each.</p>
                            <div v-if="mediaError" class="mt-1 text-xs font-semibold text-red-500">
                                {{ mediaError }}
                            </div>

                            <div v-if="mediaPreviews.length" class="mt-4">
                                <p class="text-xs text-gray-500 mb-2">Preview</p>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <div
                                        v-for="(preview, index) in mediaPreviews"
                                        :key="preview.url"
                                        class="relative rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-black/5 dark:bg-black/20"
                                    >
                                        <img
                                            v-if="preview.type === 'image'"
                                            :src="preview.url"
                                            alt="Listing media preview"
                                            class="w-full h-24 object-cover"
                                        />
                                        <video
                                            v-else
                                            :src="preview.url"
                                            class="w-full h-24 object-cover"
                                            controls
                                            muted
                                            playsinline
                                        ></video>
                                        <button
                                            type="button"
                                            class="absolute top-1 right-1 bg-red-600 text-white text-[10px] font-bold px-2 py-1 rounded"
                                            @click="removeSelectedMedia(index)"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button
                                v-if="mediaPreviews.length"
                                type="button"
                                @click="clearSelectedMedia"
                                class="text-xs text-red-500 hover:underline mt-2"
                            >
                                Clear all selected media
                            </button>
                       </div>
                        <div class="md:col-span-4 flex justify-end">
                            <button type="button" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-lg font-bold hover:bg-gray-200 dark:hover:bg-gray-600 transition" @click="showingCreateListingModal = false">
                                Cancel
                            </button>
                            <button type="submit" :disabled="form.processing" class="ms-2 bg-orange-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-orange-700 transition">
                                🚀 Post Listing
                            </button>
                        </div>
                    </form>
                </div>
                </Modal>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                    
                    <div v-for="chicken in listingItems" :key="chicken.id" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm hover:shadow-md transition-all duration-300 border border-gray-100 dark:border-gray-700 flex flex-col">
                        <div class="h-60 w-full bg-gray-200 dark:bg-gray-700 relative">
                            <template v-if="getActiveMedia(chicken)">
                                <img
                                    v-if="getActiveMedia(chicken).type === 'image'"
                                    :src="getActiveMedia(chicken).url"
                                    @click="openMedia(getActiveMedia(chicken))"
                                    class="w-full h-full object-cover transition-transform duration-300 hover:scale-105 cursor-pointer"
                                />
                                <video
                                    v-else
                                    :src="getActiveMedia(chicken).url"
                                    class="w-full h-full object-cover"
                                    controls
                                    muted
                                    loop
                                    playsinline
                                ></video>

                                <div
                                    v-if="getListingMedia(chicken).length > 1"
                                    class="absolute bottom-2 right-2 bg-black/70 text-white text-[10px] px-2 py-1 rounded"
                                >
                                    {{ getMediaIndex(chicken) + 1 }} / {{ getListingMedia(chicken).length }}
                                </div>

                                <button
                                    v-if="getListingMedia(chicken).length > 1"
                                    type="button"
                                    class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/60 text-white text-xs px-2 py-1 rounded"
                                    @click.stop="previousMedia(chicken)"
                                >
                                    Prev
                                </button>
                                <button
                                    v-if="getListingMedia(chicken).length > 1"
                                    type="button"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/60 text-white text-xs px-2 py-1 rounded"
                                    @click.stop="nextMedia(chicken)"
                                >
                                    Next
                                </button>
                            </template>
                            <div v-else class="flex items-center justify-center h-full text-gray-400 bg-orange-50 text-sm">No media</div>

                            <div v-if="!chicken.is_available" class="absolute top-3 left-3 bg-red-600 text-white text-[10px] font-bold px-2 rounded uppercase shadow-md z-10">
                                Not Available
                            </div>
                        </div>

                        <div class="p-2 flex flex-col flex-1">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="text-xl font-black text-gray-900 dark:text-gray-100 truncate mr-2">{{ chicken.breed }}</h4>
                                <span class="bg-green-100 text-green-700 text-lg font-bold px-3 py-1 rounded-full border border-green-200">${{ chicken.price }}</span>
                            </div>
                         <div class="mb-2 flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-orange-700">
                                {{ chicken.chicken_type === 'fighting_cock'
                                    ? 'Fighting Cock'
                                    : chicken.chicken_type === 'not_fighting_cock'
                                        ? 'Not Fighting Cock'
                                        : 'Unspecified Type' }}
                            </span>

                            <div v-if="Array.isArray(chicken.category_tags) && chicken.category_tags.length" class="flex flex-wrap gap-1">
                                <span
                                    v-for="tag in chicken.category_tags"
                                    :key="`${chicken.id}-${tag}`"
                                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700"
                                >
                                    {{ formatOptionLabel(tag) }}
                                </span>
                            </div>
                        </div>
                            <div class="flex items-center gap-2 text-gray-500 text-xs font-medium mb-2">
                                <span class="bg-gray-100 px-2 py-1 rounded">📍 {{ chicken.location }}</span>
                                <span class="bg-gray-100 px-2 py-1 rounded">⏳ {{ chicken.age_weeks }} Weeks</span>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-gray-500 text-xs font-medium mb-2">
                                <span class="bg-gray-100 px-2 py-1 rounded">Qty: {{ chicken.quantity ?? 1 }}</span>
                                <span class="bg-gray-100 px-2 py-1 rounded">Condition: {{ formatOptionLabel(chicken.chicken_condition) }}</span>
                                <span class="bg-gray-100 px-2 py-1 rounded">Delivery: {{ formatOptionLabel(chicken.delivery_option) }}</span>
                                <span class="bg-gray-100 px-2 py-1 rounded">Contact: {{ chicken.user?.seller_contact_number || DEFAULT_SELLER_CONTACT_NUMBER }}</span>
                                <span v-if="chicken.weight_kg" class="bg-gray-100 px-2 py-1 rounded">{{ chicken.weight_kg }} kg</span>
                                <span v-if="chicken.size_label" class="bg-gray-100 px-2 py-1 rounded">Size: {{ chicken.size_label }}</span>
                            </div>

                            <div class="pt-2 border-t border-gray-100 dark:border-gray-700 space-y-2">
                                
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
        <Modal :show="showMediaModal" @close="closeMedia">
            <div class="p-4 bg-black/90 flex items-center justify-center">
                <div class="relative max-w-3xl w-full">
                    <button
                        @click="closeMedia"
                        class="absolute -top-10 right-0 text-white text-2xl hover:text-gray-300"
                    >
                        x
                    </button>

                    <img
                        v-if="activeMedia?.type === 'image'"
                        :src="activeMedia.url"
                        class="w-full max-h-[80vh] object-contain rounded-lg shadow-lg bg-white"
                    />
                    <video
                        v-else-if="activeMedia?.type === 'video'"
                        :src="activeMedia.url"
                        class="w-full max-h-[80vh] object-contain rounded-lg shadow-lg bg-black"
                        controls
                        autoplay
                        playsinline
                    ></video>
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
