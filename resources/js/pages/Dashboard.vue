<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

interface Website {
    id: number;
    url: string;
    name: string | null;
    is_up: boolean;
    last_checked_at: string | null;
    response_time_ms: number | null;
}

interface Client {
    id: number;
    email: string;
    name: string | null;
    websites: Website[];
}

const props = defineProps<{
    clients: Client[]
}>();

const selectedClientId = ref<number | string | null>("");
const clientWebsites = ref<Website[]>([]);
const showConfirmDialog = ref(false);
const targetWebsite = ref<Website | null>(null);

const selectClient = async (clientId: number | string) => {
    selectedClientId.value = clientId;

    // If empty string or null is selected, clear websites
    if (!clientId || clientId === "") {
        clientWebsites.value = [];
        return;
    }

    try {
        const response = await axios.get(`/api/clients/${clientId}/websites`);
        clientWebsites.value = response.data.websites;
    } catch (error) {
        console.error('Failed to fetch websites:', error);
        clientWebsites.value = [];
    }
};

const handleWebsiteClick = (website: Website) => {
    targetWebsite.value = website;
    showConfirmDialog.value = true;
};

const confirmVisit = () => {
    if (targetWebsite.value) {
        window.open(targetWebsite.value.url, '_blank');
    }
    closeDialog();
};

const closeDialog = () => {
    showConfirmDialog.value = false;
    targetWebsite.value = null;
};

const formatLastChecked = (dateString: string | null) => {
    if (!dateString) return 'Never';
    return new Date(dateString).toLocaleString();
};
</script>

<template>
    <Head title="Uptime Monitor Dashboard" />

    <div class="min-h-screen bg-gray-50 py-4 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-lg rounded-lg">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                    <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">
                        Website Uptime Monitor
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Monitor the uptime status of your websites
                    </p>
                </div>

                <div class="p-4 sm:p-6">
                    <div class="mb-6">
                        <label for="client-select" class="block text-sm font-medium text-gray-700 mb-2">
                            Select Client
                        </label>
                        <select
                            id="client-select"
                            v-model="selectedClientId"
                            @change="selectClient(selectedClientId)"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 p-4 focus:ring-indigo-500 text-black sm:text-sm"
                        >
                            <option value="">-- Select a client --</option>
                            <option
                                v-for="client in props.clients"
                                :key="client.id"
                                :value="client.id"
                            >
                                {{ client.name || client.email }}
                            </option>
                        </select>
                    </div>

                    <div v-if="clientWebsites.length > 0" class="mt-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">
                            Monitored Websites
                        </h2>

                        <div class="grid gap-3 sm:gap-4">
                            <div
                                v-for="website in clientWebsites"
                                :key="website.id"
                                class="flex items-start sm:items-center p-3 sm:p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors shadow-sm"
                            >
                                <div class="flex items-start sm:items-center space-x-3 sm:space-x-4 flex-1 min-w-0">
                                    <div
                                        :class="[
                                            'w-3 h-3 sm:w-4 sm:h-4 rounded-full flex-shrink-0 mt-1 sm:mt-0',
                                            website.is_up ? 'bg-green-500' : 'bg-red-500'
                                        ]"
                                        :title="website.is_up ? 'Online' : 'Offline'"
                                    ></div>

                                    <div class="flex-1 min-w-0">
                                        <button
                                            @click="handleWebsiteClick(website)"
                                            class="text-blue-600 hover:text-blue-800 underline text-left font-medium block w-full text-sm sm:text-base"
                                            :title="website.name || website.url"
                                        >
                                            <span class="block truncate">{{ website.name || website.url }}</span>
                                        </button>
                                        <div class="text-xs sm:text-sm text-gray-600 mt-1 flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                                            <span :class="website.is_up ? 'text-green-600' : 'text-red-600'" class="font-medium">
                                                {{ website.is_up ? '● Online' : '● Offline' }}
                                            </span>
                                            <span v-if="website.response_time_ms" class="text-gray-500">
                                                {{ website.response_time_ms }}ms
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-400 mt-1">
                                            Last checked: {{ formatLastChecked(website.last_checked_at) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="selectedClientId" class="mt-6 text-center text-gray-500">
                        No websites found for this client.
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Dialog -->
        <div
            v-if="showConfirmDialog"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4"
            @click="closeDialog"
        >
            <div
                class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-lg bg-white"
                @click.stop
            >
                <div class="text-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Confirm Website Visit
                    </h3>
                    <div class="mt-3 px-2">
                        <p class="text-sm text-gray-600">
                            You are about to visit:
                        </p>
                        <p class="text-sm font-medium text-blue-600 mt-1 break-all">
                            {{ targetWebsite?.url }}
                        </p>
                        <p class="text-sm text-gray-500 mt-2">
                            Do you want to continue?
                        </p>
                    </div>
                    <div class="mt-5 flex flex-col sm:flex-row gap-3">
                        <button
                            @click="confirmVisit"
                            class="flex-1 px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300 transition-colors"
                        >
                            Continue
                        </button>
                        <button
                            @click="closeDialog"
                            class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
