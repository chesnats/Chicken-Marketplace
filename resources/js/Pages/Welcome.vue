<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

// Toggle state
const isLogin = ref(true);

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
    role: 'buyer',
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

    <div class="flex min-h-screen overflow-hidden">
        
        <div class="hidden lg:flex lg:w-2/3 bg-orange-600 items-center justify-center p-16 text-white">
            <div class="max-w-2xl text-center">
                <div class="flex justify-center mb-8">
                    <img src="chickenlogo.png" alt="Chicken Marketplace Logo" class="w-36 h-36" />
                </div>
                <h1 class="text-6xl font-extrabold mb-8 leading-tight">Premium Chicken Marketplace</h1>
                <p class="text-2xl text-orange-100 font-medium">
                    The safest place to buy and sell quality breeds. <br>
                    Quality birds, verified sellers, and secure transactions.
                </p>
                <div class="mt-16 border-t border-orange-500/50 pt-12">
                    <p class="italic text-orange-200 text-xl font-semibold">
                        "Join thousands of breeders across the country."
                    </p>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/3 flex items-center justify-center bg-orange-50 p-6 md:p-12">
            <div class="w-full max-w-md bg-white p-8 md:p-10 rounded-2xl shadow-2xl border border-gray-100">
                
                <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
                    {{ status }}
                </div>

                <Transition name="fade" mode="out-in">
                    <div v-if="isLogin" key="login">
                        <div class="mb-8">
                            <h2 class="text-3xl font-bold text-gray-900">Welcome Back</h2>
                            <p class="text-gray-500 text-sm mt-2">Log in to browse the marketplace.</p>
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
                                    <span class="ms-2 text-sm text-gray-600">Remember me</span>
                                </label>
                                <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm text-orange-600 font-bold underline hover:text-orange-700">Forgot password?</Link>
                            </div>

                            <div class="mt-8 flex flex-col gap-4">
                                <PrimaryButton class="w-full justify-center bg-orange-600 hover:bg-orange-700 py-4 text-lg rounded-xl" :disabled="loginForm.processing">
                                    Log in
                                </PrimaryButton>
                                <p class="text-center text-sm text-gray-600">
                                    Don't have an account? 
                                    <button type="button" @click="isLogin = false" class="text-orange-600 font-black underline ml-1">Register Here</button>
                                </p>
                            </div>
                        </form>
                    </div>

                    <div v-else key="register">
                        <div class="mb-8">
                            <h2 class="text-3xl font-bold text-gray-900">Create Account</h2>
                            <p class="text-gray-500 text-sm mt-2">Join our community of chicken enthusiasts.</p>
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

                            <div class="mt-4">
                                <InputLabel for="reg_role" value="Register as" />
                                <select id="reg_role" v-model="registerForm.role" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm bg-gray-50">
                                    <option value="buyer">Buyer (I want to buy)</option>
                                    <option value="seller">Seller (I want to sell)</option>
                                </select>
                            </div>

                            <div class="mt-8 flex flex-col gap-4">
                                <PrimaryButton class="w-full justify-center bg-orange-600 hover:bg-orange-700 py-4 text-lg rounded-xl" :disabled="registerForm.processing">
                                    Register Now
                                </PrimaryButton>
                                <p class="text-center text-sm text-gray-600">
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