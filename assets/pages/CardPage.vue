<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import { fetchCard } from '../services/cardService';
import CardProperty from '../components/CardProperty.vue';

const route = useRoute();
const card = ref({});
const loadingCard = ref(true);

async function loadCard(uuid) {
    loadingCard.value = true;
    card.value = await fetchCard(uuid);
    loadingCard.value = false;
}

onMounted(() => {
    loadCard(route.params.uuid);
});

</script>

<template>
    <div class="loading" v-if="loadingCard">Loading...</div>
    <div v-else>
        <div class="card">
            <h1>{{ card.name }}</h1>
            <CardProperty name="coût en mana" :value="card.manaCost" />
            <pre class="card-property-text">{{ card.text }}</pre>
            <CardProperty name="Type" :value="card.type" />
            <CardProperty name="Rareté" :value="card.rarity" />
            <CardProperty name="Édition" :value="card.setCode" />
        </div>
    </div>
    <div>
        <router-link :to="{ name: 'all-cards' }">Retourner à la liste complète</router-link>
    </div>
</template>
