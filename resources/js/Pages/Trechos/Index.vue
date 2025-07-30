<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

const props = defineProps({
    trechos: Array,
    ufs: Array,
    rodovias: Array,
});

let map = null;
const mapContainer = ref(null);
let geoJsonLayer = null;

const form = useForm({
    data_referencia: new Date().toISOString().split('T')[0],
    uf_id: null,
    rodovia_id: null,
    tipo_trecho: 'B',
    quilometragem_inicial: null,
    quilometragem_final: null,
});

const addTrecho = () => {
    form.post(route('trechos.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('quilometragem_inicial', 'quilometragem_final'),
    });
};

const clearMap = () => {
    if (!map) return;
    
    if (geoJsonLayer) {
        map.removeLayer(geoJsonLayer);
        geoJsonLayer = null;
    }
    map.setView([-15.7801, -47.9292], 4);
};

const deleteTrecho = (id) => {
    if (confirm('Tem certeza que deseja deletar este trecho?')) {
        router.delete(route('trechos.destroy', id), {
            preserveScroll: true,
            onSuccess: () => {
                clearMap();
            }
        });
    }
};

const viewOnMap = (trecho) => {
    if (geoJsonLayer) {
        map.removeLayer(geoJsonLayer);
    }
    
    if (trecho.geo) {
        try {
            let geoData = trecho.geo;
            
            if (geoData.type === 'Feature') {
                geoData = {
                    type: 'FeatureCollection',
                    features: [geoData]
                };
            }
            
            geoJsonLayer = L.geoJSON(geoData, {
                style: { color: '#007BFF', weight: 6, opacity: 0.9 }
            }).addTo(map);
            
            map.fitBounds(geoJsonLayer.getBounds());
        } catch (error) {
            console.error('Erro ao processar geometria:', error);
            alert('Erro ao processar a geometria do trecho.');
        }
    } else {
        alert('Geometria não encontrada para este trecho.');
        map.setView([-15.7801, -47.9292], 4);
    }
};

onMounted(() => {
    nextTick(() => {
        if (mapContainer.value) {
            map = L.map(mapContainer.value).setView([-15.7801, -47.9292], 4);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
        }
    });
});

watch(() => props.trechos, (newTrechos) => {
    if (newTrechos.length === 0) {
        clearMap();
    }
}, { immediate: true });

onUnmounted(() => {
    if (map) {
        map.remove();
    }
});
</script>

<template>
    <Head title="Gerenciador de Trechos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cadastro e Gestão de Trechos de Rodovias</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">Adicionar Novo Trecho</h2>
                            <p class="mt-1 text-sm text-gray-600">Preencha os dados para buscar a geometria e salvar.</p>
                        </header>

                        <div v-if="$page.props.errors.api" class="mt-4 p-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                            <span class="font-medium">Erro!</span> {{ $page.props.errors.api }}
                        </div>

                        <form @submit.prevent="addTrecho" class="mt-6 grid grid-cols-1 md:grid-cols-7 gap-4 items-end">
                            <div class="md:col-span-1">
                                <label for="data" class="block font-medium text-sm text-gray-700">Data Ref.</label>
                                <input v-model="form.data_referencia" type="date" id="data" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2" required>
                            </div>
                            <div class="md:col-span-1">
                                <label for="uf" class="block font-medium text-sm text-gray-700">UF</label>
                                <select v-model="form.uf_id" id="uf" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                    <option :value="null" disabled>Selecione</option>
                                    <option v-for="uf in ufs" :key="uf.id" :value="uf.id">{{ uf.sigla }}</option>
                                </select>
                            </div>
                            <div class="md:col-span-1">
                                <label for="rodovia" class="block font-medium text-sm text-gray-700">Rodovia</label>
                                <select v-model="form.rodovia_id" id="rodovia" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                     <option :value="null" disabled>Selecione</option>
                                     <option v-for="rodovia in rodovias" :key="rodovia.id" :value="rodovia.id">{{ rodovia.nome }}</option>
                                </select>
                            </div>
                             <div class="md:col-span-1">
                                <label for="kmi" class="block font-medium text-sm text-gray-700">Km Inicial</label>
                                <input v-model="form.quilometragem_inicial" type="number" id="kmi" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                             <div class="md:col-span-1">
                                <label for="kmf" class="block font-medium text-sm text-gray-700">Km Final</label>
                                <input v-model="form.quilometragem_final" type="number" id="kmf" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                             <div class="md:col-span-1">
                                <label for="tipo" class="block font-medium text-sm text-gray-700">Tipo</label>
                                <select v-model="form.tipo_trecho" id="tipo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                    <option value="B">B</option>
                                </select>
                            </div>
                            <div class="md:col-span-1">
                                <button type="submit" :disabled="form.processing" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                    Adicionar
                                </button>
                             </div>
                        </form>
                    </section>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                         <header>
                            <h2 class="text-lg font-medium text-gray-900">Trechos Cadastrados</h2>
                        </header>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">UF</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rodovia</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Km Inicial</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Km Final</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="trecho in trechos" :key="trecho.id">
                                        <td class="px-6 py-4 whitespace-nowrap">{{ trecho.uf.sigla }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">BR-{{ trecho.rodovia.nome }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ trecho.quilometragem_inicial }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ trecho.quilometragem_final }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <button @click="viewOnMap(trecho)" class="text-indigo-600 hover:text-indigo-900">Visualizar</button>
                                            <button @click="deleteTrecho(trecho.id)" class="text-red-600 hover:text-red-900">Deletar</button>
                                        </td>
                                    </tr>
                                    <tr v-if="trechos.length === 0">
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">Nenhum trecho cadastrado.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                 <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                     <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">Visualização no Mapa</h2>
                        </header>
                        <div ref="mapContainer" class="mt-4 w-full h-96 rounded-lg"></div>
                     </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
