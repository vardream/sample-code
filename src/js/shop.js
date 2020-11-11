import Vue from 'vue/dist/vue.esm';
import store from './store';
import ShoppingCart from './components/ShoppingCart.vue';
import ShoppingCartInfo from './components/ShoppingCartInfo.vue';

import {mapGetters, mapActions} from 'vuex/dist/vuex.esm';

new Vue({
  el: '#appShop',
  store,
  // render: h => h(ShoppingCart),
  components: {
    ShoppingCart
  },
  created() {
    this.$store.dispatch('cart/initCart');
  },
  data: {
    id: 0
  },
  computed: {
    ...mapGetters('cart', {
      cartProducts: 'getProducts',
      cartProduct: 'getProduct',
      locked: 'getReceived'
    }),
    current() {
      return this.id;
    },
    product() {
      return this.id > 0 ? this.getProduct(id) : null;
    }
  },
  methods: {
    ...mapActions('cart', [
      'addCartsProduct',
      'showCart'
    ]),
    onAddToCart(event) {
      if (this.locked) {
        let product = event.target.elements.hasOwnProperty('product') ? event.target.elements.product : null;
        if (product !== undefined) {
          this.id = parseInt(product.value);

          let current = this.cartProduct(this.id);

          if (current === undefined) {
            this.addCartsProduct(this.id);
          } else {
            this.showCart();
          }
        }
      }
    }
  },

});

new Vue({
  el: '#appShoppingCartInfo',
  store,
  components: {
    ShoppingCartInfo
  },
  render: h => h(ShoppingCartInfo)
});