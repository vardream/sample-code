import Vue from 'vue/dist/vue.esm';
import Vuex from 'vuex/dist/vuex.esm';
import cart from './modules/cart';

Vue.use(Vuex);

export default new Vuex.Store({
  modules: {
    cart
  }
});