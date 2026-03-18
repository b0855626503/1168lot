<template>
    <div class="row">
        <reward-item v-for="reward in rewards" :key="reward.code" :game="reward"></reward-item>
    </div>
</template>

<script>
export default {
    props: {
        rewards: {},
    },
    data() {
        return {
            reward : ''
        }
    }
};
</script>

