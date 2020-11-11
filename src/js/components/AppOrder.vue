<template>
  <div id="appOrder" v-show="getLoaded">
    <component :is="mode"></component>
  </div>
</template>

<script>
  import EmptyCart from './EmptyCart.vue';
  import OrderCart from './OrderCart.vue';
  import OrderSent from './OrderSent.vue';
  import { mapGetters } from 'vuex/dist/vuex.esm';

  export default {
    name: "AppOrder",
    components: {
      EmptyCart,
      OrderCart,
      OrderSent
    },
    created() {
      this.$store.dispatch('cart/initCart');
    },
    data() {
      return {

      }
    },
    computed: {
      ...mapGetters('cart', [
        'getCheckoutStatus',
        'getLoaded',
        'countProducts'
      ]),
      mode() {
        let mode = this.countProducts > 0 ? 'OrderCart' : 'EmptyCart';
        if (this.getCheckoutStatus) {
          mode = 'OrderSent';
        }
        return mode;
      }
    }
  }
</script>

<style scoped>

</style>