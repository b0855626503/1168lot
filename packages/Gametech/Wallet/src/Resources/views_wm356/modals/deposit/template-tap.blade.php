<script type="text/x-template" id="topup-tabs-template">
    <div class="x-deposit-promotion">
        <div v-for="tab in tabs" :key="tab.id" class="-promotion-box-wrapper">
            <button class="btn btn-for-deposit" @click="$emit('select', tab.id)">
                <img :src="tab.icon" class="-img img50" />
                <span class="-title">{{ tab.title }}</span>
            </button>
        </div>
    </div>
</script>