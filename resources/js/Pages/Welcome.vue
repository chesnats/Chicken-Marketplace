<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import useDarkMode from '@/Composables/useDarkMode';

const props = defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
    authMode: { type: String, default: 'login' },
});
const { isDark, toggleTheme } = useDarkMode();

// Toggle state
const isLogin = ref(props.authMode !== 'register');

// Forms
const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
});

const registerForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submitLogin = () => {
    loginForm.post(route('login'), {
        onFinish: () => loginForm.reset('password'),
    });
};

const submitRegister = () => {
    registerForm.post(route('register'), {
        onFinish: () => registerForm.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head :title="isLogin ? 'Log in' : 'Register'" />

    <div class="relative flex min-h-screen overflow-hidden">
        <button
            type="button"
            @click="toggleTheme"
            class="absolute right-4 top-4 z-20 rounded-lg p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 transition"
            :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        >
            <svg
                v-if="isDark"
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <circle cx="12" cy="12" r="4" />
                <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41" />
            </svg>
            <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M12 3a7 7 0 1 0 9 9 9 9 0 1 1-9-9z" />
            </svg>
        </button>
        
        <div class="hidden lg:flex lg:w-2/3 bg-orange-600 dark:bg-orange-700 items-center justify-center p-16 text-white">
            <div class="max-w-2xl text-center">
                <div class="flex justify-center mb-8">
                    <img src="chickenlogo.png" alt="Chicken Marketplace Logo" class="w-36 h-36" />
                </div>
                <h1 class="text-6xl font-extrabold mb-8 leading-tight">Premium Chicken Marketplace</h1>
                <p class="text-2xl text-orange-100 font-medium">
                    A single trusted seller serving many buyers. <br>
                    Quality birds, simple ordering, and secure transactions.
                </p>
                <div class="mt-16 border-t border-orange-500/50 pt-12">
                    <p class="italic text-orange-200 text-xl font-semibold">
                        "Join thousands of breeders across the country."
                    </p>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/3 flex items-center justify-center bg-orange-50 dark:bg-gray-900 p-6 md:p-12">
            <div class="w-full max-w-md bg-white dark:bg-gray-800 p-8 md:p-10 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700">
                
                <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
                    {{ status }}
                </div>

                <Transition name="fade" mode="out-in">
                    <div v-if="isLogin" key="login">
                        <div class="mb-8">
                            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Welcome Back</h2>
                            <p class="text-gray-500 dark:text-gray-300 text-sm mt-2">Log in to order from our official seller.</p>
                        </div>

                        <form @submit.prevent="submitLogin" class="space-y-4">
                            <div>
                                <InputLabel for="login_email" value="Email" />
                                <TextInput id="login_email" type="email" class="mt-1 block w-full bg-gray-50" v-model="loginForm.email" required autofocus autocomplete="username" />
                                <InputError class="mt-2" :message="loginForm.errors.email" />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="login_password" value="Password" />
                                <TextInput id="login_password" type="password" class="mt-1 block w-full bg-gray-50" v-model="loginForm.password" required autocomplete="current-password" />
                                <InputError class="mt-2" :message="loginForm.errors.password" />
                            </div>

                            <div class="mt-4 flex items-center justify-between">
                                <label class="flex items-center cursor-pointer">
                                    <Checkbox name="remember" v-model:checked="loginForm.remember" class="text-orange-600" />
                                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-300">Remember me</span>
                                </label>
                                <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm text-orange-600 font-bold underline hover:text-orange-700">Forgot password?</Link>
                            </div>

                            <div class="mt-8 flex flex-col gap-4">
                                <PrimaryButton class="w-full justify-center bg-orange-600 hover:bg-orange-700 py-4 text-lg rounded-xl" :disabled="loginForm.processing">
                                    Log in
                                </PrimaryButton>
                                <p class="text-center text-sm text-gray-600 dark:text-gray-300">
                                    Don't have an account? 
                                    <button type="button" @click="isLogin = false" class="text-orange-600 font-black underline ml-1">Register Here</button>
                                </p>
                            </div>
                        </form>
                    </div>

                    <div v-else key="register">
                        <div class="mb-8">
                            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Create Account</h2>
                            <p class="text-gray-500 dark:text-gray-300 text-sm mt-2">Join our community of chicken enthusiasts.</p>
                        </div>

                        <form @submit.prevent="submitRegister" class="space-y-4">
                            <div>
                                <InputLabel for="reg_name" value="Name" />
                                <TextInput id="reg_name" type="text" class="mt-1 block w-full bg-gray-50" v-model="registerForm.name" required autofocus />
                                <InputError class="mt-2" :message="registerForm.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="reg_email" value="Email" />
                                <TextInput id="reg_email" type="email" class="mt-1 block w-full bg-gray-50" v-model="registerForm.email" required />
                                <InputError class="mt-2" :message="registerForm.errors.email" />
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="reg_password" value="Password" />
                                    <TextInput id="reg_password" type="password" class="mt-1 block w-full bg-gray-50" v-model="registerForm.password" required />
                                </div>
                                <div>
                                    <InputLabel for="reg_password_conf" value="Confirm" />
                                    <TextInput id="reg_password_conf" type="password" class="mt-1 block w-full bg-gray-50" v-model="registerForm.password_confirmation" required />
                                </div>
                            </div>
                            <InputError class="mt-2" :message="registerForm.errors.password" />

                            <div class="mt-8 flex flex-col gap-4">
                                <PrimaryButton class="w-full justify-center bg-orange-600 hover:bg-orange-700 py-4 text-lg rounded-xl" :disabled="registerForm.processing">
                                    Register Now
                                </PrimaryButton>
                                <p class="text-center text-sm text-gray-600 dark:text-gray-300">
                                    Already have an account? 
                                    <button type="button" @click="isLogin = true" class="text-orange-600 font-black underline ml-1">Login Here</button>
                                </p>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
