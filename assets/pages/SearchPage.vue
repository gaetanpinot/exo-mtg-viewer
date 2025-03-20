<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchAllCards, fetchSetCodes } from '../services/cardService';

const route = useRoute();
const cards = ref([]);
const loadingCards = ref(true);
const search = ref('');
const setCode = ref('');
const setCods = ref([]);
const page = ref(0);
const qp = ref({
    page: route.query.page || 0,
    search: route.query.search || '',
    setCode: route.query.setCode || '',
});
let timeoutId = 0;

async function loadSetCodes() {
    setCods.value = await fetchSetCodes();
}
const router = useRouter();
async function loadCards() {
    loadingCards.value = true;
    router.push({ query: qp.value });
    console.log('qp=', qp.value.page, qp.value.search, qp.value.setCode);
    cards.value = await fetchAllCards(qp.value.page, qp.value.search, qp.value.setCode);
    console.log(cards.value);
    loadingCards.value = false;
}
function doSearch() {
    if (qp.value.search.length > 2
        || qp.value.setCode.length > 1) clearTimeout(timeoutId);
    timeoutId = setTimeout(async () => {
        loadCards();
    }, 200);
}
function previous() {
    qp.value.page = Number(qp.value.page) - 1;
    loadCards();
}
function next() {
    qp.value.page = Number(qp.value.page) + 1;
    loadCards();
}
onMounted(() => {
    loadSetCodes();
    loadCards();
});
</script>

<template>
    <div>
        <h1>Rechercher une Carte</h1>
        <form>
            <label for="search">Rechercher</label>
            <input type="text" id="search" v-model="qp.search" @input="doSearch" />
            <label for="setCode">Code de l'édition</label>
            <select v-if="setCods.length > 0" id="setCode" v-model="qp.setCode" @change="loadCards">
                <option value="">Toutes les éditions</option>
                <option v-for="code in setCods" :key="code.setCode">{{ code.setCode }}</option>
            </select>
            <select v-else id="setCode" disabled>
                <option>Loading...</option>
            </select>
        </form>
    </div>
    <div class="card-list">
        <div v-if="loadingCards">Loading...</div>
        <div v-else>
            <p>Page {{ qp.page }}</p>
            <button type="button" :disabled="qp.page <= 0" @click="previous">Previous</button>
            <button type="button" @click="next">Next</button>
            <div class="card" v-for="card in cards" :key="card.id">
                <router-link :to="{ name: 'get-card', params: { uuid: card.uuid } }"> {{ card.name }} - {{ card.uuid }}
                </router-link>
            </div>
        </div>
    </div>
</template>
