import Vue from 'vue/dist/vue.esm';
import store from './store';
import ShoppingCartInfo from './components/ShoppingCartInfo.vue';
import AppOrder from './components/AppOrder.vue';

new Vue({
  el: '#appOrder',
  store,
  components: {
    AppOrder,
  },
  render: h => h(AppOrder)
});

new Vue({
  el: '#appShoppingCartInfo',
  store,
  components: {
    ShoppingCartInfo
  },
  render: h => h(ShoppingCartInfo)
});