<script setup>
import { onMounted, ref } from 'vue';
import { fetchAllCards } from '../services/cardService';

const cards = ref([]);
const loadingCards = ref(true);
const page = ref(0);

async function loadCards() {
    loadingCards.value = true;
    cards.value = await fetchAllCards(page.value);
    console.log(cards.value);
    loadingCards.value = false;
}
function previous() {
    page.value -= 1;
    loadCards(page.value);
}
function next() {
    page.value += 1;
    loadCards(page.value);
}
onMounted(() => {
    loadCards(page);
});

</script>

<template>
    <div>
        <h1>Toutes les cartes</h1>
    </div>
    <div class="card-list">
        <div v-if="loadingCards">Loading...</div>
        <div v-else>
            <button type="button" :disabled="page <= 0" @click="previous">Previous</button>
            <button type="button" @click="next">Next</button>
            <div class="card-result" v-for="card in cards" :key="card.id">
                <router-link :to="{ name: 'get-card', params: { uuid: card.uuid } }">
                    {{ card.name }} <span>({{ card.uuid }})</span>
                </router-link>
            </div>
        </div>
    </div>
</template>
