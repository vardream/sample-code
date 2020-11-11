import Vue from 'vue/dist/vue.esm';
import SendQuestion from './components/SendQuestion.vue';
import './_validation_rules';

new Vue({
  el: '#appQuestion',
  components: {
    SendQuestion
  }
});